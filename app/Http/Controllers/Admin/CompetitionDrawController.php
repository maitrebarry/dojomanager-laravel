<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Combat;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Services\Competition\CategoryAssigner;
use App\Services\Competition\DrawService;
use App\Support\Discipline;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Catégories d'une compétition et tirage au sort : tirer, refaire, valider
 * (verrouiller), déverrouiller, et imprimer tableaux et ordre des combats.
 */
class CompetitionDrawController extends Controller
{
    use AuthorizesScope;

    public function __construct(private DrawService $draws)
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:COMPETITION_MANAGE')->only(['presets', 'storeCategory', 'destroyCategory']);
        $this->middleware('permission:COMPETITION_MANAGE,COMBAT_MANAGE')->except(['presets', 'storeCategory', 'destroyCategory']);
    }

    /* -------------------- Catégories -------------------- */

    /** Ajoute les catégories de poids standard des groupes cochés (ex. Seniors garçons). */
    public function presets(Request $request, Competition $competition): RedirectResponse
    {
        $this->guardScope($competition);
        $data = $request->validate(['groupes' => ['required', 'array', 'min:1'], 'groupes.*' => ['string']]);

        $existing = CompetitionController::groups($competition->load('categories'));
        $order = (int) $competition->categories()->max('ordre');
        $created = 0;
        foreach (Discipline::presetRows($competition->profile(), $data['groupes']) as $row) {
            if (isset($existing[CompetitionCategory::makeGroupKey($row['groupe'], $row['sexe'])])) {
                continue; // groupe déjà présent : on ne le double pas
            }
            $competition->categories()->create($row + ['ordre' => ++$order]);
            $created++;
        }

        return $this->afterCategoriesChange($competition, __('messages.competitions.categories_created', ['count' => $created]));
    }

    /** Catégorie libre (ex. Minimes garçons -30 kg), selon le règlement de la fédération. */
    public function storeCategory(Request $request, Competition $competition): RedirectResponse
    {
        $this->guardScope($competition);
        $data = $request->validate([
            'groupe' => ['required', 'string', 'max:80'],
            'sexe' => ['nullable', Rule::in(['M', 'F'])],
            'age_min' => ['nullable', 'integer', 'min:3', 'max:99'],
            'age_max' => ['nullable', 'integer', 'min:3', 'max:99', 'gte:age_min'],
            'poids_max' => ['nullable', 'numeric', 'min:10', 'max:200'],
        ]);
        $data['groupe'] = trim($data['groupe']);

        $competition->categories()->create($data + ['ordre' => (int) $competition->categories()->max('ordre') + 1]);

        return $this->afterCategoriesChange($competition, __('messages.competitions.category_created'));
    }

    public function destroyCategory(Competition $competition, CompetitionCategory $category): RedirectResponse
    {
        $this->guardScope($competition);
        abort_if($category->tirage_valide, 422, __('messages.competitions.draw_locked_change'));
        $category->delete(); // ses athlètes redeviennent « non classés »

        return $this->afterCategoriesChange($competition, __('messages.competitions.category_deleted'));
    }

    /* -------------------- Tirage -------------------- */

    public function draw(Competition $competition, CompetitionCategory $category): RedirectResponse
    {
        $this->guardScope($competition);
        $this->draws->draw($category);
        $this->log('draw', $category, 'Tirage effectué');

        return $this->backToDraw($competition, $category, __('messages.competitions.drawn'));
    }

    /** Tire toutes les catégories qui ont au moins 2 athlètes et pas encore de tirage validé. */
    public function drawAll(Competition $competition): RedirectResponse
    {
        $this->guardScope($competition);
        $count = 0;
        foreach ($competition->categories()->withCount('athletes')->get() as $category) {
            if (!$category->tirage_valide && $category->athletes_count >= 2) {
                $this->draws->draw($category);
                $this->log('draw', $category, 'Tirage effectué (toutes catégories)');
                $count++;
            }
        }

        return $this->backToDraw($competition, null, __('messages.competitions.drawn_all', ['count' => $count]));
    }

    public function validateDraw(Competition $competition, CompetitionCategory $category): RedirectResponse
    {
        $this->guardScope($competition);
        abort_unless($category->tirage_at, 422, __('messages.competitions.no_draw'));
        $category->update(['tirage_valide' => true]);
        $this->log('validate_draw', $category, 'Tirage validé');

        return $this->backToDraw($competition, $category, __('messages.competitions.draw_validated'));
    }

    public function unlock(Competition $competition, CompetitionCategory $category): RedirectResponse
    {
        $this->guardScope($competition);
        abort_if($this->draws->hasResults($category), 422, __('messages.competitions.unlock_results'));
        $category->update(['tirage_valide' => false]);
        $this->log('unlock_draw', $category, 'Tirage déverrouillé');

        return $this->backToDraw($competition, $category, __('messages.competitions.draw_unlocked'));
    }

    public function cancel(Competition $competition, CompetitionCategory $category): RedirectResponse
    {
        $this->guardScope($competition);
        $this->draws->cancel($category);
        $this->log('cancel_draw', $category, 'Tirage supprimé');

        return $this->backToDraw($competition, $category, __('messages.competitions.draw_deleted'));
    }

    /* -------------------- Résultats -------------------- */

    /** Vainqueur d'un combat (bleu ou rouge) et score facultatif ; il passe au combat suivant. */
    public function result(Request $request, Competition $competition, Combat $combat): RedirectResponse|JsonResponse
    {
        $this->guardScope($competition);
        $data = $request->validate([
            'vainqueur' => ['nullable', Rule::in(['1', '2'])],
            'score' => ['nullable', 'string', 'max:40'],
        ]);
        // Sans bouton cliqué (score + Entrée), le vainqueur est déduit du score chiffré.
        $winner = (int) ($data['vainqueur'] ?? 0) ?: $this->winnerFromScore($data['score'] ?? null);
        if (!$winner) {
            $this->checkScore($data['score'] ?? null, 1, $competition->profile()); // « 2-2 » : message d'égalité
            throw ValidationException::withMessages(['vainqueur' => __('messages.competitions.winner_missing')]);
        }
        $this->checkScore($data['score'] ?? null, $winner, $competition->profile());

        $this->draws->recordResult($combat, $winner === 1 ? $combat->athlete1_id : $combat->athlete2_id, $data['score'] ?? null);

        return $this->fightResponse($request, $competition, $combat->category, __('messages.competitions.result_saved', ['numero' => $combat->numero]));
    }

    /** Coin vainqueur d'après un score chiffré (« 2-1 » → 1), ou 0 si le score n'en dit rien. */
    private function winnerFromScore(?string $score): int
    {
        if (!preg_match('/^\s*(\d+)\s*[-–:\/]\s*(\d+)\s*$/u', (string) $score, $m) || (int) $m[1] === (int) $m[2]) {
            return 0;
        }

        return (int) $m[1] > (int) $m[2] ? 1 : 2;
    }

    /**
     * Après un résultat : saisi sans rechargement, on renvoie la carte de la catégorie et
     * le tableau des médailles à jour ; sinon, retour classique à l'onglet Tirage.
     */
    private function fightResponse(Request $request, Competition $competition, CompetitionCategory $category, string $message): RedirectResponse|JsonResponse
    {
        if (!$request->expectsJson()) {
            return $this->backToDraw($competition, $category, $message);
        }

        $competition->load([
            'categories' => fn ($q) => $q->withCount('athletes'),
            'categories.combats.athlete1',
            'categories.combats.athlete2',
            'categories.combats.vainqueurAthlete',
            'categories.athletes',
        ]);
        $podiums = $competition->categories->mapWithKeys(fn ($c) => [$c->id => $this->draws->podium($c)]);
        $card = $competition->categories->firstWhere('id', $category->id);

        return response()->json([
            'message' => $message,
            'category' => $category->id,
            'card' => view('admin.competitions.tabs._category_card', [
                'competition' => $competition,
                'c' => $card,
                'podium' => $podiums[$category->id],
                'categoryLabels' => CompetitionController::categoryLabels($competition),
            ])->render(),
            'medals' => view('admin.competitions.tabs._medals', ['medals' => CompetitionController::medals($podiums)])->render(),
        ]);
    }

    /**
     * Un score chiffré s'écrit coin du haut - coin du bas (en taekwondo Bleu-Rouge : « 2-1 »
     * = victoire du bleu) : il doit désigner le même vainqueur que le bouton cliqué, et ne
     * peut pas être à égalité. Un score en texte (« KO », « Ippon »…) n'est pas contrôlé.
     */
    private function checkScore(?string $score, int $winner, array $profile): void
    {
        if (!preg_match('/^\s*(\d+)\s*[-–:\/]\s*(\d+)\s*$/u', (string) $score, $m)) {
            return;
        }
        [$first, $second] = [(int) $m[1], (int) $m[2]];
        [$one, $two] = [$profile['corners'][1]['short'], $profile['corners'][2]['short']];

        if ($first === $second) {
            throw ValidationException::withMessages(['score' => __('messages.competitions.score_tie', ['score' => trim($score)])]);
        }
        $scoreWinner = $first > $second ? 1 : 2;
        if ($scoreWinner !== $winner) {
            throw ValidationException::withMessages(['score' => __('messages.competitions.score_mismatch', [
                'score' => trim($score),
                'side' => $scoreWinner === 1 ? $one : $two,
                'order' => $one . '-' . $two,
            ])]);
        }
    }

    public function clearResult(Request $request, Competition $competition, Combat $combat): RedirectResponse|JsonResponse
    {
        $this->guardScope($competition);
        $this->draws->clearResult($combat);

        return $this->fightResponse($request, $competition, $combat->category, __('messages.competitions.result_cleared', ['numero' => $combat->numero]));
    }

    /** Tableaux de tirage en PDF : une catégorie (?category=id) ou toutes celles tirées. */
    public function bracketsPdf(Request $request, Competition $competition)
    {
        $this->guardScope($competition);
        $categories = $this->drawnCategories($request, $competition);

        return Pdf::loadView('admin.competitions.pdf.tableaux', [
            'competition' => $competition,
            'categories' => $categories,
            'labels' => CompetitionController::categoryLabels($competition),
        ])->setPaper('a4', 'landscape')->stream('tirage-' . $competition->id . '.pdf');
    }

    /** Ordre des combats : liste à appeler, avec colonnes vides pour le vainqueur et le score. */
    public function fightsPdf(Request $request, Competition $competition)
    {
        $this->guardScope($competition);
        $categories = $this->drawnCategories($request, $competition);

        return Pdf::loadView('admin.competitions.pdf.combats', [
            'competition' => $competition,
            'categories' => $categories,
            'labels' => CompetitionController::categoryLabels($competition),
        ])->setPaper('a4', 'portrait')->stream('combats-' . $competition->id . '.pdf');
    }

    private function drawnCategories(Request $request, Competition $competition)
    {
        $competition->load(['categories.combats.athlete1', 'categories.combats.athlete2', 'categories.combats.vainqueurAthlete', 'categories.athletes']);

        $categories = $competition->categories
            ->when($request->filled('category'), fn ($c) => $c->where('id', $request->integer('category')))
            ->filter(fn ($c) => $c->tirage_at && $c->athletes->isNotEmpty())
            ->values();
        abort_if($categories->isEmpty(), 404, __('messages.competitions.no_draw'));

        return $categories;
    }

    private function afterCategoriesChange(Competition $competition, string $message): RedirectResponse
    {
        // Les athlètes en attente d'une catégorie y sont rangés dès qu'elle existe.
        $assigned = (new CategoryAssigner($competition->fresh()))->assignUnclassified($this->draws);
        $warnings = $assigned ? [__('messages.competitions.assigned', ['count' => $assigned])] : [];
        if ($redraw = $this->draws->redrawCanceled()) {
            $warnings[] = $redraw;
        }
        $redirect = redirect()->route('admin.competitions.show', [$competition, 'tab' => 'categories'])->with('success', $message);

        return $warnings ? $redirect->with('warning', implode(' ', $warnings)) : $redirect;
    }

    private function backToDraw(Competition $competition, ?CompetitionCategory $category, string $message): RedirectResponse
    {
        $url = route('admin.competitions.show', [$competition, 'tab' => 'tirage']) . ($category ? '#categorie-' . $category->id : '');

        return redirect()->to($url)->with('success', $message);
    }

    private function log(string $action, CompetitionCategory $category, string $description): void
    {
        ActivityLog::log(
            action: $action,
            subject: 'CompetitionCategory',
            subjectId: $category->id,
            description: $description . ' — ' . $category->competition->nom . ' / ' . (CompetitionController::categoryLabels($category->competition->load('categories'))[$category->id] ?? $category->groupe)
        );
    }
}
