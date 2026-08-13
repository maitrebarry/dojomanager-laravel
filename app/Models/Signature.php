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
}
