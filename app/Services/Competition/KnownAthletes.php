<?php

namespace App\Services\Competition;

use App\Http\Controllers\Admin\CompetitionAthleteController;
use App\Models\Competition;
use App\Models\CompetitionAthlete;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Personnes déjà connues de l'application, proposées dans « Choisir parmi les disciples » :
 * les disciples des salles, puis les athlètes importés ou saisis dans les compétitions
 * précédentes (hors disciples). Une même personne n'apparaît qu'une fois : même prénom et
 * nom (sans accents ni casse) et même année de naissance, ou année inconnue d'un côté. Le
 * disciple passe avant l'athlète, et la compétition la plus récente avant les anciennes.
 */
class KnownAthletes
{
    public static function nameKey(?string $prenom, ?string $nom): string
    {
        return preg_replace('/\s+/', ' ', Str::lower(Str::ascii(trim($prenom . ' ' . $nom))));
    }

    /** Année de naissance : date, sinon âge déclaré à la compétition de l'athlète. */
    public static function birthYear(CompetitionAthlete $athlete): ?int
    {
        if ($athlete->date_naissance) {
            return $athlete->date_naissance->year;
        }

        return $athlete->age ? $athlete->competition->referenceYear() - $athlete->age : null;
    }

    public static function samePerson(array $a, array $b): bool
    {
        return $a['key'] === $b['key'] && ($a['year'] === null || $b['year'] === null || $a['year'] === $b['year']);
    }

    /** Athlètes des autres compétitions visibles par l'utilisateur, hors disciples, les plus récents d'abord. */
    public static function previousAthletes(Competition $competition, ?User $user)
    {
        return CompetitionAthlete::query()
            ->whereNull('disciple_id')
            ->where('competition_id', '!=', $competition->id)
            ->whereHas('competition', fn ($q) => $q->visibleTo($user))
            ->with('competition:id,date_competition')
            ->orderByDesc('competition_id')->orderByDesc('id');
    }

    /**
     * Liste à afficher : clé « d:<id> » (disciple) ou « a:<id> » (athlète d'une compétition),
     * nom, sexe, âge l'année de la compétition, club, grade, déjà inscrit ?
     */
    public static function list(Competition $competition, ?User $user): Collection
    {
        $year = $competition->referenceYear();
        $current = $competition->athletes->map(fn ($a) => [
            'key' => self::nameKey($a->prenom, $a->nom), 'year' => $a->date_naissance?->year ?? ($a->age ? $year - $a->age : null), 'disciple' => $a->disciple_id,
        ]);
        $linked = $current->pluck('disciple')->filter()->flip();

        $people = CompetitionAthleteController::disciplesFor($competition, $user)
            ->with(['salle:id,nom', 'grade:id,nom_grade'])
            ->get(['id', 'prenom', 'nom', 'sexe', 'date_naissance', 'salle_id', 'grade_id'])
            ->map(fn ($d) => [
                'ref' => 'd:' . $d->id, 'source' => 'disciple',
                'key' => self::nameKey($d->prenom, $d->nom), 'year' => $d->date_naissance?->year,
                'name' => trim($d->prenom . ' ' . mb_strtoupper($d->nom)), 'sexe' => $d->sexe,
                'salle' => $d->salle?->nom, 'grade' => $d->grade?->nom_grade,
                'in' => isset($linked[$d->id]),
            ]);

        foreach (self::previousAthletes($competition, $user)->get() as $a) {
            $person = [
                'ref' => 'a:' . $a->id, 'source' => 'athlete',
                'key' => self::nameKey($a->prenom, $a->nom), 'year' => self::birthYear($a),
                'name' => $a->fullName(), 'sexe' => $a->sexe, 'salle' => $a->club, 'grade' => null,
            ];
            if ($people->contains(fn ($p) => self::samePerson($p, $person))) {
                continue; // déjà proposé (disciple, ou compétition plus récente)
            }
            $person['in'] = $current->contains(fn ($c) => !$c['disciple'] && self::samePerson($c, $person));
            $people->push($person);
        }

        return $people
            ->map(fn ($p) => $p + ['age' => $p['year'] ? $year - $p['year'] : null])
            ->sortBy(fn ($p) => $p['key'])->values();
    }

    /** Nom de club ou de salle comparable : sans accents, casse ni mots « salle, club, dojo… ». */
    public static function clubKey(?string $club): string
    {
        $words = preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii((string) $club)), -1, PREG_SPLIT_NO_EMPTY);

        return implode(' ', array_diff($words, ['salle', 'club', 'dojo', 'dojang', 'de', 'du', 'la', 'le', 'des', 'tkd', 'taekwondo']));
    }

    /**
     * Athlètes des compétitions de la ligue qui ne sont pas encore des disciples, une ligne
     * par personne (la plus récente), avec les identifiants de toutes ses inscriptions.
     * Ceux qui correspondent déjà à un disciple de la ligue sont à part (« matches ») :
     * ils seront seulement reliés à leur fiche.
     *
     * @return array{people: Collection, matches: Collection}
     */
    public static function unregistered(?User $user, int $ligueId): array
    {
        $disciples = \App\Models\Disciple::visibleTo($user)
            ->whereHas('salle', fn ($q) => $q->where('ligue_id', $ligueId))
            ->get(['id', 'prenom', 'nom', 'date_naissance'])
            ->map(fn ($d) => ['id' => $d->id, 'key' => self::nameKey($d->prenom, $d->nom), 'year' => $d->date_naissance?->year]);

        $people = collect();
        $matches = collect();
        CompetitionAthlete::query()->whereNull('disciple_id')
            ->whereHas('competition', fn ($q) => $q->visibleTo($user)->where('ligue_id', $ligueId))
            ->with('competition:id,date_competition')
            ->orderByDesc('competition_id')->orderByDesc('id')->get()
            ->each(function (CompetitionAthlete $a) use ($disciples, $people, $matches) {
                $person = ['key' => self::nameKey($a->prenom, $a->nom), 'year' => self::birthYear($a)];
                if ($disciple = $disciples->first(fn ($d) => self::samePerson($d, $person))) {
                    $matches->push(['athlete_id' => $a->id, 'disciple_id' => $disciple['id']]);

                    return;
                }
                $index = $people->search(fn ($p) => self::samePerson($p, $person));
                if ($index !== false) {
                    $p = $people[$index];
                    $p['ids'][] = $a->id;
                    $p['year'] ??= $person['year'];
                    $p['sexe'] ??= $a->sexe;
                    $p['date_naissance'] ??= $a->date_naissance;
                    $p['club'] = $p['club'] ?: $a->club;
                    $people[$index] = $p;

                    return;
                }
                $people->push($person + [
                    'ref' => $a->id, 'ids' => [$a->id],
                    'prenom' => $a->prenom, 'nom' => $a->nom, 'name' => $a->fullName(),
                    'sexe' => $a->sexe, 'date_naissance' => $a->date_naissance,
                    'club' => $a->club, 'since' => $a->competition->date_competition,
                ]);
            });

        return ['people' => $people->sortBy('key')->values(), 'matches' => $matches];
    }
}
