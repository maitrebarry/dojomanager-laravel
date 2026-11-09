<?php

namespace App\Services\Competition;

use App\Models\Combat;
use App\Models\CompetitionAthlete;
use App\Models\CompetitionCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Tirage au sort d'une catégorie en tableau à élimination directe :
 *  - tableau de 2, 4, 8, 16… places ; les places en trop sont des exempts, placés
 *    face aux meilleures têtes de série puis répartis dans tout le tableau ;
 *  - têtes de série (1, 2, 3…) placées aux extrémités, selon l'ordre classique
 *    (1 en haut, 2 en bas, 3 et 4 dans les autres moitiés…) ;
 *  - les athlètes d'un même club sont écartés le plus possible : ils ne peuvent se
 *    rencontrer qu'au tour le plus tardif que permet le tableau ;
 *  - le reste est tiré au hasard.
 * Pas de combat pour la 3e place : les deux perdants des demi-finales sont 3es.
 */
class DrawService
{
    private Randomizer $random;

    public function __construct(?int $seed = null)
    {
        $this->random = new Randomizer($seed === null ? null : new Mt19937($seed));
    }

    public function draw(CompetitionCategory $category): void
    {
        abort_if($category->tirage_valide, 422, __('messages.competitions.draw_locked'));

        DB::transaction(function () use ($category) {
            $category->combats()->delete();

            $athletes = $category->athletes()->get();
            if ($athletes->count() >= 2) {
                $this->createBracket($category, $this->placeAthletes($athletes));
            }

            $category->update(['tirage_at' => now(), 'tirage_valide' => false]);
            $this->renumber($category);
        });
    }

    /** Supprime un tirage non validé (ex. après l'ajout d'un athlète dans la catégorie). */
    public function cancel(CompetitionCategory $category): void
    {
        abort_if($category->tirage_valide, 422, __('messages.competitions.draw_locked'));

        DB::transaction(function () use ($category) {
            $category->combats()->delete();
            $category->update(['tirage_at' => null]);
        });
    }

    /**
     * À appeler avant d'ajouter, modifier ou retirer un athlète d'une catégorie :
     * refuse si le tirage est validé, et annule un tirage provisoire devenu faux.
     * Renvoie vrai si un tirage a été annulé.
     */
    public function beforeChange(?CompetitionCategory $category): bool
    {
        if (!$category) {
            return false;
        }
        abort_if($category->tirage_valide, 422, __('messages.competitions.draw_locked_change'));

        if ($category->tirage_at) {
            $this->cancel($category);

            return true;
        }

        return false;
    }

    /**
     * Enregistre le vainqueur d'un combat et le place dans le combat suivant (tour + 1,
     * place p/2 : en bleu s'il venait du combat du haut, en rouge sinon).
     */
    public function recordResult(Combat $combat, int $winnerId, ?string $score): void
    {
        $category = $combat->category;
        abort_unless($category->tirage_valide, 422, __('messages.competitions.results_need_validation'));
        abort_if($combat->exempt || !$combat->athlete1_id || !$combat->athlete2_id, 422, __('messages.competitions.fight_not_ready'));
        abort_unless(in_array($winnerId, [$combat->athlete1_id, $combat->athlete2_id], true), 422);

        $next = $this->nextCombat($combat);
        $third = $this->thirdPlaceFor($combat);
        $changed = $combat->vainqueur_athlete_id !== $winnerId;
        abort_if($changed && ($next?->vainqueur_athlete_id || $third?->vainqueur_athlete_id), 422, __('messages.competitions.next_fight_played'));

        $side = $combat->position % 2 === 0 ? 'athlete1_id' : 'athlete2_id';
        $loserId = $winnerId === $combat->athlete1_id ? $combat->athlete2_id : $combat->athlete1_id;
        DB::transaction(function () use ($combat, $winnerId, $score, $next, $third, $side, $loserId) {
            $combat->update(['vainqueur_athlete_id' => $winnerId, 'score' => $score !== null && trim($score) !== '' ? trim($score) : null]);
            $next?->update([$side => $winnerId]);
            $third?->update([$side => $loserId]); // le perdant d'une demi-finale joue la 3e place
        });
    }

