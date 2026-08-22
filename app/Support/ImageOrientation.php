<?php

namespace App\Support;

/**
 * Corrige la rotation EXIF d'une photo JPEG uploadée (fréquent avec les photos prises
 * au téléphone : les pixels restent dans l'orientation du capteur, la rotation réelle
 * n'est indiquée que par une métadonnée EXIF). Certains rendus (DomPDF notamment)
 * ignorent cette métadonnée et affichent l'image de travers — on corrige donc une
 * bonne fois pour toutes les pixels eux-mêmes à l'upload, peu importe où la photo est
 * affichée ensuite.
 */
class ImageOrientation
{
    public static function fix(string $absolutePath): void
    {
        if (!function_exists('exif_read_data') || !function_exists('imagerotate')) {
            return;
        }

        if (!in_array(strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION)), ['jpg', 'jpeg'], true)) {
            return;
        }

        try {
            $exif = @exif_read_data($absolutePath);
            $orientation = $exif['Orientation'] ?? 1;

            if (!in_array($orientation, [3, 6, 8], true)) {
                return;
            }

            $image = @imagecreatefromjpeg($absolutePath);
            if (!$image) {
                return;
            }

            $angle = match ($orientation) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };

            $rotated = imagerotate($image, $angle, 0);
            imagejpeg($rotated, $absolutePath, 90);
            imagedestroy($image);
            imagedestroy($rotated);
        } catch (\Throwable) {
            // Une photo mal formée ne doit jamais empêcher l'enregistrement du disciple.
        }
    }
}
