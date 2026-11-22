<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cotisation annuelle des salles (forfait payé par chaque salle à sa ligue) : une campagne
 * par année, par type ET par ligue (plusieurs ligues lancent chacune la leur).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotisations_annuelles', function (Blueprint $table) {
            $table->unique(['annee', 'type', 'ligue_id'], 'cotisations_annuelles_annee_type_ligue_unique');
        });
        Schema::table('cotisations_annuelles', function (Blueprint $table) {
            $table->dropUnique(['annee', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('cotisations_annuelles', function (Blueprint $table) {
            $table->unique(['annee', 'type']);
            $table->dropUnique('cotisations_annuelles_annee_type_ligue_unique');
        });
    }
};
