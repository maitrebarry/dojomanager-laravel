<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Disciple extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'salle_id' => 'salle_id',
        'ligue_id' => 'salle.ligue_id',
        'federation_id' => 'salle.ligue.federation_id',
    ];

    protected $fillable = [
        'nom',
        'prenom',
        'sexe',
        'date_naissance',
        'date_lieu_naissance',
        'adresse',
        'date_inscription',
        'grade_id',
        'date_obtention_grade',
        'telephone',
        'nmle',
        'photo',
        'nom_complet',
        'archived_at',
        'salle_id',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_inscription' => 'date',
        'date_obtention_grade' => 'date',
        'archived_at' => 'datetime',
    ];

    protected $appends = [
        'full_name',
        'photo_url',
        'is_archived',
        'status_label',
        'status_color',
    ];

    /* -------------------- Relations -------------------- */

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function cotisations(): HasMany
    {
        return $this->hasMany(Cotisation::class);
    }

    /* -------------------- Scopes -------------------- */

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /* -------------------- Accessors -------------------- */

    public function getFullNameAttribute(): string
    {
        if (!empty($this->attributes['nom_complet'])) {
            return $this->attributes['nom_complet'];
        }

        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (empty($this->photo)) {
            return null;
        }

        if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
            return $this->photo;
        }

        return Storage::disk('public')->url($this->photo);
    }

    public function getIsArchivedAttribute(): bool
    {
        return !is_null($this->archived_at);
    }

    /** Âge calculé depuis la date de naissance (comme calculateAge côté React). */
    public function getAgeAttribute(): ?int
    {
        return $this->date_naissance ? $this->date_naissance->age : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_archived ? __('messages.disciples.archived') : __('messages.disciples.active');
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_archived ? 'secondary' : 'success';
    }
}
