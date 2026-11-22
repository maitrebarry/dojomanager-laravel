<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CeintureNoireManuelle;
use App\Models\CotisationAnnuelle;
use App\Models\CotisationAnnuelleCeintureNoire;
use App\Models\Disciple;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CotisationAnnuelleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('role:federation,ligue'); // App.jsx : cotisations réservées ADMIN/LIGUE
        $this->middleware('permission:COTISATION_MANAGE')->only(['index', 'show', 'create', 'store', 'pay', 'destroy', 'refresh']);
    }

    public function index(): View
    {
        $cotisations = $this->campaigns(request()->user())
            ->withCount('membres')
            ->withSum('membres as total_paye', 'montant_paye')
            ->withSum('membres as total_du', 'montant_du')
            ->orderByDesc('annee')
            ->paginate(15);

        return view('admin.cotisations-annuelles.index', [
            'cotisations' => $cotisations,
            'page_title' => __('messages.cotisations_annuelles.title'),
        ]);
    }

    public function create(): View
    {
        return view('admin.cotisations-annuelles.create', [
            'cotisation' => new CotisationAnnuelle(['annee' => now()->year, 'type' => request('type') === 'SALLE' ? 'SALLE' : 'CEINTURE_NOIRE']),
            'types' => $this->types(request()->user()),
            'page_title' => __('messages.cotisations_annuelles.add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $type = $request->input('type') === 'SALLE' ? 'SALLE' : 'CEINTURE_NOIRE';
        abort_unless(in_array($type, $this->types($user), true), 403);
        $ligueId = $type === 'SALLE' ? $user->scopeContext()['ligue_id'] : null;

        $validated = $request->validate([
            'annee' => ['required', 'integer', 'min:2000', 'max:2100',
                \Illuminate\Validation\Rule::unique('cotisations_annuelles', 'annee')->where('type', $type)
                    ->where(fn ($q) => $ligueId ? $q->where('ligue_id', $ligueId) : $q->whereNull('ligue_id'))],
            'montant' => ['required', 'numeric', 'min:0'],
        ]);

        $cotisation = CotisationAnnuelle::create([
            'annee' => $validated['annee'],
            'montant' => $validated['montant'],
            'type' => $type,
            'federation_id' => $user->scopeContext()['federation_id'],
            'ligue_id' => $ligueId,
            'created_by_user_id' => auth()->id(),
        ]);

        $type === 'SALLE' ? $this->snapshotSalles($cotisation) : $this->snapshotMembers($cotisation, $user);

        return redirect()->route('admin.cotisations-annuelles.show', $cotisation)
            ->with('success', __('messages.cotisations_annuelles.created'));
    }

    /** Campagne des salles : ajoute les salles affiliées depuis son lancement. */
    public function refresh(CotisationAnnuelle $cotisations_annuelle): RedirectResponse
    {
        abort_unless($this->campaigns(request()->user())->whereKey($cotisations_annuelle->getKey())->exists(), 403);
        abort_unless($cotisations_annuelle->type === 'SALLE', 404);
        $added = $this->snapshotSalles($cotisations_annuelle);

        return back()->with('success', __('messages.cotisations_annuelles.salles_added', ['count' => $added]));
    }

    public function show(CotisationAnnuelle $cotisations_annuelle): View
    {
        abort_unless($this->campaigns(request()->user())->whereKey($cotisations_annuelle->getKey())->exists(), 403);

        $membres = $cotisations_annuelle->type === 'SALLE'
            ? $cotisations_annuelle->membres()->orderBy('salle_nom')->paginate(25)
            : $cotisations_annuelle->membres()->orderBy('prenom')->orderBy('nom')->paginate(25);
        // Salles : maître et nombre de disciples actifs, pour information.
        $salles = $cotisations_annuelle->type !== 'SALLE' ? collect() : \App\Models\Salle::whereIn('id', $membres->pluck('salle_id'))
            ->with('maitreUser:id,salle_id,name')->withCount(['disciples' => fn ($q) => $q->whereNull('archived_at')])->get()->keyBy('id');

        $totaux = [
            'du' => $cotisations_annuelle->membres()->sum('montant_du'),
            'paye' => $cotisations_annuelle->membres()->sum('montant_paye'),
            'reste' => $cotisations_annuelle->membres()->sum('reste_a_payer'),
        ];

        return view('admin.cotisations-annuelles.show', [
            'cotisation' => $cotisations_annuelle,
            'membres' => $membres,
            'totaux' => $totaux,
            'salles' => $salles,
            'page_title' => __('messages.cotisations_annuelles.title') . ' ' . $cotisations_annuelle->annee,
        ]);
    }

    public function pay(Request $request, CotisationAnnuelleCeintureNoire $membre): RedirectResponse
    {
        abort_unless($this->campaigns($request->user())->whereKey($membre->cotisation_annuelle_id)->exists(), 403);

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
            'mode_paiement' => ['required', 'string', 'max:30'],
            'reference_paiement' => ['nullable', 'string', 'max:80'],
            'date_paiement' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['created_by_user_id'] = auth()->id();
        $membre->paiements()->create($validated);
        $membre->recompute();

        return back()->with('success', __('messages.cotisations.payment_recorded'));
    }

    public function destroy(CotisationAnnuelle $cotisations_annuelle): RedirectResponse
    {
        abort_unless($this->campaigns(request()->user())->whereKey($cotisations_annuelle->getKey())->exists(), 403);

        $cotisations_annuelle->delete();

        return redirect()->route('admin.cotisations-annuelles.index')
            ->with('success', __('messages.cotisations_annuelles.deleted'));
    }

    /**
     * Campagnes visibles : celles de la fédération ; une campagne des salles n'est visible
     * que de sa ligue (et de la fédération).
     */
    private function campaigns(?User $user)
    {
        $ligueId = $user?->scopeContext()['ligue_id'] ?? null;

        return CotisationAnnuelle::query()->visibleTo($user)
            ->when($ligueId, fn ($q) => $q->where(fn ($w) => $w->where('type', '!=', 'SALLE')->orWhere('ligue_id', $ligueId)));
    }

    /** Types de campagne que l'utilisateur peut lancer (forfait des salles : ligue seulement). */
    private function types(?User $user): array
    {
        return array_values(array_filter(['CEINTURE_NOIRE', !empty($user?->scopeContext()['ligue_id']) ? 'SALLE' : null]));
    }

    /**
     * Forfait annuel des salles : une ligne par salle active de la ligue (déjà présentes
     * ignorées). Renvoie le nombre de salles ajoutées.
     */
    private function snapshotSalles(CotisationAnnuelle $cotisation): int
    {
        $montant = (float) $cotisation->montant;
        $present = $cotisation->membres()->pluck('salle_id')->flip();
        $added = 0;
        \App\Models\Salle::where('ligue_id', $cotisation->ligue_id)->where('active', true)
            ->with('ligue.federation')->get()
            ->reject(fn ($salle) => isset($present[$salle->id]))
            ->each(function ($salle) use ($cotisation, $montant, &$added) {
                CotisationAnnuelleCeintureNoire::create([
                    'cotisation_annuelle_id' => $cotisation->id,
                    'origine' => 'SALLE',
                    'source_id' => $salle->id,
                    'nom' => $salle->nom,
                    'prenom' => '',
                    'federation_id' => $salle->ligue?->federation?->id,
                    'federation_nom' => $salle->ligue?->federation?->nom,
                    'ligue_id' => $salle->ligue?->id,
                    'ligue_nom' => $salle->ligue?->nom,
                    'salle_id' => $salle->id,
                    'salle_nom' => $salle->nom,
                    'montant_du' => $montant,
                    'montant_paye' => 0,
                    'reste_a_payer' => $montant,
                    'statut' => 'IMPAYE',
                ]);
                $added++;
            });

        return $added;
    }

    /**
     * Fige la liste des ceintures noires (disciples DAN + saisies manuelles + maîtres/
     * responsables de ligue/fédération au grade DAN) pour la campagne.
     */
    private function snapshotMembers(CotisationAnnuelle $cotisation, ?\App\Models\User $user = null): void
    {
        $montant = (float) $cotisation->montant;

        Disciple::active()
            ->visibleTo($user)
            ->whereHas('grade', fn ($q) => $q->where('type_grade', 'DAN'))
            ->with(['grade', 'salle.ligue.federation'])
            ->get()
            ->each(function (Disciple $d) use ($cotisation, $montant) {
                CotisationAnnuelleCeintureNoire::create([
                    'cotisation_annuelle_id' => $cotisation->id,
                    'origine' => 'DISCIPLE',
                    'source_id' => $d->id,
                    'nom' => $d->nom,
                    'prenom' => $d->prenom,
                    'sexe' => $d->sexe,
                    'grade_nom' => $d->grade?->nom_grade,
                    'federation_id' => $d->salle?->ligue?->federation?->id,
                    'federation_nom' => $d->salle?->ligue?->federation?->nom,
                    'ligue_id' => $d->salle?->ligue?->id,
                    'ligue_nom' => $d->salle?->ligue?->nom,
                    'salle_id' => $d->salle?->id,
                    'salle_nom' => $d->salle?->nom,
                    'montant_du' => $montant,
                    'montant_paye' => 0,
                    'reste_a_payer' => $montant,
                    'statut' => 'IMPAYE',
                ]);
            });

        CeintureNoireManuelle::active()
            ->visibleTo($user)
            ->with(['grade', 'federation', 'ligue', 'salle'])
            ->get()
            ->each(function (CeintureNoireManuelle $m) use ($cotisation, $montant) {
                CotisationAnnuelleCeintureNoire::create([
                    'cotisation_annuelle_id' => $cotisation->id,
                    'origine' => 'MANUELLE',
                    'source_id' => $m->id,
                    'nom' => $m->nom,
                    'prenom' => $m->prenom,
                    'sexe' => $m->sexe,
                    'grade_nom' => $m->grade?->nom_grade,
                    'federation_id' => $m->federation?->id,
                    'federation_nom' => $m->federation?->nom,
                    'ligue_id' => $m->ligue?->id,
                    'ligue_nom' => $m->ligue?->nom,
                    'salle_id' => $m->salle?->id,
                    'salle_nom' => $m->salle?->nom,
                    'montant_du' => $montant,
                    'montant_paye' => 0,
                    'reste_a_payer' => $montant,
                    'statut' => 'IMPAYE',
                ]);
            });

        // Un maître (ou un responsable de ligue/fédération) est lui-même une ceinture
        // noire de sa structure : son grade DAN n'est jamais un Disciple. Il ne compte
        // que s'il a lui-même une salle rattachée (users.salle_id) : un responsable de
        // ligue/fédération sans salle personnelle n'est pas encore « maître responsable ».
        User::query()
            ->visibleTo($user)
            ->whereIn('role', User::TENANT_ROLES)
            ->whereHas('salle')
            ->whereHas('grade', fn ($q) => $q->where('type_grade', 'DAN'))
            ->with(['grade', 'federation', 'ligue', 'salle.ligue.federation'])
            ->get()
            ->each(function (User $u) use ($cotisation, $montant) {
                $federation = $u->federation ?? $u->salle?->ligue?->federation;
                $ligue = $u->ligue ?? $u->salle?->ligue;
                $parts = preg_split('/\s+/', trim($u->name), 2);

                CotisationAnnuelleCeintureNoire::create([
                    'cotisation_annuelle_id' => $cotisation->id,
                    'origine' => 'GESTIONNAIRE',
                    'source_id' => $u->id,
                    'nom' => $parts[1] ?? ($parts[0] ?? $u->name),
                    'prenom' => isset($parts[1]) ? $parts[0] : '',
                    'sexe' => null,
                    'user_role' => $u->role instanceof \App\Shared\Enums\UserRole ? $u->role->value : (string) $u->role,
                    'grade_nom' => $u->grade?->nom_grade,
                    'federation_id' => $federation?->id,
                    'federation_nom' => $federation?->nom,
                    'ligue_id' => $ligue?->id,
                    'ligue_nom' => $ligue?->nom,
                    'salle_id' => $u->salle?->id,
                    'salle_nom' => $u->salle?->nom,
                    'montant_du' => $montant,
                    'montant_paye' => 0,
                    'reste_a_payer' => $montant,
                    'statut' => 'IMPAYE',
                ]);
            });
    }
}
