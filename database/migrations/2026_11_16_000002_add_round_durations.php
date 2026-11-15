<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Durée des manches réglable : pour la compétition (manche, repos, nombre de manches) et
 * par catégorie (ex. cadets 1 min, seniors 1 min 30). Vide = valeur du niveau au-dessus
 * (catégorie → compétition → discipline). Utilisé par le chrono et le rapport journalier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedSmallInteger('manche_s')->nullable()->after('tolerance_g');
            $table->unsignedSmallInteger('repos_s')->nullable()->after('manche_s');
            $table->unsignedTinyInteger('manches')->nullable()->after('repos_s');
        });

        Schema::table('competition_categories', function (Blueprint $table) {
            $table->unsignedSmallInteger('manche_s')->nullable()->after('poids_max');
        });
    }

    public function down(): void
    {
        Schema::table('competition_categories', fn (Blueprint $table) => $table->dropColumn('manche_s'));
        Schema::table('competitions', fn (Blueprint $table) => $table->dropColumn(['manche_s', 'repos_s', 'manches']));
    }
};
