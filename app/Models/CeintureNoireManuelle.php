<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CeintureNoireManuelle extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'salle_id' => 'salle_id',
        'ligue_id' => 'ligue_id',
        'federation_id' => 'federation_id',
    ];

    protected $table = 'ceintures_noires_manuelles';

    protected $fillable = [
        'nom',
        'prenom',
        'sexe',
        'date_naissance',
        'date_lieu_naissance',
        'adresse',
        'telephone',
        'nmle',
        'photo_path',
        'grade_id',
        'federation_id',
        'ligue_id',
        'salle_id',
        'date_obtention_grade',
        'archived_at',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_obtention_grade' => 'date',
        'archived_at' => 'datetime',
    ];

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

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

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }
}
