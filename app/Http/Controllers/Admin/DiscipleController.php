<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\GeneratesThermalPdf;
use App\Http\Controllers\Controller;
use App\Http\Requests\DiscipleRequest;
use App\Models\Disciple;
use App\Models\Grade;
use App\Models\Salle;
use App\Models\Signature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DiscipleController extends Controller
{
    use GeneratesThermalPdf;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('role:maitre'); // App.jsx : /disciples réservé au MAITRE
        $this->middleware('permission:DISCIPLE_READ')->only(['index', 'show', 'receipt', 'receiptPdf']);
        $this->middleware('permission:DISCIPLE_CREATE')->only(['create', 'store']);
        $this->middleware('permission:DISCIPLE_UPDATE')->only(['edit', 'update', 'archive', 'restore']);
        $this->middleware('permission:DISCIPLE_DELETE')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        // Case « Afficher les archivés » (showArchived), fidèle à Disciples.jsx.
        $showArchived = $request->boolean('archived');
        $search = trim((string) $request->query('search'));

        $disciples = Disciple::query()
            ->visibleTo($request->user())
            ->with(['salle:id,nom', 'grade:id,nom_grade,ceinture'])
            ->when(!$showArchived, fn ($q) => $q->active())
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('nom_complet', 'like', "%{$search}%")
                        ->orWhere('nmle', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%");
                });
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return view('admin.disciples.index', [
            'disciples' => $disciples,
            'salles' => Salle::visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom']),
            'grades' => Grade::visibleTo(request()->user())->orderBy('niveau')->get(['id', 'nom_grade', 'ceinture']),
            'showArchived' => $showArchived,
            'search' => $search,
            'page_title' => __('messages.disciples.title'),
        ]);
    }

    public function create(): View
    {
        return view('admin.disciples.create', [
            'disciple' => new Disciple(['date_inscription' => now()->toDateString()]),
            'salles' => Salle::visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom']),
            'grades' => Grade::visibleTo(request()->user())->orderBy('niveau')->get(['id', 'nom_grade', 'ceinture']),
            'page_title' => __('messages.disciples.add'),
        ]);
    }

    public function store(DiscipleRequest $request): RedirectResponse
    {
        $data = $this->prepareData($request);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('disciples', 'public');
        }

        $disciple = Disciple::create($data);

        // Redirige directement vers le reçu d'inscription (imprimable / envoyable par
        // WhatsApp) plutôt que la liste, pour le remettre tout de suite au disciple.
        // ?auto_whatsapp=1 déclenche l'envoi automatique du reçu dès l'affichage de la
        // page (cf. WhatsappBridge.autoSendIfRequested dans disciples/receipt.blade.php).
        return redirect()->route('admin.disciples.receipt', ['disciple' => $disciple, 'auto_whatsapp' => 1])
            ->with('success', __('messages.disciples.created'));
    }

    public function show(Disciple $disciple): View
    {
        $this->authorizeScope($disciple);
        $disciple->load(['salle.ligue', 'grade']);

        return view('admin.disciples.show', [
            'disciple' => $disciple,
            'page_title' => $disciple->full_name,
        ]);
    }

    public function edit(Disciple $disciple): View
    {
        $this->authorizeScope($disciple);

        return view('admin.disciples.edit', [
            'disciple' => $disciple,
            'salles' => Salle::visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom']),
            'grades' => Grade::visibleTo(request()->user())->orderBy('niveau')->get(['id', 'nom_grade', 'ceinture']),
            'page_title' => __('messages.disciples.edit'),
        ]);
    }

    public function update(DiscipleRequest $request, Disciple $disciple): RedirectResponse
    {
        $this->authorizeScope($disciple);
        $data = $this->prepareData($request);

        if ($request->hasFile('photo')) {
            if ($disciple->photo && Storage::disk('public')->exists($disciple->photo)) {
                Storage::disk('public')->delete($disciple->photo);
            }
            $data['photo'] = $request->file('photo')->store('disciples', 'public');
        }

        $disciple->update($data);

        return redirect()->route('admin.disciples.index')
            ->with('success', __('messages.disciples.updated'));
    }

    public function destroy(Disciple $disciple): RedirectResponse
    {
        $this->authorizeScope($disciple);

        if ($disciple->photo && Storage::disk('public')->exists($disciple->photo)) {
            Storage::disk('public')->delete($disciple->photo);
        }

        $disciple->delete();

        return redirect()->route('admin.disciples.index')
            ->with('success', __('messages.disciples.deleted'));
    }

    public function archive(Disciple $disciple): RedirectResponse
    {
        $this->authorizeScope($disciple);
        $disciple->update(['archived_at' => now()]);

        return back()->with('success', __('messages.disciples.archived_success'));
    }

    public function restore(Disciple $disciple): RedirectResponse
    {
        $this->authorizeScope($disciple);
        $disciple->update(['archived_at' => null]);

        return back()->with('success', __('messages.disciples.restored_success'));
    }

    public function receipt(Disciple $disciple): View
    {
        $this->authorizeScope($disciple);
        $disciple->load('salle.maitre', 'salle.maitreUser.grade', 'grade');

        return view('admin.disciples.receipt', [
            'disciple' => $disciple,
            'signature' => Signature::forSalle($disciple->salle_id),
        ]);
    }

    public function receiptPdf(Disciple $disciple)
    {
        $this->authorizeScope($disciple);
        $disciple->load('salle.maitre', 'salle.maitreUser.grade', 'grade');

        return $this->streamThermalPdf(
            'admin.disciples.receipt_pdf',
            ['disciple' => $disciple, 'signature' => Signature::forSalle($disciple->salle_id)],
            'recu-inscription-' . $disciple->id . '.pdf'
        );
    }

    private function prepareData(DiscipleRequest $request): array
    {
        $data = $request->safe()->except(['photo']);
        $data['nom_complet'] = trim(($data['prenom'] ?? '') . ' ' . ($data['nom'] ?? ''));

        return $data;
    }

    /** 403 si le disciple est hors du périmètre de l'utilisateur. */
    private function authorizeScope(Disciple $disciple): void
    {
        abort_unless(
            Disciple::visibleTo(request()->user())->whereKey($disciple->getKey())->exists(),
            403,
            __('messages.out_of_scope')
        );
    }
}
