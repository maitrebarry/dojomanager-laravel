<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitionCategory extends Model
{
    protected $fillable = [
        'competition_id',
        'groupe',
        'sexe',
        'age_min',
        'age_max',
        'poids_max',
        'ordre',
        'tirage_at',
        'tirage_valide',
    ];

    protected $casts = [
        'poids_max' => 'decimal:1',
        'tirage_at' => 'datetime',
        'tirage_valide' => 'boolean',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function athletes(): HasMany
    {
        return $this->hasMany(CompetitionAthlete::class, 'category_id');
    }

    public function combats(): HasMany
    {
        return $this->hasMany(Combat::class, 'category_id')->orderBy('round')->orderBy('position');
    }

    /** Clé du groupe (groupe + sexe), utilisée pour ranger un athlète dans « Seniors garçons ». */
    public function groupKey(): string
    {
        return self::makeGroupKey($this->groupe, $this->sexe);
    }

    public static function makeGroupKey(string $groupe, ?string $sexe): string
    {
        return $groupe . '|' . ($sexe ?? '');
    }

    public function sexeLabel(): string
    {
        return match ($this->sexe) {
            'M' => 'Garçons',
            'F' => 'Filles',
            default => 'Mixte',
        };
    }

    public function groupLabel(): string
    {
        return $this->groupe . ' ' . mb_strtolower($this->sexeLabel());
    }

    /** Ex : « -68 kg » ou « +87 kg » (catégorie sans limite : au-dessus de la précédente). */
    public function weightLabel(?float $previousMax = null): string
    {
        if ($this->poids_max !== null) {
            return '-' . self::formatKg((float) $this->poids_max) . ' kg';
        }

        return $previousMax !== null ? '+' . self::formatKg($previousMax) . ' kg' : 'Toutes catégories';
    }

    public function ageLabel(): string
    {
        return match (true) {
            $this->age_min !== null && $this->age_max !== null => $this->age_min . '-' . $this->age_max . ' ans',
            $this->age_min !== null => $this->age_min . ' ans et +',
            $this->age_max !== null => 'jusqu\'à ' . $this->age_max . ' ans',
            default => '',
        };
    }

    public static function formatKg(float $kg): string
    {
        return rtrim(rtrim(number_format($kg, 1, ',', ''), '0'), ',');
    }
}
