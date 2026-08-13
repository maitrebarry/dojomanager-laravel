<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Restreint les requêtes au périmètre (fédération / ligue / salle) de l'utilisateur.
 *
 * Chaque modèle déclare la propriété statique $scopePaths avec, pour chaque niveau
 * ('salle_id', 'ligue_id', 'federation_id'), le chemin vers la colonne cible :
 *   - une colonne directe : 'salle_id'
 *   - un chemin de relation : 'salle.ligue.federation_id'  (whereHas)
 *
 * Utilisation : Model::visibleTo($user)->...
 */
trait ScopedToUser
{
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        // Superadmin / rôles non restreints : aucun filtrage.
        if (!$user || !$user->isTenantAdmin()) {
            return $query;
        }

        $context = $user->scopeContext();
        $paths = static::$scopePaths ?? [];

        // Du plus spécifique (salle) au plus large (fédération).
        foreach (['salle_id', 'ligue_id', 'federation_id'] as $level) {
            if (!empty($context[$level]) && isset($paths[$level])) {
                return $this->applyScopePath($query, $paths[$level], $context[$level]);
            }
        }

        // Utilisateur restreint mais périmètre non résolu : ne rien montrer.
        return $query->whereRaw('1 = 0');
    }

    private function applyScopePath(Builder $query, string $path, $value): Builder
    {
        if (!str_contains($path, '.')) {
            return $query->where($path, $value);
        }

        $segments = explode('.', $path);
        $column = array_pop($segments);
        $relation = implode('.', $segments);

        return $query->whereHas($relation, fn (Builder $q) => $q->where($column, $value));
    }
}
