<?php

namespace App\Services\Competition;

use App\Models\CompetitionAthlete;
use App\Models\Disciple;
use App\Models\Salle;
use App\Support\MatriculeGenerator;

/**
 * Un combattant inscrit avec son club devient disciple de sa salle : le club EST la salle.
 * La salle est retrouvée dans la ligue de la compétition d'après son nom (sans accents,
 * casse ni « salle / club / dojo… »), ou créée sans maître ; le maître qui y sera affecté
 * plus tard y trouvera ses disciples. Une personne déjà disciple dans la ligue (même prénom,
 * nom et année de naissance) est seulement reliée, jamais recréée.
 */
class AthleteRegistry
{
    public static function sync(CompetitionAthlete $athlete): ?Disciple
    {
        if ($athlete->disciple_id || trim((string) $athlete->club) === '') {
            return null;
        }
        $competition = $athlete->competition;
        $ligue = $competition?->ligue;
        if (!$ligue) {
            return null; // compétition de fédération : pas de ligue où ranger la salle
        }

        $key = KnownAthletes::nameKey($athlete->prenom, $athlete->nom);
        $year = KnownAthletes::birthYear($athlete);
        $disciple = Disciple::whereHas('salle', fn ($q) => $q->where('ligue_id', $ligue->id))
            ->get(['id', 'prenom', 'nom', 'date_naissance'])
            ->first(fn ($d) => KnownAthletes::samePerson(
                ['key' => KnownAthletes::nameKey($d->prenom, $d->nom), 'year' => $d->date_naissance?->year],
                ['key' => $key, 'year' => $year]
            ));

        if (!$disciple) {
            $clubKey = KnownAthletes::clubKey($athlete->club);
            $salle = Salle::where('ligue_id', $ligue->id)->get()
                ->first(fn ($s) => KnownAthletes::clubKey($s->nom) === $clubKey)
                ?? Salle::create(['ligue_id' => $ligue->id, 'nom' => trim($athlete->club), 'active' => true]);

            $disciple = Disciple::create([
                'prenom' => $athlete->prenom,
                'nom' => $athlete->nom,
                'nom_complet' => trim($athlete->prenom . ' ' . $athlete->nom),
                'sexe' => in_array($athlete->sexe, ['M', 'F'], true) ? $athlete->sexe : null,
                // Seule l'année est connue (âge déclaré) : né « vers » le 1er janvier de cette année.
                'date_naissance' => $athlete->date_naissance ?? ($year ? "{$year}-01-01" : null),
                'date_inscription' => $competition->date_competition ?? now(),
                'salle_id' => $salle->id,
                'nmle' => MatriculeGenerator::nextForLigue($ligue),
            ]);
        }

        // Toutes les inscriptions de cette personne dans les compétitions de la ligue.
        CompetitionAthlete::whereNull('disciple_id')
            ->whereHas('competition', fn ($q) => $q->where('ligue_id', $ligue->id))
            ->get()
            ->filter(fn ($a) => KnownAthletes::samePerson(['key' => KnownAthletes::nameKey($a->prenom, $a->nom), 'year' => KnownAthletes::birthYear($a)], ['key' => $key, 'year' => $year]))
            ->each(fn ($a) => $a->forceFill(['disciple_id' => $disciple->id])->saveQuietly());

        return $disciple;
    }
}
