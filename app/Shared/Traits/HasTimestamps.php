<?php

namespace App\Shared\Traits;

use Illuminate\Support\Carbon;

/**
 * Trait pour les modèles avec timestamps personnalisés
 */
trait HasTimestamps
{
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Retourne la date de création formatée
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at?->format('d/m/Y H:i') ?? 'N/A';
    }

    /**
     * Retourne la date de modification formatée
     */
    public function getFormattedUpdatedAtAttribute(): string
    {
        return $this->updated_at?->format('d/m/Y H:i') ?? 'N/A';
    }

    /**
     * Retourne si l'enregistrement a été créé aujourd'hui
     */
    public function isCreatedToday(): bool
    {
        return $this->created_at->isToday();
    }

    /**
     * Retourne le temps écoulé depuis la création
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
