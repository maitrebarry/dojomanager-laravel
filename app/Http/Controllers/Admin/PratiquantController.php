<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Disciple;
use App\Models\Grade;
use App\Models\Salle;
use App\Support\ImageOrientation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pratiquants de la circonscription : tous les disciples des salles de la ligue (ou de la
 * fédération), en consultation. Les fiches restent gérées par les maîtres ; la ligue peut
 * filtrer, exporter, imprimer les cartes de licence régionale et prendre les photos en série.
 */
class PratiquantController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:DISCIPLE_READ');
        $this->middleware('permission:DISCIPLE_UPDATE')->only('storePhoto');
        $this->middleware('permission:DISCIPLE_CREATE')->only(['toRegister', 'register']);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = $this->filtered($request);

        // Totaux par salle (actifs, sans photo), sur tout le périmètre.
        $perSalle = Disciple::visibleTo($user)->whereNull('archived_at')
            ->selectRaw('salle_id, COUNT(*) AS total, SUM(photo IS NULL OR photo = "") AS sans_photo')
            ->groupBy('salle_id')->get()->keyBy('salle_id');

        return view('admin.pratiquants.index', [
            'disciples' => $query->with(['salle:id,nom', 'grade:id,nom_grade'])
                ->orderBy('prenom')->orderBy('nom')->paginate(50)->withQueryString(),
            'salles' => $this->salles($request),
            'grades' => Grade::orderBy('niveau')->get(['id', 'nom_grade']),
            'perSalle' => $perSalle,
            'year' => now()->year,
            'page_title' => __('messages.pratiquants.title'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $year = now()->year;
        $rows = $this->filtered($request)->with(['salle:id,nom', 'grade:id,nom_grade'])
            ->orderBy('salle_id')->orderBy('prenom')->orderBy('nom')->get()
            ->map(fn (Disciple $d) => [
                $d->prenom, mb_strtoupper($d->nom), $d->sexe, $d->date_naissance?->format('d/m/Y'),
                $d->date_naissance ? $year - $d->date_naissance->year : null,
                $d->salle?->nom, $d->grade?->nom_grade, $d->telephone, $d->nmle, $d->photo ? 'oui' : 'non',
            ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Pratiquants');
        $sheet->fromArray([['Prénom', 'Nom', 'Sexe', 'Date de naissance', 'Âge', 'Salle', 'Grade', 'Téléphone', 'Matricule', 'Photo'], ...$rows->all()]);
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            'pratiquants-' . now()->format('Y-m-d') . '.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /** Prise de photos en série : caméra ouverte, un élève après l'autre. */
    public function photos(Request $request): View
    {
        $salles = $this->salles($request);
        $salleId = $request->integer('salle') ?: ($salles->count() === 1 ? $salles->first()->id : null);

        $disciples = $salleId === null ? collect() : Disciple::visibleTo($request->user())
            ->whereNull('archived_at')->where('salle_id', $salleId)
            ->with('grade:id,nom_grade')->orderBy('prenom')->orderBy('nom')->get()
            ->map(fn (Disciple $d) => [
                'id' => $d->id,
                'name' => trim($d->prenom . ' ' . mb_strtoupper($d->nom)),
                'grade' => $d->grade?->nom_grade,
                'photo' => $d->photo_url,
            ]);

        return view('admin.pratiquants.photos', [
            'salles' => $salles,
            'salleId' => $salleId,
            'disciples' => $disciples,
            'page_title' => __('messages.pratiquants.photos_title'),
        ]);
    }

    /** Photo prise par la caméra (déjà recadrée au format carte par le navigateur). */
    public function storePhoto(Request $request, Disciple $disciple): JsonResponse
    {
        abort_unless(Disciple::visibleTo($request->user())->whereKey($disciple->id)->exists(), 403);
        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096']]);

        if ($disciple->photo && !str_starts_with($disciple->photo, 'http') && Storage::disk('public')->exists($disciple->photo)) {
            Storage::disk('public')->delete($disciple->photo);
        }
        $path = $request->file('photo')->store('disciples', 'public');
        ImageOrientation::fix(Storage::disk('public')->path($path));
        $disciple->update(['photo' => $path]);

        ActivityLog::log(action: 'update', subject: 'Disciple', subjectId: $disciple->id,
            description: 'Photo prise en série : ' . $disciple->prenom . ' ' . $disciple->nom);

        return response()->json(['ok' => true, 'photo' => $disciple->fresh()->photo_url]);
    }

    /**
     * Combattants des compétitions de la ligue qui ne sont pas encore disciples. Avec un club,
     * ils deviennent disciples de cette salle (automatique pour toute nouvelle inscription ;
     * bouton pour ceux inscrits avant). Sans club : il suffit de le renseigner.
     */
    public function toRegister(Request $request): View
    {
        $ligueId = $this->ligueId($request);
        ['people' => $people, 'matches' => $matches] = \App\Services\Competition\KnownAthletes::unregistered($request->user(), $ligueId);
        $year = now()->year;
        $people = $people->map(fn ($p) => $p + ['age' => $p['year'] ? $year - $p['year'] : null]);

        return view('admin.pratiquants.register', [
            'withClub' => $people->filter(fn ($p) => trim((string) $p['club']) !== '')->values(),
            'withoutClub' => $people->filter(fn ($p) => trim((string) $p['club']) === '')->values(),
            'matches' => $matches->count(),
            'salles' => Salle::where('ligue_id', $ligueId)->orderBy('nom')->pluck('nom'),
            'page_title' => __('messages.pratiquants.register_title'),
        ]);
    }

    public function register(Request $request): \Illuminate\Http\RedirectResponse
    {
        $ligueId = $this->ligueId($request);
        $data = $request->validate(['clubs' => ['nullable', 'array'], 'clubs.*' => ['nullable', 'string', 'max:150'], 'all' => ['nullable', 'boolean']]);
        ['people' => $people, 'matches' => $matches] = \App\Services\Competition\KnownAthletes::unregistered($request->user(), $ligueId);
        $disciplesBefore = Disciple::count();
        $sallesBefore = Salle::where('ligue_id', $ligueId)->pluck('nom');

        \Illuminate\Support\Facades\DB::transaction(function () use ($people, $matches, $data) {
            foreach ($matches as $m) {
                \App\Models\CompetitionAthlete::whereKey($m['athlete_id'])->update(['disciple_id' => $m['disciple_id']]);
            }
            foreach ($people as $p) {
                $club = trim((string) ($data['clubs'][$p['ref']] ?? ''));
                if ($club !== '') {
                    // Club renseigné : sur toutes ses inscriptions, puis disciple de cette salle.
                    \App\Models\CompetitionAthlete::whereIn('id', $p['ids'])->update(['club' => $club]);
                } elseif (empty($data['all']) || trim((string) $p['club']) === '') {
                    continue;
                }
                $athlete = \App\Models\CompetitionAthlete::find($p['ref']);
                if ($athlete && !$athlete->disciple_id) {
                    \App\Services\Competition\AthleteRegistry::sync($athlete);
                }
            }
        });

        $created = Disciple::count() - $disciplesBefore;
        $newSalles = Salle::where('ligue_id', $ligueId)->pluck('nom')->diff($sallesBefore);
        ActivityLog::log(action: 'create', subject: 'Disciple', subjectId: null,
            description: $created . ' combattant(s) enregistré(s) comme disciples d\'après leur club' . ($newSalles->isNotEmpty() ? ' ; salles créées : ' . $newSalles->implode(', ') : ''));

        $message = __('messages.pratiquants.registered', ['count' => $created]);
        if ($newSalles->isNotEmpty()) {
            $message .= ' ' . __('messages.pratiquants.salles_created', ['salles' => $newSalles->implode(', ')]);
        }
        if ($matches->isNotEmpty()) {
            $message .= ' ' . __('messages.pratiquants.linked', ['count' => $matches->count()]);
        }

        return redirect()->route('admin.pratiquants.register')->with('success', $message);
    }

    /** Ligue de l'utilisateur : l'enregistrement des combattants se fait au niveau d'une ligue. */
    private function ligueId(Request $request): int
    {
        $ligueId = $request->user()->scopeContext()['ligue_id'] ?? null;
        abort_unless($ligueId, 403, __('messages.pratiquants.ligue_only'));

        return (int) $ligueId;
    }

    /** Disciples du périmètre avec les filtres de la liste (salle, grade, sexe, âge, photo, statut, texte). */
    private function filtered(Request $request): Builder
    {
        $year = now()->year;

        return Disciple::visibleTo($request->user())
            ->when($request->query('statut') !== 'archives', fn ($q) => $q->whereNull('archived_at'), fn ($q) => $q->whereNotNull('archived_at'))
            // Une salle choisie : ses disciples. Sinon, par défaut, seulement les salles qui ont un
            // maître ; « salles=sans » : celles créées d'après le club des combattants, sans maître.
            ->when($request->integer('salle'),
                fn ($q, $id) => $q->where('salle_id', $id),
                fn ($q) => $q->when($request->query('salles') !== 'toutes', fn ($q) => $request->query('salles') === 'sans'
                    ? $q->whereHas('salle', fn ($s) => self::withoutMaster($s))
                    : $q->whereHas('salle', fn ($s) => self::withMaster($s))))
            ->when($request->integer('grade'), fn ($q, $id) => $q->where('grade_id', $id))
            ->when(in_array($request->query('sexe'), ['M', 'F'], true), fn ($q) => $q->where('sexe', $request->query('sexe')))
            ->when($request->filled('age_min'), fn ($q) => $q->whereYear('date_naissance', '<=', $year - $request->integer('age_min')))
            ->when($request->filled('age_max'), fn ($q) => $q->whereYear('date_naissance', '>=', $year - $request->integer('age_max')))
            ->when($request->query('photo') === 'sans', fn ($q) => $q->where(fn ($w) => $w->whereNull('photo')->orWhere('photo', '')))
            ->when($request->query('photo') === 'avec', fn ($q) => $q->whereNotNull('photo')->where('photo', '!=', ''))
            ->when(trim((string) $request->query('q')), function ($q, $text) {
                $q->where(fn ($w) => $w->where('nom', 'like', "%{$text}%")->orWhere('prenom', 'like', "%{$text}%")
                    ->orWhere('nmle', 'like', "%{$text}%")->orWhere('telephone', 'like', "%{$text}%"));
            });
    }

    /** Salles du périmètre, avec « has_master » (maître rattaché ou compte maître de la salle). */
    private function salles(Request $request)
    {
        return Salle::visibleTo($request->user())->orderBy('nom')->get(['id', 'nom', 'maitre_id'])
            ->each(function ($s) { $s->has_master = $s->maitre_id || $s->maitreUser()->exists(); });
    }

    private static function withMaster($query)
    {
        return $query->where(fn ($w) => $w->whereNotNull('maitre_id')->orWhereHas('maitreUser'));
    }

    private static function withoutMaster($query)
    {
        return $query->whereNull('maitre_id')->whereDoesntHave('maitreUser');
    }
}
