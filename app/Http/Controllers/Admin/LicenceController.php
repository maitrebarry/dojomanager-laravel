<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Disciple;
use App\Models\Grade;
use App\Models\Signature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Génération des cartes de licence, calquées à l'identique sur licence_regionale_tkd :
 *   - maître (salle)       → planche de cartes 86×54mm (admin/cartes/planches/imprimer)
 *   - ligue / fédération   → maquette pliable A4 « Kieup » (admin/cartes → Maquette Kieup)
 * Le libellé de licence s'adapte au rôle : LICENCE / LICENCE RÉGIONALE / LICENCE FÉDÉRALE.
 * Les disciples sont filtrés par le périmètre de l'utilisateur (visibleTo).
 */
class LicenceController extends Controller
{
    /**
     * Valeurs officielles par défaut (reprises de CardSettings de licence_regionale_tkd).
     * Public : réutilisé par d'autres documents officiels (ex. liste des candidats au
     * passage de grade dans DiscipleGradeController).
     */
    public const OFFICIAL = [
        'ministry' => "Ministère de la Jeunesse et des Sports chargé de l'Instruction Civique et de la Construction Citoyenne",
        'federation' => 'Fédération Malienne de Taekwondo',
        'motto' => 'Courtoisie - Loyauté - Persévérance - Maîtrise de soi - Combativité - Discipline',
    ];

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function disciples(Request $request): View
    {
        $ids = $this->parseIds($request->query('ids'));

        abort_if(empty($ids), 404, __('messages.licences.none_selected'));

        $disciples = Disciple::query()
            ->visibleTo($request->user())
            ->whereIn('id', $ids)
            ->with(['grade:id,nom_grade', 'salle.ligue.federation', 'salle.maitre', 'salle.maitreUser.grade'])
            ->orderBy('nom')->orderBy('prenom')
            ->get();

        abort_if($disciples->isEmpty(), 404, __('messages.licences.none_selected'));

        $meta = $request->user()->licenceMeta();
        $role = strtolower((string) ($request->user()->role->value ?? $request->user()->role));
        $issuerSignature = $this->currentSignature($request->user());

        $cards = $disciples->map(fn (Disciple $d) => $this->buildCard($d, $role, $meta, $issuerSignature))->all();

        $payload = [
            'cards' => $cards,
            'meta' => $meta,
            'official' => self::OFFICIAL,
            // Signature de repli (celle de l'émetteur connecté).
            'signature' => $issuerSignature?->signature_data,
        ];

        // Maître → planche de cartes ; ligue / fédération / superadmin → maquette Kieup.
        return $role === 'maitre'
            ? view('admin.licences.planche', $payload)
            : view('admin.licences.kieup', $payload);
    }

    /** Signature « courante » de l'émetteur (GET /admin/licences/signature). */
    public function signatureCurrent(Request $request): JsonResponse
    {
        $signature = $this->currentSignature($request->user());

        return response()->json([
            'signature_data' => $signature?->signature_data,
            'master_name' => $signature?->master_name,
            'master_grade' => $signature?->master_grade,
        ]);
    }

    /** Enregistre/actualise la signature dessinée par l'émetteur (POST /admin/licences/signature). */
    public function signatureSave(Request $request): JsonResponse
    {
        $data = $request->validate([
            'signature_data' => ['required', 'string'],
        ]);

        $user = $request->user();

        $signature = Signature::updateOrCreate(
            ['user_id' => $user->id],
            [
                'role' => strtolower((string) ($user->role->value ?? $user->role)),
                'federation_id' => $user->federation_id,
                'ligue_id' => $user->ligue_id,
                'salle_id' => $user->salle_id,
                'master_name' => $user->name,
                'master_grade' => $this->userGradeName($user),
                'signature_data' => $data['signature_data'],
            ]
        );

        return response()->json([
            'ok' => true,
            'signature_data' => $signature->signature_data,
            'master_name' => $signature->master_name,
            'master_grade' => $signature->master_grade,
        ]);
    }

    /** Résout la signature liée à l'émetteur (par utilisateur). */
    private function currentSignature($user): ?Signature
    {
        return Signature::where('user_id', $user->id)->latest('id')->first();
    }

    /**
     * Signature du responsable du périmètre de la carte :
     *   maître (salle) → signature du maître de la salle
     *   ligue          → signature du président de la ligue
     *   fédération      → signature du président de la fédération
     */
    private function signatureForScope(string $role, $salle, $ligue, $federation): ?Signature
    {
        $query = Signature::whereNotNull('signature_data');

        if ($role === 'maitre' && $salle) {
            return $query->where('salle_id', $salle->id)->latest('id')->first();
        }

        if ($role === 'ligue' && $ligue) {
            return $query->where('ligue_id', $ligue->id)->where('role', 'ligue')->latest('id')->first();
        }

        if ($federation) {
            return $query->where('federation_id', $federation->id)
                ->whereIn('role', ['federation', 'superadmin', 'admin'])
                ->latest('id')->first();
        }

        return null;
    }

