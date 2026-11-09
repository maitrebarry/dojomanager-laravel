<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pesée : un athlète peut être inscrit sans poids (liste manuscrite : nom, prénom, âge)
 * et pesé plus tard. Le groupe choisi à l'import (« Seniors|M ») est gardé pour le
 * ranger une fois pesé, même si la liste ne donnait ni son sexe ni son âge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_athletes', function (Blueprint $table) {
            $table->decimal('poids', 5, 1)->nullable()->change();
            $table->string('groupe', 90)->nullable()->after('club');
        });
    }

    public function down(): void
    {
        Schema::table('competition_athletes', function (Blueprint $table) {
            $table->dropColumn('groupe');
            $table->decimal('poids', 5, 1)->nullable(false)->change();
        });
    }
};
