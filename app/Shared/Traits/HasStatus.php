<?php

namespace App\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Trait pour les modèles avec soft delete
 */
trait HasStatus
{
    /**
     * Scope pour récupérer les éléments actifs
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour récupérer les éléments inactifs
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Activer l'enregistrement
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Désactiver l'enregistrement
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Vérifier si l'enregistrement est actif
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
