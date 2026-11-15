<?php

namespace App\Support;

/** Durées saisies comme sur une feuille d'arbitrage : « 1:30 », « 1 » (minutes) ou « 90 » (secondes). */
class Duration
{
    /** En secondes, ou null si vide ou illisible. */
    public static function parse(?string $value): ?int
    {
        $value = str_replace(['min', 'mn', ' '], ['', '', ''], trim((string) $value));
        if ($value === '') {
            return null;
        }
        if (preg_match('/^(\d{1,2})[:.,](\d{1,2})$/', $value, $m)) {
            return (int) $m[1] * 60 + (int) $m[2];
        }
        if (ctype_digit($value)) {
            // Jusqu'à 10 : des minutes (« 2 » = 2 min) ; au-delà : des secondes (« 90 »).
            return (int) $value <= 10 ? (int) $value * 60 : (int) $value;
        }

        return null;
    }

    /** « 1:30 », « 2:00 ». */
    public static function format(?int $seconds): string
    {
        if ($seconds === null) {
            return '';
        }

        return intdiv($seconds, 60) . ':' . str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);
    }
}
