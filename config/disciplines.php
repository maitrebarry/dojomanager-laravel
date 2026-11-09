<?php

/*
 * Profils des disciplines d'arts martiaux. Une fédération choisit sa discipline ; ses
 * compétitions et cartes en prennent le vocabulaire :
 *  - corners     : les deux coins d'un combat (1 = en haut du tableau, 2 = en bas)
 *  - color_grade : nom des grades de couleur (code interne KEUP dans la table grades)
 *  - presets     : catégories de poids proposées (groupe => âges + limites par sexe,
 *                  null = catégorie « + » sans limite). Vide : catégories à créer à la main.
 *  - bronze      : règle par défaut, modifiable par compétition (deux | match)
 *
 * Les limites de poids reprennent les règlements internationaux connus : elles restent
 * modifiables dans chaque compétition et sont à vérifier avec la fédération nationale.
 */
return [
    'taekwondo' => [
        'label' => 'Taekwondo',
        'corners' => [
            1 => ['name' => 'Bleu (Chung)', 'short' => 'Bleu', 'color' => '#1d4ed8'],
            2 => ['name' => 'Rouge (Hong)', 'short' => 'Rouge', 'color' => '#b91c1c'],
        ],
        'color_grade' => 'Keup',
        'bronze' => 'deux',
        'score_example' => '2-1',
        'presets' => [
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
        ],
    ],

    'karate' => [
        'label' => 'Karaté (WKF)',
        'corners' => [
            1 => ['name' => 'Rouge (Aka)', 'short' => 'Aka', 'color' => '#b91c1c'],
            2 => ['name' => 'Bleu (Ao)', 'short' => 'Ao', 'color' => '#1d4ed8'],
        ],
        'color_grade' => 'Kyu',
        'bronze' => 'deux',
        'score_example' => '5-3',
        'presets' => [
            'Cadets' => [
                'age_min' => 14, 'age_max' => 15,
                'M' => [52, 57, 63, 70, null],
                'F' => [47, 54, null],
            ],
            'Juniors' => [
                'age_min' => 16, 'age_max' => 17,
                'M' => [55, 61, 68, 76, null],
                'F' => [48, 53, 59, 66, null],
            ],
            'Seniors' => [
                'age_min' => 18, 'age_max' => null,
                'M' => [60, 67, 75, 84, null],
                'F' => [50, 55, 61, 68, null],
            ],
        ],
    ],

    'kyokushin' => [
        'label' => 'Karaté Kyokushin',
        'corners' => [
            1 => ['name' => 'Rouge (Aka)', 'short' => 'Aka', 'color' => '#b91c1c'],
            2 => ['name' => 'Blanc (Shiro)', 'short' => 'Shiro', 'color' => '#64748b'],
        ],
        'color_grade' => 'Kyu',
        'bronze' => 'match',
        'score_example' => 'Ippon',
        'presets' => [],
    ],

    'judo' => [
        'label' => 'Judo',
        'corners' => [
            1 => ['name' => 'Blanc', 'short' => 'Blanc', 'color' => '#64748b'],
            2 => ['name' => 'Bleu', 'short' => 'Bleu', 'color' => '#1d4ed8'],
        ],
        'color_grade' => 'Kyu',
        'bronze' => 'deux',
        'score_example' => 'Ippon',
        'presets' => [
            'Cadets' => [
                'age_min' => 15, 'age_max' => 17,
                'M' => [50, 55, 60, 66, 73, 81, 90, null],
                'F' => [40, 44, 48, 52, 57, 63, 70, null],
            ],
            'Seniors' => [
                'age_min' => 18, 'age_max' => null,
                'M' => [60, 66, 73, 81, 90, 100, null],
                'F' => [48, 52, 57, 63, 70, 78, null],
            ],
        ],
    ],

    'autre' => [
        'label' => 'Autre discipline',
        'corners' => [
            1 => ['name' => 'Bleu', 'short' => 'Bleu', 'color' => '#1d4ed8'],
            2 => ['name' => 'Rouge', 'short' => 'Rouge', 'color' => '#b91c1c'],
        ],
        'color_grade' => 'Kyu',
        'bronze' => 'deux',
        'score_example' => '2-1',
        'presets' => [],
    ],
];
