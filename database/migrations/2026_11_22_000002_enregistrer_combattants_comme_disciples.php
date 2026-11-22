<?php

use App\Models\CompetitionAthlete;
use App\Services\Competition\AthleteRegistry;
use Illuminate\Database\Migrations\Migration;

/**
 * Combattants inscrits avant la règle « le club est la salle » : ceux qui ont un club
 * deviennent disciples de leur salle (créée sans maître au besoin), comme les nouveaux.
 * Ceux sans club restent dans Pratiquants → « Sans salle », en attente de leur club.
 */
return new class extends Migration
{
    public function up(): void
    {
        CompetitionAthlete::query()
            ->whereNull('disciple_id')
            ->whereNotNull('club')->where('club', '!=', '')
            ->whereHas('competition', fn ($q) => $q->whereNotNull('ligue_id'))
            ->orderBy('id')
            ->get()
            ->each(function (CompetitionAthlete $athlete) {
                // Une inscription déjà reliée par un passage précédent (même personne) est sautée.
                if (!$athlete->fresh()->disciple_id) {
                    AthleteRegistry::sync($athlete);
                }
            });
    }

    public function down(): void
    {
        // Les disciples créés restent : ce sont de vraies fiches, à archiver au besoin.
    }
};
