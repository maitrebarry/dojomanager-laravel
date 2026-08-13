<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Federation extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'federation_id' => 'id',
    ];

    protected $fillable = [
        'nom',
        'adresse',
        'telephone',
        'sigle',
        'email',
    ];

    public function ligues(): HasMany
    {
        return $this->hasMany(Ligue::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }
}
