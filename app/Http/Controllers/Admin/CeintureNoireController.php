<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Controller;
use App\Models\CeintureNoireManuelle;
use App\Models\Disciple;
use App\Models\Federation;
use App\Models\Grade;
use App\Models\Ligue;
use App\Models\Salle;
use App\Models\User;
use App\Support\MatriculeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CeintureNoireController extends Controller
{
    use AuthorizesScope;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('role:federation,ligue'); // App.jsx : ceintures noires réservées ADMIN/LIGUE
        $this->middleware('permission:CEINTURESNOIRES_READ')->only(['index']);
        $this->middleware('permission:CEINTURESNOIRES_CREATE')->only(['create', 'store']);
        $this->middleware('permission:CEINTURESNOIRES_UPDATE')->only(['edit', 'update', 'editMaitre', 'updateMaitre']);
        $this->middleware('permission:CEINTURESNOIRES_DELETE')->only(['destroy']);
    }

    /**
     * Liste combinée des ceintures noires : disciples au grade DAN + saisies manuelles.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $disciplesDan = Disciple::active()
            ->visibleTo($request->user())
            ->whereHas('grade', fn ($q) => $q->where('type_grade', 'DAN'))
            ->with(['grade:id,nom_grade', 'salle:id,nom'])
            ->when($search, fn ($q) => $q->where('nom_complet', 'like', "%{$search}%"))
            ->get()
            ->map(fn ($d) => (object) [
                'origine' => 'DISCIPLE',
                'id' => $d->id,
                'full_name' => $d->full_name,
                'sexe' => $d->sexe,
                'grade_nom' => $d->grade?->nom_grade,
                'salle_nom' => $d->salle?->nom,
                'editable' => false,
                'edit_maitre_url' => null,
            ]);

        $manuelles = CeintureNoireManuelle::active()
            ->visibleTo($request->user())
            ->with(['grade:id,nom_grade', 'salle:id,nom'])
            ->when($search, fn ($q) => $q->where(fn ($s) => $s->where('nom', 'like', "%{$search}%")->orWhere('prenom', 'like', "%{$search}%")))
            ->get()
            ->map(fn ($m) => (object) [
                'origine' => 'MANUELLE',
                'id' => $m->id,
                'full_name' => $m->full_name,
                'sexe' => $m->sexe,
                'grade_nom' => $m->grade?->nom_grade,
                'salle_nom' => $m->salle?->nom,
                'editable' => true,
                'edit_maitre_url' => null,
            ]);

        // Maîtres / responsables de ligue / fédération : leur grade personnel n'est
        // jamais un Disciple (ils gèrent une structure, ils n'en sont pas membres) —
        // on les inclut ici dès qu'ils détiennent un grade DAN ET ont eux-mêmes une
        // salle rattachée (users.salle_id) : un responsable de ligue/fédération sans
        // salle personnelle n'est pas encore « maître responsable ».
        $gestionnairesDan = User::query()
            ->visibleTo($request->user())
            ->whereIn('role', User::TENANT_ROLES)
            ->whereHas('salle')
            ->whereHas('grade', fn ($q) => $q->where('type_grade', 'DAN'))
            ->with(['grade:id,nom_grade', 'salle:id,nom'])
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->get()
            ->map(fn (User $u) => (object) [
                'origine' => 'GESTIONNAIRE',
                'id' => $u->id,
                'full_name' => $u->name,
                'sexe' => null,
                'grade_nom' => $u->grade?->nom_grade,
                'salle_nom' => $u->salle?->nom,
                'editable' => false,
                // Pas de Disciple/CeintureNoireManuelle à modifier : seules les
                // informations de licence (naissance/adresse/matricule) du compte
                // maître/responsable lui-même sont éditables, via un écran dédié.
                'edit_maitre_url' => route('admin.ceintures-noires.maitres.edit', $u),
            ]);

        $ceinturesNoires = $disciplesDan->concat($manuelles)->concat($gestionnairesDan)->sortBy('full_name')->values();

        return view('admin.ceintures-noires.index', [
            'ceinturesNoires' => $ceinturesNoires,
            'page_title' => __('messages.ceintures_noires.title'),
        ]);
    }

    public function create(): View
    {
        return view('admin.ceintures-noires.create', array_merge($this->formData(), [
            'ceintureNoire' => new CeintureNoireManuelle(),
            'page_title' => __('messages.ceintures_noires.add'),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('ceintures-noires', 'public');
        }

        // Matricule attribué automatiquement (jamais saisi à la main) : préfixe de
        // ligue + compteur séquentiel, cf. MatriculeGenerator.
        $data['nmle'] = MatriculeGenerator::nextForLigue(
            !empty($data['ligue_id']) ? Ligue::find($data['ligue_id']) : null
        );

        CeintureNoireManuelle::create($data);

        return redirect()->route('admin.ceintures-noires.index')
            ->with('success', __('messages.ceintures_noires.created'));
    }

    public function edit(CeintureNoireManuelle $ceintures_noire): View
    {
        $this->guardScope($ceintures_noire);

        return view('admin.ceintures-noires.edit', array_merge($this->formData(), [
            'ceintureNoire' => $ceintures_noire,
            'page_title' => __('messages.ceintures_noires.edit'),
        ]));
    }

    public function update(Request $request, CeintureNoireManuelle $ceintures_noire): RedirectResponse
    {
        $this->guardScope($ceintures_noire);
        $data = $this->validateData($request);

        if ($request->hasFile('photo')) {
            if ($ceintures_noire->photo_path && Storage::disk('public')->exists($ceintures_noire->photo_path)) {
                Storage::disk('public')->delete($ceintures_noire->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('ceintures-noires', 'public');
        }

        $ceintures_noire->update($data);

        return redirect()->route('admin.ceintures-noires.index')
            ->with('success', __('messages.ceintures_noires.updated'));
    }

    public function destroy(CeintureNoireManuelle $ceintures_noire): RedirectResponse
    {
        $this->guardScope($ceintures_noire);

        if ($ceintures_noire->photo_path && Storage::disk('public')->exists($ceintures_noire->photo_path)) {
            Storage::disk('public')->delete($ceintures_noire->photo_path);
        }

        $ceintures_noire->delete();

        return redirect()->route('admin.ceintures-noires.index')
            ->with('success', __('messages.ceintures_noires.deleted'));
    }

    /**
     * Modifie les informations de licence (naissance/adresse/matricule) d'un maître
     * ou responsable de ligue/fédération — jamais son nom/rôle/grade, gérés ailleurs
     * (gestion des utilisateurs). Le matricule est attribué automatiquement, une
     * seule fois, à la première modification.
     */
    public function editMaitre(User $maitre): View
    {
        $this->guardTenantUser($maitre);

        return view('admin.ceintures-noires.edit-maitre', [
            'maitre' => $maitre,
            'page_title' => __('messages.ceintures_noires.edit_manager'),
        ]);
    }

    public function updateMaitre(Request $request, User $maitre): RedirectResponse
    {
        $this->guardTenantUser($maitre);

        $data = $request->validate([
            'date_naissance' => ['nullable', 'date'],
            'date_lieu_naissance' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($maitre->matricule)) {
            $data['matricule'] = MatriculeGenerator::nextForLigue($maitre->ligue ?? $maitre->salle?->ligue);
        }

        $maitre->update($data);

        return redirect()->route('admin.ceintures-noires.index')
            ->with('success', __('messages.ceintures_noires.updated'));
    }

    /** 403 si le compte n'est pas un maître/responsable dans le périmètre de l'utilisateur. */
    private function guardTenantUser(User $maitre): void
    {
        abort_unless(in_array($maitre->role instanceof \App\Shared\Enums\UserRole ? $maitre->role->value : (string) $maitre->role, User::TENANT_ROLES, true), 404);

        abort_unless(
            User::query()->visibleTo(request()->user())->whereKey($maitre->getKey())->exists(),
            403,
            __('messages.out_of_scope')
        );
    }

    private function formData(): array
    {
        $user = request()->user();

        return [
            'grades' => Grade::visibleTo($user)->where('type_grade', 'DAN')->orderBy('niveau')->get(['id', 'nom_grade', 'ceinture']),
            'federations' => Federation::visibleTo($user)->orderBy('nom')->get(['id', 'nom']),
            'ligues' => Ligue::visibleTo($user)->orderBy('nom')->get(['id', 'nom']),
            'salles' => Salle::visibleTo($user)->orderBy('nom')->get(['id', 'nom']),
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'prenom' => ['required', 'string', 'max:120'],
            'sexe' => ['nullable', 'in:M,F'],
            'date_naissance' => ['nullable', 'date'],
            'date_lieu_naissance' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:40'],
            'grade_id' => ['required', 'exists:grades,id'],
            'federation_id' => ['required', 'exists:federations,id'],
            'ligue_id' => ['nullable', 'exists:ligues,id'],
            'salle_id' => ['nullable', 'exists:salles,id'],
            'date_obtention_grade' => ['required', 'date'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
    }
}
