<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'salle_id' => 'salle_id',
        'ligue_id' => 'ligue_id',
        'federation_id' => 'federation_id',
    ];

    protected $fillable = [
        'user_id',
        'role',
        'federation_id',
        'ligue_id',
        'salle_id',
        'master_name',
        'master_grade',
        'signature_data',
    ];

    /** Signature du maître d'une salle (même résolution que les cartes de licence). */
    public static function forSalle(?int $salleId): ?self
    {
        if (!$salleId) {
            return null;
        }

        return static::whereNotNull('signature_data')
            ->where('salle_id', $salleId)
            ->latest('id')
            ->first();
    }
}
