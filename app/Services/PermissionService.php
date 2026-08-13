<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service pour la gestion des permissions
 */
class PermissionService
{
    public const SCHOOL_PERMISSION_SLUGS = [
        'view_school_cards',
        'create_school_card',
        'edit_school_card',
        'delete_school_card',
        'manage_school_card_settings',
    ];

    /**
     * Créer une nouvelle permission
     */
    public function create(array $data): Permission
    {
        $permission = Permission::create($data);

        ActivityLog::log(
            action: 'create_permission',
            subject: 'Permission',
            subjectId: $permission->id,
            description: "La permission '{$permission->name}' a été créée"
        );

        return $permission;
    }

    /**
     * Mettre à jour une permission
     */
    public function update(Permission $permission, array $data): bool
    {
        $changes = [];

        foreach ($data as $key => $value) {
            if ($permission->$key != $value) {
                $changes[$key] = [
                    'old' => $permission->$key,
                    'new' => $value,
                ];
            }
        }

        $result = $permission->update($data);

        if ($result && !empty($changes)) {
            ActivityLog::log(
                action: 'update_permission',
                subject: 'Permission',
                subjectId: $permission->id,
                description: "La permission '{$permission->name}' a été modifiée",
                changes: $changes
            );
        }

        return $result;
    }

    /**
     * Supprimer une permission
     */
    public function delete(Permission $permission): bool
    {
        $result = $permission->delete();

        if ($result) {
            ActivityLog::log(
                action: 'delete_permission',
                subject: 'Permission',
                subjectId: $permission->id,
                description: "La permission '{$permission->name}' a été supprimée"
            );
        }

        return $result;
    }

    /**
     * Récupérer toutes les permissions
     */
    public function getAll(): Collection
    {
        return Permission::active()->ordered()->get();
    }

    /**
     * Récupérer les permissions groupées par module
     */
    public function groupedByModule(): Collection
    {
        return Permission::groupedByModule();
    }

    /**
     * Récupérer les permissions d'un module
     */
    public function getByModule(string $module): Collection
    {
        return Permission::forModule($module);
    }

    /**
     * Trouver une permission par slug
     */
    public function findBySlug(string $slug): ?Permission
    {
        return Permission::where('slug', $slug)->first();
    }

    /**
     * Trouver une permission par ID
     */
    public function find(int $id): ?Permission
    {
        return Permission::find($id);
    }

    /**
     * Vérifier si une permission existe
     */
    public function exists(string $slug): bool
    {
        return Permission::where('slug', $slug)->exists();
    }

    /**
     * Créer les permissions par défaut
     */
    public function createDefaultPermissions(): void
    {
        $permissions = [
            // Gestion des utilisateurs
            ['name' => 'Voir les utilisateurs', 'slug' => 'view_users', 'module' => 'users', 'action' => 'view'],
            ['name' => 'Ajouter un utilisateur', 'slug' => 'create_user', 'module' => 'users', 'action' => 'create'],
            ['name' => 'Modifier un utilisateur', 'slug' => 'edit_user', 'module' => 'users', 'action' => 'edit'],
            ['name' => 'Supprimer un utilisateur', 'slug' => 'delete_user', 'module' => 'users', 'action' => 'delete'],

            // Gestion des cartes
            ['name' => 'Voir les cartes', 'slug' => 'view_licence_holders', 'module' => 'cards', 'action' => 'view'],
            ['name' => 'Ajouter une carte', 'slug' => 'create_licence_holder', 'module' => 'cards', 'action' => 'create'],
            ['name' => 'Modifier une carte', 'slug' => 'edit_licence_holder', 'module' => 'cards', 'action' => 'edit'],
            ['name' => 'Supprimer une carte', 'slug' => 'delete_licence_holder', 'module' => 'cards', 'action' => 'delete'],

            // Gestion des permissions
            ['name' => 'Voir les permissions', 'slug' => 'view_permissions', 'module' => 'permissions', 'action' => 'view'],
            ['name' => 'Gérer les permissions', 'slug' => 'manage_permissions', 'module' => 'permissions', 'action' => 'edit'],

            // Système
            ['name' => 'Accéder au tableau de bord', 'slug' => 'access_dashboard', 'module' => 'dashboard', 'action' => 'view'],
            ['name' => 'Gérer les paramètres', 'slug' => 'manage_settings', 'module' => 'settings', 'action' => 'edit'],
        ];

        foreach ($permissions as $permission) {
            if (!$this->exists($permission['slug'])) {
                $this->create($permission);
            }
        }
    }

    /**
     * Obtenir les modules disponibles avec leurs permissions
     */
    public function getModulesWithPermissions(): array
    {
        $modules = [];
        $grouped = $this->groupedByModule();

        foreach ($grouped as $module => $permissions) {
            if (!auth()->user()?->isSuperAdmin()) {
                $permissions = $permissions->reject(
                    fn (Permission $permission) => in_array($permission->slug, self::SCHOOL_PERMISSION_SLUGS, true)
                );
            }

            if ($permissions->isEmpty()) {
                continue;
            }

            $modules[$module] = [
                'permissions' => $permissions->keyBy('action'),
                'count' => $permissions->count(),
            ];
        }

        return $modules;
    }

    /**
     * Récupérer les permissions pour un utilisateur donnécomme un tableau d'administration
     */
    public function getAdminGrid(int $userId = null)
    {
        $modules = $this->getModulesWithPermissions();
        $userPermissions = [];

        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $userPermissions = $user->permissions()->pluck('id')->toArray();
            }
        }

        return [
            'modules' => $modules,
            'userPermissions' => $userPermissions,
        ];
    }
}
