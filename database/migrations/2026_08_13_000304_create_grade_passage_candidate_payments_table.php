<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_passage_candidate_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->foreign('candidate_id', 'gp_payment_candidate_fk')
                ->references('id')->on('grade_passage_candidates')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->string('mode_paiement', 40);
            $table->string('reference_paiement', 120)->nullable();
            $table->date('date_paiement');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_passage_candidate_payments');
    }
};