    /** Annule le résultat d'un combat, tant que le combat suivant n'est pas joué. */
    public function clearResult(Combat $combat): void
    {
        abort_if($combat->exempt, 422);
        $next = $this->nextCombat($combat);
        $third = $this->thirdPlaceFor($combat);
        abort_if($next?->vainqueur_athlete_id || $third?->vainqueur_athlete_id, 422, __('messages.competitions.next_fight_played'));

        $side = $combat->position % 2 === 0 ? 'athlete1_id' : 'athlete2_id';
        DB::transaction(function () use ($combat, $next, $third, $side) {
            $combat->update(['vainqueur_athlete_id' => null, 'score' => null]);
            $next?->update([$side => null]);
            $third?->update([$side => null]);
        });
    }

    /** Combat où va le vainqueur (le match pour la 3e place n'en est jamais un). */
    public function nextCombat(Combat $combat): ?Combat
    {
        if ($combat->troisieme_place) {
            return null;
        }

        return Combat::where('category_id', $combat->category_id)
            ->where('troisieme_place', false)
            ->where('round', $combat->round + 1)
            ->where('position', intdiv($combat->position, 2))
            ->first();
    }

    /** Match pour la 3e place où va le perdant, si le combat est une demi-finale. */
    public function thirdPlaceFor(Combat $combat): ?Combat
    {
        if ($combat->troisieme_place) {
            return null;
        }
        $third = Combat::where('category_id', $combat->category_id)->where('troisieme_place', true)->first();

        return $third && $third->round === $combat->round + 1 ? $third : null;
    }

    /**
     * Podium d'une catégorie : or et argent à la finale ; bronze au vainqueur du match
     * pour la 3e place s'il existe, sinon aux deux perdants des demi-finales. Un athlète
     * seul est vainqueur sans combat.
     *
     * @return array{gold: ?CompetitionAthlete, silver: ?CompetitionAthlete, bronze: array<int, CompetitionAthlete>, complete: bool}
     */
    public function podium(CompetitionCategory $category): array
    {
        $athletes = $category->athletes->keyBy('id');
        if ($athletes->count() === 1) {
            return ['gold' => $athletes->first(), 'silver' => null, 'bronze' => [], 'complete' => true];
        }

        $combats = $category->combats;
        $rounds = (int) $combats->max('round');
        $final = $combats->first(fn ($c) => $c->round === $rounds && !$c->troisieme_place);
        $third = $combats->first(fn ($c) => $c->troisieme_place);
        if (!$final?->vainqueur_athlete_id || ($third && !$third->vainqueur_athlete_id)) {
            return ['gold' => null, 'silver' => null, 'bronze' => [], 'complete' => false];
        }

        $bronze = $third
            ? [$athletes[$third->vainqueur_athlete_id] ?? null]
            : $combats->filter(fn ($c) => $c->round === $rounds - 1 && $c->loserId())
                ->map(fn ($c) => $athletes[$c->loserId()] ?? null)->all();

        return [
            'gold' => $athletes[$final->vainqueur_athlete_id] ?? null,
            'silver' => $athletes[$final->loserId()] ?? null,
            'bronze' => array_values(array_filter($bronze)),
            'complete' => true,
        ];
    }

    /** Un tirage validé n'est déverrouillable que tant qu'aucun résultat n'est saisi. */
    public function hasResults(CompetitionCategory $category): bool
    {
        return $category->combats()->where('exempt', false)->whereNotNull('vainqueur_athlete_id')->exists();
    }

