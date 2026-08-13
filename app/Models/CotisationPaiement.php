<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotisationPaiement extends Model
{
    use HasFactory;

    protected $fillable = [
        'montant',
        'mode_paiement',
        'reference_paiement',
        'date_paiement',
        'note',
        'created_by_user_id',
        'cotisation_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'date',
    ];

    public function cotisation(): BelongsTo
    {
        return $this->belongsTo(Cotisation::class);
    }
}
