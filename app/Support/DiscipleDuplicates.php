<?php

namespace App\Support;

use App\Models\Disciple;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Doublons de disciples : une même personne ne doit exister qu'une fois dans une salle.
 * Même personne = même prénom et nom (sans tenir compte des accents, majuscules ni espaces)
 * et date de naissance compatible : identique, ou même année quand l'une n'est connue qu'à
 * l'année (enregistrée au 1er janvier), ou inconnue d'un côté.
 */
class DiscipleDuplicates
{
    public static function nameKey(?string $prenom, ?string $nom): string
    {
        return preg_replace('/\s+/', ' ', Str::lower(Str::ascii(trim($prenom . ' ' . $nom))));
    }

    public static function sameBirth($a, $b): bool
    {
        if (!$a || !$b) {
            return true;
        }
        $a = Carbon::parse($a);
        $b = Carbon::parse($b);
        if ($a->isSameDay($b)) {
            return true;
        }
        $approximate = fn (Carbon $d) => $d->day === 1 && $d->month === 1;

        return $a->year === $b->year && ($approximate($a) || $approximate($b));
    }

    /** Disciple (actif) de la salle qui est la même personne, sinon null. */
    public static function find(int $salleId, ?string $prenom, ?string $nom, $birth = null, ?int $exceptId = null): ?Disciple
    {
        $key = self::nameKey($prenom, $nom);

        return Disciple::where('salle_id', $salleId)->whereNull('archived_at')
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->get(['id', 'prenom', 'nom', 'date_naissance', 'nmle', 'salle_id'])
            ->first(fn ($d) => self::nameKey($d->prenom, $d->nom) === $key && self::sameBirth($d->date_naissance, $birth));
    }
}
