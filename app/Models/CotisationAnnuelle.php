<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CotisationAnnuelle extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'federation_id' => 'federation_id',
    ];

    protected $table = 'cotisations_annuelles';

    protected $fillable = [
        'annee',
        'montant',
        'type',
        'federation_id',
        'ligue_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'annee' => 'integer',
        'montant' => 'decimal:2',
    ];

    public function membres(): HasMany
    {
        return $this->hasMany(CotisationAnnuelleCeintureNoire::class, 'cotisation_annuelle_id');
    }
}
