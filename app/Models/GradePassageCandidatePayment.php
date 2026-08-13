<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradePassageCandidatePayment extends Model
{
    use HasFactory;

    protected $table = 'grade_passage_candidate_payments';

    protected $fillable = [
        'candidate_id',
        'montant',
        'mode_paiement',
        'reference_paiement',
        'date_paiement',
        'created_by_user_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'date',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(GradePassageCandidate::class, 'candidate_id');
    }
}
