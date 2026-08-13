<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CotisationAnnuelleCeintureNoire extends Model
{
    use HasFactory;

    protected $table = 'cotisations_annuelles_ceintures_noires';

    protected $fillable = [
        'cotisation_annuelle_id',
        'origine',
        'source_id',
        'nom',
        'prenom',
        'sexe',
        'user_role',
        'grade_nom',
        'federation_id',
        'federation_nom',
        'ligue_id',
        'ligue_nom',
        'salle_id',
        'salle_nom',
        'montant_du',
        'montant_paye',
        'reste_a_payer',
        'statut',
    ];

    protected $casts = [
        'montant_du' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'reste_a_payer' => 'decimal:2',
    ];

    public function cotisationAnnuelle(): BelongsTo
    {
        return $this->belongsTo(CotisationAnnuelle::class, 'cotisation_annuelle_id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(CotisationAnnuellePaiement::class, 'cotisation_ceinture_noire_id');
    }

    public function fullName(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
    }

    public function recompute(): void
    {
        $paye = (float) $this->paiements()->sum('montant');
        $this->montant_paye = $paye;
        $this->reste_a_payer = max(0, (float) $this->montant_du - $paye);
        $this->statut = $paye <= 0 ? 'IMPAYE' : ($this->reste_a_payer > 0 ? 'PARTIEL' : 'PAYE');
        $this->save();
    }

    public function statutColor(): string
    {
        return match ($this->statut) {
            'PAYE' => 'success',
            'PARTIEL' => 'warning',
            default => 'danger',
        };
    }
}
