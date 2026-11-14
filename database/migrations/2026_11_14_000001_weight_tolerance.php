<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pesée au gramme près et tolérance par compétition : un athlète reste dans sa
 * catégorie s'il dépasse la limite d'au plus tolerance_g grammes (5 g par défaut),
 * sinon il passe à la catégorie suivante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_athletes', function (Blueprint $table) {
            $table->decimal('poids', 6, 3)->nullable()->change();
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedSmallInteger('tolerance_g')->default(5)->after('bronze');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', fn (Blueprint $table) => $table->dropColumn('tolerance_g'));

        Schema::table('competition_athletes', function (Blueprint $table) {
            $table->decimal('poids', 5, 1)->nullable()->change();
        });
    }
};
