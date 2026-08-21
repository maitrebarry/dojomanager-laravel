<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Disciple;
use App\Models\Grade;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Mise à jour directe des grades des disciples — sans passer par une session
 * de passage de grade (module « Passage de grade », qui suppose une ligue ou
 * une fédération active pour créer/valider les sessions). Ici, le maître
 * promeut lui-même les disciples de sa salle, en groupe ou individuellement.
 */
class DiscipleGradeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('role:maitre');
        $this->middleware('permission:DISCIPLE_UPDATE');
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search'));
        $gradeId = $request->query('grade_id');

        $sequence = $this->gradeSequence($user);
        $nextGradeMap = $this->nextGradeMap($sequence);

        $disciples = Disciple::active()
            ->visibleTo($user)
            ->with('grade')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('nom_complet', 'like', "%{$search}%")
                        ->orWhere('nmle', 'like', "%{$search}%");
                });
            })
            ->when($gradeId !== null && $gradeId !== '', fn ($q) => $q->where('grade_id', $gradeId))
            ->orderBy('nom')->orderBy('prenom')
            ->get();

        return view('admin.disciples.grade-updates', [
            'disciples' => $disciples,
            'grades' => $sequence,
            'nextGradeMap' => $nextGradeMap,
            'search' => $search,
            'gradeId' => $gradeId,
            'page_title' => __('messages.disciple_grades.title'),
        ]);
    }

    public function apply(Request $request): RedirectResponse
    {
        $user = $request->user();
        $sequence = $this->gradeSequence($user);

        $validated = $request->validate([
            'disciple_ids' => ['required', 'array', 'min:1'],
            'disciple_ids.*' => ['integer', 'exists:disciples,id'],
            'update_mode' => ['required', 'in:next,custom'],
            'target_grade_id' => ['nullable', 'integer', Rule::in($sequence->pluck('id')->all())],
            'refresh_date' => ['nullable', 'boolean'],
        ], [
            'disciple_ids.required' => __('messages.disciple_grades.no_selection'),
            'target_grade_id.in' => __('messages.disciple_grades.invalid_target'),
        ]);

        if ($validated['update_mode'] === 'custom' && empty($validated['target_grade_id'])) {
            return back()->withInput()->with('warning', __('messages.disciple_grades.choose_target'));
        }

        $nextGradeMap = $this->nextGradeMap($sequence);

        $disciples = Disciple::active()->visibleTo($user)->whereIn('id', $validated['disciple_ids'])->get();
        $refreshDate = $request->boolean('refresh_date');

        $updated = 0;
        $skipped = 0;

        foreach ($disciples as $disciple) {
            $targetGradeId = $validated['update_mode'] === 'next'
                ? ($nextGradeMap[$disciple->grade_id ?? 0] ?? null)
                : (int) $validated['target_grade_id'];

            if (!$targetGradeId) {
                $skipped++;
                continue;
            }

            $payload = ['grade_id' => $targetGradeId];
            if ($refreshDate) {
                $payload['date_obtention_grade'] = now()->toDateString();
            }

            $disciple->update($payload);
            $updated++;
        }

        $message = __('messages.disciple_grades.updated_count', ['count' => $updated]);
        if ($skipped > 0) {
            $message .= ' ' . __('messages.disciple_grades.skipped_count', ['count' => $skipped]);
        }

        return redirect()
            ->route('admin.disciples.grades.index', $request->only(['search', 'grade_id']))
            ->with('success', $message);
    }

    public function attestation(Disciple $disciple)
    {
        $this->authorizeScope($disciple);
        abort_unless($disciple->grade_id, 404);

        $disciple->load('salle.maitre', 'salle.maitreUser.grade', 'grade');

        $pdf = Pdf::loadView('admin.disciples.grade_attestation_pdf', [
            'disciples' => collect([$disciple]),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('attestation-grade-' . $disciple->id . '.pdf');
    }

    public function attestationsSelection(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'disciple_ids' => ['required', 'array', 'min:1'],
            'disciple_ids.*' => ['integer', 'exists:disciples,id'],
        ], [
            'disciple_ids.required' => __('messages.disciple_grades.no_selection'),
        ]);

        $disciples = Disciple::visibleTo($user)
            ->whereIn('id', $validated['disciple_ids'])
            ->whereNotNull('grade_id')
            ->with('salle.maitre', 'salle.maitreUser.grade', 'grade')
            ->orderBy('nom')->orderBy('prenom')
            ->get();

        abort_if($disciples->isEmpty(), 404);

        $pdf = Pdf::loadView('admin.disciples.grade_attestation_pdf', [
            'disciples' => $disciples,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('attestations-grades-selection.pdf');
    }

    /**
     * Liste des candidats à la passation de grade — un document que le maître
     * peut produire seul (sans session « Passage de grade », qui suppose une
     * ligue/fédération active) : à imprimer, annoter le jour de l'examen
     * interne et conserver, indépendamment de toute validation externe.
     */
    public function candidatesList(Request $request)
    {
        $user = $request->user();
        $sequence = $this->gradeSequence($user);
        $nextGradeMap = $this->nextGradeMap($sequence);

        $validated = $request->validate([
            'disciple_ids' => ['required', 'array', 'min:1'],
            'disciple_ids.*' => ['integer', 'exists:disciples,id'],
        ], [
            'disciple_ids.required' => __('messages.disciple_grades.no_selection'),
        ]);

        $disciples = Disciple::active()
            ->visibleTo($user)
            ->whereIn('id', $validated['disciple_ids'])
            ->with(['grade', 'salle.ligue.federation'])
            ->orderBy('nom')->orderBy('prenom')
            ->get()
            ->map(fn (Disciple $d) => (object) [
                'disciple' => $d,
                'nextGrade' => $sequence->firstWhere('id', $nextGradeMap[$d->grade_id ?? 0] ?? null),
            ]);

        abort_if($disciples->isEmpty(), 404);

        $salle = $disciples->first()->disciple->salle;
        $ligue = $salle?->ligue;
        $federation = $ligue?->federation;

        $pdf = Pdf::loadView('admin.disciples.grade_candidates_list_pdf', [
            'rows' => $disciples,
            'salle' => $salle,
            'ligue' => $ligue,
            'federation' => $federation,
            'official' => LicenceController::OFFICIAL,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('liste-candidats-passage-grade.pdf');
    }

    /**
     * Séquence ordonnée des grades visibles, du plus débutant au plus avancé.
     * `niveau` est un rang unique et croissant sur toute la progression d'une
     * fédération (KEUP puis DAN enchaînés sans se chevaucher — vérifié sur les
     * données réelles : 9e KEUP=1 ... 1er KEUP=9, 1er DAN=10, 2e DAN=11, ...),
     * donc un simple tri par niveau suffit, sans distinction de type_grade.
     */
    private function gradeSequence($user): Collection
    {
        return Grade::visibleTo($user)->orderBy('niveau')->get()->values();
    }

    /** [id_du_grade_actuel => id_du_grade_suivant] ; clé 0 = disciple sans grade → premier grade de la séquence. */
    private function nextGradeMap(Collection $sequence): array
    {
        $map = [0 => $sequence->first()?->id];

        foreach ($sequence as $index => $grade) {
            $map[$grade->id] = $sequence->get($index + 1)?->id;
        }

        return $map;
    }

    /** 403 si le disciple est hors du périmètre de l'utilisateur (même garde que DiscipleController). */
    private function authorizeScope(Disciple $disciple): void
    {
        abort_unless(
            Disciple::visibleTo(request()->user())->whereKey($disciple->getKey())->exists(),
            403,
            __('messages.out_of_scope')
        );
    }
}
