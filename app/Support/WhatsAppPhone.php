<?php

namespace App\Support;

/**
 * Normalise un numéro de téléphone saisi librement (espaces, "00", indicatif
 * omis…) vers le format attendu par la passerelle WhatsApp (indicatif +
 * numéro, chiffres uniquement). Les numéros saisis localement (8 chiffres,
 * convention malienne) reçoivent l'indicatif par défaut de config('services.
 * whatsapp_bridge.default_country_code').
 */
class WhatsAppPhone
{
    public static function normalize(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        // "00223…" est l'écriture internationale d'un "+223…" au clavier local.
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $defaultCode = (string) config('services.whatsapp_bridge.default_country_code', '');

        // Un numéro local malien tient sur 8 chiffres ; en dessous de 11 chiffres,
        // on considère qu'il manque l'indicatif et on préfixe celui par défaut.
        if ($defaultCode !== '' && strlen($digits) <= 9 && !str_starts_with($digits, $defaultCode)) {
            $digits = $defaultCode . $digits;
        }

        return $digits;
    }
}
