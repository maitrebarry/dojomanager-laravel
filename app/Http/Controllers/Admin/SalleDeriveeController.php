<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Grade;
use App\Models\Maitre;
use App\Models\Salle;
use App\Services\Competition\KnownAthletes;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Espace du maître d'une salle mère : il crée la salle dérivée de son subalterne (dans la
 * même ligue, rattachée à sa salle) avec le compte de connexion de ce maître. Filiation
 * seulement : il ne gère pas ensuite les disciples de la salle dérivée.
 */
class SalleDeriveeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('role:maitre');
    }

    public function index(Request $request): View
    {
        $mere = $this->maSalle($request);
        // Toutes les salles affiliées, à tous les niveaux (dérivées des dérivées…), en arbre.
        $affiliees = Salle::whereIn('id', array_diff($mere->descendantIds(), [$mere->id]))
            ->with(['maitre', 'maitreUser', 'salleMere:id,nom'])
            ->withCount(['disciples' => fn ($q) => $q->whereNull('archived_at')])->orderBy('nom')->get();
        $affiliees = Salle::tree($affiliees->map(function ($s) use ($mere) {
            if ($s->salle_mere_id === $mere->id) {
                $s->salle_mere_id = null; // racines de l'arbre : les dérivées directes
            }

            return $s;
        }))->each(fn ($s) => $s->depth = ($s->depth ?? 0) + 1);

        return view('admin.salles-derivees.index', [
            'mere' => $mere,
            'derivees' => $affiliees,
            'totalDisciples' => $affiliees->sum('disciples_count'),
            'grades' => Grade::where('type_grade', 'DAN')->when($mere->ligue?->federation_id, fn ($q, $id) => $q->where('federation_id', $id))
                ->orderBy('niveau')->get(['id', 'nom_grade']),
            'page_title' => __('messages.salles_derivees.title'),
        ]);
    }

    public function store(Request $request, UserService $users): RedirectResponse
    {
        $mere = $this->maSalle($request);
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:140'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'nom_complet' => ['required', 'string', 'max:120'],
            'telephone' => ['required', 'string', 'max:20'],
            'grade_id' => ['nullable', 'exists:grades,id'],
        ]);
        $key = KnownAthletes::clubKey($data['nom']);
        if ($existing = Salle::where('ligue_id', $mere->ligue_id)->get()->first(fn ($s) => KnownAthletes::clubKey($s->nom) === $key)) {
            throw ValidationException::withMessages(['nom' => __('messages.pratiquants.salle_exists', ['salle' => $existing->nom])]);
        }
        $grade = isset($data['grade_id']) ? Grade::find($data['grade_id']) : null;
        $password = (string) random_int(100000, 999999);

        $salle = DB::transaction(function () use ($mere, $data, $grade, $password, $users) {
            $maitre = Maitre::create(['nom_complet' => $data['nom_complet'], 'telephone' => $data['telephone'], 'grade' => $grade?->nom_grade]);
            $salle = Salle::create([
                'nom' => trim($data['nom']), 'adresse' => $data['adresse'] ?? null, 'active' => true,
                'ligue_id' => $mere->ligue_id, 'salle_mere_id' => $mere->id, 'maitre_id' => $maitre->id,
            ]);
            $users->create([
                'name' => $data['nom_complet'], 'phone' => $data['telephone'], 'password' => $password,
                'role' => 'maitre', 'status' => 'active', 'salle_id' => $salle->id,
                'ligue_id' => $mere->ligue_id, 'federation_id' => $mere->ligue?->federation_id, 'grade_id' => $grade?->id,
            ]);

            return $salle;
        });

        ActivityLog::log(action: 'create', subject: 'Salle', subjectId: $salle->id,
            description: "Salle dérivée créée par le maître de {$mere->nom} : {$salle->nom} ({$data['nom_complet']})");

        return redirect()->route('admin.salles-derivees.index')
            ->with('success', __('messages.salles_derivees.created', ['salle' => $salle->nom]))
            ->with('maitre_account', __('messages.pratiquants.maitre_account', ['name' => $data['nom_complet'], 'phone' => $data['telephone'], 'password' => $password]));
    }

    /** Disciples d'une salle affiliée, en consultation (le maître de la salle mère ne les modifie pas). */
    public function disciples(Request $request, Salle $salle): View
    {
        $mere = $this->maSalle($request);
        abort_unless($salle->id !== $mere->id && in_array($salle->id, $mere->descendantIds(), true), 403);
        $salle->load(['maitre', 'maitreUser', 'salleMere:id,nom']);

        return view('admin.salles-derivees.disciples', [
            'mere' => $mere,
            'salle' => $salle,
            'disciples' => $salle->disciples()->whereNull('archived_at')->with('grade:id,nom_grade')->orderBy('prenom')->orderBy('nom')->get(),
            'year' => now()->year,
            'page_title' => __('messages.salles_derivees.disciples_title', ['salle' => $salle->nom]),
        ]);
    }

    /** Salle du maître connecté (la salle mère). */
    private function maSalle(Request $request): Salle
    {
        $salle = Salle::with('ligue')->find($request->user()->salle_id);
        abort_unless($salle, 403, __('messages.salles_derivees.no_salle'));

        return $salle;
    }
}
