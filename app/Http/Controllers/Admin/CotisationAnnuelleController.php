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
        $this->middleware('permission:COTISATION_MANAGE')->only(['index', 'show', 'create', 'store', 'pay', 'destroy']);
    }

    public function index(): View
    {
        $cotisations = CotisationAnnuelle::query()
            ->visibleTo(request()->user())
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
            'cotisation' => new CotisationAnnuelle(['annee' => now()->year, 'type' => 'CEINTURE_NOIRE']),
            'page_title' => __('messages.cotisations_annuelles.add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'annee' => ['required', 'integer', 'min:2000', 'max:2100', 'unique:cotisations_annuelles,annee'],
            'montant' => ['required', 'numeric', 'min:0'],
        ]);

        $cotisation = CotisationAnnuelle::create([
            'annee' => $validated['annee'],
            'montant' => $validated['montant'],
            'type' => 'CEINTURE_NOIRE',
            'federation_id' => $request->user()->scopeContext()['federation_id'],
            'created_by_user_id' => auth()->id(),
        ]);

        $this->snapshotMembers($cotisation, $request->user());

        return redirect()->route('admin.cotisations-annuelles.show', $cotisation)
            ->with('success', __('messages.cotisations_annuelles.created'));
    }

    public function show(CotisationAnnuelle $cotisations_annuelle): View
    {
        abort_unless(
            CotisationAnnuelle::visibleTo(request()->user())->whereKey($cotisations_annuelle->getKey())->exists(),
            403
        );

        $membres = $cotisations_annuelle->membres()->orderBy('nom')->orderBy('prenom')->paginate(25);

        $totaux = [
            'du' => $cotisations_annuelle->membres()->sum('montant_du'),
            'paye' => $cotisations_annuelle->membres()->sum('montant_paye'),
            'reste' => $cotisations_annuelle->membres()->sum('reste_a_payer'),
        ];

        return view('admin.cotisations-annuelles.show', [
            'cotisation' => $cotisations_annuelle,
            'membres' => $membres,
            'totaux' => $totaux,
            'page_title' => __('messages.cotisations_annuelles.title') . ' ' . $cotisations_annuelle->annee,
        ]);
    }

    public function pay(Request $request, CotisationAnnuelleCeintureNoire $membre): RedirectResponse
    {
        abort_unless(
            CotisationAnnuelle::visibleTo($request->user())->whereKey($membre->cotisation_annuelle_id)->exists(),
            403
        );

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
        abort_unless(
            CotisationAnnuelle::visibleTo(request()->user())->whereKey($cotisations_annuelle->getKey())->exists(),
            403
        );

        $cotisations_annuelle->delete();

        return redirect()->route('admin.cotisations-annuelles.index')
            ->with('success', __('messages.cotisations_annuelles.deleted'));
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
