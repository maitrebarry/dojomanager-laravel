<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Competition;
use App\Models\CompetitionAthlete;
use App\Services\Competition\AthleteListParser;
use App\Services\Competition\CategoryAssigner;
use App\Services\Competition\DrawService;
use App\Services\Competition\PdfWithoutTextException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Liste des athlètes d'une compétition : saisie en tableau, import (Excel, CSV,
 * copier-coller) avec écran d'aperçu à corriger, et liste PDF à remplir au stylo.
 * La saisie en tableau et l'import passent tous deux par confirm().
 */
class CompetitionAthleteController extends Controller
{
    use AuthorizesScope;

    private const IMPORT_SESSION = 'competition_import';

    public function __construct(private DrawService $draws)
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:COMPETITION_MANAGE');
    }

    public function update(Request $request, Competition $competition, CompetitionAthlete $athlete): RedirectResponse
    {
        $this->guardScope($competition);
        $data = $this->validateAthlete($request, $competition);

        $old = $athlete->category;
        $athlete->fill($data);
        // Catégorie laissée vide : rangement automatique selon le poids, le sexe et l'âge.
        $new = $data['category_id']
            ? $competition->categories()->find($data['category_id'])
            : (new CategoryAssigner($competition))->categoryFor($athlete);

        $canceled = false;
        if ($old?->id !== $new?->id || $athlete->isDirty(['poids', 'club', 'tete_de_serie'])) {
            $canceled = $this->draws->beforeChange($old);
            if ($new && $new->id !== $old?->id) {
                $canceled = $this->draws->beforeChange($new) || $canceled;
            }
        }
        $athlete->category_id = $new?->id;
        $athlete->save();

        return $this->backToList($competition, __('messages.competitions.athlete_updated'), $canceled);
    }

    public function destroy(Competition $competition, CompetitionAthlete $athlete): RedirectResponse
    {
        $this->guardScope($competition);
        $canceled = $this->draws->beforeChange($athlete->category);
        $athlete->delete();

        return $this->backToList($competition, __('messages.competitions.athlete_deleted'), $canceled);
    }

    /**
     * Suppression en masse : les athlètes cochés (ids[]) ou toute la liste (tout=1), par ex.
     * pour reprendre un import raté. Les catégories au tirage validé ne sont pas touchées ;
     * un tirage provisoire devenu faux est annulé.
     */
    public function destroyMany(Request $request, Competition $competition): RedirectResponse
    {
        $this->guardScope($competition);
        $data = $request->validate([
            'tout' => ['nullable', 'boolean'],
            'ids' => ['required_without:tout', 'array'],
            'ids.*' => ['integer'],
        ], ['ids.required_without' => __('messages.competitions.delete_none_selected')]);

        $athletes = $competition->athletes()->with('category')
            ->when(empty($data['tout']), fn ($q) => $q->whereIn('id', $data['ids'] ?? []))
            ->get();
        [$locked, $deletable] = $athletes->partition(fn ($a) => $a->category?->tirage_valide);

        $canceled = false;
        foreach ($deletable->pluck('category')->filter()->unique('id') as $category) {
            $canceled = $this->draws->beforeChange($category) || $canceled;
        }
        CompetitionAthlete::whereIn('id', $deletable->pluck('id'))->delete();

        ActivityLog::log(
            action: 'delete_athletes',
            subject: 'Competition',
            subjectId: $competition->id,
            description: $deletable->count() . ' athlète(s) supprimé(s) de « ' . $competition->nom . ' »' . (empty($data['tout']) ? '' : ' (toute la liste)')
        );

        $redirect = $this->backToList($competition, __('messages.competitions.athletes_deleted', ['count' => $deletable->count()]), $canceled);

        return $locked->isNotEmpty()
            ? $redirect->with('warning', __('messages.competitions.athletes_locked_kept', ['count' => $locked->count()]))
            : $redirect;
    }

    /**
     * Pesée : enregistre les poids saisis (champ poids[id]) et range chaque athlète pesé
     * dans sa catégorie. Une catégorie au tirage validé n'est pas modifiée.
     */
    public function weighIn(Request $request, Competition $competition): RedirectResponse
    {
        $this->guardScope($competition);
        $request->validate(['poids' => ['required', 'array']]);

        $assigner = new CategoryAssigner($competition);
        $athletes = $competition->athletes()->with('category')->whereIn('id', array_keys($request->input('poids')))->get();
        $saved = 0;
        $invalid = [];
        $locked = [];
        $canceled = false;

        foreach ($athletes as $athlete) {
            $raw = str_replace([',', ' ', 'kg'], ['.', '', ''], trim((string) $request->input('poids.' . $athlete->id)));
            if ($raw === '' || ($athlete->poids !== null && (float) $raw === (float) $athlete->poids)) {
                continue; // rien saisi, ou poids inchangé
            }
            if (!is_numeric($raw) || (float) $raw < 10 || (float) $raw > 200) {
                $invalid[] = $athlete->fullName();
                continue;
            }

            $old = $athlete->category;
            $athlete->poids = (float) $raw;
            $new = $assigner->categoryFor($athlete);
            if ($old?->tirage_valide || $new?->tirage_valide) {
                $locked[] = $athlete->fullName();
                continue;
            }
            $canceled = $this->draws->beforeChange($old) || $canceled;
            if ($new?->id !== $old?->id) {
                $canceled = $this->draws->beforeChange($new) || $canceled;
            }
            $athlete->category_id = $new?->id;
            $athlete->save();
            $saved++;
        }

        $redirect = redirect()->route('admin.competitions.show', [$competition, 'tab' => 'pesee'])
            ->with('success', __('messages.competitions.weighed_saved', ['count' => $saved]));
        $warnings = [];
        if ($invalid) {
            $warnings[] = __('messages.competitions.weighed_invalid', ['names' => implode(', ', $invalid)]);
        }
        if ($locked) {
            $warnings[] = __('messages.competitions.weighed_locked', ['names' => implode(', ', $locked)]);
        }
        if ($canceled) {
            $warnings[] = __('messages.competitions.draw_canceled');
        }

        return $warnings ? $redirect->with('warning', implode(' ', $warnings)) : $redirect;
    }

    /** Range automatiquement les athlètes « non classés » (après ajout de catégories). */
    public function assign(Competition $competition): RedirectResponse
    {
        $this->guardScope($competition);
        $count = (new CategoryAssigner($competition))->assignUnclassified();

        return back()->with('success', __('messages.competitions.assigned', ['count' => $count]));
    }

    /** Étape 1 de l'import : lit le fichier ou le texte collé, puis affiche l'aperçu. */
    public function import(Request $request, Competition $competition, AthleteListParser $parser): RedirectResponse
    {
        $this->guardScope($competition);
        $request->validate([
            'fichier' => ['nullable', 'file', 'mimes:xlsx,xls,csv,txt,ods,pdf', 'max:10240'],
            'texte' => ['nullable', 'string', 'max:200000'],
            'groupe' => ['nullable', 'string', Rule::in(array_keys(CompetitionController::groups($competition)))],
            'ordre' => ['nullable', Rule::in(array_keys(AthleteListParser::ORDERS))],
        ]);
        $order = $request->input('ordre') ?: 'modele';

        try {
            $file = $request->file('fichier');
            $rows = match (true) {
                $file === null => $parser->fromText((string) $request->input('texte'), $order),
                strtolower($file->getClientOriginalExtension()) === 'pdf' => $parser->fromPdf($file, $order),
                default => $parser->fromUpload($file, $order),
            };
        } catch (PdfWithoutTextException) {
            return back()->with('error', __('messages.competitions.import_pdf_scanned'));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', __('messages.competitions.import_unreadable'));
        }

        if ($rows === []) {
            return back()->with('error', __('messages.competitions.import_empty'));
        }

        // Colonne Groupe : signalée dès l'aperçu si le groupe n'existe pas dans la compétition.
        $groups = CompetitionController::groups($competition);
        foreach ($rows as $i => $row) {
            [, $error] = $this->resolveGroup($row, $request->input('groupe'), $groups);
            if ($error) {
                $rows[$i]['errors'][] = $error;
            }
        }

        session([self::IMPORT_SESSION . '.' . $competition->id => [
            'rows' => $rows,
            'groupe' => $request->input('groupe'),
        ]]);

        return redirect()->route('admin.competitions.athletes.preview', $competition);
    }

    public function preview(Competition $competition): View|RedirectResponse
    {
        $this->guardScope($competition);
        $import = session(self::IMPORT_SESSION . '.' . $competition->id);
        if (!$import) {
            return redirect()->route('admin.competitions.show', $competition);
        }

        $competition->load('categories');

        return view('admin.competitions.import_preview', [
            'competition' => $competition,
            'rows' => $import['rows'],
            'groupe' => $import['groupe'],
            'groups' => CompetitionController::groups($competition),
            'existing' => $competition->athletes()->get(['nom', 'prenom', 'poids'])
                ->map(fn ($a) => self::identity($a->nom, $a->prenom, $a->poids))->flip()->all(),
            'page_title' => __('messages.competitions.import_preview'),
        ]);
    }

    /** Étape 2 : enregistre les lignes corrigées de l'aperçu. Les lignes en erreur sont renvoyées. */
    public function confirm(Request $request, Competition $competition, AthleteListParser $parser): RedirectResponse
    {
        $this->guardScope($competition);
        $groups = CompetitionController::groups($competition);
        $request->validate([
            'rows' => ['required', 'array'],
            'groupe' => ['nullable', 'string', Rule::in(array_keys($groups))],
        ]);
        $groupKey = $request->input('groupe') ?: null;

        $assigner = new CategoryAssigner($competition);
        $existing = $competition->athletes()->get(['nom', 'prenom', 'poids'])
            ->map(fn ($a) => self::identity($a->nom, $a->prenom, $a->poids))->flip()->all();

        $added = 0;
        $skipped = 0;
        $invalid = [];
        $canceled = false;
        foreach ($request->input('rows') as $input) {
            // Ligne décochée dans l'aperçu, ou laissée vide dans le tableau de saisie.
            if (!empty($input['ignore']) || implode('', array_map('trim', Arr::only($input, AthleteListParser::COLUMNS))) === '') {
                continue;
            }
            $line = $parser->check(Arr::only($input, AthleteListParser::COLUMNS) + ['line' => (int) ($input['line'] ?? 0)]);
            [$lineGroup, $groupError] = $this->resolveGroup($line, $groupKey, $groups);
            if ($groupError) {
                $line['errors'][] = $groupError;
            }
            if ($line['errors']) {
                $invalid[] = $line;
                continue;
            }
            $identity = self::identity($line['nom'], $line['prenom'], $line['poids']);
            if (isset($existing[$identity])) {
                $skipped++;
                continue;
            }

            $birth = $parser->parseBirth($line['naissance']);
            $athlete = new CompetitionAthlete([
                'competition_id' => $competition->id,
                'nom' => mb_strtoupper($line['nom']),
                'prenom' => $line['prenom'],
                'poids' => $line['poids'] === '' ? null : (float) $line['poids'],
                'sexe' => $line['sexe'] ?: ($lineGroup ? (explode('|', $lineGroup)[1] ?: null) : null),
                'age' => $birth['age'],
                'date_naissance' => $birth['date_naissance'],
                'club' => $line['club'] ?: null,
                'groupe' => $lineGroup,
            ]);
            $category = $assigner->categoryFor($athlete, $lineGroup);
            $canceled = $this->draws->beforeChange($category) || $canceled;
            $athlete->category_id = $category?->id;
            $athlete->save();
            $existing[$identity] = true;
            $added++;
        }

        if ($invalid) {
            session([self::IMPORT_SESSION . '.' . $competition->id => ['rows' => $invalid, 'groupe' => $groupKey]]);

            return redirect()->route('admin.competitions.athletes.preview', $competition)
                ->with('warning', __('messages.competitions.import_partial', ['added' => $added, 'invalid' => count($invalid)]));
        }

        session()->forget(self::IMPORT_SESSION . '.' . $competition->id);

        $message = __('messages.competitions.import_done', ['added' => $added])
            . ($skipped ? ' ' . __('messages.competitions.import_duplicates', ['count' => $skipped]) : '');

        return $this->backToList($competition, $message, $canceled);
    }

    /**
     * Groupe d'une ligne importée. La colonne Groupe (« Juniors », « cadet »…) l'emporte sur
     * le groupe choisi pour toute la liste ; avec la colonne Sexe elle désigne « Juniors|F ».
     *
     * @param array<string, string> $groups groupes de la compétition (« Juniors|F » => libellé)
     * @return array{0: ?string, 1: ?string} [clé du groupe, erreur]
     */
    private function resolveGroup(array $line, ?string $listGroup, array $groups): array
    {
        $name = trim((string) ($line['groupe'] ?? ''));
        if ($name === '') {
            return [$listGroup ?: null, null];
        }

        $simple = fn (string $v) => rtrim(Str::lower(Str::ascii(trim($v))), 's');
        $candidates = array_values(array_filter(array_keys($groups), fn ($key) => $simple(explode('|', $key)[0]) === $simple($name)));
        if ($candidates === []) {
            return [null, __('messages.competitions.group_unknown', ['groupe' => $name])];
        }

        $sexe = $line['sexe'] ?? '';
        $sexe = in_array($sexe, ['M', 'F'], true) ? $sexe : ($listGroup ? (explode('|', $listGroup)[1] ?? '') : '');
        $bySex = [];
        foreach ($candidates as $key) {
            $bySex[explode('|', $key)[1]] = $key;
        }
        // Le groupe du bon sexe, sinon un groupe mixte, sinon le seul groupe existant.
        if ($sexe !== '' && isset($bySex[$sexe])) {
            return [$bySex[$sexe], null];
        }
        if (isset($bySex[''])) {
            return [$bySex[''], null];
        }
        if ($sexe === '' && count($bySex) === 1) {
            return [reset($bySex), null];
        }

        return [null, $sexe === ''
            ? __('messages.competitions.group_needs_sex', ['groupe' => $name])
            : __('messages.competitions.group_unknown', ['groupe' => $name . ' ' . ($sexe === 'F' ? 'filles' : 'garçons')])];
    }

    public function cancelImport(Competition $competition): RedirectResponse
    {
        $this->guardScope($competition);
        session()->forget(self::IMPORT_SESSION . '.' . $competition->id);

        return redirect()->route('admin.competitions.show', $competition);
    }

    /** Fichier Excel modèle : les colonnes attendues et deux lignes d'exemple. */
    public function template(Competition $competition): StreamedResponse
    {
        $this->guardScope($competition);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Athlètes');
        $sheet->fromArray([
            ['Nom', 'Prénom', 'Poids (kg)', 'Sexe (M/F)', 'Date de naissance ou âge', 'Club'],
            ['TRAORE', 'Awa', 52.5, 'F', '12/03/2009', 'Salle Université'],
            ['DIARRA', 'Moussa', 68, 'M', 17, ''],
        ]);
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getFont()->getColor()->setRGB('B91C1C');
        $sheet->setCellValue('H1', 'Obligatoire : Nom, Prénom, Poids (en rouge). Le reste est facultatif.');
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            'modele-liste-athletes.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /** Liste par catégorie à remplir au stylo (pesée, signature) ; ?category=id pour une seule. */
    public function listPdf(Request $request, Competition $competition)
    {
        $this->guardScope($competition);
        $competition->load(['categories', 'athletes' => fn ($q) => $q->orderBy('nom')->orderBy('prenom')]);

        $labels = CompetitionController::categoryLabels($competition);
        $byCategory = $competition->athletes->groupBy(fn ($a) => $a->category_id ?? 0);
        $sections = $competition->categories
            ->when($request->filled('category'), fn ($c) => $c->where('id', $request->integer('category')))
            ->filter(fn ($c) => isset($byCategory[$c->id]))
            ->map(fn ($c) => ['label' => $labels[$c->id], 'athletes' => $byCategory[$c->id]])
            ->values();
        if (!$request->filled('category') && isset($byCategory[0])) {
            foreach ([__('messages.competitions.unclassified') => $byCategory[0]->whereNotNull('poids'), __('messages.competitions.to_weigh') => $byCategory[0]->whereNull('poids')] as $label => $athletes) {
                if ($athletes->isNotEmpty()) {
                    $sections->push(['label' => $label, 'athletes' => $athletes->values()]);
                }
            }
        }
        abort_if($sections->isEmpty(), 404, __('messages.competitions.no_athletes'));

        return Pdf::loadView('admin.competitions.pdf.liste', [
            'competition' => $competition,
            'sections' => $sections,
            'year' => $competition->referenceYear(),
        ])->setPaper('a4', 'portrait')->stream('liste-athletes-' . $competition->id . '.pdf');
    }

    private function validateAthlete(Request $request, Competition $competition): array
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'poids' => ['nullable', 'numeric', 'min:10', 'max:200'],
            'sexe' => ['nullable', Rule::in(['M', 'F'])],
            'naissance' => ['nullable', 'string', 'max:20'],
            'club' => ['nullable', 'string', 'max:150'],
            'tete_de_serie' => ['nullable', 'integer', 'min:1', 'max:32'],
            'category_id' => ['nullable', Rule::exists('competition_categories', 'id')->where('competition_id', $competition->id)],
        ]);

        $birth = app(AthleteListParser::class)->parseBirth((string) ($data['naissance'] ?? ''));
        if ($birth === null) {
            throw ValidationException::withMessages(['naissance' => __('messages.competitions.birth_unreadable')]);
        }

        return [
            'nom' => mb_strtoupper(trim($data['nom'])),
            'prenom' => trim($data['prenom']),
            'poids' => $data['poids'] ?? null,
            'sexe' => $data['sexe'] ?? null,
            'age' => $birth['age'],
            'date_naissance' => $birth['date_naissance'],
            'club' => $data['club'] ?? null,
            'tete_de_serie' => $data['tete_de_serie'] ?? null,
            'category_id' => $data['category_id'] ?? null,
        ];
    }

    /**
     * Clé de doublon : nom + prénom + poids. Deux homonymes de groupes différents (ex. un
     * cadet et un junior « Mohamed TRAORE ») ont un poids différent ; la même liste
     * importée deux fois, le même poids.
     */
    public static function identity(string $nom, string $prenom, $poids = null): string
    {
        $grams = $poids === null || $poids === '' ? '' : (string) (int) round((float) str_replace(',', '.', (string) $poids) * 1000);

        return mb_strtolower(trim($nom) . '|' . trim($prenom)) . '|' . $grams;
    }

    private function backToList(Competition $competition, string $message, bool $drawCanceled): RedirectResponse
    {
        $redirect = redirect()->route('admin.competitions.show', [$competition, 'tab' => 'athletes'])->with('success', $message);

        return $drawCanceled ? $redirect->with('warning', __('messages.competitions.draw_canceled')) : $redirect;
    }
}