    /** Nom du grade de l'utilisateur (pour le libellé signataire). */
    private function userGradeName($user): string
    {
        if (empty($user->grade_id)) {
            return '';
        }

        return (string) (Grade::whereKey($user->grade_id)->value('nom_grade') ?? '');
    }

    /** Normalise ?ids=1,2,3 ou ?ids[]=1&ids[]=2 en tableau d'entiers. */
    private function parseIds(mixed $raw): array
    {
        if (is_array($raw)) {
            $parts = $raw;
        } else {
            $parts = explode(',', (string) $raw);
        }

        return collect($parts)
            ->map(fn ($v) => (int) trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Prépare les données d'une carte pour les maquettes planche / Kieup. */
    private function buildCard(Disciple $d, string $role, array $meta, ?Signature $issuerSignature = null): array
    {
        $salle = $d->salle;
        $ligue = $salle?->ligue;
        $federation = $ligue?->federation;

        [$birthDate, $birthPlace] = $this->splitBirth($d);

        // Signature à apposer selon le type de licence :
        //   planche (salle)     → signature du maître de la salle
        //   régionale (ligue)   → signature du président de la ligue
        //   fédérale (fédér.)   → signature du président de la fédération
        $signature = $this->signatureForScope($role, $salle, $ligue, $federation) ?? $issuerSignature;

        return [
            'nom' => $d->nom,
            'prenom' => $d->prenom,
            'full_name' => $d->full_name,
            'gender' => $d->sexe === 'F' ? 'Féminin' : ($d->sexe === 'M' ? 'Masculin' : ''),
            'birth_date' => $birthDate,
            'birth_place' => $birthPlace,
            'adresse' => $d->adresse ?? '',
            'reference' => $d->nmle ?: ('REF-' . $d->id),
            'grade' => $d->grade?->nom_grade ?? '',
            'phone' => $d->telephone ?? '',
            'salle' => $salle?->nom ?? '',
            'ligue' => $ligue?->nom ?? '',
            'region' => $ligue?->region ?? '',
            'federation' => $federation?->nom ?: self::OFFICIAL['federation'],
            // Carte de salle (maître) : précise la salle concernée, ex. « LICENCE DE LA
            // SALLE DOJO CENTRAL ». Ligue/fédération gardent leur libellé générique.
            'license_label' => ($role === 'maitre' && $salle)
                ? $meta['badge_type'] . ' DE LA SALLE ' . mb_strtoupper($salle->nom)
                : $meta['badge_type'],
            'photo' => $this->photoData($d),
            'signature' => $signature?->signature_data,
            'signer' => $meta['signer'],
            'signer_name' => $signature?->master_name ?: ($salle?->maitre_display_name ?? ''),
            'signer_grade' => $signature?->master_grade ?: ($salle?->maitre_display_grade ?? ''),
        ];
    }

    /** Sépare date/lieu de naissance ("12/01/2000 à Bamako"). */
    private function splitBirth(Disciple $d): array
    {
        $date = $d->date_naissance ? $d->date_naissance->format('d/m/Y') : '';
        $place = '';

        if (!empty($d->date_lieu_naissance)) {
            $parts = preg_split('/\s+à\s+/iu', (string) $d->date_lieu_naissance, 2);
            if (count($parts) === 2) {
                $place = trim($parts[1]);
                if ($date === '') {
                    $date = trim($parts[0]);
                }
            } elseif ($date === '') {
                $date = trim($d->date_lieu_naissance);
            }
        }

        return [$date, $place];
    }

    /** Photo en data URI (embarquée pour l'impression), sinon null. */
    private function photoData(Disciple $d): ?string
    {
        if (empty($d->photo)) {
            return null;
        }

        if (str_starts_with($d->photo, 'http') || str_starts_with($d->photo, 'data:')) {
            return $d->photo;
        }

        try {
            if (Storage::disk('public')->exists($d->photo)) {
                $bytes = Storage::disk('public')->get($d->photo);
                $mime = Storage::disk('public')->mimeType($d->photo) ?: 'image/jpeg';

                return 'data:' . $mime . ';base64,' . base64_encode($bytes);
            }
        } catch (\Throwable) {
            // Ignore : on retombe sur le placeholder de la vue.
        }

        return null;
    }
}
