<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Combat;
use App\Models\Competition;
use App\Models\Disciple;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:COMPETITION_MANAGE')->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
        $this->middleware('permission:COMPETITION_MANAGE,COMBAT_MANAGE')->only(['addCombat', 'removeCombat']);
    }

    public function index(Request $request): View
    {
        $competitions = Competition::query()
            ->withCount('combats')
            ->when($request->filled('search'), fn ($q) => $q->where('nom', 'like', '%' . $request->query('search') . '%'))
            ->orderByDesc('date_competition')
            ->paginate(15)
            ->withQueryString();

        return view('admin.competitions.index', [
            'competitions' => $competitions,
            'page_title' => __('messages.competitions.title'),
        ]);
    }

    public function create(): View
    {
        return view('admin.competitions.create', [
            'competition' => new Competition(),
            'page_title' => __('messages.competitions.add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Competition::create($this->validateData($request));

        return redirect()->route('admin.competitions.index')
            ->with('success', __('messages.competitions.created'));
    }

    public function show(Competition $competition): View
    {
        $competition->load(['combats.combattant1:id,nom,prenom,nom_complet', 'combats.combattant2:id,nom,prenom,nom_complet', 'combats.vainqueur:id,nom,prenom,nom_complet']);

        return view('admin.competitions.show', [
            'competition' => $competition,
            'disciples' => Disciple::active()->visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom', 'prenom', 'nom_complet']),
            'page_title' => $competition->nom,
        ]);
    }

    public function edit(Competition $competition): View
    {
        return view('admin.competitions.edit', [
            'competition' => $competition,
            'page_title' => __('messages.competitions.edit'),
        ]);
    }

    public function update(Request $request, Competition $competition): RedirectResponse
    {
        $competition->update($this->validateData($request));

        return redirect()->route('admin.competitions.index')
            ->with('success', __('messages.competitions.updated'));
    }

    public function destroy(Competition $competition): RedirectResponse
    {
        $competition->delete();

        return redirect()->route('admin.competitions.index')
            ->with('success', __('messages.competitions.deleted'));
    }

    public function addCombat(Request $request, Competition $competition): RedirectResponse
    {
        $validated = $request->validate([
            'tour' => ['required', 'string', 'max:50'],
            'combattant1_id' => ['nullable', 'exists:disciples,id'],
            'combattant2_id' => ['nullable', 'exists:disciples,id'],
            'vainqueur_id' => ['nullable', 'exists:disciples,id'],
        ]);

        $competition->combats()->create($validated);

        return back()->with('success', __('messages.competitions.combat_added'));
    }

    public function removeCombat(Competition $competition, Combat $combat): RedirectResponse
    {
        $combat->delete();

        return back()->with('success', __('messages.competitions.combat_removed'));
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'date_competition' => ['nullable', 'date'],
            'lieu' => ['nullable', 'string', 'max:180'],
            'type' => ['nullable', 'string', 'max:80'],
        ]);
    }
}
