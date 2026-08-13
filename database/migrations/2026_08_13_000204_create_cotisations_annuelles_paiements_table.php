<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotisations_annuelles_paiements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotisation_ceinture_noire_id');
            $table->foreign('cotisation_ceinture_noire_id', 'ca_paiement_membre_fk')
                ->references('id')->on('cotisations_annuelles_ceintures_noires')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('montant', 12, 2);
            $table->string('mode_paiement', 30);
            $table->string('reference_paiement', 80)->nullable();
            $table->date('date_paiement');
            $table->string('note', 255)->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotisations_annuelles_paiements');
    }
};