    /**
     * Place des athlètes dans le tableau.
     *
     * @return array<int, ?CompetitionAthlete> place (0 = en haut) => athlète, null = exempt
     */
    public function placeAthletes(Collection $athletes): array
    {
        $count = $athletes->count();
        $size = 2 ** (int) ceil(log($count, 2));
        $rankAt = self::seedOrder($size);        // place => rang de tête de série
        $placeOfRank = array_flip($rankAt);       // rang => place

        $slots = array_fill(0, $size, null);
        $free = [];
        foreach ($rankAt as $place => $rank) {
            if ($rank <= $count) {
                $free[$place] = true; // les rangs au-delà du nombre d'athlètes sont les exempts
            }
        }

        // Têtes de série : rang 1, 2, 3… dans l'ordre indiqué.
        $seeded = $athletes->filter(fn ($a) => $a->tete_de_serie)->sortBy('tete_de_serie')->values();
        foreach ($seeded as $index => $athlete) {
            $place = $placeOfRank[$index + 1];
            $slots[$place] = $athlete;
            unset($free[$place]);
        }

        // Autres athlètes : les clubs les plus nombreux (têtes de série comprises) d'abord,
        // chacun placé là où il rencontrerait le plus tard un athlète de son club. Ce
        // placement pas à pas peut se bloquer ; on garde le meilleur de plusieurs essais.
        $others = $athletes->reject(fn ($a) => $a->tete_de_serie)->values();
        $clubSize = $athletes->filter(fn ($a) => $this->clubKey($a) !== null)->countBy(fn ($a) => $this->clubKey($a));

        $best = null;
        $bestScore = null;
        for ($attempt = 0; $attempt < 40; $attempt++) {
            $byClub = $others->groupBy(fn ($a) => $this->clubKey($a) ?? 'solo-' . $a->id)
                ->map(fn ($group) => $this->random->shuffleArray($group->all()))
                // Sans club : 0, placés en dernier. À taille égale, ordre tiré au hasard.
                ->sortByDesc(fn ($group) => ($clubSize[$this->clubKey($group[0]) ?? ''] ?? 0) * 1000 + $this->random->getInt(0, 999));

            $candidate = $slots;
            $candidateFree = $free;
            foreach ($byClub as $group) {
                foreach ($group as $athlete) {
                    $place = $this->bestPlace(array_keys($candidateFree), $athlete, $candidate);
                    $candidate[$place] = $athlete;
                    unset($candidateFree[$place]);
                }
            }

            $score = $this->separationScore($candidate);
            if ($bestScore === null || $score > $bestScore) {
                [$best, $bestScore] = [$candidate, $score];
            }
        }

        return $best ?? $slots;
    }

    /**
     * Qualité de la séparation des clubs : rencontre la plus précoce entre deux athlètes
     * d'un même club (plus elle est tardive, mieux c'est), puis le moins de paires à ce tour.
     */
    private function separationScore(array $slots): array
    {
        $earliest = PHP_INT_MAX;
        $pairs = 0;
        $places = array_keys(array_filter($slots));
        foreach ($places as $i => $a) {
            foreach (array_slice($places, $i + 1) as $b) {
                $club = $this->clubKey($slots[$a]);
                if ($club === null || $club !== $this->clubKey($slots[$b])) {
                    continue;
                }
                $round = self::meetingRound($a, $b);
                if ($round < $earliest) {
                    [$earliest, $pairs] = [$round, 1];
                } elseif ($round === $earliest) {
                    $pairs++;
                }
            }
        }

        return [$earliest, -$pairs];
    }

    /** Ordre des têtes de série par place : 4 places → [1, 4, 2, 3], 8 → [1, 8, 4, 5, 2, 7, 3, 6]. */
    public static function seedOrder(int $size): array
    {
        $order = [1];
        while (count($order) < $size) {
            $next = count($order) * 2;
            $expanded = [];
            foreach ($order as $rank) {
                $expanded[] = $rank;
                $expanded[] = $next + 1 - $rank;
            }
            $order = $expanded;
        }

        return $order;
    }

    /** Tour où se rencontreraient deux places (1 = premier tour). */
    public static function meetingRound(int $a, int $b): int
    {
        return (int) floor(log($a ^ $b, 2)) + 1;
    }

