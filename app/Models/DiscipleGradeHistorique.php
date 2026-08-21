<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Date d'obtention de chaque grade franchi par un disciple — au contraire de
 * disciples.date_obtention_grade (qui ne garde que la date du grade ACTUEL),
 * une ligne par grade obtenu. Sert à remplir automatiquement le tableau des
 * grades au verso de la carte de licence (planche.blade.php).
 */
class DiscipleGradeHistorique extends Model
{
    protected $table = 'disciple_grade_historiques';

    protected $fillable = [
        'disciple_id',
        'grade_id',
        'date_obtention',
    ];

    protected $casts = [
        'date_obtention' => 'date',
    ];

    public function disciple(): BelongsTo
    {
        return $this->belongsTo(Disciple::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }
}
