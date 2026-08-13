<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradePassageTariff extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'federation_id' => 'federation_id',
    ];

    protected $table = 'grade_passage_tariffs';

    protected $fillable = [
        'type_grade',
        'federation_id',
        'ligue_id',
        'grade_id',
        'tarif_label',
        'ceinture_keys',
        'montant',
        'active',
        'created_by_user_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }
}
