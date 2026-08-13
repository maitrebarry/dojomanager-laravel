<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competition extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'date_competition',
        'lieu',
        'type',
    ];

    protected $casts = [
        'date_competition' => 'date',
    ];

    public function combats(): HasMany
    {
        return $this->hasMany(Combat::class);
    }
}
