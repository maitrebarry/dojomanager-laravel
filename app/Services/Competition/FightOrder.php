<?php

namespace App\Services\Competition;

use App\Models\Combat;
use App\Models\Competition;
use Illuminate\Support\Collection;

/**
 * Ordre de passage de tous les combats d'une compétition, tour par tour : d'abord les
 * tours les plus éloignés de la finale dans toutes les catégories, puis les quarts, les
 * demi-finales, les matchs pour la 3e place et enfin toutes les finales. Dans un même
 * tour, on suit l'ordre des catégories (cadets → seniors, du plus léger au plus lourd).
 * Les athlètes ont ainsi du repos entre deux combats et les finales clôturent la journée.
 *
 * Avec plusieurs aires de combat, les combats sont répartis à tour de rôle (1, 2, 1, 2…).
 * L'ordre est recalculé à chaque affichage : il suit les tirages en cours.
 */
class FightOrder
{
    /**
     * @param Competition $competition catégories chargées avec leurs combats
     * @return Collection<int, array{order: int, aire: int, combat: Combat, category_id: int, stage: string}>
     */
    public static function for(Competition $competition, int $aires = 1): Collection
    {
        $aires = max(1, $aires);
        $categoryRank = $competition->categories->values()->mapWithKeys(fn ($c, $i) => [$c->id => $i]);

        $fights = collect();
        foreach ($competition->categories as $category) {
            $rounds = (int) $category->combats->reject(fn ($c) => $c->troisieme_place)->max('round');
            foreach ($category->combats as $combat) {
                if ($combat->exempt) {
                    continue;
                }
                // Distance à la finale : 0 = finale, 0,5 = match pour la 3e place (juste avant).
                $distance = $combat->troisieme_place ? 0.5 : $rounds - $combat->round;
                $fights->push([
                    'combat' => $combat,
                    'category_id' => $category->id,
                    'distance' => $distance,
                    'stage' => $combat->tour,
                    'rank' => [-$distance, $categoryRank[$category->id], $combat->position],
                ]);
            }
        }

        return $fights
            ->sortBy(fn ($f) => $f['rank'])
            ->values()
            ->map(fn ($f, $i) => [
                'order' => $i + 1,
                'aire' => $i % $aires + 1,
                'combat' => $f['combat'],
                'category_id' => $f['category_id'],
                'stage' => $f['stage'],
            ]);
    }
}
