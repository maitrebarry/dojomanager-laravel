<?php

namespace App\Services\Competition;

use App\Http\Controllers\Admin\CompetitionController;
use App\Models\Competition;
use Illuminate\Support\Carbon;

/**
 * Rapport d'une journée de compétition : combats disputés ce jour-là, avancement général,
 * catégories terminées et podiums, médailles par club, ce qui reste à jouer et une
 * prévision de durée (rythme réel de la journée, à défaut durées du chrono).
 */
class DailyReport
{
    /** Battement entre deux combats (appel, saluts, décision), ajouté au temps de combat. */
    private const TRANSITION_SECONDS = 120;

    public static function build(Competition $competition, Carbon $day, int $aires = 1): array
    {
        $competition->load([
            'categories' => fn ($q) => $q->withCount('athletes'),
            'categories.combats.athlete1',
            'categories.combats.athlete2',
            'categories.combats.vainqueurAthlete',
            'categories.athletes',
            'athletes',
        ]);
        $labels = CompetitionController::categoryLabels($competition);
        $draws = app(DrawService::class);
        $podiums = $competition->categories->mapWithKeys(fn ($c) => [$c->id => $draws->podium($c)]);

        $fights = $competition->categories->flatMap(fn ($c) => $c->combats->map(fn ($f) => [$f, $c]))
            ->reject(fn ($pair) => $pair[0]->exempt);
        $played = $fights->filter(fn ($pair) => $pair[0]->vainqueur_athlete_id);
        $playedAt = fn ($f) => $f->joue_at ?? $f->updated_at;

        // Combats du jour, dans l'ordre où ils ont été enregistrés.
        $today = $played->filter(fn ($pair) => $playedAt($pair[0])?->isSameDay($day))
            ->sortBy(fn ($pair) => $playedAt($pair[0]))->values()
            ->map(fn ($pair) => [
                'time' => $playedAt($pair[0]),
                'category' => $labels[$pair[1]->id],
                'stage' => $pair[0]->tour,
                'winner' => $pair[0]->vainqueurAthlete,
                'loser' => $pair[0]->vainqueur_athlete_id === $pair[0]->athlete1_id ? $pair[0]->athlete2 : $pair[0]->athlete1,
                'score' => $pair[0]->score,
            ]);

        // Catégories terminées ce jour-là : finale (ou match pour la 3e place) jouée ce jour.
        $finishedToday = $competition->categories->filter(function ($c) use ($podiums, $day, $playedAt) {
            if (!$podiums[$c->id]['complete'] || $c->combats->isEmpty()) {
                return false;
            }
            $last = $c->combats->reject(fn ($f) => $f->exempt)->max(fn ($f) => $playedAt($f));

            return $last && Carbon::parse($last)->isSameDay($day);
        })->map(fn ($c) => ['category' => $labels[$c->id], 'podium' => $podiums[$c->id]])->values();

        // Rythme : temps moyen entre deux résultats enregistrés ce jour (toutes aires confondues).
        $times = $today->pluck('time')->filter()->sort()->values();
        $pace = $times->count() >= 3 ? $times->first()->diffInSeconds($times->last()) / ($times->count() - 1) : null;
        // Résultats saisis d'un coup après les combats (depuis la feuille) : rythme sans valeur.
        if ($pace !== null && $pace < 60) {
            $pace = null;
        }
        $chrono = $competition->profile()['chrono'];
        $theoretical = ($chrono['rounds'] * $chrono['round'] + max(0, $chrono['rounds'] - 1) * $chrono['rest'] + self::TRANSITION_SECONDS) / max(1, $aires);
        $remaining = $fights->count() - $played->count();
        $secondsLeft = (int) round($remaining * ($pace ?: $theoretical));

        $readyOrder = FightOrder::for($competition, $aires)->filter(fn ($i) => $i['combat']->isReady())->take(10)->values()
            ->map(fn ($i) => ['order' => $i['order'], 'aire' => $i['aire'], 'category' => $labels[$i['category_id']], 'stage' => $i['stage'], 'one' => $i['combat']->athlete1, 'two' => $i['combat']->athlete2]);

        $remainingByCategory = $competition->categories
            ->map(fn ($c) => ['category' => $labels[$c->id], 'left' => $c->combats->reject(fn ($f) => $f->exempt || $f->vainqueur_athlete_id)->count(), 'total' => $c->combats->reject(fn ($f) => $f->exempt)->count()])
            ->filter(fn ($r) => $r['left'] > 0)->values();

        $active = $competition->categories->filter(fn ($c) => $c->athletes_count > 0);

        return [
            'day' => $day,
            'aires' => $aires,
            'labels' => $labels,
            'athletes' => $competition->athletes->count(),
            'clubs' => $competition->athletes->pluck('club')->filter()->unique()->count(),
            'categories' => $active->count(),
            'categories_finished' => $active->filter(fn ($c) => $podiums[$c->id]['complete'])->count(),
            'fights_total' => $fights->count(),
            'fights_played' => $played->count(),
            'fights_today' => $today,
            'fights_remaining' => $remaining,
            'finished_today' => $finishedToday,
            'medals' => CompetitionController::medals($podiums),
            'remaining_by_category' => $remainingByCategory,
            'next_fights' => $readyOrder,
            'pace_seconds' => $pace ? (int) round($pace) : null,
            'theoretical_seconds' => (int) round($theoretical),
            'seconds_left' => $secondsLeft,
            'estimated_end' => $remaining && $day->isToday() ? now()->addSeconds($secondsLeft) : null,
            'to_weigh' => $competition->athletes->whereNull('poids')->count(),
            'unclassified' => $competition->athletes->whereNotNull('poids')->whereNull('category_id')->count(),
            'not_drawn' => $active->filter(fn ($c) => $c->athletes_count >= 2 && !$c->tirage_at)->count(),
            'singles' => $active->filter(fn ($c) => $c->athletes_count === 1)->count(),
        ];
    }
}