    private function bestPlace(array $freePlaces, CompetitionAthlete $athlete, array $slots): int
    {
        $club = $this->clubKey($athlete);
        $mates = [];
        if ($club !== null) {
            foreach ($slots as $place => $placed) {
                if ($placed && $this->clubKey($placed) === $club) {
                    $mates[] = $place;
                }
            }
        }

        $best = [];
        $bestScore = -1;
        foreach ($freePlaces as $place) {
            $score = PHP_INT_MAX;
            foreach ($mates as $mate) {
                $score = min($score, self::meetingRound($place, $mate));
            }
            if ($score > $bestScore) {
                [$best, $bestScore] = [[$place], $score];
            } elseif ($score === $bestScore) {
                $best[] = $place;
            }
        }

        return $best[$this->random->getInt(0, count($best) - 1)];
    }

    private function clubKey(CompetitionAthlete $athlete): ?string
    {
        $club = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $athlete->club)));

        return $club === '' ? null : $club;
    }

    /** Crée tous les combats du tableau ; un athlète exempt passe directement au 2e tour. */
    private function createBracket(CompetitionCategory $category, array $slots): void
    {
        $size = count($slots);
        $rounds = (int) log($size, 2);
        $base = ['competition_id' => $category->competition_id, 'category_id' => $category->id];

        $winners = [];
        for ($position = 0; $position < $size / 2; $position++) {
            [$top, $bottom] = [$slots[2 * $position], $slots[2 * $position + 1]];
            $exempt = $top === null || $bottom === null;
            Combat::create($base + [
                'tour' => self::roundName(1, $rounds),
                'round' => 1,
                'position' => $position,
                'athlete1_id' => $top?->id,
                'athlete2_id' => $bottom?->id,
                'exempt' => $exempt,
                'vainqueur_athlete_id' => $exempt ? ($top ?? $bottom)->id : null,
            ]);
            $winners[$position] = $exempt ? ($top ?? $bottom)->id : null;
        }

        for ($round = 2; $round <= $rounds; $round++) {
            $count = $size / (2 ** $round);
            for ($position = 0; $position < $count; $position++) {
                Combat::create($base + [
                    'tour' => self::roundName($round, $rounds),
                    'round' => $round,
                    'position' => $position,
                    // Seul le 2e tour peut déjà connaître un combattant (venu d'un exempt).
                    'athlete1_id' => $round === 2 ? ($winners[2 * $position] ?? null) : null,
                    'athlete2_id' => $round === 2 ? ($winners[2 * $position + 1] ?? null) : null,
                ]);
            }
        }

        // Match pour la 3e place (si la compétition le prévoit) : seulement si les deux
        // demi-finales sont de vrais combats, sinon il n'y a qu'un perdant, 3e d'office.
        // Avec 2 tours, les demi-finales sont le 1er tour : un vainqueur déjà connu = un exempt.
        $semiHasBye = $rounds === 2 && array_filter($winners) !== [];
        if ($category->competition->bronze === 'match' && $rounds >= 2 && !$semiHasBye) {
            Combat::create($base + [
                'tour' => self::THIRD_PLACE,
                'round' => $rounds,
                'position' => 1,
                'troisieme_place' => true,
            ]);
        }
    }

    public const THIRD_PLACE = 'Match pour la 3e place';

    /** Ex. pour 3 tours : « Quarts de finale », « Demi-finales », « Finale ». */
    public static function roundName(int $round, int $rounds): string
    {
        return match ($rounds - $round) {
            0 => 'Finale',
            1 => 'Demi-finales',
            2 => 'Quarts de finale',
            3 => 'Huitièmes de finale',
            4 => 'Seizièmes de finale',
            default => 'Tour ' . $round,
        };
    }

    /**
     * Numéros d'appel dans la catégorie, tour par tour (les exempts n'en ont pas).
     * Numérotés par catégorie pour que tirer une autre catégorie ne change pas les
     * numéros d'un tableau déjà imprimé.
     */
    private function renumber(CompetitionCategory $category): void
    {
        $number = 0;
        // Le match pour la 3e place se dispute juste avant la finale.
        $category->combats()->get()->sortBy([['round', 'asc'], ['troisieme_place', 'desc'], ['position', 'asc']])->each(function (Combat $combat) use (&$number) {
            $combat->update(['numero' => $combat->exempt ? null : ++$number]);
        });
    }
}
