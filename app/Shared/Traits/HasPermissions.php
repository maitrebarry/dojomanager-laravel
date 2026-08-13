<?php

namespace App\Shared\Traits;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait pour la gestion des permissions des utilisateurs
 */
trait HasPermissions
{
    /**
     * Récupérer tous les permissions de l'utilisateur
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withTimestamps()
            ->withPivot('granted_by', 'reason', 'granted_at');
    }

    /**
     * Vérifier si l'utilisateur possède une permission
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin a toutes les permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Vérifier si la permission existe avec ce slug
        return $this->permissions()
            ->where('slug', $permission)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Vérifier si l'utilisateur possède plusieurs permissions (ET)
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Vérifier si l'utilisateur possède au moins une permission (OU)
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Octroyer une permission à l'utilisateur
     */
    public function grantPermission(Permission|string $permission, ?string $reason = null): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->first();
            if (!$permission) {
                throw new \InvalidArgumentException("Permission '$permission' not found");
            }
        }

        // Vérifier si la permission existe déjà
        if (!$this->permissions()->where('permission_id', $permission->id)->exists()) {
            $this->permissions()->attach($permission->id, [
                'granted_by' => auth()->id(),
                'reason' => $reason,
                'granted_at' => now(),
            ]);
        }
    }

    /**
     * Révoquer une permission de l'utilisateur
     */
    public function revokePermission(Permission|string $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->first();
            if (!$permission) {
                throw new \InvalidArgumentException("Permission '$permission' not found");
            }
        }

        $this->permissions()->detach($permission->id);
    }

    /**
     * Octroyer plusieurs permissions
     */
    public function grantPermissions(array $permissions, ?string $reason = null): void
    {
        foreach ($permissions as $permission) {
            $this->grantPermission($permission, $reason);
        }
    }

    /**
     * Révoquer plusieurs permissions
     */
    public function revokePermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->revokePermission($permission);
        }
    }

    /**
     * Révoquer toutes les permissions
     */
    public function revokeAllPermissions(): void
    {
        $this->permissions()->detach();
    }

    /**
     * Récupérer le slug de tous les permissions de l'utilisateur
     */
    public function getPermissionSlugs(): array
    {
        return $this->permissions()
            ->where('is_active', true)
            ->pluck('slug')
            ->toArray();
    }

    /**
     * Récupérer les permissions groupées par module
     */
    public function getPermissionsByModule(): array
    {
        return $this->permissions()
            ->where('is_active', true)
            ->groupBy('module')
            ->map(fn ($group) => $group->pluck('slug')->toArray())
            ->toArray();
    }

    /**
     * Vérifier si l'utilisateur peut accéder à un module
     */
    public function canAccessModule(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->permissions()
            ->where('module', $module)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Récupérer tous les modules accessibles
     */
    public function getAccessibleModules(): array
    {
        if ($this->isSuperAdmin()) {
            return Permission::active()->distinct()->pluck('module')->toArray();
        }

        return $this->permissions()
            ->where('is_active', true)
            ->distinct()
            ->pluck('module')
            ->toArray();
    }

    /**
     * Scope pour filtrer les utilisateurs ayant une permission
     */
    public function scopeWithPermission(Builder $query, string $permission): Builder
    {
        return $query->whereHas('permissions', function (Builder $query) use ($permission) {
            $query->where('slug', $permission)->where('is_active', true);
        });
    }
}
