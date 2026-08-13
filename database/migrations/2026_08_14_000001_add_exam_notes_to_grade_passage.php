<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_passage_candidates', function (Blueprint $table) {
            $table->decimal('note_forme', 8, 2)->nullable()->after('note_globale');
            $table->decimal('note_mouvement_base', 8, 2)->nullable()->after('note_forme');
            $table->decimal('note_poomsea', 8, 2)->nullable()->after('note_mouvement_base');
            $table->decimal('note_attaque_defense', 8, 2)->nullable()->after('note_poomsea');
            $table->decimal('note_combat', 8, 2)->nullable()->after('note_attaque_defense');
            $table->decimal('moyenne_generale', 8, 2)->nullable()->after('note_combat');
        });

        Schema::table('grade_passage_sessions', function (Blueprint $table) {
            // Barème par critère : 20 (SUR_20) ou 100 (SUR_100)
            $table->unsignedSmallInteger('bareme')->default(20)->after('type_notation');
        });
    }

    public function down(): void
    {
        Schema::table('grade_passage_candidates', function (Blueprint $table) {
            $table->dropColumn(['note_forme', 'note_mouvement_base', 'note_poomsea', 'note_attaque_defense', 'note_combat', 'moyenne_generale']);
        });
        Schema::table('grade_passage_sessions', function (Blueprint $table) {
            $table->dropColumn('bareme');
        });
    }
};
