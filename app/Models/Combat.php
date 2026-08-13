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
}
