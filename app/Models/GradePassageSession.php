<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradePassageSession extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'federation_id' => 'federation_id',
    ];

    protected $table = 'grade_passage_sessions';

    protected $fillable = [
        'date_session',
        'lieu',
        'type_grade',
        'frais_participation',
        'type_notation',
        'bareme',
        'federation_id',
        'ligue_id',
        'annonce',
        'finalisee',
        'created_by_user_id',
    ];

    protected $casts = [
        'date_session' => 'date',
        'frais_participation' => 'decimal:2',
        'bareme' => 'integer',
        'finalisee' => 'boolean',
    ];

    public function candidats(): HasMany
    {
        return $this->hasMany(GradePassageCandidate::class, 'session_id');
    }
}
