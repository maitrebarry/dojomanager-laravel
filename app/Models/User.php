<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Shared\Traits\HasPermissions;
use App\Shared\Enums\UserRole;

/**
 * @method bool hasAnyPermission(array $permissions)
 * @method bool hasAllPermissions(array $permissions)
 * @method void grantPermission($permission, ?string $reason = null)
 * @method void revokePermission($permission)
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasPermissions {
        HasPermissions::hasPermission as hasGranularPermission;
    }

    /** Rôles à périmètre restreint (isolation multi-tenant) */
    public const TENANT_ROLES = ['federation', 'ligue', 'maitre'];

    /** Alias legacy de permissions (repris de PermissionService.java). */
    private const LEGACY_ALIASES = [
        'CEINTURESNOIRES_CREATE' => ['GRADES_CREATE'],
        'CEINTURESNOIRES_READ' => ['GRADES_READ'],
        'CEINTURESNOIRES_UPDATE' => ['GRADES_UPDATE'],
        'CEINTURESNOIRES_DELETE' => ['GRADES_DELETE'],
        'PASSAGEGRADES_READ' => ['DISCIPLE_READ', 'DISCIPLE_UPDATE'],
        'PASSAGEGRADES_MANAGE' => ['DISCIPLE_UPDATE'],
    ];

    /** Cache mémoire des codes de permission normalisés de l'utilisateur. */
    private ?array $permissionCodeCache = null;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role',
        'status',
        'locale',
        'is_active',
        'federation_id',
        'ligue_id',
        'salle_id',
        'grade_id',
        'fonction',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /* -------------------- Relations de périmètre -------------------- */

    public function federation(): BelongsTo
    {
        return $this->belongsTo(Federation::class);
    }

    public function ligue(): BelongsTo
    {
        return $this->belongsTo(Ligue::class);
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    /* -------------------- Connexion -------------------- */

    public function recordLogin(): void
    {
        $this->last_login_at = now();
        $this->last_login_ip = request()?->ip();
        $this->save();
    }

    public function recordLogout(): void
    {
        $this->save();
    }

    /* -------------------- Rôles -------------------- */

    private function roleValue(): string
    {
        return $this->role instanceof UserRole ? $this->role->value : (string) $this->role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role && $this->roleValue() === UserRole::SUPERADMIN->value;
    }

    /**
     * Métadonnées de la carte de licence selon le rôle émetteur.
     * Fidèle à getLicenceRoleMeta() (dojo-frontend/src/utils/licenceCards.js) :
     *  - superadmin/fédération → LICENCE FÉDÉRALE (La Fédération)
     *  - ligue                → LICENCE RÉGIONALE (La Ligue)
     *  - maître               → LICENCE (Le Maître)
     */
    public function licenceMeta(): array
    {
        $role = $this->roleValue();

        if (in_array($role, [UserRole::SUPERADMIN->value, UserRole::PRESIDENT->value, UserRole::ADMIN->value, 'federation'], true)) {
            return [
                'badge_type' => 'LICENCE FÉDÉRALE',
                'signer' => 'La Fédération',
                'file_prefix' => 'licences_federales',
                'document_title' => 'Licences fédérales',
            ];
        }

        if ($role === 'ligue') {
            return [
                'badge_type' => 'LICENCE RÉGIONALE',
                'signer' => 'La Ligue',
                'file_prefix' => 'licences_regionales',
                'document_title' => 'Licences régionales',
            ];
        }

        return [
            'badge_type' => 'LICENCE',
            'signer' => 'Le Maître',
            'file_prefix' => 'cartes_licence',
            'document_title' => 'Cartes de licence',
        ];
    }

    public function hasFullAccess(): bool
    {
        return $this->role && in_array($this->roleValue(), [UserRole::SUPERADMIN->value, UserRole::PRESIDENT->value], true);
    }

    /** Rôle dont les données sont restreintes à un périmètre (fédération / ligue / salle). */
    public function isTenantAdmin(): bool
    {
        return in_array($this->roleValue(), self::TENANT_ROLES, true);
    }

    public function isAdmin(): bool
    {
        if ($this->hasFullAccess() || $this->isTenantAdmin()) {
            return true;
        }

        return $this->roleValue() === UserRole::ADMIN->value || $this->hasAnyActivePermission();
    }

    public function hasAnyActivePermission(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->permissions()->where('is_active', true)->exists();
    }

    /**
     * Le superadmin a tout ; les autres ont exactement les permissions assignées
     * (comparaison insensible aux accents + alias legacy, comme PermissionService.java).
     * Le périmètre des DONNÉES est géré séparément par les scopes visibleTo().
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $requested = $this->normalizePermission($permission);
        if ($requested === '') {
            return false;
        }

        $assigned = $this->permissionCodes();

        if (in_array($requested, $assigned, true)) {
            return true;
        }

        foreach (self::LEGACY_ALIASES[$requested] ?? [] as $alias) {
            if (in_array($this->normalizePermission($alias), $assigned, true)) {
                return true;
            }
        }

        return false;
    }

    /** Codes de permission normalisés (accents retirés, majuscules), mis en cache. */
    private function permissionCodes(): array
    {
        if ($this->permissionCodeCache === null) {
            $this->permissionCodeCache = $this->permissions()
                ->where('is_active', true)
                ->pluck('slug')
                ->map(fn ($code) => $this->normalizePermission($code))
                ->all();
        }

        return $this->permissionCodeCache;
    }

    /**
     * Visibilité d'une entrée de menu (copie de la logique de Sidebar.jsx) :
     * superadmin = tout ; sinon le rôle doit être dans $roles (s'il est fourni)
     * ET la permission doit être détenue (si elle est fournie).
     * $roles est exprimé dans le vocabulaire Laravel : superadmin/federation/ligue/maitre.
     */
    public function canSeeMenu(?string $permission, ?array $roles = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($roles !== null && !in_array($this->roleValue(), $roles, true)) {
            return false;
        }

        return $permission === null || $this->hasPermission($permission);
    }

    private function normalizePermission(?string $code): string
    {
        if ($code === null || $code === '') {
            return '';
        }

        $code = strtr(trim($code), [
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'À' => 'A', 'Â' => 'A', 'à' => 'a', 'â' => 'a',
            'Ô' => 'O', 'ô' => 'o', 'Î' => 'I', 'î' => 'i',
            'Ç' => 'C', 'ç' => 'c', 'Ù' => 'U', 'Û' => 'U', 'ù' => 'u', 'û' => 'u',
        ]);

        return mb_strtoupper($code);
    }

    /* -------------------- Périmètre de données -------------------- */

    /**
     * Contexte de périmètre de l'utilisateur, avec résolution ascendante :
     *   ['scoped' => bool, 'federation_id' => ?int, 'ligue_id' => ?int, 'salle_id' => ?int]
     * Un utilisateur non-tenant (superadmin, etc.) => scoped=false (voit tout).
     */
    public function scopeContext(): array
    {
        if (!$this->isTenantAdmin()) {
            return ['scoped' => false, 'federation_id' => null, 'ligue_id' => null, 'salle_id' => null];
        }

        $role = $this->roleValue();
        $federationId = $this->federation_id;
        $ligueId = $this->ligue_id;
        $salleId = $this->salle_id;

        if ($role === 'maitre') {
            $ligueId = null;
            $federationId = null;
            if ($salleId && ($salle = Salle::with('ligue')->find($salleId))) {
                $ligueId = $salle->ligue_id;
                $federationId = $salle->ligue?->federation_id;
            }
            return ['scoped' => true, 'federation_id' => $federationId, 'ligue_id' => $ligueId, 'salle_id' => $salleId];
        }

        if ($role === 'ligue') {
            if ($ligueId && ($ligue = Ligue::find($ligueId))) {
                $federationId = $ligue->federation_id;
            }
            return ['scoped' => true, 'federation_id' => $federationId, 'ligue_id' => $ligueId, 'salle_id' => null];
        }

        // federation
        return ['scoped' => true, 'federation_id' => $federationId, 'ligue_id' => null, 'salle_id' => null];
    }
}
