<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Controller;
use App\Models\Federation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FederationController extends Controller
{
    use AuthorizesScope;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:FÉDÉRATION_READ')->only(['index']);
        $this->middleware('permission:FÉDÉRATION_CREATE')->only(['create', 'store']);
        $this->middleware('permission:FÉDÉRATION_UPDATE')->only(['edit', 'update']);
        $this->middleware('permission:FÉDÉRATION_DELETE')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $federations = Federation::query()
            ->visibleTo($request->user())
            ->withCount(['ligues', 'grades'])
            ->when($request->filled('search'), fn ($q) => $q->where('nom', 'like', '%' . $request->query('search') . '%'))
            ->orderBy('nom')
            ->paginate(15)
            ->withQueryString();

        return view('admin.federations.index', [
            'federations' => $federations,
            'page_title' => __('messages.federations.title'),
        ]);
    }

    public function create(): View
    {
        return view('admin.federations.create', [
            'federation' => new Federation(),
            'page_title' => __('messages.federations.add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Federation::create($this->validateData($request));

        return redirect()->route('admin.settings', ['tab' => 'federations'])
            ->with('success', __('messages.federations.created'));
    }

    public function edit(Federation $federation): View
    {
        $this->guardScope($federation);

        return view('admin.federations.edit', [
            'federation' => $federation,
            'page_title' => __('messages.federations.edit'),
        ]);
    }

    public function update(Request $request, Federation $federation): RedirectResponse
    {
        $this->guardScope($federation);
        $federation->update($this->validateData($request, $federation));

        return redirect()->route('admin.settings', ['tab' => 'federations'])
            ->with('success', __('messages.federations.updated'));
    }

    public function destroy(Federation $federation): RedirectResponse
    {
        $this->guardScope($federation);

        if ($federation->ligues()->exists() || $federation->grades()->exists()) {
            return back()->with('error', __('messages.federations.delete_blocked'));
        }

        $federation->delete();

        return redirect()->route('admin.settings', ['tab' => 'federations'])
            ->with('success', __('messages.federations.deleted'));
    }

    private function validateData(Request $request, ?Federation $federation = null): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:120', 'unique:federations,nom' . ($federation ? ',' . $federation->id : '')],
            'sigle' => ['nullable', 'string', 'max:20'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
        ]);
    }
}
