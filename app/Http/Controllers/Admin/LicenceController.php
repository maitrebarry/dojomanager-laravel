<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CeintureNoireManuelle;
use App\Models\Disciple;
use App\Models\Grade;
use App\Models\Signature;
use App\Models\User;
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
        $userIds = $this->parseIds($request->query('user_ids'));
        $manuelleIds = $this->parseIds($request->query('manuelle_ids'));

        abort_if(empty($ids) && empty($userIds) && empty($manuelleIds), 404, __('messages.licences.none_selected'));

        $disciples = empty($ids) ? collect() : Disciple::query()
            ->visibleTo($request->user())
            ->whereIn('id', $ids)
            ->with(['grade:id,nom_grade,niveau', 'salle.ligue.federation', 'salle.maitre', 'salle.maitreUser.grade', 'gradeHistoriques.grade'])
            ->get();

        // Maîtres / responsables de ligue / fédération sélectionnés depuis l'onglet
        // Ceintures noires (leur propre grade DAN n'est jamais un Disciple).
        $gestionnaires = empty($userIds) ? collect() : User::query()
            ->visibleTo($request->user())
            ->whereIn('id', $userIds)
            ->whereIn('role', User::TENANT_ROLES)
            ->with(['grade:id,nom_grade,niveau', 'federation', 'ligue', 'salle.ligue.federation'])
            ->get();

        // Ceintures noires saisies manuellement (ni Disciple ni compte Utilisateur).
        $manuelles = empty($manuelleIds) ? collect() : CeintureNoireManuelle::query()
            ->visibleTo($request->user())
            ->whereIn('id', $manuelleIds)
            ->with(['grade:id,nom_grade,niveau', 'federation', 'ligue', 'salle.ligue.federation'])
            ->get();

        abort_if($disciples->isEmpty() && $gestionnaires->isEmpty() && $manuelles->isEmpty(), 404, __('messages.licences.none_selected'));

        $meta = $request->user()->licenceMeta();
        $role = strtolower((string) ($request->user()->role->value ?? $request->user()->role));
        $issuerSignature = $this->currentSignature($request->user());

        $cards = $disciples->map(fn (Disciple $d) => $this->buildCard($d, $role, $meta, $issuerSignature))
            ->concat($gestionnaires->map(fn (User $u) => $this->buildCardFromUser($u, $role, $meta, $issuerSignature)))
            ->concat($manuelles->map(fn (CeintureNoireManuelle $m) => $this->buildCardFromManuelle($m, $role, $meta, $issuerSignature)))
            ->sortBy('nom')
            ->values()
            ->all();

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

        [$birthDate, $birthPlace] = $this->splitBirth($d->date_naissance, $d->date_lieu_naissance);

        // Signature à apposer selon le type de licence :
        //   planche (salle)     → signature du maître de la salle
        //   régionale (ligue)   → signature du président de la ligue
        //   fédérale (fédér.)   → signature du président de la fédération
        $signature = $this->signatureForScope($role, $salle, $ligue, $federation) ?? $issuerSignature;

        $signerName = $signature?->master_name ?: ($salle?->maitre_display_name ?? '');
        $signerGrade = $signature?->master_grade ?: ($salle?->maitre_display_grade ?? '');

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
            'signer_name' => $signerName,
            'signer_grade' => $signerGrade,
            'grade_rows' => $this->gradeRows($d, $federation, $signature?->signature_data, $signerName, $signerGrade),
        ];
    }

    /**
     * Lignes du tableau des grades (verso de la carte planche) : grades KEUP de la
     * fédération, dans l'ordre (niveau croissant), avec la date d'obtention et la
     * signature/nom/grade du maître dès qu'un DiscipleGradeHistorique existe.
     */
    private function gradeRows(Disciple $d, $federation, ?string $signatureData, string $signerName, string $signerGrade): array
    {
        $keupGrades = $federation
            ? Grade::where('federation_id', $federation->id)
                ->where('type_grade', 'KEUP')
                ->orderBy('niveau')
                ->get(['id', 'nom_grade'])
            : collect();

        $historyByGrade = $d->gradeHistoriques->keyBy('grade_id');

        return $keupGrades->map(function (Grade $grade) use ($historyByGrade, $signatureData, $signerName, $signerGrade) {
            $entry = $historyByGrade->get($grade->id);

            return [
                'label' => $grade->nom_grade,
                'date' => $entry ? $entry->date_obtention->format('d/m/Y') : '',
                'signature' => $entry ? $signatureData : null,
                'signer_name' => $entry ? $signerName : '',
                'signer_grade' => $entry ? $signerGrade : '',
            ];
        })->all();
    }

    /**
     * Sépare date/lieu de naissance, utilisable pour Disciple ou CeintureNoireManuelle.
     * Cas normal : date_naissance est une vraie date, dateLieuNaissance ne contient que
     * le lieu. Compat rétro : anciennes données où ce champ texte combinait les deux
     * ("12/01/2000 à Bamako"), utilisé si date_naissance est vide.
     */
    private function splitBirth(?\Illuminate\Support\Carbon $dateNaissance, ?string $dateLieuNaissance): array
    {
        $date = $dateNaissance ? $dateNaissance->format('d/m/Y') : '';

        if (empty($dateLieuNaissance)) {
            return [$date, ''];
        }

        $parts = preg_split('/\s+à\s+/iu', $dateLieuNaissance, 2);
        if (count($parts) === 2) {
            return [$date !== '' ? $date : trim($parts[0]), trim($parts[1])];
        }

        return $date !== '' ? [$date, trim($dateLieuNaissance)] : [trim($dateLieuNaissance), ''];
    }

    /** Photo en data URI (embarquée pour l'impression), sinon null. */
    private function photoData(Disciple $d): ?string
    {
        return $this->fileToDataUri($d->photo);
    }

    /** Prépare les données d'une carte (régionale/fédérale) pour un maître/responsable. */
    private function buildCardFromUser(User $u, string $role, array $meta, ?Signature $issuerSignature = null): array
    {
        $salle = $u->salle;
        $ligue = $u->ligue ?? $salle?->ligue;
        $federation = $u->federation ?? $salle?->ligue?->federation;

        $signature = $this->signatureForScope($role, $salle, $ligue, $federation) ?? $issuerSignature;

        $signerName = $signature?->master_name ?: ($salle?->maitre_display_name ?? '');
        $signerGrade = $signature?->master_grade ?: ($salle?->maitre_display_grade ?? '');

        [$prenom, $nom] = $this->splitName($u->name);

        return [
            'nom' => $nom,
            'prenom' => $prenom,
            'full_name' => $u->name,
            'gender' => '',
            'birth_date' => '',
            'birth_place' => '',
            'adresse' => '',
            'reference' => 'GEST-' . $u->id,
            'grade' => $u->grade?->nom_grade ?? '',
            'phone' => $u->phone ?? '',
            'salle' => $salle?->nom ?? '',
            'ligue' => $ligue?->nom ?? '',
            'region' => $ligue?->region ?? '',
            'federation' => $federation?->nom ?: self::OFFICIAL['federation'],
            'license_label' => $meta['badge_type'],
            'photo' => $this->fileToDataUri($u->avatar),
            'signature' => $signature?->signature_data,
            'signer' => $meta['signer'],
            'signer_name' => $signerName,
            'signer_grade' => $signerGrade,
        ];
    }

    /** Prépare les données d'une carte pour une ceinture noire saisie manuellement. */
    private function buildCardFromManuelle(CeintureNoireManuelle $m, string $role, array $meta, ?Signature $issuerSignature = null): array
    {
        $salle = $m->salle;
        $ligue = $m->ligue ?? $salle?->ligue;
        $federation = $m->federation ?? $salle?->ligue?->federation;

        $signature = $this->signatureForScope($role, $salle, $ligue, $federation) ?? $issuerSignature;

        $signerName = $signature?->master_name ?: ($salle?->maitre_display_name ?? '');
        $signerGrade = $signature?->master_grade ?: ($salle?->maitre_display_grade ?? '');

        [$birthDate, $birthPlace] = $this->splitBirth($m->date_naissance, $m->date_lieu_naissance);

        return [
            'nom' => $m->nom,
            'prenom' => $m->prenom,
            'full_name' => $m->full_name,
            'gender' => $m->sexe === 'F' ? 'Féminin' : ($m->sexe === 'M' ? 'Masculin' : ''),
            'birth_date' => $birthDate,
            'birth_place' => $birthPlace,
            'adresse' => $m->adresse ?? '',
            'reference' => $m->nmle ?: ('MAN-' . $m->id),
            'grade' => $m->grade?->nom_grade ?? '',
            'phone' => $m->telephone ?? '',
            'salle' => $salle?->nom ?? '',
            'ligue' => $ligue?->nom ?? '',
            'region' => $ligue?->region ?? '',
            'federation' => $federation?->nom ?: self::OFFICIAL['federation'],
            'license_label' => $meta['badge_type'],
            'photo' => $this->fileToDataUri($m->photo_path),
            'signature' => $signature?->signature_data,
            'signer' => $meta['signer'],
            'signer_name' => $signerName,
            'signer_grade' => $signerGrade,
        ];
    }

    /** Sépare "Prénom Nom" en [prénom, nom] (le nom prend le reste de la chaîne). */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [$parts[0] ?? $fullName, $parts[1] ?? ''];
    }

    /** Convertit un chemin de fichier (disque public) ou une URL/data URI en data URI, sinon null. */
    private function fileToDataUri(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http') || str_starts_with($path, 'data:')) {
            return $path;
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                $bytes = Storage::disk('public')->get($path);
                $mime = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';

                return 'data:' . $mime . ';base64,' . base64_encode($bytes);
            }
        } catch (\Throwable) {
            // Ignore : on retombe sur le placeholder de la vue.
        }

        return null;
    }
}
