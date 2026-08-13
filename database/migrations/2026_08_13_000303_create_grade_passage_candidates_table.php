<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_passage_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->foreign('session_id', 'gp_candidate_session_fk')
                ->references('id')->on('grade_passage_sessions')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('candidate_type', 20)->default('DISCIPLE');
            $table->unsignedBigInteger('source_id');
            $table->string('nom', 120);
            $table->string('prenom', 120);
            $table->string('sexe', 10)->nullable();
            $table->unsignedBigInteger('salle_id')->nullable();
            $table->string('salle_nom', 140)->nullable();
            $table->unsignedBigInteger('current_grade_id')->nullable();
            $table->string('current_grade_nom', 120)->nullable();
            $table->unsignedBigInteger('proposed_grade_id')->nullable();
            $table->string('proposed_grade_nom', 120)->nullable();
            $table->decimal('frais_participation', 10, 2)->default(0);
            $table->decimal('montant_paye', 10, 2)->default(0);
            $table->string('statut_paiement', 20)->default('IMPAYE'); // IMPAYE | PARTIEL | PAYE
            $table->decimal('note_globale', 8, 2)->nullable();
            $table->string('resultat', 20)->nullable(); // ADMIS | AJOURNE
            $table->string('statut', 20)->default('EN_ATTENTE'); // EN_ATTENTE | VALIDE | REFUSE
            $table->timestamps();

            $table->index(['session_id', 'statut'], 'gp_candidate_session_statut_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_passage_candidates');
    }
};
