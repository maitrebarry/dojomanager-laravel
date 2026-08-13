<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Salle extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'salle_id' => 'id',
        'ligue_id' => 'ligue_id',
        'federation_id' => 'ligue.federation_id',
    ];

    protected $fillable = [
        'nom',
        'adresse',
        'telephone',
        'mensualite',
        'active',
        'ligue_id',
        'maitre_id',
    ];

    protected $casts = [
        'mensualite' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function ligue(): BelongsTo
    {
        return $this->belongsTo(Ligue::class);
    }

    public function maitre(): BelongsTo
    {
        return $this->belongsTo(Maitre::class);
    }

    public function disciples(): HasMany
    {
        return $this->hasMany(Disciple::class);
    }
}
