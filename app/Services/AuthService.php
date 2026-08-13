<?php

namespace App\Services;

use App\Models\User;
use App\Shared\Enums\UserStatus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Service pour l'authentification
 */
class AuthService
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Enregistrer un nouvel utilisateur
     */
    public function register(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $data['status'] = UserStatus::PENDING->value;
        $data['is_active'] = false;

        return $this->userService->create($data);
    }

    /**
     * Se connecter avec téléphone et mot de passe
     */
    public function login(string $phone, string $password, bool $remember = false): bool
    {
        $user = $this->userService->findByPhone($phone);

        if (!$user) {
            Log::warning('AuthService: login failed - user not found', ['phone' => $phone]);
            return false;
        }

        $hashOk = Hash::check($password, $user->password);
        if (!$hashOk) {
            Log::warning('AuthService: login failed - invalid password', ['phone' => $phone]);
            return false;
        }

        if (!$user->is_active || $user->status !== UserStatus::ACTIVE->value) {
            Log::warning('AuthService: login failed - inactive or wrong status', ['phone' => $phone, 'is_active' => $user->is_active, 'status' => $user->status]);
            return false;
        }

        auth()->login($user, $remember);
        $user->recordLogin();

        return true;
    }

    /**
     * Se déconnecter
     */
    public function logout(): void
    {
        auth()->user()?->recordLogout();
        auth()->logout();
    }

    /**
     * Vérifier si l'utilisateur est authentifié
     */
    public function isAuthenticated(): bool
    {
        return auth()->check();
    }

    /**
     * Récupérer l'utilisateur actuel
     */
    public function currentUser(): ?User
    {
        return auth()->user();
    }

    /**
     * Changer le mot de passe de l'utilisateur
     */
    public function changePassword(User $user, string $oldPassword, string $newPassword): bool
    {
        if (!Hash::check($oldPassword, $user->password)) {
            return false;
        }

        return $this->userService->update($user, [
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Réinitialiser le mot de passe d'un utilisateur
     */
    public function resetPassword(User $user, string $newPassword): bool
    {
        return $this->userService->update($user, [
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Vérifier si l'utilisateur possède une permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->currentUser()?->hasPermission($permission) ?? false;
    }

    /**
     * Vérifier si l'utilisateur possède plusieurs permissions (ET)
     */
    public function hasAllPermissions(array $permissions): bool
    {
        return $this->currentUser()?->hasAllPermissions($permissions) ?? false;
    }

    /**
     * Vérifier si l'utilisateur possède au moins une permission (OU)
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->currentUser()?->hasAnyPermission($permissions) ?? false;
    }

    /**
     * Vérifier si l'utilisateur peut accéder à un module
     */
    public function canAccessModule(string $module): bool
    {
        return $this->currentUser()?->canAccessModule($module) ?? false;
    }

    /**
     * Obtenir les permissions de l'utilisateur actuel
     */
    public function getCurrentUserPermissions(): array
    {
        return $this->currentUser()?->getPermissionSlugs() ?? [];
    }

    /**
     * Obtenir les modules accessibles par l'utilisateur actuel
     */
    public function getAccessibleModules(): array
    {
        return $this->currentUser()?->getAccessibleModules() ?? [];
    }
}
