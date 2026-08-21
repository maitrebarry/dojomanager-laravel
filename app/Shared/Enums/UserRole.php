<?php

namespace App\Shared\Enums;

use App\Models\User;

/**
 * Énumération des rôles utilisateur
 */
enum UserRole: string
{
    case SUPERADMIN = 'superadmin';
    case PRESIDENT = 'president';
    case VPRESIDENT = 'vpresident';
    case SEGAL = 'segal';
    case DTN = 'dtn';
    case ADMIN_SCOLAIRE = 'admin_scolaire';
    case ADMIN = 'admin';
    // Rôles multi-tenant (isolation des données par périmètre)
    case FEDERATION = 'federation';
    case LIGUE = 'ligue';
    case MAITRE = 'maitre';
    case MANAGER = 'manager';
    case USER = 'user';
    case GUEST = 'guest';

    public function label(): string
    {
        return __("messages.roles.{$this->value}");
    }

    public function permissions(): array
    {
        return match ($this) {
            self::SUPERADMIN, self::PRESIDENT => ['*'],
            self::VPRESIDENT => [
                'view_users',
                'create_user',
                'edit_user',
                'delete_user',
                'view_permissions',
                'manage_permissions',
                'create_permission',
                'delete_permission',
                'manage_settings',
                'view_licence_holders',
                'create_licence_holder',
                'edit_licence_holder',
                'delete_licence_holder',
            ],
            self::SEGAL, self::DTN => [
                'view_users',
                'view_permissions',
                'view_licence_holders',
            ],
            self::ADMIN_SCOLAIRE => [
                'view_school_cards',
                'create_school_card',
                'edit_school_card',
                'delete_school_card',
                'manage_school_card_settings',
            ],
            self::ADMIN, self::FEDERATION, self::LIGUE, self::MAITRE,
            self::MANAGER, self::USER, self::GUEST => [],
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::SUPERADMIN => 6,
            self::PRESIDENT => 5,
            self::VPRESIDENT => 4,
            self::SEGAL => 3,
            self::DTN => 2,
            self::ADMIN_SCOLAIRE => 1,
            self::ADMIN => 1,
            self::FEDERATION => 1,
            self::LIGUE => 0,
            self::MAITRE => 0,
            self::MANAGER => 0,
            self::USER => -1,
            self::GUEST => -2,
        };
    }

    public function canManage(UserRole $other): bool
    {
        return $this->level() > $other->level();
    }

    public static function visibleBy(?User $user): array
    {
        if (!$user) {
            return [];
        }

        $currentRole = self::tryFrom($user->role);
        if (!$currentRole) {
            return [];
        }

        if ($currentRole === self::SUPERADMIN) {
            return array_values(array_filter(
                self::cases(),
                fn (self $role) => $role !== self::SUPERADMIN
            ));
        }

        if ($currentRole === self::PRESIDENT) {
            return [self::VPRESIDENT, self::SEGAL, self::DTN];
        }

        return array_values(array_filter(
            self::cases(),
            fn (self $role) => $role !== self::SUPERADMIN && $currentRole->canManage($role)
        ));
    }

    /**
     * Rôles qu'un utilisateur peut créer (copie fidèle de getRoleOptions de Parametres.jsx) :
     *   superadmin              → Fédération, Ligue, Maître
     *   fédération + PRESIDENT  → Fédération, Ligue, Maître
     *   fédération (délégué)    → Ligue, Maître
     *   ligue                   → Ligue, Maître
     *   maître                  → (aucun)
     */
    public static function assignableBy(?User $user): array
    {
        if (!$user) {
            return [self::FEDERATION, self::LIGUE, self::MAITRE];
        }

        $role = $user->role instanceof self ? $user->role->value : (string) $user->role;
        $fonction = strtoupper((string) ($user->fonction ?? ''));

        return match ($role) {
            self::SUPERADMIN->value => [self::FEDERATION, self::LIGUE, self::MAITRE],
            self::FEDERATION->value, self::ADMIN->value => $fonction === 'PRESIDENT'
                ? [self::FEDERATION, self::LIGUE, self::MAITRE]
                : [self::LIGUE, self::MAITRE],
            self::LIGUE->value => [self::LIGUE, self::MAITRE],
            default => [],
        };
    }

    /**
     * Options de fonction pour un rôle cible, selon l'émetteur
     * (copie fidèle de getFunctionOptions de Parametres.jsx).
     */
    public static function functionOptions(?User $user, string $targetRole): array
    {
        $adminFunctions = ['PRESIDENT', 'SEGAL', 'DTN', 'TRESORIER'];
        $adminDelegate = ['SEGAL', 'DTN', 'TRESORIER'];
        $ligueFunctions = ['PRESIDENT_LIGUE', 'SEGAL_LIGUE', 'DTN_LIGUE', 'TRESORIER_LIGUE'];

        if (!$user) {
            return [];
        }

        $role = $user->role instanceof self ? $user->role->value : (string) $user->role;
        $fonction = strtoupper((string) ($user->fonction ?? ''));

        if ($role === self::SUPERADMIN->value) {
            if ($targetRole === self::FEDERATION->value) return $adminFunctions;
            if ($targetRole === self::LIGUE->value) return $ligueFunctions;
            return [];
        }

        if (in_array($role, [self::FEDERATION->value, self::ADMIN->value], true) && $fonction === 'PRESIDENT') {
            return $targetRole === self::FEDERATION->value ? $adminDelegate : [];
        }

        if ($role === self::LIGUE->value) {
            return $targetRole === self::LIGUE->value ? $ligueFunctions : [];
        }

        return [];
    }
}
