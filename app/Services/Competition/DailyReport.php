<?php

namespace App\Services\Competition;

use App\Http\Controllers\Admin\CompetitionController;
use App\Models\Competition;
use Illuminate\Support\Carbon;

/**
 * Rapport d'une journée de compétition : combats disputés ce jour-là, avancement général,
 * catégories terminées et podiums, médailles par club, ce qui reste à jouer et une
 * prévision de durée (rythme réel de la journée, à défaut durée réglementaire des
 * manches de chaque catégorie).
 */
class DailyReport
{
    /** Battement entre deux combats (appel, saluts, décision), ajouté au temps de combat. */
    private const TRANSITION_SECONDS = 120;

    /**
     * $options : 'pace' (secondes par combat et par aire, donnée par l'organisateur),
     * 'start' et 'end' (« 09:00 », « 14:00 ») : horaires de la journée de combats.
     */
    public static function build(Competition $competition, Carbon $day, int $aires = 1, array $options = []): array
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

        // Rythme : écart médian entre deux résultats enregistrés ce jour (toutes aires
        // confondues). La médiane écarte les pauses (repas, réclamation) et les résultats
        // saisis d'un coup depuis la feuille (écarts de quelques secondes).
        $times = $today->pluck('time')->filter()->sort()->values();
        $gaps = $times->slice(1)->values()->map(fn ($t, $i) => $times[$i]->diffInSeconds($t))
            ->filter(fn ($g) => $g >= 30 && $g <= 20 * 60)->sort()->values();
        $pace = $gaps->count() >= 3 ? $gaps[intdiv($gaps->count(), 2)] : null;
        // Rythme annoncé par l'organisateur (un combat toutes les 4 min sur chaque aire) : prioritaire.
        $paceSource = $pace ? 'observed' : null;
        if (!empty($options['pace'])) {
            $pace = $options['pace'] / max(1, $aires);
            $paceSource = 'set';
        }
        // Durée réglementaire de chaque combat restant, avec la durée de manche de sa catégorie.
        // Combat en manches gagnantes (taekwondo : 2 sur 3) : 2,5 manches en moyenne, pas 3.
        $chrono = $competition->chronoSettings();
        $toWin = $competition->profile()['rounds_to_win'] ?? null;
        $rounds = $toWin ? ($toWin + $chrono['rounds']) / 2 : $chrono['rounds'];
        $remainingFights = $fights->reject(fn ($pair) => $pair[0]->vainqueur_athlete_id);
        $remaining = $remainingFights->count();
        $theoreticalTotal = $remainingFights->sum(fn ($pair) => $rounds * $pair[1]->roundSeconds() + max(0, $rounds - 1) * $chrono['rest'] + self::TRANSITION_SECONDS) / max(1, $aires);
        $theoretical = $remaining ? $theoreticalTotal / $remaining
            : ($rounds * $chrono['round'] + max(0, $rounds - 1) * $chrono['rest'] + self::TRANSITION_SECONDS) / max(1, $aires);
        $secondsLeft = (int) round($pace ? $remaining * $pace : $theoreticalTotal);

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
            'pace_source' => $paceSource,
            'pace_per_area' => $paceSource === 'set' ? (int) $options['pace'] : null,
        ] + self::schedule($day, $times, $remaining, $secondsLeft, $options) + [
            'to_weigh' => $competition->athletes->whereNull('poids')->count(),
            'unclassified' => $competition->athletes->whereNotNull('poids')->whereNull('category_id')->count(),
            'not_drawn' => $active->filter(fn ($c) => $c->athletes_count >= 2 && !$c->tirage_at)->count(),
            'singles' => $active->filter(fn ($c) => $c->athletes_count === 1)->count(),
        ];
    }

    /**
     * Fin prévue. Journée en cours : maintenant + temps restant. Journée achevée (heure de fin
     * passée, journée antérieure, ou plus d'une heure sans résultat) : à partir de la reprise,
     * sans compter la nuit.
     */
    private static function schedule(Carbon $day, $times, int $remaining, int $secondsLeft, array $options): array
    {
        $at = fn (?string $hm) => $hm ? $day->copy()->setTimeFromTimeString($hm) : null;
        $start = $at($options['start'] ?? null);
        $end = $at($options['end'] ?? null);
        $last = $times->last();
        $ended = !$day->isToday()
            || ($end && now()->greaterThanOrEqualTo($end))
            || (!$end && $last && $last->diffInMinutes(now()) > 60);
        $resume = $start ?? $times->first();

        return [
            'day_start' => $start ?? $times->first(),
            'day_end' => $ended ? ($end ?? $last) : null,
            'resume_at' => $remaining && $ended ? $resume : null,
            'estimated_end' => !$remaining ? null
                : ($ended ? ($resume ? $resume->copy()->addSeconds($secondsLeft) : null) : now()->addSeconds($secondsLeft)),
        ];
    }
}
