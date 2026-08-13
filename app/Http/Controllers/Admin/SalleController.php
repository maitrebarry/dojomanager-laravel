<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Controller;
use App\Models\Ligue;
use App\Models\Maitre;
use App\Models\Salle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalleController extends Controller
{
    use AuthorizesScope;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:SALLE_READ')->only(['index']);
        $this->middleware('permission:SALLE_CREATE')->only(['create', 'store']);
        $this->middleware('permission:SALLE_UPDATE')->only(['edit', 'update']);
        $this->middleware('permission:SALLE_DELETE')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $salles = Salle::query()
            ->visibleTo($request->user())
            ->with(['ligue:id,nom', 'maitre:id,nom_complet'])
            ->withCount('disciples')
            ->when($request->filled('search'), fn ($q) => $q->where('nom', 'like', '%' . $request->query('search') . '%'))
            ->when($request->filled('ligue'), fn ($q) => $q->where('ligue_id', $request->query('ligue')))
            ->orderBy('nom')
            ->paginate(15)
            ->withQueryString();

        return view('admin.salles.index', [
            'salles' => $salles,
            'ligues' => Ligue::visibleTo($request->user())->orderBy('nom')->get(['id', 'nom']),
            'page_title' => __('messages.salles.title'),
        ]);
    }

    public function create(): View
    {
        return view('admin.salles.create', [
            'salle' => new Salle(['active' => true]),
            'ligues' => Ligue::visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom']),
            'maitres' => Maitre::visibleTo(request()->user())->orderBy('nom_complet')->get(['id', 'nom_complet']),
            'page_title' => __('messages.salles.add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Salle::create($this->validateData($request));

        return redirect()->route('admin.settings', ['tab' => 'salles'])
            ->with('success', __('messages.salles.created'));
    }

    public function edit(Salle $salle): View
    {
        $this->guardScope($salle);

        return view('admin.salles.edit', [
            'salle' => $salle,
            'ligues' => Ligue::visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom']),
            'maitres' => Maitre::visibleTo(request()->user())->orderBy('nom_complet')->get(['id', 'nom_complet']),
            'page_title' => __('messages.salles.edit'),
        ]);
    }

    public function update(Request $request, Salle $salle): RedirectResponse
    {
        $this->guardScope($salle);
        $salle->update($this->validateData($request));

        return redirect()->route('admin.settings', ['tab' => 'salles'])
            ->with('success', __('messages.salles.updated'));
    }

    public function destroy(Salle $salle): RedirectResponse
    {
        $this->guardScope($salle);

        if ($salle->disciples()->exists()) {
            return back()->with('error', __('messages.salles.delete_blocked'));
        }

        $salle->delete();

        return redirect()->route('admin.settings', ['tab' => 'salles'])
            ->with('success', __('messages.salles.deleted'));
    }

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:140'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:40'],
            'mensualite' => ['nullable', 'numeric', 'min:0'],
            'ligue_id' => ['required', 'exists:ligues,id'],
            'maitre_id' => ['nullable', 'exists:maitres,id'],
            'active' => ['nullable', 'boolean'],
        ]);

        $validated['active'] = $request->boolean('active');

        return $validated;
    }
}
