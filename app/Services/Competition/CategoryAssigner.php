<?php

namespace App\Services\Competition;

use App\Models\Competition;
use App\Models\CompetitionAthlete;
use App\Models\CompetitionCategory;
use Illuminate\Support\Collection;

/**
 * Range un athlète dans la plus petite catégorie de poids qui l'accepte, parmi
 * les catégories de son groupe (sexe + âge). Le groupe peut être imposé quand la
 * liste importée ne donne ni le sexe ni l'âge (ex. « liste des seniors garçons »).
 */
class CategoryAssigner
{
    private Collection $categories;

    public function __construct(private Competition $competition)
    {
        $this->categories = $competition->categories()->get();
    }

    /**
     * @param string|null $groupKey groupe imposé « Seniors|M » ; à défaut celui gardé à l'import,
     *                              sinon déduit du sexe et de l'âge
     */
    public function categoryFor(CompetitionAthlete $athlete, ?string $groupKey = null): ?CompetitionCategory
    {
        if ($athlete->poids === null) {
            return null; // pas encore pesé
        }
        $groupKey ??= $athlete->groupe;
        $age = $athlete->sportAge($this->competition->referenceYear());

        $candidates = $this->categories->filter(function (CompetitionCategory $c) use ($athlete, $age, $groupKey) {
            if ($groupKey !== null) {
                return $c->groupKey() === $groupKey;
            }
            if ($c->sexe !== null && $c->sexe !== $athlete->sexe) {
                return false;
            }
            if ($age === null) {
                // Sans âge, on ne peut trancher que si un seul groupe d'âge existe pour ce sexe.
                return true;
            }

            return ($c->age_min === null || $age >= $c->age_min) && ($c->age_max === null || $age <= $c->age_max);
        });

        if ($groupKey === null && $candidates->map->groupKey()->unique()->count() !== 1) {
            return null; // aucun groupe, ou plusieurs possibles : à classer à la main
        }

        // Comparaison en grammes : jusqu'à tolerance_g au-dessus de la limite, l'athlète
        // reste dans la catégorie ; au-delà, il passe à la suivante.
        $grams = (int) round((float) $athlete->poids * 1000);
        $tolerance = (int) ($this->competition->tolerance_g ?? 0);

        return $candidates
            ->sortBy(fn (CompetitionCategory $c) => $c->poids_max === null ? PHP_FLOAT_MAX : (float) $c->poids_max)
            ->first(fn (CompetitionCategory $c) => $c->poids_max === null || $grams <= (int) round((float) $c->poids_max * 1000) + $tolerance);
    }

    /**
     * Reclasse tous les athlètes pesés (ex. après un changement de tolérance). Les
     * catégories au tirage validé ne bougent pas : leurs athlètes qui devraient changer
     * de catégorie sont comptés à part. Renvoie [déplacés, restés dans un tirage validé].
     *
     * @return array{0: int, 1: int}
     */
    public function reassignAll(DrawService $draws): array
    {
        $moved = 0;
        $locked = 0;
        $this->competition->athletes()->with('category')->whereNotNull('poids')->get()
            ->each(function (CompetitionAthlete $athlete) use ($draws, &$moved, &$locked) {
                $new = $this->categoryFor($athlete);
                if ($new?->id === $athlete->category_id) {
                    return;
                }
                if ($athlete->category?->tirage_valide || $new?->tirage_valide) {
                    $locked++;

                    return;
                }
                $draws->beforeChange($athlete->category);
                $draws->beforeChange($new);
                $athlete->update(['category_id' => $new?->id]);
                $moved++;
            });

        return [$moved, $locked];
    }

    /**
     * Range tous les athlètes pesés et non classés ; renvoie le nombre d'athlètes rangés.
     * Avec $draws, le tirage provisoire d'une catégorie qui reçoit un athlète est annulé
     * (et un tirage validé n'en reçoit pas).
     */
    public function assignUnclassified(?DrawService $draws = null): int
    {
        $count = 0;
        $this->competition->athletes()->whereNull('category_id')->whereNotNull('poids')->get()
            ->each(function (CompetitionAthlete $athlete) use (&$count, $draws) {
                if (($category = $this->categoryFor($athlete)) && !$category->tirage_valide) {
                    $draws?->beforeChange($category);
                    $athlete->update(['category_id' => $category->id]);
                    $count++;
                }
            });

        return $count;
    }
}
