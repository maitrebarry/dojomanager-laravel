<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ligue extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'salle_id' => 'salles.id',
        'ligue_id' => 'id',
        'federation_id' => 'federation_id',
    ];

    protected $table = 'ligues';

    protected $fillable = [
        'nom',
        'region',
        'active',
        'federation_id',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function federation(): BelongsTo
    {
        return $this->belongsTo(Federation::class);
    }

    public function salles(): HasMany
    {
        return $this->hasMany(Salle::class);
    }
}
