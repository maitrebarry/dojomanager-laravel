<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Maitre extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'salle_id' => 'salles.id',
        'ligue_id' => 'salles.ligue_id',
        'federation_id' => 'salles.ligue.federation_id',
    ];

    protected $table = 'maitres';

    protected $fillable = [
        'nom_complet',
        'telephone',
        'email',
        'grade',
    ];

    public function salles(): HasMany
    {
        return $this->hasMany(Salle::class);
    }
}
