<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\User;
use App\Shared\Enums\UserRole;
use App\Shared\Enums\UserStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * Service pour la gestion des utilisateurs
 */
class UserService
{
    /**
     * Créer un nouvel utilisateur
     */
    public function create(array $data): User
    {
        // Empêcher les non-superadmins d'attribuer le rôle superadmin
        if (isset($data['role']) && $data['role'] === 'superadmin' && (!auth()->check() || !auth()->user()->isSuperAdmin())) {
            throw ValidationException::withMessages(['role' => ['Vous n\'êtes pas autorisé à attribuer le rôle Super Administrateur.']]);
        }

        // Lors de la création, forcer l'utilisateur comme actif
        $data['is_active'] = true;
        $data['status'] = $data['status'] ?? UserStatus::ACTIVE->value;
        $data = $this->syncActiveFlagWithStatus($data);
        $user = User::create($data);
        $this->grantDefaultAdminPermissions($user);

        ActivityLog::log(
            action: 'create_user',
            subject: 'User',
            subjectId: $user->id,
            description: "L'utilisateur {$user->name} a été créé"
        );

        return $user;
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(User $user, array $data): bool
    {
        if (array_key_exists('password', $data) && blank($data['password'])) {
            unset($data['password']);
        }

        // Empêcher les non-superadmins d'attribuer le rôle superadmin
        if (isset($data['role']) && $data['role'] === 'superadmin' && (!auth()->check() || !auth()->user()->isSuperAdmin())) {
            throw ValidationException::withMessages(['role' => ['Vous n\'êtes pas autorisé à attribuer le rôle Super Administrateur.']]);
        }

        $data = $this->syncActiveFlagWithStatus($data);

        $changes = [];
        
        // Tracker les modifications
        foreach ($data as $key => $value) {
            if ($user->$key != $value) {
                $changes[$key] = [
                    'old' => $user->$key,
                    'new' => $value,
                ];
            }
        }

        $result = $user->update($data);
        $freshUser = $user->fresh();
        if ($freshUser) {
            $this->grantDefaultAdminPermissions($freshUser);
        }

        if ($result && !empty($changes)) {
            ActivityLog::log(
                action: 'update_user',
                subject: 'User',
                subjectId: $user->id,
                description: "L'utilisateur {$user->name} a été modifié",
                changes: $changes
            );
        }

        return $result;
    }

    /**
     * Supprimer un utilisateur (soft delete)
     */
    public function delete(User $user): bool
    {
        $result = $user->delete();

        if ($result) {
            ActivityLog::log(
                action: 'delete_user',
                subject: 'User',
                subjectId: $user->id,
                description: "L'utilisateur {$user->name} a été supprimé"
            );
        }

        return $result;
    }

    /**
     * Restaurer un utilisateur supprimé
     */
    public function restore(User $user): bool
    {
        $result = $user->restore();

        if ($result) {
            ActivityLog::log(
                action: 'restore_user',
                subject: 'User',
                subjectId: $user->id,
                description: "L'utilisateur {$user->name} a été restauré"
            );
        }

        return $result;
    }

    /**
     * Activer un utilisateur
     */
    public function activate(User $user): bool
    {
        return $this->update($user, [
            'is_active' => true,
            'status' => UserStatus::ACTIVE->value,
        ]);
    }

    /**
     * Désactiver un utilisateur
     */
    public function deactivate(User $user): bool
    {
        return $this->update($user, [
            'is_active' => false,
            'status' => UserStatus::INACTIVE->value,
        ]);
    }

    /**
     * Récupérer tous les utilisateurs avec pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return User::paginate($perPage);
    }

    /**
     * Rechercher des utilisateurs
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return User::where('name', 'like', "%$query%")
            ->orWhere('email', 'like', "%$query%")
            ->orWhere('phone', 'like', "%$query%")
            ->paginate($perPage);
    }

    /**
     * Trouver un utilisateur par email
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Trouver un utilisateur par téléphone
     */
    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    /**
     * Trouver un utilisateur par ID
     */
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Récupérer les utilisateurs actifs
     */
    public function getActive(): object
    {
        return User::active()->get();
    }

    /**
     * Récupérer les utilisateurs par rôle
     */
    public function getByRole(string $role)
    {
        return User::where('role', $role)->get();
    }

    /**
     * Compter les utilisateurs
     */
    public function count(): int
    {
        return User::count();
    }

    /**
     * Compter les utilisateurs actifs
     */
    public function countActive(): int
    {
        return User::active()->count();
    }

    /**
     * Vérifier si un utilisateur existe par email
     */
    public function existsByEmail(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Octroyer des permissions à un utilisateur
     */
    public function grantPermissions(User $user, array $permissionIds, ?string $reason = null): void
    {
        foreach ($permissionIds as $permissionId) {
            $user->permissions()->syncWithoutDetaching([$permissionId => [
                'granted_by' => auth()->id(),
                'reason' => $reason,
                'granted_at' => now(),
            ]]);
        }

        ActivityLog::log(
            action: 'grant_permissions',
            subject: 'User',
            subjectId: $user->id,
            description: "Des permissions ont été accordées à {$user->name}"
        );
    }

    /**
     * Révoquer des permissions d'un utilisateur
     */
    public function revokePermissions(User $user, array $permissionIds): void
    {
        $user->permissions()->detach($permissionIds);

        ActivityLog::log(
            action: 'revoke_permissions',
            subject: 'User',
            subjectId: $user->id,
            description: "Des permissions ont été révoquées à {$user->name}"
        );
    }

    /**
     * Synchroniser les permissions d'un utilisateur
     */
    public function syncPermissions(User $user, array $permissionIds): void
    {
        $oldPermissions = $user->permissions()->pluck('id')->toArray();
        
        $user->permissions()->sync($permissionIds);

        ActivityLog::log(
            action: 'sync_permissions',
            subject: 'User',
            subjectId: $user->id,
            description: "Les permissions de {$user->name} ont été synchronisées",
            changes: [
                'old_permissions' => $oldPermissions,
                'new_permissions' => $permissionIds,
            ]
        );
    }

    /**
     * Synchroniser le drapeau is_active avec le statut
     */
    private function syncActiveFlagWithStatus(array $data): array
    {
        if (isset($data['status'])) {
            $data['is_active'] = $data['status'] === 'active';
        }

        return $data;
    }

    private function grantDefaultAdminPermissions(User $user): void
    {
        if (in_array($user->role, [UserRole::ADMIN->value, UserRole::PRESIDENT->value], true)) {
            $permissionIds = Permission::where('is_active', true)
                ->whereNotIn('slug', [
                    'view_school_cards',
                    'create_school_card',
                    'edit_school_card',
                    'delete_school_card',
                    'manage_school_card_settings',
                ])
                ->pluck('id')
                ->all();
        } else {
            return;
        }
        
        if (empty($permissionIds)) {
            return;
        }

        $syncData = collect($permissionIds)
            ->mapWithKeys(fn($permissionId) => [
                $permissionId => [
                    'granted_by' => auth()->id(),
                    'reason' => 'Permissions par défaut du rôle admin',
                    'granted_at' => now(),
                ],
            ])
            ->all();

        $user->permissions()->syncWithoutDetaching($syncData);
    }
}
