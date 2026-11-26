<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Disciple;
use App\Models\DiscipleGradeHistorique;
use App\Models\Grade;
use App\Models\Maitre;
use App\Models\Salle;
use App\Services\UserService;
use App\Support\DiscipleDuplicates;
use App\Support\MatriculeGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Collecte des informations des cartes de licence auprès des salles :
 *  - fiche PDF par salle à remettre au maître (disciples connus pré-remplis, cases à compléter,
 *    lignes vides pour les nouveaux) ;
 *  - saisie rapide, en tableau, de ce que le maître a rempli ;
 *  - maîtres des salles : affectation rapide (fiche maître et, au besoin, compte de connexion).
 */
class PratiquantSaisieController extends Controller
{
    private const BLANK_ROWS = 8;

    /** Avertissement de l'import : la fiche donne un autre maître que celui de la salle. */
    private ?string $maitreConflict = null;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:DISCIPLE_READ');
        $this->middleware('permission:DISCIPLE_UPDATE')->only(['saisie', 'saveSaisie', 'importExcel', 'importAny']);
        $this->middleware('permission:MAITRE_CREATE')->only(['storeMaitre']);
    }

    /** Fiche à remplir : ?salle=id (une salle) ou toutes les salles du périmètre, une par page. */
    public function fichePdf(Request $request)
    {
        $salles = Salle::visibleTo($request->user())
            ->when($request->integer('salle'), fn ($q, $id) => $q->whereKey($id))
            ->with(['ligue.federation', 'maitre', 'maitreUser.grade'])
            ->orderBy('nom')->get();
        abort_if($salles->isEmpty(), 404);

        $disciples = Disciple::whereIn('salle_id', $salles->pluck('id'))->whereNull('archived_at')
            ->with('grade:id,nom_grade')->orderBy('prenom')->orderBy('nom')->get()->groupBy('salle_id');

        return Pdf::loadView('admin.pratiquants.fiche_pdf', [
            'salles' => $salles,
            'disciples' => $disciples,
            'blankRows' => self::BLANK_ROWS,
            'grades' => $this->grades($salles->first()),
        ])->setPaper('a4', 'landscape')
            ->stream('fiche-licences-' . ($salles->count() === 1 ? \Illuminate\Support\Str::slug($salles->first()->nom) : 'salles') . '.pdf');
    }

    /** Saisie rapide d'une salle : un tableau, une ligne par disciple, plus des lignes pour les nouveaux. */
    public function saisie(Request $request): View
    {
        $salles = Salle::visibleTo($request->user())->orderBy('nom')->get(['id', 'nom']);
        $salle = $request->integer('salle') ? Salle::visibleTo($request->user())->with(['maitre', 'maitreUser', 'ligue.federation'])->find($request->integer('salle')) : null;

        return view('admin.pratiquants.saisie', [
            'salles' => $salles,
            'salle' => $salle,
            'disciples' => $salle ? $salle->disciples()->whereNull('archived_at')->orderBy('prenom')->orderBy('nom')->get() : collect(),
            'grades' => $this->grades($salle),
            'blankRows' => self::BLANK_ROWS,
            'page_title' => __('messages.pratiquants.saisie_title'),
        ]);
    }

    public function saveSaisie(Request $request, Salle $salle): RedirectResponse
    {
        abort_unless(Salle::visibleTo($request->user())->whereKey($salle->id)->exists(), 403);
        $data = $request->validate([
            'rows' => ['nullable', 'array'],
            'rows.*.prenom' => ['nullable', 'string', 'max:120'],
            'rows.*.nom' => ['nullable', 'string', 'max:120'],
            'rows.*.sexe' => ['nullable', 'in:M,F'],
            'rows.*.date_naissance' => ['nullable', 'string', 'max:20'],
            'rows.*.lieu' => ['nullable', 'string', 'max:150'],
            'rows.*.adresse' => ['nullable', 'string', 'max:255'],
            'rows.*.telephone' => ['nullable', 'string', 'max:40'],
            'rows.*.grade_id' => ['nullable', 'exists:grades,id'],
            'rows.*.date_grade' => ['nullable', 'string', 'max:20'],
        ]);

        [$updated, $created, $errors, $merged] = $this->applyRows($salle, $data['rows'] ?? [], $request->user()->hasPermission('DISCIPLE_CREATE'));

        ActivityLog::log(action: 'update', subject: 'Salle', subjectId: $salle->id,
            description: "Saisie rapide des fiches de licence — {$salle->nom} : {$updated} mis à jour, {$created} ajoutés");

        $redirect = redirect()->route('admin.pratiquants.saisie', ['salle' => $salle->id])
            ->with('success', __('messages.pratiquants.saisie_saved', ['updated' => $updated, 'created' => $created])
                . ($merged ? ' ' . __('messages.pratiquants.duplicates_merged', ['count' => $merged]) : ''));

        return $errors ? $redirect->with('warning', __('messages.pratiquants.saisie_errors', ['names' => implode(', ', $errors)])) : $redirect;
    }

    /**
     * Enregistre des lignes de fiche : clé = id du disciple (mise à jour) ou « newN » (nouveau).
     * Une ligne « nouvelle » qui correspond à un disciple de la salle le complète (pas de doublon).
     * Renvoie [mis à jour, ajoutés, noms des lignes refusées, lignes reconnues comme existantes].
     */
    private function applyRows(Salle $salle, array $rows, bool $canCreate): array
    {
        $existing = $salle->disciples()->whereNull('archived_at')->get()->keyBy('id');
        $updated = 0;
        $created = 0;
        $merged = 0;
        $errors = [];

        DB::transaction(function () use ($rows, &$existing, $salle, $canCreate, &$updated, &$created, &$merged, &$errors) {
            foreach ($rows as $key => $row) {
                $row = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $row);
                $isNew = str_starts_with((string) $key, 'new');
                if ($isNew && ($row['prenom'] ?? '') === '' && ($row['nom'] ?? '') === '') {
                    continue; // ligne vide
                }
                $label = trim(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? '')) ?: ('#' . $key);
                $birth = $this->parseDate($row['date_naissance'] ?? null);
                $gradeDate = $this->parseDate($row['date_grade'] ?? null);
                if ($birth === false || $gradeDate === false) {
                    $errors[] = $label;
                    continue;
                }
                $fields = array_filter([
                    'prenom' => $row['prenom'] ?? null,
                    'nom' => $row['nom'] ?? null,
                    'sexe' => $row['sexe'] ?? null,
                    'date_naissance' => $birth,
                    'date_lieu_naissance' => ($row['lieu'] ?? '') !== '' ? $row['lieu'] : null,
                    'adresse' => $row['adresse'] ?? null,
                    'telephone' => $row['telephone'] ?? null,
                    'grade_id' => $row['grade_id'] ?? null,
                    'date_obtention_grade' => $gradeDate,
                ], fn ($v) => $v !== null && $v !== '');

                // Même personne déjà dans la salle (même prénom et nom, naissance compatible) :
                // la ligne « nouvelle » complète sa fiche au lieu de créer un doublon.
                if ($isNew && !empty($fields['prenom']) && !empty($fields['nom'])
                    && ($same = DiscipleDuplicates::find($salle->id, $fields['prenom'], $fields['nom'], $fields['date_naissance'] ?? null))) {
                    $isNew = false;
                    $key = $same->id;
                    $existing[$same->id] ??= Disciple::find($same->id);
                    $merged++;
                }

                if ($isNew) {
                    if (!$canCreate || empty($fields['prenom']) || empty($fields['nom'])) {
                        $errors[] = $label;
                        continue;
                    }
                    $disciple = Disciple::create($fields + [
                        'nom_complet' => $fields['prenom'] . ' ' . $fields['nom'],
                        'date_inscription' => now()->toDateString(),
                        'salle_id' => $salle->id,
                        'nmle' => MatriculeGenerator::nextForLigue($salle->ligue),
                    ]);
                    $created++;
                } elseif ($disciple = $existing[(int) $key] ?? null) {
                    // Simple différence de majuscules (NOM écrit en capitales sur la fiche) : on garde l'écriture enregistrée.
                    $same = fn ($a, $b) => mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $a))) === mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $b)));
                    foreach (['prenom', 'nom'] as $field) {
                        if (isset($fields[$field]) && $same($fields[$field], $disciple->{$field})) {
                            unset($fields[$field]);
                        }
                    }
                    // Lieu enregistré « date à lieu » : inchangé si le lieu est le même.
                    if (isset($fields['date_lieu_naissance']) && $same($fields['date_lieu_naissance'], preg_replace('/^.*\s+à\s+/iu', '', (string) $disciple->date_lieu_naissance))) {
                        unset($fields['date_lieu_naissance']);
                    }
                    // Année seule (01/01) de la même année qu'une date précise déjà connue : on garde la date précise.
                    if (isset($fields['date_naissance']) && $disciple->date_naissance && str_ends_with($fields['date_naissance'], '-01-01')
                        && (int) substr($fields['date_naissance'], 0, 4) === $disciple->date_naissance->year) {
                        unset($fields['date_naissance']);
                    }
                    $disciple->fill($fields);
                    if ($disciple->isDirty(['prenom', 'nom', 'date_naissance'])
                        && DiscipleDuplicates::find($salle->id, $disciple->prenom, $disciple->nom, $disciple->date_naissance, $disciple->id)) {
                        $errors[] = $label . ' (' . __('messages.pratiquants.duplicate_short') . ')';
                        continue; // la correction en ferait le doublon d'un autre disciple de la salle
                    }
                    if ($disciple->isDirty(['prenom', 'nom'])) {
                        $disciple->nom_complet = $disciple->prenom . ' ' . $disciple->nom;
                    }
                    if (!$disciple->isDirty()) {
                        continue;
                    }
                    $disciple->save();
                    $updated++;
                } else {
                    continue;
                }

                // Grade et date d'obtention : alimentent le tableau des grades au verso de la carte.
                if ($disciple->grade_id && $disciple->date_obtention_grade) {
                    DiscipleGradeHistorique::updateOrCreate(
                        ['disciple_id' => $disciple->id, 'grade_id' => $disciple->grade_id],
                        ['date_obtention' => $disciple->date_obtention_grade]
                    );
                }
            }
        });

        return [$updated, $created, $errors, $merged];
    }

    /** Colonnes de la fiche Excel (onglet Disciples), dans l'ordre. */
    private const EXCEL_COLUMNS = ['Matricule', 'Prénom', 'NOM', 'Sexe (M/F)', 'Date de naissance (jj/mm/aaaa)', 'Lieu de naissance', 'Adresse (quartier, ville)', 'Téléphone', 'Grade actuel', 'Date du grade (jj/mm/aaaa)', 'Réf.'];
    private const EXCEL_HEADER_ROW = 4;
    private const EXCEL_BLANK_ROWS = 40;
    private const EXCEL_BLANK_ROWS_VIERGE = 150;
    private const BLANK_ROWS_VIERGE = 30;

    /** Fiche Excel d'une salle : disciples connus pré-remplis, lignes libres, onglet Maître. */
    public function ficheExcel(Request $request, Salle $salle)
    {
        abort_unless(Salle::visibleTo($request->user())->whereKey($salle->id)->exists(), 403);
        $salle->load(['ligue.federation', 'maitre', 'maitreUser.grade']);

        return $this->workbook($salle, false);
    }

    /** Fiche vierge (PDF ou Excel) pour une salle pas encore dans le système : le maître écrit tout. */
    public function ficheVierge(Request $request)
    {
        $salle = $this->blankSalle($request);

        if ($request->query('format') === 'pdf') {
            return Pdf::loadView('admin.pratiquants.fiche_pdf', [
                'salles' => collect([$salle]),
                'disciples' => collect(),
                'blankRows' => self::BLANK_ROWS_VIERGE,
                'grades' => $this->grades($salle),
                'blank' => true,
            ])->setPaper('a4', 'landscape')->stream('fiche-licences-vierge.pdf');
        }

        return $this->workbook($salle, true);
    }

    /** Salle non enregistrée, rattachée à la ligue de l'utilisateur (pour l'en-tête et les grades). */
    private function blankSalle(Request $request): Salle
    {
        $ligue = \App\Models\Ligue::with('federation')->find($request->user()->scopeContext()['ligue_id'] ?? null);
        $salle = new Salle(['nom' => '', 'ligue_id' => $ligue?->id]);

        return $salle->setRelation('ligue', $ligue)->setRelation('maitre', null)->setRelation('maitreUser', null);
    }

    private function workbook(Salle $salle, bool $blank)
    {
        $grades = $this->grades($salle);
        $disciples = $blank ? collect() : $salle->disciples()->whereNull('archived_at')->with('grade:id,nom_grade')->orderBy('prenom')->orderBy('nom')->get();

        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $book->getProperties()->setTitle('Fiche licences — ' . ($blank ? 'nouvelle salle' : $salle->nom))
            ->setKeywords($blank ? 'dojomanager-fiche-vierge' : 'dojomanager-salle:' . $salle->id);

        // Listes (grades) dans un onglet caché, pour les listes déroulantes.
        $lists = $book->createSheet()->setTitle('Listes');
        foreach ($grades->values() as $i => $g) {
            $lists->setCellValue('A' . ($i + 1), $g->nom_grade);
        }
        $dan = $grades->where('type_grade', 'DAN')->values();
        foreach ($dan as $i => $g) {
            $lists->setCellValue('B' . ($i + 1), $g->nom_grade);
        }
        $lists->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

        $sheet = $book->getSheet(0)->setTitle('Disciples');
        $sheet->setCellValue('A1', 'FICHE DE RENSEIGNEMENTS — LICENCE RÉGIONALE');
        $sheet->setCellValue('A2', ($blank ? 'Salle : (à écrire dans l\'onglet « Maître »)' : 'Salle : ' . mb_strtoupper($salle->nom))
            . '   —   ' . implode(' / ', array_filter([$salle->ligue?->federation?->nom, $salle->ligue?->nom])));
        $sheet->setCellValue('A3', $blank
            ? 'Écrivez d\'abord le nom de votre salle et vos informations dans l\'onglet « Maître », puis un disciple par ligne ici, et renvoyez ce fichier à la ligue.'
            : 'Vérifiez et complétez les informations, ajoutez vos nouveaux disciples sur les lignes vides, remplissez l\'onglet « Maître », puis renvoyez ce fichier à la ligue. Ne modifiez pas la colonne « Réf. ».');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F5132');
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9);
        $sheet->fromArray(self::EXCEL_COLUMNS, null, 'A' . self::EXCEL_HEADER_ROW);
        $sheet->getStyle('A' . self::EXCEL_HEADER_ROW . ':K' . self::EXCEL_HEADER_ROW)->getFont()->setBold(true);
        $sheet->getStyle('A' . self::EXCEL_HEADER_ROW . ':K' . self::EXCEL_HEADER_ROW)->getFill()->setFillType('solid')->getStartColor()->setRGB('E5E7EB');

        $row = self::EXCEL_HEADER_ROW + 1;
        foreach ($disciples as $d) {
            $sheet->fromArray([
                $d->nmle, $d->prenom, mb_strtoupper($d->nom), $d->sexe,
                $d->date_naissance?->format('d/m/Y'), preg_replace('/^.*\s+à\s+/iu', '', (string) $d->date_lieu_naissance),
                $d->adresse, $d->telephone, $d->grade?->nom_grade, $d->date_obtention_grade?->format('d/m/Y'), $d->id,
            ], null, 'A' . $row);
            $row++;
        }
        $last = $row + ($blank ? self::EXCEL_BLANK_ROWS_VIERGE : self::EXCEL_BLANK_ROWS) - 1;
        $sheet->getStyle("A{$row}:J{$last}")->getFill()->setFillType('solid')->getStartColor()->setRGB('FFF8E1'); // lignes des nouveaux
        $first = self::EXCEL_HEADER_ROW + 1;
        // Texte : dates et téléphones gardés tels quels (pas de conversion d'Excel).
        foreach (['E', 'H', 'J'] as $col) {
            $sheet->getStyle("{$col}{$first}:{$col}{$last}")->getNumberFormat()->setFormatCode('@');
        }
        $this->listValidation($sheet, "D{$first}:D{$last}", '"M,F"');
        if ($grades->isNotEmpty()) {
            $this->listValidation($sheet, "I{$first}:I{$last}", 'Listes!$A$1:$A$' . $grades->count());
        }
        $sheet->getStyle("K{$first}:K{$last}")->getFont()->getColor()->setRGB('9CA3AF');
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setWidth(match ($col) { 'A' => 16, 'B', 'C', 'F' => 18, 'G' => 26, 'E', 'J' => 15, 'D' => 9, 'K' => 7, default => 14 });
        }
        $sheet->freezePane('C' . $first);

        $maitre = $book->createSheet(1)->setTitle('Maître');
        $maitre->fromArray([
            ['INFORMATIONS DE LA SALLE ET DU MAÎTRE', ''],
            ['Salle', $salle->nom],
            ['Adresse de la salle', $salle->adresse],
            ['Prénom et nom', $salle->maitre_display_name],
            ['Téléphone', $salle->maitre?->telephone ?: $salle->maitreUser?->phone],
            ['Grade', $salle->maitre_display_grade],
            ['E-mail', $salle->maitre?->email],
        ]);
        $maitre->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $maitre->getStyle('A2:A7')->getFont()->setBold(true);
        $maitre->getStyle('B5')->getNumberFormat()->setFormatCode('@');
        $maitre->getStyle('B2:B7')->getFill()->setFillType('solid')->getStartColor()->setRGB('FFF8E1');
        $maitre->getColumnDimension('A')->setWidth(20);
        $maitre->getColumnDimension('B')->setWidth(45);
        if ($dan->isNotEmpty()) {
            $this->listValidation($maitre, 'B6', 'Listes!$B$1:$B$' . $dan->count());
        }
        $book->setActiveSheetIndex(0);

        return response()->streamDownload(
            fn () => (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output'),
            'fiche-licences-' . ($blank ? 'vierge' : \Illuminate\Support\Str::slug($salle->nom)) . '.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Import d'une fiche Excel renvoyée par le maître : disciples (Réf. → mise à jour, sinon
     * matricule ou même prénom/nom dans la salle, sinon nouveau) et informations du maître.
     */
    public function importExcel(Request $request, Salle $salle): RedirectResponse
    {
        abort_unless(Salle::visibleTo($request->user())->whereKey($salle->id)->exists(), 403);
        $book = $this->loadBook($request);
        // Fiche d'une autre salle : refusée (l'identifiant de la salle est dans les propriétés du fichier).
        if (preg_match('/dojomanager-salle:(\d+)/', (string) $book->getProperties()->getKeywords(), $m) && (int) $m[1] !== $salle->id) {
            $other = Salle::find((int) $m[1]);
            throw ValidationException::withMessages(['fichier' => __('messages.pratiquants.import_other_salle', ['salle' => $other?->nom ?? '#' . $m[1]])]);
        }

        return $this->importInto($book, $salle, $request);
    }

    /**
     * Import d'une fiche sans choisir la salle : fiche d'une salle connue (identifiant dans le
     * fichier), ou fiche vierge dont l'onglet « Maître » donne le nom de la salle — retrouvée
     * d'après son nom dans la ligue, ou créée.
     */
    public function importAny(Request $request): RedirectResponse
    {
        $book = $this->loadBook($request);
        if (preg_match('/dojomanager-salle:(\d+)/', (string) $book->getProperties()->getKeywords(), $m)) {
            $salle = Salle::visibleTo($request->user())->find((int) $m[1]);
            abort_unless($salle, 403);

            return $this->importInto($book, $salle, $request);
        }

        $info = $this->maitreValues($book->getSheetByName('Maître'));
        $name = trim((string) ($info['salle'] ?? ''));
        if ($name === '') {
            throw ValidationException::withMessages(['fichier' => __('messages.pratiquants.import_no_salle')]);
        }
        $ligueId = $request->user()->scopeContext()['ligue_id'] ?? null;
        abort_unless($ligueId, 403, __('messages.pratiquants.ligue_only'));
        $key = \App\Services\Competition\KnownAthletes::clubKey($name);
        $salle = Salle::where('ligue_id', $ligueId)->get()->first(fn ($s) => \App\Services\Competition\KnownAthletes::clubKey($s->nom) === $key);
        $isNew = false;
        if (!$salle) {
            abort_unless($request->user()->hasPermission('SALLE_CREATE'), 403);
            $salle = Salle::create(['ligue_id' => $ligueId, 'nom' => $name, 'adresse' => ($info['adresse de la salle'] ?? '') ?: null, 'active' => true]);
            ActivityLog::log(action: 'create', subject: 'Salle', subjectId: $salle->id, description: 'Salle créée par import de fiche : ' . $salle->nom);
            $isNew = true;
        } elseif (!$salle->adresse && !empty($info['adresse de la salle'])) {
            $salle->update(['adresse' => $info['adresse de la salle']]);
        }

        return $this->importInto($book, $salle, $request,
            __($isNew ? 'messages.pratiquants.import_salle_created' : 'messages.pratiquants.import_salle_found', ['salle' => $salle->nom]));
    }

    private function loadBook(Request $request): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $request->validate(['fichier' => ['required', 'file', 'mimes:xlsx,xls,ods', 'max:10240']]);
        try {
            return \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('fichier')->getRealPath());
        } catch (\Throwable) {
            throw ValidationException::withMessages(['fichier' => __('messages.pratiquants.import_unreadable')]);
        }
    }

    private function importInto(\PhpOffice\PhpSpreadsheet\Spreadsheet $book, Salle $salle, Request $request, ?string $note = null): RedirectResponse
    {
        $sheet = $book->getSheetByName('Disciples') ?? $book->getSheet(0);

        $grades = $this->grades($salle)->mapWithKeys(fn ($g) => [$this->simple($g->nom_grade) => $g->id]);
        $existing = $salle->disciples()->whereNull('archived_at')->get();
        $byNmle = $existing->filter(fn ($d) => $d->nmle)->keyBy(fn ($d) => mb_strtoupper(trim($d->nmle)));
        $byName = $existing->keyBy(fn ($d) => $this->simple($d->prenom . ' ' . $d->nom));
        $rows = [];
        $unknownGrades = [];
        $n = 0;
        foreach ($sheet->toArray(null, true, false, false) as $index => $line) {
            if ($index < self::EXCEL_HEADER_ROW) {
                continue; // titre, consignes, en-têtes
            }
            $cell = fn (int $i) => trim((string) ($line[$i] ?? ''));
            if ($cell(1) === '' && $cell(2) === '') {
                continue;
            }
            $id = (int) $cell(10);
            $disciple = ($id && $existing->contains('id', $id) ? $existing->firstWhere('id', $id) : null)
                ?? ($cell(0) !== '' ? $byNmle[mb_strtoupper($cell(0))] ?? null : null)
                ?? $byName[$this->simple($cell(1) . ' ' . $cell(2))] ?? null;
            $gradeId = null;
            if ($cell(8) !== '') {
                $gradeId = $grades[$this->simple($cell(8))] ?? null;
                if (!$gradeId) {
                    $unknownGrades[] = $cell(8);
                }
            }
            $rows[$disciple ? $disciple->id : 'new' . (++$n)] = [
                'prenom' => $cell(1), 'nom' => $cell(2),
                'sexe' => in_array(mb_strtoupper($cell(3)), ['M', 'F'], true) ? mb_strtoupper($cell(3)) : null,
                'date_naissance' => $this->excelDate($line[4] ?? null), 'lieu' => $cell(5), 'adresse' => $cell(6),
                'telephone' => $cell(7), 'grade_id' => $gradeId, 'date_grade' => $this->excelDate($line[9] ?? null),
            ];
        }

        [$updated, $created, $errors, $merged] = $this->applyRows($salle, $rows, $request->user()->hasPermission('DISCIPLE_CREATE'));
        $maitreSaved = $this->importMaitre($book->getSheetByName('Maître'), $salle);

        ActivityLog::log(action: 'import', subject: 'Salle', subjectId: $salle->id,
            description: "Import de la fiche Excel — {$salle->nom} : {$updated} mis à jour, {$created} ajoutés" . ($maitreSaved ? ', maître renseigné' : ''));

        $redirect = redirect()->route('admin.pratiquants.saisie', ['salle' => $salle->id])
            ->with('success', ($note ? $note . ' ' : '') . __('messages.pratiquants.saisie_saved', ['updated' => $updated, 'created' => $created])
                . ($merged ? ' ' . __('messages.pratiquants.duplicates_merged', ['count' => $merged]) : '')
                . ($maitreSaved ? ' ' . __('messages.pratiquants.import_maitre', ['name' => $maitreSaved]) : ''));
        $warnings = array_filter([
            $errors ? __('messages.pratiquants.saisie_errors', ['names' => implode(', ', $errors)]) : null,
            $unknownGrades ? __('messages.pratiquants.import_unknown_grades', ['grades' => implode(', ', array_unique($unknownGrades))]) : null,
            $this->maitreConflict,
        ]);

        return $warnings ? $redirect->with('warning', implode(' ', $warnings)) : $redirect;
    }

    /** Onglet « Maître » : fiche Maître de la salle créée ou mise à jour. Renvoie son nom, ou null. */
    private function importMaitre(?\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, Salle $salle): ?string
    {
        if (!$sheet) {
            return null;
        }
        $values = $this->maitreValues($sheet);
        $name = $values['prenom et nom'] ?? '';
        if ($name === '') {
            return null;
        }
        // Salle qui a déjà un autre maître : on ne l'écrase pas depuis une fiche.
        $current = $salle->maitre_display_name;
        if ($current && $this->simple($current) !== $this->simple($name)) {
            $this->maitreConflict = __('messages.pratiquants.import_maitre_conflict', ['salle' => $salle->nom, 'current' => $current, 'name' => $name]);

            return null;
        }
        $maitre = $salle->maitre ?? new Maitre();
        $maitre->fill(array_filter([
            'nom_complet' => $name,
            'telephone' => $values['telephone'] ?? null,
            'grade' => $values['grade'] ?? null,
            'email' => filter_var($values['e-mail'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
        ], fn ($v) => $v !== null && $v !== ''))->save();
        $salle->update(['maitre_id' => $maitre->id]);

        return $name;
    }

    /** Onglet « Maître » : libellé simplifié (« salle », « prenom et nom »…) → valeur. */
    private function maitreValues(?\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): \Illuminate\Support\Collection
    {
        return !$sheet ? collect() : collect($sheet->toArray(null, true, false, false))
            ->mapWithKeys(fn ($line) => [$this->simple((string) ($line[0] ?? '')) => trim((string) ($line[1] ?? ''))]);
    }

    /** Date d'une cellule : texte « jj/mm/aaaa » gardé, nombre Excel converti. */
    private function excelDate($value): string
    {
        // Une année seule (« 2013 ») reste une année ; au-delà, c'est un numéro de date Excel.
        if (is_numeric($value) && $value > 3000 && $value < 80000) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('d/m/Y');
        }

        return trim((string) $value);
    }

    private function simple(string $text): string
    {
        return preg_replace('/\s+/', ' ', \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(trim($text))));
    }

    private function listValidation($sheet, string $range, string $formula): void
    {
        $validation = $sheet->getCell(explode(':', $range)[0])->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
            ->setAllowBlank(true)->setShowDropDown(true)->setShowErrorMessage(true)->setFormula1($formula);
        $validation->setSqref($range);
    }

    /** Maîtres des salles : qui a un maître, et formulaire rapide pour les autres. */
    public function maitres(Request $request): View
    {
        // Avancement du recensement : fiche complète pour la carte = sexe, date et lieu de
        // naissance, grade et photo renseignés.
        $complete = fn ($q) => $q->whereNull('archived_at')->whereNotNull('sexe')->whereNotNull('date_naissance')
            ->whereNotNull('date_lieu_naissance')->where('date_lieu_naissance', '!=', '')->whereNotNull('grade_id')
            ->whereNotNull('photo')->where('photo', '!=', '');
        $salles = Salle::visibleTo($request->user())->with(['maitre', 'maitreUser.grade'])
            ->withCount(['disciples' => fn ($q) => $q->whereNull('archived_at'), 'disciples as complete_count' => $complete])
            ->orderBy('nom')->get();

        return view('admin.pratiquants.maitres', [
            'salles' => $salles,
            'canCreateSalle' => $request->user()->hasPermission('SALLE_CREATE') && !empty($request->user()->scopeContext()['ligue_id']),
            'grades' => Grade::where('type_grade', 'DAN')->orderBy('niveau')->get(['id', 'nom_grade']),
            'canAccount' => $request->user()->hasPermission('UTILISATEUR_CREATE'),
            'page_title' => __('messages.pratiquants.maitres_title'),
        ]);
    }

    /** Nouvelle salle de la ligue (pas encore dans le système), avec son maître s'il est connu. */
    public function storeSalle(Request $request, UserService $users): RedirectResponse
    {
        $ligueId = $request->user()->scopeContext()['ligue_id'] ?? null;
        abort_unless($ligueId && $request->user()->hasPermission('SALLE_CREATE'), 403);
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'adresse' => ['nullable', 'string', 'max:255'],
        ]);
        $key = \App\Services\Competition\KnownAthletes::clubKey($data['nom']);
        if ($existing = Salle::where('ligue_id', $ligueId)->get()->first(fn ($s) => \App\Services\Competition\KnownAthletes::clubKey($s->nom) === $key)) {
            throw ValidationException::withMessages(['nom' => __('messages.pratiquants.salle_exists', ['salle' => $existing->nom])]);
        }
        $salle = Salle::create(['ligue_id' => $ligueId, 'nom' => trim($data['nom']), 'adresse' => $data['adresse'] ?? null, 'active' => true]);
        ActivityLog::log(action: 'create', subject: 'Salle', subjectId: $salle->id, description: 'Salle ajoutée au recensement : ' . $salle->nom);

        if ($request->filled('nom_complet')) {
            return $this->storeMaitre($request, $salle, $users);
        }

        return back()->with('success', __('messages.pratiquants.salle_created', ['salle' => $salle->nom]));
    }

    public function storeMaitre(Request $request, Salle $salle, UserService $users): RedirectResponse
    {
        abort_unless(Salle::visibleTo($request->user())->whereKey($salle->id)->exists(), 403);
        $data = $request->validate([
            'nom_complet' => ['required', 'string', 'max:120'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'compte' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:4', 'max:60'],
        ]);
        $grade = isset($data['grade_id']) ? Grade::find($data['grade_id']) : null;
        $withAccount = !empty($data['compte']) && $request->user()->hasPermission('UTILISATEUR_CREATE');
        if ($withAccount && empty($data['telephone'])) {
            throw ValidationException::withMessages(['telephone' => __('messages.pratiquants.maitre_phone_required')]);
        }
        $password = $data['password'] ?? null;
        if ($withAccount && !$password) {
            $password = (string) random_int(100000, 999999);
        }

        DB::transaction(function () use ($salle, $data, $grade, $withAccount, $password, $users) {
            $maitre = $salle->maitre ?? new Maitre();
            $maitre->fill(['nom_complet' => $data['nom_complet'], 'telephone' => $data['telephone'] ?? null, 'grade' => $grade?->nom_grade])->save();
            $salle->update(['maitre_id' => $maitre->id]);

            if ($withAccount && !$salle->maitreUser()->exists()) {
                $users->create([
                    'name' => $data['nom_complet'],
                    'phone' => $data['telephone'],
                    'password' => $password,
                    'role' => 'maitre',
                    'status' => 'active',
                    'salle_id' => $salle->id,
                    'ligue_id' => $salle->ligue_id,
                    'federation_id' => $salle->ligue?->federation_id,
                    'grade_id' => $grade?->id,
                ]);
            }
        });

        ActivityLog::log(action: 'update', subject: 'Salle', subjectId: $salle->id,
            description: "Maître affecté à {$salle->nom} : {$data['nom_complet']}" . ($withAccount ? ' (compte créé)' : ''));

        $redirect = back()->with('success', __('messages.pratiquants.maitre_saved', ['name' => $data['nom_complet'], 'salle' => $salle->nom]));

        // Identifiants affichés dans un encadré qui reste à l'écran (à recopier pour le maître).
        return $withAccount ? $redirect->with('maitre_account', __('messages.pratiquants.maitre_account', ['name' => $data['nom_complet'], 'phone' => $data['telephone'], 'password' => $password])) : $redirect;
    }

    /** « 12/03/2009 », « 12-03-2009 », « 2009-03-12 », « 2009 » (→ 01/01/2009). Null si vide, false si illisible. */
    private function parseDate(?string $value): string|null|false
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{2,4})$/', $value, $m)) {
            $year = strlen($m[3]) === 2 ? 2000 + (int) $m[3] - ((int) $m[3] > (int) date('y') ? 100 : 0) : (int) $m[3];

            return checkdate((int) $m[2], (int) $m[1], $year) ? sprintf('%04d-%02d-%02d', $year, $m[2], $m[1]) : false;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
        if (preg_match('/^(19|20)\d{2}$/', $value)) {
            return $value . '-01-01';
        }

        return false;
    }

    /** Grades KEUP puis DAN de la fédération de la salle. */
    private function grades(?Salle $salle)
    {
        return Grade::query()
            ->when($salle?->ligue?->federation_id, fn ($q, $id) => $q->where('federation_id', $id))
            ->orderByRaw("CASE type_grade WHEN 'KEUP' THEN 0 ELSE 1 END")->orderBy('niveau')
            ->get(['id', 'nom_grade', 'type_grade']);
    }
}
