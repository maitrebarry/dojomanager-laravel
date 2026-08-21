<?php

namespace App\Http\Controllers\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Génère un PDF au format "reçu thermique" (largeur fixe d'imprimante à
 * ticket, hauteur ajustée au contenu réel) — même principe que le module
 * billetterie (Projets_licence/app/core/Controller.php::renderThermalDompdf) :
 * on cherche par dichotomie la plus petite hauteur qui tient le contenu sur
 * une seule page, pour ne pas gâcher de papier à l'impression sur un rouleau
 * 80mm.
 */
trait GeneratesThermalPdf
{
    private function renderThermalPdf(string $view, array $data, float $widthMm = 80.0)
    {
        $ptPerMm = 72 / 25.4;
        $widthPt = $widthMm * $ptPerMm;

        $render = function (float $heightPt) use ($view, $data, $widthPt) {
            $pdf = Pdf::loadView($view, $data);
            $pdf->setPaper([0, 0, $widthPt, $heightPt], 'portrait');
            $pdf->render();

            return $pdf;
        };

        $lo = 40 * $ptPerMm;
        $hi = 400 * $ptPerMm;

        for ($i = 0; $i < 8; $i++) {
            $mid = ($lo + $hi) / 2;
            $pageCount = $render($mid)->getDomPDF()->getCanvas()->get_page_count();
            if ($pageCount > 1) {
                $lo = $mid;
            } else {
                $hi = $mid;
            }
        }

        // petite marge de sécurité pour éviter de rogner la dernière ligne
        return $render($hi + (4 * $ptPerMm));
    }

    protected function streamThermalPdf(string $view, array $data, string $filename, float $widthMm = 80.0)
    {
        return $this->renderThermalPdf($view, $data, $widthMm)->stream($filename);
    }

    protected function downloadThermalPdf(string $view, array $data, string $filename, float $widthMm = 80.0)
    {
        return $this->renderThermalPdf($view, $data, $widthMm)->download($filename);
    }
}
