<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Salle extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'salle_id' => 'id',
        'ligue_id' => 'ligue_id',
        'federation_id' => 'ligue.federation_id',
    ];

    protected $fillable = [
        'nom',
        'adresse',
        'telephone',
        'mensualite',
        'active',
        'ligue_id',
        'maitre_id',
    ];

    protected $casts = [
        'mensualite' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function ligue(): BelongsTo
    {
        return $this->belongsTo(Ligue::class);
    }

    public function maitre(): BelongsTo
    {
        return $this->belongsTo(Maitre::class);
    }

    public function disciples(): HasMany
    {
        return $this->hasMany(Disciple::class);
    }

    /**
     * Compte utilisateur (rôle maître) rattaché à cette salle via users.salle_id.
     * Distinct de la fiche « Maitre » (nom_complet/téléphone/grade), qui doit être
     * créée et liée séparément via le module Maîtres — beaucoup de salles n'ont que
     * le compte de connexion, sans fiche associée.
     */
    public function maitreUser(): HasOne
    {
        return $this->hasOne(User::class, 'salle_id')->where('role', 'maitre');
    }

    /**
     * Nom du maître à afficher : la fiche Maitre si elle existe, sinon le compte
     * utilisateur maître de la salle (évite un « Non affecté » trompeur quand la
     * salle a bien un maître connecté mais pas de fiche Maitre renseignée).
     */
    public function getMaitreDisplayNameAttribute(): ?string
    {
        return $this->maitre?->nom_complet ?: $this->maitreUser?->name;
    }

    /** Même repli que getMaitreDisplayNameAttribute(), pour le grade affiché en signature. */
    public function getMaitreDisplayGradeAttribute(): ?string
    {
        return $this->maitre?->grade ?: $this->maitreUser?->grade?->nom_grade;
    }
}
