<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotisation extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'salle_id' => 'disciple.salle_id',
        'ligue_id' => 'disciple.salle.ligue_id',
        'federation_id' => 'disciple.salle.ligue.federation_id',
    ];

    protected $fillable = [
        'mois',
        'annee',
        'montant',
        'montant_paye',
        'reste_a_payer',
        'date_paiement',
        'statut',
        'disciple_id',
    ];

    protected $casts = [
        'mois' => 'integer',
        'annee' => 'integer',
        'montant' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'reste_a_payer' => 'decimal:2',
        'date_paiement' => 'date',
    ];

    public const MOIS = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    public function disciple(): BelongsTo
    {
        return $this->belongsTo(Disciple::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(CotisationPaiement::class);
    }

    public function moisLabel(): string
    {
        return self::MOIS[$this->mois] ?? (string) $this->mois;
    }

    public function recompute(): void
    {
        $paye = (float) $this->paiements()->sum('montant');
        $this->montant_paye = $paye;
        $this->reste_a_payer = max(0, (float) $this->montant - $paye);
        $this->date_paiement = optional($this->paiements()->latest('date_paiement')->first())->date_paiement;

        if ($paye <= 0) {
            $this->statut = 'IMPAYE';
        } elseif ($this->reste_a_payer > 0) {
            $this->statut = 'PARTIEL';
        } else {
            $this->statut = 'PAYE';
        }

        $this->save();
    }

    public function statutColor(): string
    {
        return match ($this->statut) {
            'PAYE' => 'success',
            'PARTIEL' => 'warning',
            default => 'danger',
        };
    }
}
