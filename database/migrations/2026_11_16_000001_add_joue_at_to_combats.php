<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Heure à laquelle le résultat d'un combat a été enregistré : sert au rapport journalier
 * (combats du jour, rythme, prévision de fin). Les résultats déjà saisis reprennent leur
 * date de dernière modification.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('combats', function (Blueprint $table) {
            $table->timestamp('joue_at')->nullable()->after('score');
        });

        DB::table('combats')->whereNotNull('vainqueur_athlete_id')->where('exempt', false)
            ->update(['joue_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('combats', fn (Blueprint $table) => $table->dropColumn('joue_at'));
    }
};
