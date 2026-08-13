<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Federation;
use App\Models\Grade;
use App\Models\GradePassageTariff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GradePassageTariffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:PASSAGEGRADES_READ')->only(['index']);
        $this->middleware('permission:PASSAGEGRADES_MANAGE')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(): View
    {
        return view('admin.grade-passage-tariffs.index', [
            'tariffs' => GradePassageTariff::visibleTo(request()->user())->with('grade:id,nom_grade')->orderBy('type_grade')->orderByDesc('active')->paginate(20),
            'page_title' => __('messages.grade_passage_tariffs.title'),
        ]);
    }

    public function create(): View
    {
        return view('admin.grade-passage-tariffs.create', array_merge($this->formData(), [
            'tariff' => new GradePassageTariff(['type_grade' => 'KEUP', 'active' => true]),
            'page_title' => __('messages.grade_passage_tariffs.add'),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->applyScope($request, $this->validateData($request));

        GradePassageTariff::create($data + ['created_by_user_id' => auth()->id()]);

        return back()->with('success', __('messages.grade_passage_tariffs.created'));
    }

    /**
     * Ajout groupé de tarifs depuis le panier de la vue Configuration
     * (DAN par grade → fédération ; KEUP par ceinture → ligue), fidèle à handleSaveTariff.
     */
    public function storeBatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type_grade' => ['required', Rule::in(['KEUP', 'DAN'])],
            'federation_id' => ['nullable', 'exists:federations,id'],
            'ligue_id' => ['nullable', 'exists:ligues,id'],
            'tariffs' => ['required', 'array', 'min:1'],
            'tariffs.*.tarif_label' => ['required', 'string', 'max:140'],
            'tariffs.*.montant' => ['required', 'numeric', 'min:0'],
            'tariffs.*.grade_id' => ['nullable', 'exists:grades,id'],
            'tariffs.*.ceinture_keys' => ['nullable', 'string', 'max:255'],
        ]);

        $ctx = $request->user()->scopeContext();
        $type = $validated['type_grade'];
        $federationId = $ctx['federation_id'] ?: ($validated['federation_id'] ?? null);
        $ligueId = $type === 'KEUP' ? ($ctx['ligue_id'] ?: ($validated['ligue_id'] ?? null)) : null;

        $count = 0;
        foreach ($validated['tariffs'] as $row) {
            GradePassageTariff::create([
                'type_grade' => $type,
                'federation_id' => $federationId,
                'ligue_id' => $ligueId,
                'grade_id' => $type === 'DAN' ? ($row['grade_id'] ?? null) : null,
                'tarif_label' => $row['tarif_label'],
                'ceinture_keys' => $type === 'KEUP' ? ($row['ceinture_keys'] ?? null) : null,
                'montant' => $row['montant'],
                'active' => true,
                'created_by_user_id' => auth()->id(),
            ]);
            $count++;
        }

        return back()->with('success', __('messages.grade_passages.tariffs_added', ['count' => $count]));
    }

    /** Applique le périmètre de l'utilisateur au tarif (fédération pour DAN, ligue pour KEUP). */
    private function applyScope(Request $request, array $data): array
    {
        $ctx = $request->user()->scopeContext();
        if ($ctx['federation_id']) {
            $data['federation_id'] = $ctx['federation_id'];
        }
        if (($data['type_grade'] ?? null) === 'KEUP' && $ctx['ligue_id']) {
            $data['ligue_id'] = $ctx['ligue_id'];
        }
        if (($data['type_grade'] ?? null) === 'DAN') {
            $data['ligue_id'] = null;
        }

        return $data;
    }

    public function edit(GradePassageTariff $grade_passage_tariff): View
    {
        return view('admin.grade-passage-tariffs.edit', array_merge($this->formData(), [
            'tariff' => $grade_passage_tariff,
            'page_title' => __('messages.grade_passage_tariffs.edit'),
        ]));
    }

    public function update(Request $request, GradePassageTariff $grade_passage_tariff): RedirectResponse
    {
        $grade_passage_tariff->update($this->validateData($request));

        return redirect()->route('admin.grade-passage-tariffs.index')
            ->with('success', __('messages.grade_passage_tariffs.updated'));
    }

    public function destroy(GradePassageTariff $grade_passage_tariff): RedirectResponse
    {
        $grade_passage_tariff->delete();

        return redirect()->route('admin.grade-passage-tariffs.index')
            ->with('success', __('messages.grade_passage_tariffs.deleted'));
    }

    private function formData(): array
    {
        return [
            'federations' => Federation::visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom']),
            'grades' => Grade::visibleTo(request()->user())->orderBy('type_grade')->orderBy('niveau')->get(['id', 'nom_grade', 'type_grade']),
        ];
    }

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'type_grade' => ['required', Rule::in(['KEUP', 'DAN'])],
            'tarif_label' => ['required', 'string', 'max:140'],
            'montant' => ['required', 'numeric', 'min:0'],
            'federation_id' => ['nullable', 'exists:federations,id'],
            'ligue_id' => ['nullable', 'exists:ligues,id'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'ceinture_keys' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ]);

        $validated['active'] = $request->boolean('active', true);

        return $validated;
    }
}
