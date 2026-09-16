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
     * Se connecter avec téléphone et mot de passe. Le téléphone n'étant plus unique
     * (plusieurs comptes peuvent le partager, ex. foyer commun), on ne retient que les
     * comptes dont CE mot de passe est valide : s'il en reste plusieurs, on renvoie
     * 'choose' pour que le contrôleur propose un choix de compte, sans se connecter.
     *
     * Renvoie un tableau ['status' => 'ok'|'choose'|'blocked'|'fail', ...].
     */
    public function login(string $phone, string $password, bool $remember = false): array
    {
        $candidates = $this->userService->findAllByPhone($phone)
            ->filter(fn (User $u) => Hash::check($password, $u->password))
            ->values();

        if ($candidates->isEmpty()) {
            Log::warning('AuthService: login failed - no matching credentials', ['phone' => $phone]);
            return ['status' => 'fail'];
        }

        if ($candidates->count() > 1) {
            return ['status' => 'choose', 'candidates' => $candidates];
        }

        return $this->finalizeLogin($candidates->first(), $remember);
    }

    /**
     * Termine la connexion après un choix de compte : $userId doit obligatoirement
     * figurer parmi $allowedIds (les comptes déjà validés par login() pour cette même
     * tentative), jamais un identifiant arbitraire fourni par le client.
     */
    public function finalizeChosenLogin(int $userId, array $allowedIds, bool $remember): array
    {
        if (!in_array($userId, $allowedIds, true)) {
            return ['status' => 'fail'];
        }

        $user = $this->userService->find($userId);
        if (!$user) {
            return ['status' => 'fail'];
        }

        return $this->finalizeLogin($user, $remember);
    }

    private function finalizeLogin(User $user, bool $remember): array
    {
        if (!$user->is_active || $user->status !== UserStatus::ACTIVE->value) {
            Log::warning('AuthService: login failed - inactive or wrong status', ['user_id' => $user->id, 'status' => $user->status]);
            return ['status' => 'blocked', 'reason' => $user->deactivation_reason ?: __('messages.auth.account_disabled_default')];
        }

        auth()->login($user, $remember);
        $user->recordLogin();

        return ['status' => 'ok'];
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
