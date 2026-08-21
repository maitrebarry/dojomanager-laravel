<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Concerns\GeneratesThermalPdf;
use App\Http\Controllers\Controller;
use App\Models\Cotisation;
use App\Models\Disciple;
use App\Models\Salle;
use App\Models\Signature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MensualiteController extends Controller
{
    use AuthorizesScope;
    use GeneratesThermalPdf;

    public function __construct()
    {
        // Mensualités : accès à tout utilisateur admin (données filtrées par le périmètre).
        // Le menu ne l'expose qu'au rôle MAITRE (cf. sidebar), fidèle au backend Spring.
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('role:maitre'); // App.jsx : /mensualites réservé au MAITRE
    }

    public function index(Request $request): View
    {
        $annee = (int) $request->query('annee', now()->year);
        $mois = (int) $request->query('mois', now()->month);
        $montantAttendu = $request->filled('montant') ? (float) $request->query('montant') : null;
        $search = trim((string) $request->query('search'));

        // Auto-génère les mensualités de la période pour chaque disciple actif du périmètre
        // (fidèle à fetchMensualites de Mensualites.jsx : une ligne par disciple).
        $disciples = Disciple::active()->visibleTo($request->user())->with('salle:id,nom,mensualite')->get();
        foreach ($disciples as $d) {
            $montant = $montantAttendu ?? (float) ($d->salle->mensualite ?? 0);
            Cotisation::firstOrCreate(
                ['disciple_id' => $d->id, 'mois' => $mois, 'annee' => $annee],
                ['montant' => $montant, 'montant_paye' => 0, 'reste_a_payer' => $montant, 'statut' => 'IMPAYE']
            );
        }

        $cotisations = Cotisation::query()
            ->visibleTo($request->user())
            ->with(['disciple:id,nom,prenom,nom_complet,salle_id', 'disciple.salle:id,nom', 'paiements:id,cotisation_id,created_at'])
            ->where('annee', $annee)
            ->where('mois', $mois)
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->query('statut')))
            ->when($search !== '', fn ($q) => $q->whereHas('disciple', fn ($d) => $d->where('nom_complet', 'like', "%{$search}%")))
            ->get()
            ->sortBy(fn ($c) => $c->disciple?->full_name)
            ->values();

        $totaux = [
            'du' => $cotisations->sum('montant'),
            'paye' => $cotisations->sum('montant_paye'),
            'reste' => $cotisations->sum('reste_a_payer'),
        ];

        // Mensualité de référence (mono-salle pour un maître)
        $montantDefaut = $montantAttendu ?? (float) (Salle::visibleTo($request->user())->value('mensualite') ?? 10000);

        return view('admin.mensualites.index', [
            'cotisations' => $cotisations,
            'salles' => Salle::visibleTo($request->user())->orderBy('nom')->get(['id', 'nom']),
            'annee' => $annee,
            'mois' => $mois,
            'montantAttendu' => $montantDefaut,
            'search' => $search,
            'statut' => $request->query('statut', ''),
            'moisLabels' => Cotisation::MOIS,
            'totaux' => $totaux,
            'page_title' => __('messages.cotisations.title'),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'salle_id' => ['required', 'exists:salles,id'],
            'mois' => ['required', 'integer', 'between:1,12'],
            'annee' => ['required', 'integer', 'min:2000', 'max:2100'],
            'montant' => ['nullable', 'numeric', 'min:0'],
        ]);

        $salle = Salle::findOrFail($validated['salle_id']);
        $this->guardScope($salle);
        $montant = $validated['montant'] ?? $salle->mensualite ?? 0;

        $created = 0;
        $disciples = Disciple::active()->where('salle_id', $salle->id)->get();

        foreach ($disciples as $disciple) {
            $cotisation = Cotisation::firstOrNew([
                'disciple_id' => $disciple->id,
                'mois' => $validated['mois'],
                'annee' => $validated['annee'],
            ]);

            if (!$cotisation->exists) {
                $cotisation->montant = $montant;
                $cotisation->montant_paye = 0;
                $cotisation->reste_a_payer = $montant;
                $cotisation->statut = 'IMPAYE';
                $cotisation->save();
                $created++;
            }
        }

        return redirect()
            ->route('admin.mensualites.index', ['salle' => $salle->id, 'mois' => $validated['mois'], 'annee' => $validated['annee']])
            ->with('success', __('messages.cotisations.generated', ['count' => $created]));
    }

    public function pay(Request $request, Cotisation $cotisation): RedirectResponse
    {
        $this->guardScope($cotisation);
        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
            'mode_paiement' => ['required', 'string', 'max:30'],
            'reference_paiement' => ['nullable', 'string', 'max:80'],
            'date_paiement' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['created_by_user_id'] = auth()->id();
        $cotisation->paiements()->create($validated);
        $cotisation->recompute();

        // La liste consomme ce flash pour envoyer automatiquement le reçu par WhatsApp
        // en tâche de fond (cf. WhatsappBridge.sendFromUrl dans mensualites/index.blade.php),
        // sans quitter la page ni perdre les filtres en cours.
        return back()
            ->with('success', __('messages.cotisations.payment_recorded'))
            ->with('autoSendCotisation', $cotisation->id);
    }

    /** Paiement groupé : verse un montant à chaque cotisation sélectionnée (plafonné au reste). */
    public function bulkPay(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'string'],
            'montant' => ['required', 'numeric', 'min:0.01'],
        ]);

        $ids = array_filter(array_map('intval', explode(',', $validated['ids'])));
        $count = 0;
        $paidIds = [];

        Cotisation::query()->visibleTo($request->user())->whereIn('id', $ids)->get()
            ->each(function (Cotisation $c) use ($validated, $request, &$count, &$paidIds) {
                $reste = (float) $c->reste_a_payer;
                if ($c->statut === 'PAYE' || $reste <= 0) {
                    return;
                }
                $montant = min((float) $validated['montant'], $reste);
                $c->paiements()->create([
                    'montant' => $montant,
                    'mode_paiement' => 'ESPECES',
                    'date_paiement' => now()->toDateString(),
                    'created_by_user_id' => auth()->id(),
                ]);
                $c->recompute();
                $count++;
                $paidIds[] = $c->id;
            });

        // La liste envoie automatiquement chaque reçu par WhatsApp en tâche de fond
        // (cf. WhatsappBridge.sendFromUrl dans mensualites/index.blade.php).
        return back()
            ->with('success', __('messages.cotisations.bulk_done', ['count' => $count]))
            ->with('autoSendCotisations', $paidIds);
    }

    public function destroy(Cotisation $cotisation): RedirectResponse
    {
        $this->guardScope($cotisation);
        $cotisation->delete();

        return back()->with('success', __('messages.cotisations.deleted'));
    }

    public function receipt(Cotisation $cotisation): View
    {
        $this->guardScope($cotisation);
        $cotisation->load(['disciple.salle.maitre', 'disciple.salle.maitreUser.grade', 'paiements']);

        return view('admin.mensualites.receipt', [
            'cotisation' => $cotisation,
            'signature' => Signature::forSalle($cotisation->disciple?->salle_id),
        ]);
    }

    public function receiptPdf(Cotisation $cotisation)
    {
        $this->guardScope($cotisation);
        $cotisation->load(['disciple.salle.maitre', 'disciple.salle.maitreUser.grade', 'paiements']);

        return $this->downloadThermalPdf(
            'admin.mensualites.receipt_pdf',
            ['cotisation' => $cotisation, 'signature' => Signature::forSalle($cotisation->disciple?->salle_id)],
            'recu-cotisation-' . $cotisation->id . '.pdf'
        );
    }
}
