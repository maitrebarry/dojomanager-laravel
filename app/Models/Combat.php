<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Combat extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour',
        'competition_id',
        'combattant1_id',
        'combattant2_id',
        'vainqueur_id',
        'category_id',
        'round',
        'position',
        'numero',
        'athlete1_id',
        'athlete2_id',
        'vainqueur_athlete_id',
        'exempt',
    ];

    protected $casts = [
        'exempt' => 'boolean',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function combattant1(): BelongsTo
    {
        return $this->belongsTo(Disciple::class, 'combattant1_id');
    }

    public function combattant2(): BelongsTo
    {
        return $this->belongsTo(Disciple::class, 'combattant2_id');
    }

    public function vainqueur(): BelongsTo
    {
        return $this->belongsTo(Disciple::class, 'vainqueur_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'category_id');
    }

    /** Combattant bleu (chung), en haut du tableau. */
    public function athlete1(): BelongsTo
    {
        return $this->belongsTo(CompetitionAthlete::class, 'athlete1_id');
    }

    /** Combattant rouge (hong), en bas du tableau. */
    public function athlete2(): BelongsTo
    {
        return $this->belongsTo(CompetitionAthlete::class, 'athlete2_id');
    }

    public function vainqueurAthlete(): BelongsTo
    {
        return $this->belongsTo(CompetitionAthlete::class, 'vainqueur_athlete_id');
    }
}
