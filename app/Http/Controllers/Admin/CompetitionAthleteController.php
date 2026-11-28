<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Competition;
use App\Models\CompetitionAthlete;
use App\Models\Disciple;
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

    /**
     * Disciples que l'utilisateur peut inscrire : ceux de son périmètre (salle, ligue,
     * fédération) et du périmètre de la compétition (salles de la ligue organisatrice,
     * sinon de la fédération), hors disciples archivés.
     */
    public static function disciplesFor(Competition $competition, $user)
    {
        return Disciple::visibleTo($user)
            ->whereNull('archived_at')
            ->when($competition->ligue_id,
                fn ($q) => $q->whereHas('salle', fn ($s) => $s->where('ligue_id', $competition->ligue_id)),
                fn ($q) => $q->when($competition->federation_id, fn ($q) => $q->whereHas('salle.ligue', fn ($l) => $l->where('federation_id', $competition->federation_id))));
    }

    /**
     * Ajoute les disciples cochés : prénom, nom, sexe, date de naissance et club (la salle)
     * sont repris de leur fiche, il ne reste que la pesée. Un disciple déjà inscrit n'est pas
     * doublé ; un athlète saisi à la main avec les mêmes prénom, nom et date de naissance est
     * relié à sa fiche.
     */
    public function addDisciples(Request $request, Competition $competition): RedirectResponse
    {
        $this->guardScope($competition);
        $groups = CompetitionController::groups($competition->load('categories'));
        $data = $request->validate([
            'disciples' => ['nullable', 'array'],
            'disciples.*' => ['integer'],
            'athletes' => ['nullable', 'array'],
            'athletes.*' => ['integer'],
            'groupe' => ['nullable', Rule::in(array_keys($groups))],
        ]);
        if (empty($data['disciples']) && empty($data['athletes'])) {
            throw ValidationException::withMessages(['disciples' => __('messages.competitions.disciples_none')]);
        }

        $disciples = self::disciplesFor($competition, $request->user())->with('salle')
            ->whereIn('id', $data['disciples'] ?? [])->get();
        $athletes = $competition->athletes()->get();
        $added = 0;
        $linked = 0;
        $already = 0;
        foreach ($disciples as $disciple) {
            if ($athletes->contains('disciple_id', $disciple->id)) {
                $already++;
                continue;
            }
            $same = $athletes->first(fn ($a) => !$a->disciple_id
                && Str::lower(Str::ascii($a->prenom . ' ' . $a->nom)) === Str::lower(Str::ascii($disciple->prenom . ' ' . $disciple->nom))
                && (!$a->date_naissance || !$disciple->date_naissance || $a->date_naissance->isSameDay($disciple->date_naissance)));
            if ($same) {
                $same->update(array_filter([
                    'disciple_id' => $disciple->id,
                    'sexe' => $same->sexe ?: $disciple->sexe,
                    'date_naissance' => $same->date_naissance ?: $disciple->date_naissance,
                    'club' => $same->club ?: $disciple->salle?->nom,
                ]));
                $linked++;
                continue;
            }
            $competition->athletes()->create([
                'disciple_id' => $disciple->id,
                'prenom' => $disciple->prenom,
                'nom' => $disciple->nom,
                'sexe' => in_array($disciple->sexe, ['M', 'F'], true) ? $disciple->sexe : null,
                'date_naissance' => $disciple->date_naissance,
                'club' => $disciple->salle?->nom,
                'groupe' => $data['groupe'] ?? null,
            ]);
            $added++;
        }
        // Athlètes des compétitions précédentes : recopiés sans poids (à peser), sauf s'ils
        // sont déjà inscrits ici (même prénom, nom et année de naissance).
        $year = $competition->referenceYear();
        $current = $competition->athletes()->get()->map(fn ($a) => [
            'key' => \App\Services\Competition\KnownAthletes::nameKey($a->prenom, $a->nom),
            'year' => $a->date_naissance?->year ?? ($a->age ? $year - $a->age : null),
        ]);
        $previous = \App\Services\Competition\KnownAthletes::previousAthletes($competition, $request->user())
            ->whereIn('id', $data['athletes'] ?? [])->get();
        foreach ($previous as $old) {
            $person = ['key' => \App\Services\Competition\KnownAthletes::nameKey($old->prenom, $old->nom), 'year' => \App\Services\Competition\KnownAthletes::birthYear($old)];
            if ($current->contains(fn ($c) => \App\Services\Competition\KnownAthletes::samePerson($c, $person))) {
                $already++;
                continue;
            }
            $competition->athletes()->create([
                'prenom' => $old->prenom,
                'nom' => $old->nom,
                'sexe' => $old->sexe,
                'date_naissance' => $old->date_naissance,
                'age' => $old->date_naissance ? null : ($person['year'] ? $year - $person['year'] : null),
                'club' => $old->club,
                'groupe' => $data['groupe'] ?? (isset($groups[$old->groupe]) ? $old->groupe : null),
            ]);
            $current->push($person);
            $added++;
        }

        ActivityLog::log(action: 'create', subject: 'Competition', subjectId: $competition->id,
            description: $added . ' athlète(s) connu(s) inscrit(s) — ' . $competition->nom);

        $message = trim(implode(' ', array_filter([
            $added ? __('messages.competitions.disciples_added', ['count' => $added]) : null,
            $linked ? __('messages.competitions.disciples_linked', ['count' => $linked]) : null,
            $already ? __('messages.competitions.disciples_already', ['count' => $already]) : null,
        ]))) ?: __('messages.competitions.disciples_none');

        return redirect()->route('admin.competitions.show', [$competition, 'tab' => $added ? 'pesee' : 'athletes'])->with('success', $message);
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

        if ($old?->id !== $new?->id || $athlete->isDirty(['poids', 'club', 'tete_de_serie'])) {
            $this->draws->beforeChange($old);
            if ($new && $new->id !== $old?->id) {
                $this->draws->beforeChange($new);
            }
        }
        $athlete->category_id = $new?->id;
        $athlete->save();

        return $this->backToList($competition, __('messages.competitions.athlete_updated'));
    }

    public function destroy(Competition $competition, CompetitionAthlete $athlete): RedirectResponse
    {
        $this->guardScope($competition);
        $this->draws->beforeChange($athlete->category);
        $athlete->delete();

        return $this->backToList($competition, __('messages.competitions.athlete_deleted'));
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

        foreach ($deletable->pluck('category')->filter()->unique('id') as $category) {
            $this->draws->beforeChange($category);
        }
        CompetitionAthlete::whereIn('id', $deletable->pluck('id'))->delete();

        ActivityLog::log(
            action: 'delete_athletes',
            subject: 'Competition',
            subjectId: $competition->id,
            description: $deletable->count() . ' athlète(s) supprimé(s) de « ' . $competition->nom . ' »' . (empty($data['tout']) ? '' : ' (toute la liste)')
        );

        return $this->backToList(
            $competition,
            __('messages.competitions.athletes_deleted', ['count' => $deletable->count()]),
            $locked->isNotEmpty() ? [__('messages.competitions.athletes_locked_kept', ['count' => $locked->count()])] : []
        );
    }

    /**
     * Pesée : enregistre les poids saisis (champ poids[id]) et range chaque athlète pesé
     * dans sa catégorie. Une catégorie au tirage validé n'est pas modifiée.
     */
    /**
     * Pesée : ajout sur place d'un athlète absent de la liste (prénom, nom, sexe, âge ou
     * naissance, club, poids). Rangé aussitôt dans sa catégorie ; refusé s'il est déjà inscrit.
     */
    public function quickAdd(Request $request, Competition $competition): \Illuminate\Http\JsonResponse
    {
        $this->guardScope($competition);
        $groups = CompetitionController::groups($competition->load('categories'));
        $data = $request->validate([
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'sexe' => ['nullable', Rule::in(['M', 'F'])],
            'naissance' => ['nullable', 'string', 'max:20'],
            'club' => ['nullable', 'string', 'max:150'],
            'poids' => ['nullable', 'string', 'max:10'],
            'groupe' => ['nullable', Rule::in(array_keys($groups))],
        ]);
        $year = $competition->referenceYear();
        $birth = trim((string) ($data['naissance'] ?? ''));
        $date = null;
        $age = null;
        if (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/', $birth, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            $date = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        } elseif (preg_match('/^(19|20)\d{2}$/', $birth)) {
            $age = $year - (int) $birth;
        } elseif (preg_match('/^\d{1,2}$/', $birth)) {
            $age = (int) $birth;
        } elseif ($birth !== '') {
            throw ValidationException::withMessages(['naissance' => __('messages.competitions.quick_birth_invalid')]);
        }
        $poids = null;
        if (trim((string) ($data['poids'] ?? '')) !== '') {
            $raw = str_replace([',', ' ', 'kg'], ['.', '', ''], trim($data['poids']));
            if (!is_numeric($raw) || (float) $raw < 10 || (float) $raw > 200) {
                throw ValidationException::withMessages(['poids' => __('messages.competitions.quick_weight_invalid')]);
            }
            $poids = (float) $raw;
        }

        // Déjà inscrit (même prénom et nom, année de naissance compatible) : pas de doublon.
        $key = \App\Services\Competition\KnownAthletes::nameKey($data['prenom'], $data['nom']);
        $birthYear = $date ? (int) substr($date, 0, 4) : ($age ? $year - $age : null);
        $same = $competition->athletes()->get()->first(fn ($a) => \App\Services\Competition\KnownAthletes::samePerson(
            ['key' => \App\Services\Competition\KnownAthletes::nameKey($a->prenom, $a->nom), 'year' => $a->date_naissance?->year ?? ($a->age ? $year - $a->age : null)],
            ['key' => $key, 'year' => $birthYear]
        ));
        if ($same) {
            throw ValidationException::withMessages(['nom' => __('messages.competitions.quick_exists', ['name' => $same->fullName()])]);
        }

        $athlete = new CompetitionAthlete([
            'competition_id' => $competition->id,
            'prenom' => trim($data['prenom']),
            'nom' => trim($data['nom']),
            'sexe' => $data['sexe'] ?? null,
            'date_naissance' => $date,
            'age' => $date ? null : $age,
            'club' => trim((string) ($data['club'] ?? '')) ?: null,
            'poids' => $poids,
            'groupe' => $data['groupe'] ?? null,
        ]);
        $category = $poids !== null ? (new CategoryAssigner($competition))->categoryFor($athlete) : null;
        if ($category?->tirage_valide) {
            $category = null; // un tirage validé ne reçoit pas d'athlète : reste « non classé »
        }
        if ($category) {
            $this->draws->beforeChange($category);
        }
        $athlete->category_id = $category?->id;
        $athlete->save();
        $redraw = $this->draws->redrawCanceled();
        ActivityLog::log(action: 'create', subject: 'Competition', subjectId: $competition->id,
            description: 'Athlète ajouté à la pesée : ' . $athlete->fullName() . ' — ' . $competition->nom);

        $labels = CompetitionController::categoryLabels($competition->fresh()->load('categories'));

        return response()->json([
            'athlete' => [
                'id' => $athlete->id,
                'prenom' => $athlete->prenom,
                'nom' => $athlete->nom,
                'age' => $athlete->sportAge($year),
                'naissance' => $athlete->date_naissance?->format('d/m/Y') ?? ($athlete->age ? (string) $athlete->age : ''),
                'sexe' => $athlete->sexe,
                'club' => $athlete->club,
                'poids' => $poids !== null ? \App\Models\CompetitionCategory::formatKg($poids) : '',
                'category' => $category ? ($labels[$category->id] ?? '') : null,
                'group' => $category?->groupKey() ?? $athlete->groupe,
            ],
            'message' => __('messages.competitions.quick_added', ['name' => $athlete->fullName()]),
            'redraw' => $redraw,
        ]);
    }

    /**
     * Pesée : correction sur place du club ou de l'âge (âge, année ou date de naissance) d'un
     * athlète. Un nouvel âge peut changer son groupe : il est rangé à nouveau aussitôt.
     */
    public function quickUpdate(Request $request, Competition $competition, CompetitionAthlete $athlete): \Illuminate\Http\JsonResponse
    {
        $this->guardScope($competition);
        abort_unless($athlete->competition_id === $competition->id, 404);
        $data = $request->validate(['club' => ['nullable', 'string', 'max:150'], 'naissance' => ['nullable', 'string', 'max:20']]);
        $year = $competition->referenceYear();

        if ($request->has('club')) {
            $athlete->club = trim((string) ($data['club'] ?? '')) ?: null;
        }
        if ($request->has('naissance')) {
            $birth = trim((string) ($data['naissance'] ?? ''));
            if ($birth === '') {
                $athlete->date_naissance = null;
                $athlete->age = null;
            } elseif (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/', $birth, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                $athlete->date_naissance = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
                $athlete->age = null;
            } elseif (preg_match('/^(19|20)\d{2}$/', $birth)) {
                $athlete->date_naissance = null;
                $athlete->age = $year - (int) $birth;
            } elseif (preg_match('/^\d{1,2}$/', $birth)) {
                $athlete->date_naissance = null;
                $athlete->age = (int) $birth;
            } else {
                throw ValidationException::withMessages(['naissance' => __('messages.competitions.quick_birth_invalid')]);
            }
        }
        if (!$athlete->isDirty()) {
            return $this->athleteRow($competition, $athlete, null);
        }

        // Rangement à nouveau (le club compte pour la séparation des clubs au tirage).
        $old = $athlete->category;
        $new = $athlete->poids !== null ? (new CategoryAssigner($competition))->categoryFor($athlete) : $old;
        if ($old?->tirage_valide || ($new?->id !== $old?->id && $new?->tirage_valide)) {
            throw ValidationException::withMessages(['naissance' => __('messages.competitions.weighed_locked', ['names' => $athlete->fullName()])]);
        }
        $this->draws->beforeChange($old);
        if ($new && $new->id !== $old?->id) {
            $this->draws->beforeChange($new);
        }
        $athlete->category_id = $new?->id;
        $athlete->save();

        return $this->athleteRow($competition, $athlete->fresh(), $this->draws->redrawCanceled());
    }

    /** Ligne de pesée d'un athlète, pour la mise à jour sur place. */
    private function athleteRow(Competition $competition, CompetitionAthlete $athlete, ?string $redraw): \Illuminate\Http\JsonResponse
    {
        $labels = CompetitionController::categoryLabels($competition->fresh()->load('categories'));
        $athlete->load('category');

        return response()->json([
            'row' => [
                'id' => $athlete->id,
                'age' => $athlete->sportAge($competition->referenceYear()),
                'naissance' => $athlete->date_naissance?->format('d/m/Y') ?? ($athlete->age ? (string) $athlete->age : ''),
                'club' => $athlete->club,
                'poids' => $athlete->poids !== null ? \App\Models\CompetitionCategory::formatKg((float) $athlete->poids) : '',
                'category' => $athlete->category_id ? ($labels[$athlete->category_id] ?? '') : null,
                'locked' => (bool) $athlete->category?->tirage_valide,
                'group' => $athlete->category?->groupKey() ?? $athlete->groupe,
            ],
            'redraw' => $redraw,
        ]);
    }

    public function weighIn(Request $request, Competition $competition): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->guardScope($competition);
        $request->validate(['poids' => ['required', 'array']]);

        $assigner = new CategoryAssigner($competition);
        $athletes = $competition->athletes()->with('category')->whereIn('id', array_keys($request->input('poids')))->get();
        $saved = 0;
        $invalid = [];
        $locked = [];
        $status = []; // id => saved | invalid | locked | unchanged | cleared (pour l'enregistrement automatique)

        foreach ($athletes as $athlete) {
            $raw = str_replace([',', ' ', 'kg'], ['.', '', ''], trim((string) $request->input('poids.' . $athlete->id)));
            if ($raw === '' || ($athlete->poids !== null && (float) $raw === (float) $athlete->poids)) {
                $status[$athlete->id] = 'unchanged';
                continue; // rien saisi, ou poids inchangé
            }
            if (!is_numeric($raw) || (float) $raw < 10 || (float) $raw > 200) {
                $invalid[] = $athlete->fullName();
                $status[$athlete->id] = 'invalid';
                continue;
            }

            $old = $athlete->category;
            $athlete->poids = (float) $raw;
            $new = $assigner->categoryFor($athlete);
            if ($old?->tirage_valide || $new?->tirage_valide) {
                $locked[] = $athlete->fullName();
                $status[$athlete->id] = 'locked';
                continue;
            }
            $this->draws->beforeChange($old);
            if ($new?->id !== $old?->id) {
                $this->draws->beforeChange($new);
            }
            $athlete->category_id = $new?->id;
            $athlete->save();
            $saved++;
            $status[$athlete->id] = 'saved';
        }

        $redraw = $this->draws->redrawCanceled();

        // Enregistrement automatique (un poids à la fois) : réponse JSON pour mettre la ligne à jour.
        if ($request->wantsJson()) {
            $labels = CompetitionController::categoryLabels($competition->fresh()->load('categories'));
            $rows = $competition->athletes()->with('category')->whereIn('id', array_keys($status))->get()->map(fn ($a) => [
                'id' => $a->id,
                'status' => $status[$a->id],
                'poids' => $a->poids !== null ? \App\Models\CompetitionCategory::formatKg((float) $a->poids) : '',
                'category' => $a->category_id ? ($labels[$a->category_id] ?? '') : null,
                'locked' => (bool) $a->category?->tirage_valide,
                'group' => $a->category?->groupKey(),
                'message' => match ($status[$a->id]) {
                    'invalid' => __('messages.competitions.weighed_invalid', ['names' => $a->fullName()]),
                    'locked' => __('messages.competitions.weighed_locked', ['names' => $a->fullName()]),
                    default => null,
                },
            ])->values();

            return response()->json(['rows' => $rows, 'redraw' => $redraw]);
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
        if ($redraw) {
            $warnings[] = $redraw; // tirages provisoires refaits avec les nouveaux poids
        }

        return $warnings ? $redirect->with('warning', implode(' ', $warnings)) : $redirect;
    }

    /** Range automatiquement les athlètes « non classés » (après ajout de catégories). */
    public function assign(Competition $competition): RedirectResponse
    {
        $this->guardScope($competition);
        $count = (new CategoryAssigner($competition))->assignUnclassified($this->draws);
        $redirect = back()->with('success', __('messages.competitions.assigned', ['count' => $count]));
        $redraw = $this->draws->redrawCanceled();

        return $redraw ? $redirect->with('warning', $redraw) : $redirect;
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
            $this->draws->beforeChange($category);
            $athlete->category_id = $category?->id;
            $athlete->save();
            $existing[$identity] = true;
            $added++;
        }

        if ($invalid) {
            session([self::IMPORT_SESSION . '.' . $competition->id => ['rows' => $invalid, 'groupe' => $groupKey]]);
            $redraw = $this->draws->redrawCanceled();

            return redirect()->route('admin.competitions.athletes.preview', $competition)
                ->with('warning', trim(__('messages.competitions.import_partial', ['added' => $added, 'invalid' => count($invalid)]) . ' ' . $redraw));
        }

        session()->forget(self::IMPORT_SESSION . '.' . $competition->id);

        $message = __('messages.competitions.import_done', ['added' => $added])
            . ($skipped ? ' ' . __('messages.competitions.import_duplicates', ['count' => $skipped]) : '');

        return $this->backToList($competition, $message);
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

        // Groupes de la compétition (Cadets, Juniors…) : exemples et liste déroulante de la colonne G.
        $groupNames = $competition->categories()->pluck('groupe')->unique()->values()->all();
        $examples = [$groupNames[0] ?? 'Juniors', $groupNames[1] ?? $groupNames[0] ?? 'Seniors'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Athlètes');
        $sheet->fromArray([
            ['Prénom', 'Nom', 'Poids (kg)', 'Sexe (M/F)', 'Date de naissance ou âge', 'Club', 'Groupe'],
            ['Awa', 'TRAORE', 52.5, 'F', '12/03/2009', 'Salle Université', $examples[0]],
            ['Moussa', 'DIARRA', 68, 'M', 17, '', $examples[1]],
        ]);
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:B1')->getFont()->getColor()->setRGB('B91C1C');
        $sheet->setCellValue('I1', 'Obligatoire : Prénom, Nom (en rouge). Le poids peut être saisi ici ou à la pesée ; le reste est facultatif.');
        $sheet->setCellValue('I2', 'Groupe : ' . ($groupNames ? implode(', ', $groupNames) : 'Cadets, Juniors, Seniors…')
            . '. Vide = déduit de l\'âge et du sexe, ou choisi à l\'import pour toute la liste.');
        if ($groupNames) {
            // Liste déroulante sur la colonne Groupe (saisie libre refusée : le nom doit exister).
            $validation = $sheet->getCell('G2')->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                ->setAllowBlank(true)->setShowDropDown(true)->setShowErrorMessage(true)
                ->setErrorTitle('Groupe inconnu')->setError('Choisissez un groupe de la liste : ' . implode(', ', $groupNames))
                ->setFormula1('"' . implode(',', $groupNames) . '"');
            $validation->setSqref('G2:G1000');
        }
        foreach (range('A', 'G') as $column) {
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
        $competition->load(['categories', 'athletes' => fn ($q) => $q->orderBy('prenom')->orderBy('nom')]);

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

    /** Retour à la liste ; les tirages provisoires touchés sont refaits et signalés. */
    private function backToList(Competition $competition, string $message, array $warnings = []): RedirectResponse
    {
        if ($redraw = $this->draws->redrawCanceled()) {
            $warnings[] = $redraw;
        }
        $redirect = redirect()->route('admin.competitions.show', [$competition, 'tab' => 'athletes'])->with('success', $message);

        return $warnings ? $redirect->with('warning', implode(' ', $warnings)) : $redirect;
    }
}
