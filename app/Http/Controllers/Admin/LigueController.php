<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Controller;
use App\Models\Federation;
use App\Models\Ligue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LigueController extends Controller
{
    use AuthorizesScope;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:LIGUE_READ')->only(['index']);
        $this->middleware('permission:LIGUE_CREATE')->only(['create', 'store']);
        $this->middleware('permission:LIGUE_UPDATE')->only(['edit', 'update']);
        $this->middleware('permission:LIGUE_DELETE')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $ligues = Ligue::query()
            ->visibleTo($request->user())
            ->with('federation:id,nom')
            ->withCount('salles')
            ->when($request->filled('search'), fn ($q) => $q->where('nom', 'like', '%' . $request->query('search') . '%'))
            ->when($request->filled('federation'), fn ($q) => $q->where('federation_id', $request->query('federation')))
            ->orderBy('nom')
            ->paginate(15)
            ->withQueryString();

        return view('admin.ligues.index', [
            'ligues' => $ligues,
            'federations' => Federation::visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom']),
            'page_title' => __('messages.ligues.title'),
        ]);
    }

    public function create(): View
    {
        return view('admin.ligues.create', [
            'ligue' => new Ligue(['active' => true]),
            'federations' => Federation::visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom']),
            'page_title' => __('messages.ligues.add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Ligue::create($this->validateData($request));

        return redirect()->route('admin.settings', ['tab' => 'ligues'])
            ->with('success', __('messages.ligues.created'));
    }

    public function edit(Ligue $ligue): View
    {
        $this->guardScope($ligue);

        return view('admin.ligues.edit', [
            'ligue' => $ligue,
            'federations' => Federation::visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom']),
            'page_title' => __('messages.ligues.edit'),
        ]);
    }

    public function update(Request $request, Ligue $ligue): RedirectResponse
    {
        $this->guardScope($ligue);
        $ligue->update($this->validateData($request));

        return redirect()->route('admin.settings', ['tab' => 'ligues'])
            ->with('success', __('messages.ligues.updated'));
    }

    public function destroy(Ligue $ligue): RedirectResponse
    {
        $this->guardScope($ligue);

        if ($ligue->salles()->exists()) {
            return back()->with('error', __('messages.ligues.delete_blocked'));
        }

        $ligue->delete();

        return redirect()->route('admin.settings', ['tab' => 'ligues'])
            ->with('success', __('messages.ligues.deleted'));
    }

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:255'],
            'federation_id' => ['required', 'exists:federations,id'],
            'active' => ['nullable', 'boolean'],
        ]);

        $validated['active'] = $request->boolean('active');

        return $validated;
    }
}
