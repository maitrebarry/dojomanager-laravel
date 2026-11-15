<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competition extends Model
{
    use HasFactory, ScopedToUser;

    // Une ligue voit ses compétitions, une fédération celles de toutes ses ligues.
    protected static array $scopePaths = [
        'ligue_id' => 'ligue_id',
        'federation_id' => 'federation_id',
    ];

    protected $fillable = [
        'nom',
        'date_competition',
        'lieu',
        'type',
        'bronze',
        'tolerance_g',
        'manche_s',
        'repos_s',
        'manches',
        'federation_id',
        'ligue_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'date_competition' => 'date',
    ];

    public function combats(): HasMany
    {
        return $this->hasMany(Combat::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(CompetitionCategory::class)->orderBy('ordre')->orderBy('id');
    }

    public function athletes(): HasMany
    {
        return $this->hasMany(CompetitionAthlete::class);
    }

    public function federation(): BelongsTo
    {
        return $this->belongsTo(Federation::class);
    }

    public function ligue(): BelongsTo
    {
        return $this->belongsTo(Ligue::class);
    }

    /** Profil de la discipline (vocabulaire des coins, grades, catégories proposées). */
    public function profile(): array
    {
        return \App\Support\Discipline::forCompetition($this);
    }

    /**
     * Durées des combats (secondes) : celles de la compétition, à défaut celles de la
     * discipline. La durée d'une manche peut encore être précisée par catégorie.
     *
     * @return array{round: int, rest: int, rounds: int}
     */
    public function chronoSettings(): array
    {
        $default = $this->profile()['chrono'];

        return [
            'round' => $this->manche_s ?? $default['round'],
            'rest' => $this->repos_s ?? $default['rest'],
            'rounds' => $this->manches ?? $default['rounds'],
        ];
    }

    /** Année de référence pour l'âge sportif (année de la compétition − année de naissance). */
    public function referenceYear(): int
    {
        return (int) ($this->date_competition?->year ?? now()->year);
    }
}
