<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
use App\Models\User;
use App\Shared\Enums\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Database\Seeders\PermissionSeeder;

class AppServiceProvider extends ServiceProvider
{
    private const SCHOOL_PERMISSION_SLUGS = [
        'view_school_cards',
        'create_school_card',
        'edit_school_card',
        'delete_school_card',
        'manage_school_card_settings',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pagination au style Bootstrap 5 (sinon Laravel sort du Tailwind, cassé ici).
        Paginator::useBootstrapFive();

        // Enregistrer les gates dynamiquement pour les permissions
        Gate::define('manage-settings', function (User $user) {
            return $user->hasFullAccess() || $user->hasPermission('manage_settings');
        });

        $this->ensureDefaultPermissions();

        // Définir dynamiquement les gates pour chaque permission active en base
        if ($this->hasTableSafely('permissions')) {
            $permissionSlugs = Permission::where('is_active', true)->pluck('slug')->toArray();
            foreach ($permissionSlugs as $permission) {
                Gate::define($permission, function (User $user) use ($permission) {
                    if (in_array($permission, self::SCHOOL_PERMISSION_SLUGS, true)) {
                        return $user->isSuperAdmin() || $user->hasPermission($permission);
                    }

                    return $user->hasFullAccess() || $user->hasPermission($permission);
                });
            }
        }
    }

    private function ensureDefaultPermissions(): void
    {
        if (!$this->hasTableSafely('permissions') || !$this->hasTableSafely('users') || !$this->hasTableSafely('user_permissions')) {
            return;
        }

        if (Permission::count() === 0) {
            (new PermissionSeeder())->run();
        }

        $permissionSlugs = Permission::where('is_active', true)->pluck('slug')->toArray();
        $defaultAdminPermissionSlugs = array_values(array_diff($permissionSlugs, self::SCHOOL_PERMISSION_SLUGS));
        if (empty($permissionSlugs)) {
            return;
        }

        $superAdmins = User::where('role', UserRole::SUPERADMIN->value)->get();

        // Si aucun superadmin n'existe, créer le compte superadmin par défaut.
        if ($superAdmins->count() === 0) {
            $defaults = [
                [
                    'name' => env('DEFAULT_SUPERADMIN_NAME', 'Moustapha BARRY'),
                    'email' => env('DEFAULT_SUPERADMIN_EMAIL', 'maitedjkbarry@icloud.com'),
                    'phone' => env('DEFAULT_SUPERADMIN_PHONE', '67205736'),
                    'password' => env('DEFAULT_SUPERADMIN_PASSWORD', 'superadmin123'),
                ],
            ];

            foreach ($defaults as $d) {
                try {
                    $user = User::updateOrCreate(
                        ['email' => $d['email']],
                        [
                            'name' => $d['name'],
                            'phone' => $d['phone'],
                            'password' => Hash::make($d['password']),
                            'role' => UserRole::SUPERADMIN->value,
                            'is_active' => true,
                            'status' => \App\Shared\Enums\UserStatus::ACTIVE->value,
                        ]
                    );

                    // S'assurer que le rôle est bien superadmin (au cas où il existait déjà avec un autre rôle)
                    if ($user->role !== UserRole::SUPERADMIN->value) {
                        $user->role = UserRole::SUPERADMIN->value;
                        $user->save();
                    }

                    // Attribuer les permissions initiales
                    $user->grantPermissions($permissionSlugs, 'Création automatique du superadmin');
                } catch (\Exception $ex) {
                    Log::warning('Impossible de créer le superadmin par défaut: ' . $ex->getMessage());
                }
            }
        } else {
            foreach ($superAdmins as $superAdmin) {
                $superAdmin->grantPermissions($permissionSlugs, 'Initialisation automatique des permissions pour superadmin');
            }
        }

        User::whereIn('role', [UserRole::ADMIN->value, UserRole::PRESIDENT->value])
            ->get()
            ->each(function (User $admin) use ($defaultAdminPermissionSlugs) {
                $admin->grantPermissions($defaultAdminPermissionSlugs, 'Initialisation automatique des permissions pour admin/president');
            });

        $this->removeAutomaticallyGrantedSchoolPermissions();
    }

    private function removeAutomaticallyGrantedSchoolPermissions(): void
    {
        $schoolPermissionIds = Permission::whereIn('slug', self::SCHOOL_PERMISSION_SLUGS)->pluck('id');

        if ($schoolPermissionIds->isEmpty()) {
            return;
        }

        User::whereIn('role', [UserRole::ADMIN->value, UserRole::PRESIDENT->value])
            ->get()
            ->each(function (User $admin) use ($schoolPermissionIds) {
                $autoGrantedIds = $admin->permissions()
                    ->whereIn('permissions.id', $schoolPermissionIds)
                    ->wherePivot('reason', 'Initialisation automatique des permissions pour admin/president')
                    ->pluck('permissions.id')
                    ->all();

                if (!empty($autoGrantedIds)) {
                    $admin->permissions()->detach($autoGrantedIds);
                }
            });
    }

    private function hasTableSafely(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $exception) {
            Log::warning("Impossible de vérifier la table {$table}: " . $exception->getMessage());
            return false;
        }
    }
}
