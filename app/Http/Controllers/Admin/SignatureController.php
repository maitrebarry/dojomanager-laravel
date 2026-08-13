<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Controller;
use App\Models\Federation;
use App\Models\Ligue;
use App\Models\Salle;
use App\Models\Signature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SignatureController extends Controller
{
    use AuthorizesScope;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:PARAMETRES_READ,PARAMETRES_MANAGE')->only(['index']);
        $this->middleware('permission:PARAMETRES_MANAGE')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(): View
    {
        return view('admin.signatures.index', [
            'signatures' => Signature::visibleTo(request()->user())->orderBy('master_name')->paginate(15),
            'page_title' => __('messages.signatures.title'),
        ]);
    }

    public function create(): View
    {
        return view('admin.signatures.create', array_merge($this->formData(), [
            'signature' => new Signature(),
            'page_title' => __('messages.signatures.add'),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        Signature::create($this->prepareData($request));

        return redirect()->route('admin.signatures.index')
            ->with('success', __('messages.signatures.created'));
    }

    public function edit(Signature $signature): View
    {
        $this->guardScope($signature);

        return view('admin.signatures.edit', array_merge($this->formData(), [
            'signature' => $signature,
            'page_title' => __('messages.signatures.edit'),
        ]));
    }

    public function update(Request $request, Signature $signature): RedirectResponse
    {
        $this->guardScope($signature);
        $signature->update($this->prepareData($request, $signature));

        return redirect()->route('admin.signatures.index')
            ->with('success', __('messages.signatures.updated'));
    }

    public function destroy(Signature $signature): RedirectResponse
    {
        $this->guardScope($signature);
        $signature->delete();

        return redirect()->route('admin.signatures.index')
            ->with('success', __('messages.signatures.deleted'));
    }

    private function formData(): array
    {
        $user = request()->user();

        return [
            'federations' => Federation::visibleTo($user)->orderBy('nom')->get(['id', 'nom']),
            'ligues' => Ligue::visibleTo($user)->orderBy('nom')->get(['id', 'nom']),
            'salles' => Salle::visibleTo($user)->orderBy('nom')->get(['id', 'nom']),
        ];
    }

    private function prepareData(Request $request, ?Signature $signature = null): array
    {
        $validated = $request->validate([
            'master_name' => ['required', 'string', 'max:200'],
            'master_grade' => ['nullable', 'string', 'max:80'],
            'role' => ['nullable', 'string', 'max:40'],
            'federation_id' => ['nullable', 'exists:federations,id'],
            'ligue_id' => ['nullable', 'exists:ligues,id'],
            'salle_id' => ['nullable', 'exists:salles,id'],
            'signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data = collect($validated)->except('signature')->all();

        if ($request->hasFile('signature')) {
            $file = $request->file('signature');
            $data['signature_data'] = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        } elseif ($signature) {
            $data['signature_data'] = $signature->signature_data;
        }

        return $data;
    }
}
