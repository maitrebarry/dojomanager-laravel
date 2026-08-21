<?php

namespace App\Support;

use App\Models\CeintureNoireManuelle;
use App\Models\Ligue;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Génère le matricule (numéro d'ordre) d'une ceinture noire : préfixe dérivé
 * de la ligue ("SEGOU" → SEG-0001) suivi d'un compteur séquentiel propre à
 * cette ligue, partagé entre les saisies manuelles (CeintureNoireManuelle)
 * et les comptes maître/responsable (User) pour éviter toute collision.
 */
class MatriculeGenerator
{
    public static function nextForLigue(?Ligue $ligue): string
    {
        $prefix = self::prefixFor($ligue);

        $max = 0;
        foreach (self::sequencesFor($prefix) as $sequence) {
            $max = max($max, $sequence);
        }

        return $prefix . '-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    private static function sequencesFor(string $prefix): array
    {
        $sequences = [];

        foreach ([
            CeintureNoireManuelle::query()->where('nmle', 'like', $prefix . '-%')->pluck('nmle'),
            User::query()->where('matricule', 'like', $prefix . '-%')->pluck('matricule'),
        ] as $values) {
            foreach ($values as $value) {
                if (preg_match('/-(\d+)$/', (string) $value, $m)) {
                    $sequences[] = (int) $m[1];
                }
            }
        }

        return $sequences;
    }

    private static function prefixFor(?Ligue $ligue): string
    {
        $source = $ligue?->region ?: $ligue?->nom ?: 'CN';
        $letters = Str::of($source)->ascii()->upper()->replaceMatches('/[^A-Z]/', '')->value();

        return $letters !== '' ? substr($letters, 0, 3) : 'CN';
    }
}
