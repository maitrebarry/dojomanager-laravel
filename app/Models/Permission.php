<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Permission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'module',
        'action',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Récupérer tous les utilisateurs qui possèdent cette permission
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withTimestamps()
            ->withPivot('granted_by', 'reason', 'granted_at');
    }

    /**
     * Récupérer l'utilisateur qui a accordé cette permission
     */
    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Scopes pour les requêtes courantes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('module')->orderBy('order');
    }

    public function getDisplayNameAttribute(): string
    {
        $key = "messages.permissions.names.{$this->slug}";
        $label = __($key);

        return $label === $key ? $this->name : $label;
    }

    public function getModuleKeyAttribute(): string
    {
        if (Str::contains($this->slug, ['school_card'])) {
            return 'school_cards';
        }

        if (Str::contains($this->slug, ['licence_holder'])) {
            return 'cards';
        }

        if (Str::contains($this->slug, ['permission'])) {
            return 'permissions';
        }

        if (Str::contains($this->slug, ['user'])) {
            return 'users';
        }

        if (Str::contains($this->slug, ['setting'])) {
            return 'settings';
        }

        return Str::slug((string) $this->module, '_');
    }

    public function getDisplayModuleAttribute(): string
    {
        $key = "messages.permissions.modules.{$this->module_key}";
        $label = __($key);

        return $label === $key ? $this->module : $label;
    }

    /**
     * Récupérer toutes les permissions groupées par module
     */
    public static function groupedByModule()
    {
        return static::active()
            ->ordered()
            ->get()
            ->groupBy('module');
    }

    /**
     * Récupérer les permissions pour un module donné
     */
    public static function forModule(string $module)
    {
        return static::byModule($module)->active()->get();
    }
}
