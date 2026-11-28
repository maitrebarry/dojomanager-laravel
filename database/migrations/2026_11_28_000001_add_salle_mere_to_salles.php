<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Filiation des salles : une salle peut dériver d'une salle mère (lignée de maîtres).
 * Information seulement : aucun droit ni cotisation ne passe de l'une à l'autre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salles', function (Blueprint $table) {
            $table->foreignId('salle_mere_id')->nullable()->after('maitre_id')
                ->constrained('salles')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('salles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salle_mere_id');
        });
    }
};
