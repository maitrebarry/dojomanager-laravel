<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Controller;
use App\Models\Combat;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Support\WeightCategories;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function create(): View
    {
        return view('admin.competitions.create', [
            'competition' => new Competition(),
            'page_title' => __('messages.competitions.add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $context = $request->user()->scopeContext();

        // Une ligue crée pour sa ligue (et sa fédération), une fédération pour elle-même.
        $competition = Competition::create($this->validateData($request) + [
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
            'athletes' => fn ($q) => $q->orderBy('nom')->orderBy('prenom'),
        ]);

        return view('admin.competitions.show', [
            'competition' => $competition,
            'tab' => $tab,
            'categoryLabels' => self::categoryLabels($competition),
            'groups' => self::groups($competition),
            'presets' => WeightCategories::PRESETS,
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
        $competition->update($this->validateData($request));

        return redirect()->route('admin.competitions.show', $competition)
            ->with('success', __('messages.competitions.updated'));
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
        ]);
    }
}
