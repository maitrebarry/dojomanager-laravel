<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'federation_id' => 'federation_id',
    ];

    protected $fillable = [
        'niveau',
        'nom_grade',
        'ceinture',
        'federation_id',
        'type_grade',
    ];

    protected $casts = [
        'niveau' => 'integer',
    ];

    public function federation(): BelongsTo
    {
        return $this->belongsTo(Federation::class);
    }

    public function disciples(): HasMany
    {
        return $this->hasMany(Disciple::class);
    }
}
