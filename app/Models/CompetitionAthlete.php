<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitionAthlete extends Model
{
    protected $fillable = [
        'competition_id',
        'category_id',
        'disciple_id',
        'nom',
        'prenom',
        'sexe',
        'date_naissance',
        'age',
        'poids',
        'club',
        'groupe',
        'tete_de_serie',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'poids' => 'decimal:3',
    ];

    /** Inscrit (ou complété) avec son club : devient disciple de cette salle (voir AthleteRegistry). */
    protected static function booted(): void
    {
        static::saved(function (CompetitionAthlete $athlete) {
            if (!$athlete->disciple_id && trim((string) $athlete->club) !== '') {
                \App\Services\Competition\AthleteRegistry::sync($athlete);
            }
        });
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'category_id');
    }

    public function disciple(): BelongsTo
    {
        return $this->belongsTo(Disciple::class);
    }

    /** « Prénom NOM », comme partout dans l'application. */
    public function fullName(): string
    {
        return $this->prenom . ' ' . mb_strtoupper($this->nom);
    }

    /** Âge sportif : année de la compétition − année de naissance (règle du taekwondo), sinon l'âge saisi. */
    public function sportAge(int $referenceYear): ?int
    {
        if ($this->date_naissance) {
            return $referenceYear - $this->date_naissance->year;
        }

        return $this->age;
    }
}
