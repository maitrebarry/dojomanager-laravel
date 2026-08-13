<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradePassageCandidate extends Model
{
    use HasFactory;

    protected $table = 'grade_passage_candidates';

    protected $fillable = [
        'session_id',
        'candidate_type',
        'source_id',
        'nom',
        'prenom',
        'sexe',
        'salle_id',
        'salle_nom',
        'current_grade_id',
        'current_grade_nom',
        'proposed_grade_id',
        'proposed_grade_nom',
        'frais_participation',
        'montant_paye',
        'statut_paiement',
        'note_globale',
        'note_forme',
        'note_mouvement_base',
        'note_poomsea',
        'note_attaque_defense',
        'note_combat',
        'moyenne_generale',
        'resultat',
        'statut',
    ];

    protected $casts = [
        'frais_participation' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'note_globale' => 'decimal:2',
        'note_forme' => 'decimal:2',
        'note_mouvement_base' => 'decimal:2',
        'note_poomsea' => 'decimal:2',
        'note_attaque_defense' => 'decimal:2',
        'note_combat' => 'decimal:2',
        'moyenne_generale' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GradePassageSession::class, 'session_id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(GradePassageCandidatePayment::class, 'candidate_id');
    }

    public function fullName(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
    }

    public function recomputePayment(): void
    {
        $paye = (float) $this->paiements()->sum('montant');
        $this->montant_paye = $paye;
        $reste = max(0, (float) $this->frais_participation - $paye);
        $this->statut_paiement = $paye <= 0 ? 'IMPAYE' : ($reste > 0 ? 'PARTIEL' : 'PAYE');
        $this->save();
    }

    public function resteAPayer(): float
    {
        return max(0, (float) $this->frais_participation - (float) $this->montant_paye);
    }

    public function statutColor(): string
    {
        return match ($this->statut) {
            'VALIDE' => 'success',
            'REFUSE' => 'danger',
            default => 'secondary',
        };
    }

    public function paiementColor(): string
    {
        return match ($this->statut_paiement) {
            'PAYE' => 'success',
            'PARTIEL' => 'warning',
            default => 'danger',
        };
    }
}
