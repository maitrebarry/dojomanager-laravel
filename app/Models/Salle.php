<?php

namespace App\Models;

use App\Models\Concerns\ScopedToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Salle extends Model
{
    use HasFactory, ScopedToUser;

    protected static array $scopePaths = [
        'salle_id' => 'id',
        'ligue_id' => 'ligue_id',
        'federation_id' => 'ligue.federation_id',
    ];

    protected $fillable = [
        'nom',
        'adresse',
        'telephone',
        'mensualite',
        'active',
        'ligue_id',
        'maitre_id',
        'salle_mere_id',
    ];

    protected $casts = [
        'mensualite' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function ligue(): BelongsTo
    {
        return $this->belongsTo(Ligue::class);
    }

    /** Salle mère dont cette salle dérive (filiation, information seulement). */
    public function salleMere(): BelongsTo
    {
        return $this->belongsTo(Salle::class, 'salle_mere_id');
    }

    /** Salles dérivées de celle-ci. */
    public function sallesDerivees(): HasMany
    {
        return $this->hasMany(Salle::class, 'salle_mere_id')->orderBy('nom');
    }

    /**
     * Salles à ne pas proposer comme salle mère de celle-ci : elle-même et toute sa
     * descendance (sinon la filiation tournerait en rond).
     */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];
        while ($frontier) {
            $frontier = Salle::whereIn('salle_mere_id', $frontier)->whereNotIn('id', $ids)->pluck('id')->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    /**
     * Range des salles en arbre de filiation : chaque salle mère suivie de ses salles
     * dérivées (attribut « depth » = niveau, 0 pour une salle sans mère dans la liste).
     */
    public static function tree($salles)
    {
        $ids = $salles->pluck('id')->flip();
        $children = $salles->groupBy(fn ($s) => $s->salle_mere_id && isset($ids[$s->salle_mere_id]) ? $s->salle_mere_id : 0);
        $out = collect();
        $seen = [];
        $walk = function ($parentId, $depth) use (&$walk, $children, $out, &$seen) {
            foreach ($children[$parentId] ?? [] as $salle) {
                if (isset($seen[$salle->id])) {
                    continue;
                }
                $seen[$salle->id] = true;
                $salle->depth = $depth;
                $out->push($salle);
                $walk($salle->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $out->concat($salles->reject(fn ($s) => isset($seen[$s->id]))); // rien n'est perdu
    }

    public function maitre(): BelongsTo
    {
        return $this->belongsTo(Maitre::class);
    }

    public function disciples(): HasMany
    {
        return $this->hasMany(Disciple::class);
    }

    /**
     * Compte utilisateur (rôle maître) rattaché à cette salle via users.salle_id.
     * Distinct de la fiche « Maitre » (nom_complet/téléphone/grade), qui doit être
     * créée et liée séparément via le module Maîtres — beaucoup de salles n'ont que
     * le compte de connexion, sans fiche associée.
     */
    public function maitreUser(): HasOne
    {
        return $this->hasOne(User::class, 'salle_id')->where('role', 'maitre');
    }

    /**
     * Nom du maître à afficher : la fiche Maitre si elle existe, sinon le compte
     * utilisateur maître de la salle (évite un « Non affecté » trompeur quand la
     * salle a bien un maître connecté mais pas de fiche Maitre renseignée).
     */
    public function getMaitreDisplayNameAttribute(): ?string
    {
        return $this->maitre?->nom_complet ?: $this->maitreUser?->name;
    }

    /** Même repli que getMaitreDisplayNameAttribute(), pour le grade affiché en signature. */
    public function getMaitreDisplayGradeAttribute(): ?string
    {
        return $this->maitre?->grade ?: $this->maitreUser?->grade?->nom_grade;
    }
}
