<?php

namespace App\Support;

use App\Models\Competition;
use App\Models\Federation;

/**
 * Profil de la discipline d'une fédération (config/disciplines.php) : vocabulaire des
 * coins, des grades, catégories de poids proposées et règle du bronze. Sans fédération
 * ou discipline inconnue : taekwondo, discipline d'origine de l'application.
 */
class Discipline
{
    public const DEFAULT = 'taekwondo';

    /** @return array<string, string> clé => libellé, pour les listes de choix */
    public static function options(): array
    {
        return collect(config('disciplines'))->map(fn ($p) => $p['label'])->all();
    }

    public static function profile(?string $key): array
    {
        $profiles = config('disciplines');
        $key = isset($profiles[$key]) ? $key : self::DEFAULT;

        return $profiles[$key] + ['key' => $key];
    }

    public static function forFederation(?Federation $federation): array
    {
        return self::profile($federation?->discipline);
    }

    public static function forCompetition(Competition $competition): array
    {
        return self::forFederation($competition->federation);
    }

    /**
     * Libellé d'un type de grade. Le code interne KEUP désigne les grades de couleur :
     * « KEUP » en taekwondo, « KYU » en karaté ou judo… DAN est commun à tous.
     * Sans fédération précisée, celle de l'utilisateur connecté ; sans fédération du tout
     * (superadmin), les deux noms.
     */
    public static function gradeType(?string $type, ?int $federationId = null): string
    {
        if ($type !== 'KEUP') {
            return (string) ($type ?? '-');
        }

        $federationId ??= auth()->user()?->scopeContext()['federation_id'];
        if (!$federationId) {
            return 'KEUP / KYU';
        }

        static $disciplines = [];
        $disciplines[$federationId] ??= Federation::whereKey($federationId)->value('discipline');

        return mb_strtoupper(self::profile($disciplines[$federationId])['color_grade']);
    }

    /**
     * Lignes de catégories à créer pour les groupes cochés (ex. ['Seniors|M']).
     *
     * @return array<int, array{groupe: string, sexe: string, age_min: ?int, age_max: ?int, poids_max: ?float}>
     */
    public static function presetRows(array $profile, array $groupKeys): array
    {
        $rows = [];
        foreach ($profile['presets'] as $groupe => $preset) {
            foreach (['M', 'F'] as $sexe) {
                if (!in_array($groupe . '|' . $sexe, $groupKeys, true) || empty($preset[$sexe])) {
                    continue;
                }
                foreach ($preset[$sexe] as $max) {
                    $rows[] = ['groupe' => $groupe, 'sexe' => $sexe, 'age_min' => $preset['age_min'], 'age_max' => $preset['age_max'], 'poids_max' => $max];
                }
            }
        }

        return $rows;
    }
}
