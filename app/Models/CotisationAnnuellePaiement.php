<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotisationAnnuellePaiement extends Model
{
    use HasFactory;

    protected $table = 'cotisations_annuelles_paiements';

    protected $fillable = [
        'cotisation_ceinture_noire_id',
        'montant',
        'mode_paiement',
        'reference_paiement',
        'date_paiement',
        'note',
        'created_by_user_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'date',
    ];

    public function membre(): BelongsTo
    {
        return $this->belongsTo(CotisationAnnuelleCeintureNoire::class, 'cotisation_ceinture_noire_id');
    }
}
