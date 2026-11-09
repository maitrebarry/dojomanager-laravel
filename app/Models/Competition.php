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

    /** Année de référence pour l'âge sportif (année de la compétition − année de naissance). */
    public function referenceYear(): int
    {
        return (int) ($this->date_competition?->year ?? now()->year);
    }
}
