<?php

namespace App\Support;

/**
 * Catégories de combat proposées par défaut (World Taekwondo). Elles sont copiées
 * dans chaque compétition, où la ligue peut les modifier ou en ajouter (poussins,
 * benjamins, minimes… selon le règlement de la fédération).
 *
 * Âges : année de la compétition − année de naissance. Les juniors WT vont jusqu'à
 * 17 ans ; les seniors commencent ici à 18 ans pour qu'un athlète ne tombe que dans
 * un seul groupe (un junior de 17 ans peut être déplacé à la main en seniors).
 */
class WeightCategories
{
    public const PRESETS = [
        'Cadets' => [
            'age_min' => 12, 'age_max' => 14,
            'M' => [33, 37, 41, 45, 49, 53, 57, 61, 65, null],
            'F' => [29, 33, 37, 41, 44, 47, 51, 55, 59, null],
        ],
        'Juniors' => [
            'age_min' => 15, 'age_max' => 17,
            'M' => [45, 48, 51, 55, 59, 63, 68, 73, 78, null],
            'F' => [42, 44, 46, 49, 52, 55, 59, 63, 68, null],
        ],
        'Seniors' => [
            'age_min' => 18, 'age_max' => null,
            'M' => [54, 58, 63, 68, 74, 80, 87, null],
            'F' => [46, 49, 53, 57, 62, 67, 73, null],
        ],
    ];

    /**
     * Lignes à créer pour les groupes choisis, ex. ['Seniors|M', 'Juniors|F'].
     *
     * @return array<int, array{groupe: string, sexe: string, age_min: ?int, age_max: ?int, poids_max: ?float}>
     */
    public static function rowsFor(array $groupKeys): array
    {
        $rows = [];
        foreach (self::PRESETS as $groupe => $preset) {
            foreach (['M', 'F'] as $sexe) {
                if (!in_array($groupe . '|' . $sexe, $groupKeys, true)) {
                    continue;
                }
                foreach ($preset[$sexe] as $max) {
                    $rows[] = [
                        'groupe' => $groupe,
                        'sexe' => $sexe,
                        'age_min' => $preset['age_min'],
                        'age_max' => $preset['age_max'],
                        'poids_max' => $max,
                    ];
                }
            }
        }

        return $rows;
    }
}
