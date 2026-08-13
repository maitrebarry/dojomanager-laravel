<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Controller;
use App\Models\Maitre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaitreController extends Controller
{
    use AuthorizesScope;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:MAITRE_READ,MAITRE_MANAGE')->only(['index']);
        $this->middleware('permission:MAITRE_CREATE,MAITRE_MANAGE')->only(['create', 'store']);
        $this->middleware('permission:MAITRE_UPDATE,MAITRE_MANAGE')->only(['edit', 'update']);
        $this->middleware('permission:MAITRE_DELETE,MAITRE_MANAGE')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $maitres = Maitre::query()
            ->visibleTo($request->user())
            ->withCount('salles')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->query('search');
                $q->where('nom_complet', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            })
            ->orderBy('nom_complet')
            ->paginate(15)
            ->withQueryString();

        return view('admin.maitres.index', [
            'maitres' => $maitres,
            'page_title' => __('messages.maitres.title'),
        ]);
    }

    public function create(): View
    {
        return view('admin.maitres.create', [
            'maitre' => new Maitre(),
            'page_title' => __('messages.maitres.add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Maitre::create($this->validateData($request));

        return redirect()->route('admin.maitres.index')
            ->with('success', __('messages.maitres.created'));
    }

    public function edit(Maitre $maitre): View
    {
        $this->guardScope($maitre);

        return view('admin.maitres.edit', [
            'maitre' => $maitre,
            'page_title' => __('messages.maitres.edit'),
        ]);
    }

    public function update(Request $request, Maitre $maitre): RedirectResponse
    {
        $this->guardScope($maitre);
        $maitre->update($this->validateData($request));

        return redirect()->route('admin.maitres.index')
            ->with('success', __('messages.maitres.updated'));
    }

    public function destroy(Maitre $maitre): RedirectResponse
    {
        $this->guardScope($maitre);

        if ($maitre->salles()->exists()) {
            return back()->with('error', __('messages.maitres.delete_blocked'));
        }

        $maitre->delete();

        return redirect()->route('admin.maitres.index')
            ->with('success', __('messages.maitres.deleted'));
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'nom_complet' => ['required', 'string', 'max:120'],
            'telephone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'grade' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
