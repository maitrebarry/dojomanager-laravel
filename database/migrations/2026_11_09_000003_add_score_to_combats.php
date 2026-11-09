<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Résultat d'un combat : score ou issue libre (ex. « 2-1 », « 12-8 », « KO », « disqualification »). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('combats', function (Blueprint $table) {
            $table->string('score', 40)->nullable()->after('vainqueur_athlete_id');
        });
    }

    public function down(): void
    {
        Schema::table('combats', function (Blueprint $table) {
            $table->dropColumn('score');
        });
    }
};
