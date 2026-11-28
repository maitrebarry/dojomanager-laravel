<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ligue;
use App\Models\Salle;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vue globale de la filiation des salles, pour la ligue (ses salles) et la fédération
 * (toutes les ligues) : chaque salle mère avec ses salles affiliées, maîtres et effectifs.
 */
class SalleArbreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('role:federation,ligue');
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $ligues = Ligue::visibleTo($user)->orderBy('nom')->get(['id', 'nom']);
        $ligueId = $request->integer('ligue') ?: null;

        $salles = Salle::visibleTo($user)
            ->when($ligueId, fn ($q, $id) => $q->where('ligue_id', $id))
            ->with(['ligue:id,nom', 'maitre', 'maitreUser', 'salleMere:id,nom'])
            ->withCount(['disciples' => fn ($q) => $q->whereNull('archived_at'), 'sallesDerivees'])
            ->orderBy('nom')->get();

        $groups = $salles->groupBy('ligue_id')->map(fn ($list) => Salle::tree($list))
            ->sortBy(fn ($list) => $list->first()?->ligue?->nom);

        return view('admin.salles-arbre.index', [
            'groups' => $groups,
            'ligues' => $ligues,
            'ligueId' => $ligueId,
            'stats' => [
                'salles' => $salles->count(),
                'meres' => $salles->where('salles_derivees_count', '>', 0)->count(),
                'affiliees' => $salles->whereNotNull('salle_mere_id')->count(),
                'disciples' => $salles->sum('disciples_count'),
            ],
            'page_title' => __('messages.salles_arbre.title'),
        ]);
    }
}
