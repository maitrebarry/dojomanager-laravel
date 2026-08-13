<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Controller;
use App\Models\Disciple;
use App\Models\Federation;
use App\Models\Grade;
use App\Models\GradePassageCandidate;
use App\Models\GradePassageSession;
use App\Models\Ligue;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GradePassageSessionController extends Controller
{
    use AuthorizesScope;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:PASSAGEGRADES_READ')->only(['index', 'soumission', 'examen', 'show', 'attestation']);
        $this->middleware('permission:PASSAGEGRADES_MANAGE')->only(['configuration']);
        $this->middleware('permission:PASSAGEGRADES_MANAGE')->only(['create', 'store', 'addCandidate', 'addCandidatesBatch', 'edit', 'update', 'pay', 'evaluate', 'saveExamNotes', 'finalize', 'removeCandidate', 'destroy']);
    }

    public function index(): View|RedirectResponse
    {
        // Un maître ne voit que l'onglet Soumission (fidèle à showConfigTab/showExamTab).
        if ($this->isMaitre(request()->user())) {
            return redirect()->route('admin.grade-passages.soumission');
        }

        return view('admin.grade-passages.index', [
            'sessions' => GradePassageSession::visibleTo(request()->user())->withCount('candidats')->orderByDesc('date_session')->paginate(15),
            'page_title' => __('messages.grade_passages.title'),
        ]);
    }

    /**
     * Vue Configuration (copie fidèle de PassageGrades.jsx) :
     * création de session & annonce + grille tarifaire (DAN/KEUP).
     */
    public function configuration(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($this->isMaitre($user)) {
            return redirect()->route('admin.grade-passages.soumission');
        }

        $grades = Grade::visibleTo($user)->orderBy('type_grade')->orderBy('niveau')->get(['id', 'nom_grade', 'type_grade', 'ceinture', 'niveau']);

        return view('admin.grade-passages.configuration', array_merge($this->formData(), [
            'session' => new GradePassageSession([
                'date_session' => now()->toDateString(),
                'type_grade' => 'KEUP',
                'type_notation' => 'NOTE',
                'bareme' => 20,
                'frais_participation' => 0,
            ]),
            'tariffs' => \App\Models\GradePassageTariff::visibleTo($user)
                ->with(['grade:id,nom_grade'])
                ->orderBy('type_grade')->orderBy('tarif_label')->get(),
            'danGrades' => $grades->where('type_grade', 'DAN')->values(),
            // Ceintures KEUP distinctes (source des groupes tarifaires KEUP), fidèle à availableKeupBelts.
            'keupBelts' => $grades->where('type_grade', 'KEUP')
                ->pluck('ceinture')
                ->map(fn ($c) => trim((string) $c))
                ->filter()
                ->unique()
                ->values(),
            'page_title' => __('messages.grade_passages.tab_config'),
        ]));
    }

    /**
     * Vue Soumission (copie fidèle de PassageGrades.jsx) : sélecteur de session,
     * panier de candidats éligibles, liste des candidatures avec encaissement.
     */
    public function soumission(Request $request): View
    {
        $user = $request->user();

        $sessions = GradePassageSession::visibleTo($user)
            ->withCount('candidats')
            ->orderByDesc('date_session')
            ->get();

        $selectedId = $request->integer('session') ?: $sessions->first()?->id;
        $session = $selectedId
            ? GradePassageSession::visibleTo($user)->with('candidats')->find($selectedId)
            : null;

        $candidates = collect();
        $eligibles = collect();
        $grades = collect();
        $stats = ['candidatures' => 0, 'validees' => 0, 'evaluees' => 0, 'admis' => 0, 'attendus' => 0, 'paye' => 0, 'reste' => 0];

        if ($session) {
            $candidates = $session->candidats->sortBy([['nom', 'asc'], ['prenom', 'asc']])->values();

            $existingIds = $candidates->where('candidate_type', 'DISCIPLE')->pluck('source_id')->filter()->all();
            $eligibles = Disciple::active()->visibleTo($user)
                ->with(['grade:id,nom_grade,niveau', 'salle:id,nom'])
                ->whereNotIn('id', $existingIds)
                ->orderBy('nom')->orderBy('prenom')
                ->get();

            $grades = Grade::visibleTo($user)
                ->where('type_grade', $session->type_grade)
                ->orderBy('niveau')
                ->get(['id', 'nom_grade', 'niveau', 'ceinture']);

            // Frais applicable par grade proposé depuis la grille tarifaire
            // (DAN → tarif par grade ; KEUP → tarif par ceinture), fidèle à Spring/React.
            $tariffs = \App\Models\GradePassageTariff::visibleTo($user)
                ->where('type_grade', $session->type_grade)
                ->where('active', true)
                ->get();

            $feeByGradeId = [];
            foreach ($grades as $g) {
                if ($session->type_grade === 'DAN') {
                    $t = $tariffs->firstWhere('grade_id', $g->id);
                } else {
                    $belt = trim((string) $g->ceinture);
                    $t = $tariffs->first(fn ($x) => trim((string) $x->ceinture_keys) === $belt && $belt !== '');
                }
                if ($t) {
                    $feeByGradeId[$g->id] = (float) $t->montant;
                }
            }

            $stats = [
                'candidatures' => $candidates->count(),
                'validees' => $candidates->where('statut', 'VALIDE')->count(),
                'evaluees' => $candidates->whereNotNull('resultat')->count(),
                'admis' => $candidates->where('resultat', 'ADMIS')->count(),
                'attendus' => (float) $candidates->sum('frais_participation'),
                'paye' => (float) $candidates->sum('montant_paye'),
                'reste' => (float) $candidates->sum(fn ($c) => max(0, (float) $c->frais_participation - (float) $c->montant_paye)),
            ];
        }

        return view('admin.grade-passages.soumission', [
            'sessions' => $sessions,
            'session' => $session,
            'candidates' => $candidates,
            'eligibles' => $eligibles,
            'grades' => $grades,
            'feeByGradeId' => $feeByGradeId ?? [],
            'stats' => $stats,
            'page_title' => __('messages.grade_passages.tab_submission'),
        ]);
    }

    /**
     * Vue Examen (copie fidèle de PassageGrades.jsx) : grille de notation
     * (Forme, Mouvement de base, Poomsae, Attaque-défense, Combat) + moyenne + finalisation.
     */
    public function examen(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($this->isMaitre($user)) {
            return redirect()->route('admin.grade-passages.soumission');
        }

        $sessions = GradePassageSession::visibleTo($user)
            ->withCount('candidats')
            ->orderByDesc('date_session')
            ->get();

        $selectedId = $request->integer('session') ?: $sessions->first()?->id;
        $session = $selectedId
            ? GradePassageSession::visibleTo($user)->with('candidats')->find($selectedId)
            : null;

        $candidates = collect();
        $completion = ['total' => 0, 'evaluated' => 0, 'complete' => false];

        if ($session) {
            // On note les candidats dont le paiement est complet (fidèle au flux React).
            $candidates = $session->candidats
                ->sortBy([['salle_nom', 'asc'], ['nom', 'asc'], ['prenom', 'asc']])
                ->values();

            $total = $candidates->count();
            $evaluated = $candidates->whereNotNull('moyenne_generale')->count();
            $completion = [
                'total' => $total,
                'evaluated' => $evaluated,
                'complete' => $total > 0 && $evaluated === $total,
            ];
        }

        return view('admin.grade-passages.examen', [
            'sessions' => $sessions,
            'session' => $session,
            'candidates' => $candidates,
            'completion' => $completion,
            'page_title' => __('messages.grade_passages.tab_exam'),
        ]);
    }

    /** Enregistre les 5 notes d'un candidat, calcule la moyenne et le résultat (auto-save AJAX). */
    public function saveExamNotes(Request $request, GradePassageSession $grade_passage, GradePassageCandidate $candidate): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->assertNotFinalised($grade_passage);

        $bareme = (int) ($grade_passage->bareme ?: 20);

        $validated = $request->validate([
            'note_forme' => ['nullable', 'numeric', 'min:0', "max:{$bareme}"],
            'note_mouvement_base' => ['nullable', 'numeric', 'min:0', "max:{$bareme}"],
            'note_poomsea' => ['nullable', 'numeric', 'min:0', "max:{$bareme}"],
            'note_attaque_defense' => ['nullable', 'numeric', 'min:0', "max:{$bareme}"],
            'note_combat' => ['nullable', 'numeric', 'min:0', "max:{$bareme}"],
        ]);

        $notes = [
            (float) ($validated['note_forme'] ?? 0),
            (float) ($validated['note_mouvement_base'] ?? 0),
            (float) ($validated['note_poomsea'] ?? 0),
            (float) ($validated['note_attaque_defense'] ?? 0),
            (float) ($validated['note_combat'] ?? 0),
        ];
        $moyenne = round(array_sum($notes) / count($notes), 2);
        $admis = $moyenne >= ($bareme / 2);

        $candidate->update($validated + [
            'moyenne_generale' => $moyenne,
            'note_globale' => $moyenne,
            'resultat' => $admis ? 'ADMIS' : 'AJOURNE',
            'statut' => $admis ? 'VALIDE' : 'REFUSE',
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'moyenne' => $moyenne,
                'resultat' => $admis ? 'ADMIS' : 'AJOURNE',
                'statut' => $admis ? 'VALIDE' : 'REFUSE',
            ]);
        }

        return back()->with('success', __('messages.grade_passages.notes_saved'));
    }

    /** Ajout groupé de candidatures depuis le panier de la vue Soumission. */
    public function addCandidatesBatch(Request $request, GradePassageSession $grade_passage): RedirectResponse
    {
        $this->assertNotFinalised($grade_passage);

        $validated = $request->validate([
            'candidates' => ['required', 'array', 'min:1'],
            'candidates.*.disciple_id' => ['required', 'exists:disciples,id'],
            'candidates.*.proposed_grade_id' => ['required', 'exists:grades,id'],
            'candidates.*.frais_participation' => ['nullable', 'numeric', 'min:0'],
        ]);

        $added = 0;
        foreach ($validated['candidates'] as $row) {
            $disciple = Disciple::with(['grade', 'salle'])->find($row['disciple_id']);
            if (!$disciple) {
                continue;
            }
            if ($grade_passage->candidats()->where('candidate_type', 'DISCIPLE')->where('source_id', $disciple->id)->exists()) {
                continue;
            }
            $proposed = Grade::find($row['proposed_grade_id']);
            $grade_passage->candidats()->create([
                'candidate_type' => 'DISCIPLE',
                'source_id' => $disciple->id,
                'nom' => $disciple->nom,
                'prenom' => $disciple->prenom,
                'sexe' => $disciple->sexe,
                'salle_id' => $disciple->salle?->id,
                'salle_nom' => $disciple->salle?->nom,
                'current_grade_id' => $disciple->grade?->id,
                'current_grade_nom' => $disciple->grade?->nom_grade,
                'proposed_grade_id' => $proposed?->id,
                'proposed_grade_nom' => $proposed?->nom_grade,
                'frais_participation' => $row['frais_participation'] ?? $grade_passage->frais_participation,
                'montant_paye' => 0,
                'statut_paiement' => 'IMPAYE',
                'statut' => 'EN_ATTENTE',
            ]);
            $added++;
        }

        return back()->with('success', __('messages.grade_passages.candidates_added', ['count' => $added]));
    }

    public function create(): View
    {
        return view('admin.grade-passages.create', array_merge($this->formData(), [
            'session' => new GradePassageSession(['date_session' => now()->toDateString(), 'type_grade' => 'KEUP', 'type_notation' => 'NOTE', 'frais_participation' => 0]),
            'page_title' => __('messages.grade_passages.add'),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $userFederation = $request->user()->scopeContext()['federation_id'];
        if ($userFederation) {
            $data['federation_id'] = $userFederation;
        }

        $session = GradePassageSession::create($data + ['created_by_user_id' => auth()->id()]);

        return redirect()->route('admin.grade-passages.show', $session)
            ->with('success', __('messages.grade_passages.created'));
    }

    public function show(GradePassageSession $grade_passage): View
    {
        abort_unless(
            GradePassageSession::visibleTo(request()->user())->whereKey($grade_passage->getKey())->exists(),
            403
        );

        $grade_passage->load(['candidats' => fn ($q) => $q->orderBy('nom')->orderBy('prenom')]);

        return view('admin.grade-passages.show', [
            'session' => $grade_passage,
            'disciples' => Disciple::active()->visibleTo(request()->user())->with('grade:id,nom_grade,niveau')->orderBy('nom')->get(),
            'grades' => Grade::visibleTo(request()->user())->where('type_grade', $grade_passage->type_grade)->orderBy('niveau')->get(['id', 'nom_grade', 'niveau']),
            'page_title' => __('messages.grade_passages.session') . ' — ' . $grade_passage->date_session?->format('d/m/Y'),
        ]);
    }

    public function edit(GradePassageSession $grade_passage): View
    {
        $this->guardScope($grade_passage);

        return view('admin.grade-passages.edit', array_merge($this->formData(), [
            'session' => $grade_passage,
            'page_title' => __('messages.grade_passages.edit'),
        ]));
    }

    public function update(Request $request, GradePassageSession $grade_passage): RedirectResponse
    {
        $this->guardScope($grade_passage);
        $grade_passage->update($this->validateData($request));

        return redirect()->route('admin.grade-passages.show', $grade_passage)
            ->with('success', __('messages.grade_passages.updated'));
    }

    public function destroy(GradePassageSession $grade_passage): RedirectResponse
    {
        $this->guardScope($grade_passage);
        $grade_passage->delete();

        return redirect()->route('admin.grade-passages.index')
            ->with('success', __('messages.grade_passages.deleted'));
    }

    public function addCandidate(Request $request, GradePassageSession $grade_passage): RedirectResponse
    {
        $this->assertNotFinalised($grade_passage);

        $validated = $request->validate([
            'disciple_id' => ['required', 'exists:disciples,id'],
            'proposed_grade_id' => ['required', 'exists:grades,id'],
            'frais_participation' => ['nullable', 'numeric', 'min:0'],
        ]);

        $disciple = Disciple::with(['grade', 'salle'])->findOrFail($validated['disciple_id']);
        $proposed = Grade::find($validated['proposed_grade_id']);

        if ($grade_passage->candidats()->where('candidate_type', 'DISCIPLE')->where('source_id', $disciple->id)->exists()) {
            return back()->with('warning', __('messages.grade_passages.candidate_exists'));
        }

        $grade_passage->candidats()->create([
            'candidate_type' => 'DISCIPLE',
            'source_id' => $disciple->id,
            'nom' => $disciple->nom,
            'prenom' => $disciple->prenom,
            'sexe' => $disciple->sexe,
            'salle_id' => $disciple->salle?->id,
            'salle_nom' => $disciple->salle?->nom,
            'current_grade_id' => $disciple->grade?->id,
            'current_grade_nom' => $disciple->grade?->nom_grade,
            'proposed_grade_id' => $proposed?->id,
            'proposed_grade_nom' => $proposed?->nom_grade,
            'frais_participation' => $validated['frais_participation'] ?? $grade_passage->frais_participation,
            'montant_paye' => 0,
            'statut_paiement' => 'IMPAYE',
            'statut' => 'EN_ATTENTE',
        ]);

        return back()->with('success', __('messages.grade_passages.candidate_added'));
    }

    public function removeCandidate(GradePassageSession $grade_passage, GradePassageCandidate $candidate): RedirectResponse
    {
        $this->assertNotFinalised($grade_passage);
        $candidate->delete();

        return back()->with('success', __('messages.grade_passages.candidate_removed'));
    }

    public function pay(Request $request, GradePassageSession $grade_passage, GradePassageCandidate $candidate): RedirectResponse
    {
        $this->guardScope($grade_passage);
        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
            'mode_paiement' => ['required', 'string', 'max:40'],
            'reference_paiement' => ['nullable', 'string', 'max:120'],
            'date_paiement' => ['required', 'date'],
        ]);

        $validated['created_by_user_id'] = auth()->id();
        $candidate->paiements()->create($validated);
        $candidate->recomputePayment();

        return back()->with('success', __('messages.cotisations.payment_recorded'));
    }

    public function evaluate(Request $request, GradePassageSession $grade_passage, GradePassageCandidate $candidate): RedirectResponse
    {
        $this->assertNotFinalised($grade_passage);

        $validated = $request->validate([
            'note_globale' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'resultat' => ['required', Rule::in(['ADMIS', 'AJOURNE'])],
        ]);

        $candidate->update([
            'note_globale' => $validated['note_globale'] ?? null,
            'resultat' => $validated['resultat'],
            'statut' => $validated['resultat'] === 'ADMIS' ? 'VALIDE' : 'REFUSE',
        ]);

        return back()->with('success', __('messages.grade_passages.evaluated'));
    }

    public function finalize(GradePassageSession $grade_passage): RedirectResponse
    {
        $this->guardScope($grade_passage);
        $grade_passage->update(['finalisee' => true]);

        return back()->with('success', __('messages.grade_passages.finalized'));
    }

    public function attestation(GradePassageSession $grade_passage, GradePassageCandidate $candidate)
    {
        $this->guardScope($grade_passage);
        abort_unless($candidate->statut === 'VALIDE', 404);

        $pdf = Pdf::loadView('admin.grade-passages.attestation_pdf', [
            'session' => $grade_passage,
            'candidate' => $candidate,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('attestation-' . $candidate->id . '.pdf');
    }

    /** Vrai si l'utilisateur est un maître (ne voit que l'onglet Soumission). */
    private function isMaitre($user): bool
    {
        return ($user->role->value ?? $user->role) === 'maitre';
    }

    private function assertNotFinalised(GradePassageSession $session): void
    {
        $this->guardScope($session);
        abort_if($session->finalisee, 422, __('messages.grade_passages.finalized_locked'));
    }

    private function formData(): array
    {
        return [
            'federations' => Federation::visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom']),
            'ligues' => Ligue::visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom']),
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'date_session' => ['required', 'date'],
            'lieu' => ['required', 'string', 'max:180'],
            'type_grade' => ['required', Rule::in(['KEUP', 'DAN'])],
            'frais_participation' => ['nullable', 'numeric', 'min:0'],
            'type_notation' => ['required', Rule::in(['NOTE', 'ADMIS'])],
            'bareme' => ['nullable', Rule::in([20, 100])],
            'federation_id' => ['nullable', 'exists:federations,id'],
            'ligue_id' => ['nullable', 'exists:ligues,id'],
            'annonce' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
