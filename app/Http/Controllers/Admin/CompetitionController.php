<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Controller;
use App\Models\Combat;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Services\Competition\DrawService;
use App\Models\Federation;
use App\Support\Discipline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    use AuthorizesScope;

    public const TABS = ['athletes', 'pesee', 'categories', 'tirage'];

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:COMPETITION_MANAGE')->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
        $this->middleware('permission:COMPETITION_MANAGE,COMBAT_MANAGE')->only(['removeCombat']);
    }

    public function index(Request $request): View
    {
        $competitions = Competition::query()
            ->visibleTo($request->user())
            ->withCount(['athletes', 'categories'])
            ->when($request->filled('search'), fn ($q) => $q->where('nom', 'like', '%' . $request->query('search') . '%'))
            ->orderByDesc('date_competition')
            ->paginate(15)
            ->withQueryString();

        return view('admin.competitions.index', [
            'competitions' => $competitions,
            'page_title' => __('messages.competitions.title'),
        ]);
    }

    public function create(Request $request): View
    {
        $federation = Federation::find($request->user()->scopeContext()['federation_id']);

        return view('admin.competitions.create', [
            // Règle du bronze proposée selon la discipline de la fédération.
            'competition' => new Competition(['bronze' => Discipline::forFederation($federation)['bronze']]),
            'page_title' => __('messages.competitions.add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $context = $request->user()->scopeContext();

        // Une ligue crée pour sa ligue (et sa fédération), une fédération pour elle-même.
        $data = $this->validateData($request);
        $data['bronze'] ??= Discipline::forFederation(Federation::find($context['federation_id']))['bronze'];

        $competition = Competition::create($data + [
            'federation_id' => $context['federation_id'],
            'ligue_id' => $context['ligue_id'],
            'created_by_user_id' => $request->user()->id,
        ]);

        return redirect()->route('admin.competitions.show', [$competition, 'tab' => 'categories'])
            ->with('success', __('messages.competitions.created'));
    }

    public function show(Request $request, Competition $competition): View
    {
        $this->guardScope($competition);

        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'athletes';

        $competition->load([
            'categories' => fn ($q) => $q->withCount('athletes'),
            'categories.combats.athlete1',
            'categories.combats.athlete2',
            'categories.combats.vainqueurAthlete',
            'categories.athletes',
            'athletes' => fn ($q) => $q->orderBy('nom')->orderBy('prenom'),
        ]);

        $draws = app(DrawService::class);
        $podiums = $competition->categories->mapWithKeys(fn ($c) => [$c->id => $draws->podium($c)]);

        return view('admin.competitions.show', [
            'competition' => $competition,
            'tab' => $tab,
            'podiums' => $podiums,
            'medals' => self::medals($podiums),
            'categoryLabels' => self::categoryLabels($competition),
            'groups' => self::groups($competition),
            'profile' => $competition->profile(),
            'presets' => $competition->profile()['presets'],
            'orders' => \App\Services\Competition\AthleteListParser::ORDERS,
            'legacyCombats' => $competition->combats()->whereNull('category_id')
                ->with(['combattant1', 'combattant2', 'vainqueur'])->get(),
            'page_title' => $competition->nom,
        ]);
    }

    public function edit(Competition $competition): View
    {
        $this->guardScope($competition);

        return view('admin.competitions.edit', [
            'competition' => $competition,
            'page_title' => __('messages.competitions.edit'),
        ]);
    }

    public function update(Request $request, Competition $competition): RedirectResponse
    {
        $this->guardScope($competition);
        $data = $this->validateData($request);
        $data['bronze'] ??= $competition->bronze;

        // La règle du bronze change la forme des tableaux : refusée si un tirage est validé,
        // sinon les tirages provisoires sont annulés pour être refaits.
        $canceled = 0;
        if ($data['bronze'] !== $competition->bronze) {
            if ($competition->categories()->where('tirage_valide', true)->exists()) {
                throw ValidationException::withMessages(['bronze' => __('messages.competitions.bronze_locked')]);
            }
            foreach ($competition->categories()->whereNotNull('tirage_at')->get() as $category) {
                app(DrawService::class)->cancel($category);
                $canceled++;
            }
        }
        $competition->update($data);

        $redirect = redirect()->route('admin.competitions.show', $competition)->with('success', __('messages.competitions.updated'));

        return $canceled ? $redirect->with('warning', __('messages.competitions.bronze_redraw', ['count' => $canceled])) : $redirect;
    }

    public function destroy(Competition $competition): RedirectResponse
    {
        $this->guardScope($competition);
        $competition->delete();

        return redirect()->route('admin.competitions.index')
            ->with('success', __('messages.competitions.deleted'));
    }

    /** Combats saisis à la main avant le module de tirage (anciennes données). */
    public function removeCombat(Competition $competition, Combat $combat): RedirectResponse
    {
        $this->guardScope($competition);
        abort_unless($combat->competition_id === $competition->id && $combat->category_id === null, 404);
        $combat->delete();

        return back()->with('success', __('messages.competitions.combat_removed'));
    }

    /**
     * Libellé complet de chaque catégorie, ex. « Seniors garçons -68 kg » ; la dernière
     * catégorie d'un groupe (sans limite) devient « +87 kg ».
     *
     * @return array<int, string> id => libellé
     */
    public static function categoryLabels(Competition $competition): array
    {
        $labels = [];
        $competition->categories->groupBy(fn (CompetitionCategory $c) => $c->groupKey())
            ->each(function ($group) use (&$labels) {
                $previousMax = null;
                foreach ($group->sortBy(fn ($c) => $c->poids_max === null ? PHP_FLOAT_MAX : (float) $c->poids_max) as $category) {
                    $labels[$category->id] = $category->groupLabel() . ' ' . $category->weightLabel($previousMax);
                    $previousMax = $category->poids_max !== null ? (float) $category->poids_max : $previousMax;
                }
            });

        return $labels;
    }

    /**
     * Médailles par club sur les catégories terminées, classées or > argent > bronze.
     *
     * @return array<string, array{gold: int, silver: int, bronze: int}>
     */
    public static function medals($podiums): array
    {
        $table = [];
        $add = function ($athlete, string $medal) use (&$table) {
            if (!$athlete) {
                return;
            }
            $club = $athlete->club ?: __('messages.competitions.no_club');
            $table[$club] ??= ['gold' => 0, 'silver' => 0, 'bronze' => 0];
            $table[$club][$medal]++;
        };
        foreach ($podiums as $podium) {
            if (!$podium['complete']) {
                continue;
            }
            $add($podium['gold'], 'gold');
            $add($podium['silver'], 'silver');
            foreach ($podium['bronze'] as $athlete) {
                $add($athlete, 'bronze');
            }
        }
        uasort($table, fn ($a, $b) => [$b['gold'], $b['silver'], $b['bronze']] <=> [$a['gold'], $a['silver'], $a['bronze']]);

        return $table;
    }

    /** Groupes (groupe + sexe) existants : clé « Seniors|M » => « Seniors garçons ». */
    public static function groups(Competition $competition): array
    {
        return $competition->categories
            ->mapWithKeys(fn (CompetitionCategory $c) => [$c->groupKey() => $c->groupLabel()])
            ->all();
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'date_competition' => ['nullable', 'date'],
            'lieu' => ['nullable', 'string', 'max:180'],
            'type' => ['nullable', 'string', 'max:80'],
            'bronze' => ['nullable', Rule::in(['deux', 'match'])],
        ]);
    }
}
