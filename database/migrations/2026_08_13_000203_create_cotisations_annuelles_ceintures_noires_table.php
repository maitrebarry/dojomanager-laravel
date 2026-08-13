<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotisations_annuelles_ceintures_noires', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotisation_annuelle_id');
            $table->foreign('cotisation_annuelle_id', 'ca_cn_campagne_fk')
                ->references('id')->on('cotisations_annuelles')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('origine', 30); // DISCIPLE | MANUELLE
            $table->unsignedBigInteger('source_id');
            $table->string('nom', 120);
            $table->string('prenom', 120);
            $table->string('sexe', 10)->nullable();
            $table->string('user_role', 40)->nullable();
            $table->string('grade_nom', 120)->nullable();
            $table->unsignedBigInteger('federation_id')->nullable();
            $table->string('federation_nom', 160)->nullable();
            $table->unsignedBigInteger('ligue_id')->nullable();
            $table->string('ligue_nom', 160)->nullable();
            $table->unsignedBigInteger('salle_id')->nullable();
            $table->string('salle_nom', 160)->nullable();
            $table->decimal('montant_du', 12, 2);
            $table->decimal('montant_paye', 12, 2)->default(0);
            $table->decimal('reste_a_payer', 12, 2)->default(0);
            $table->string('statut', 20)->default('IMPAYE');
            $table->timestamps();

            $table->index(['cotisation_annuelle_id', 'statut'], 'ca_cn_campagne_statut_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotisations_annuelles_ceintures_noires');
    }
};
