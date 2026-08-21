<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Manuel d'utilisation intégré à l'application (copie fidèle, mise à jour pour les
 * modules propres à cette version, de dojo-frontend/src/pages/Documentation.jsx).
 * Volontairement sans permission dédiée : visible par tout utilisateur connecté,
 * quel que soit son rôle ou ses permissions.
 */
class DocumentationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function index(): View
    {
        return view('admin.documentation', ['page_title' => __('messages.documentation.title')]);
    }

    public function pdf(): Response
    {
        return Pdf::loadView('admin.documentation_pdf')
            ->setPaper('a4', 'portrait')
            ->download('documentation-dojomanager.pdf');
    }
}
