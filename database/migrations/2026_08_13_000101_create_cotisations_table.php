<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotisations', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('mois');
            $table->unsignedSmallInteger('annee');
            $table->decimal('montant', 12, 2);
            $table->decimal('montant_paye', 12, 2)->default(0);
            $table->decimal('reste_a_payer', 12, 2)->default(0);
            $table->date('date_paiement')->nullable();
            $table->string('statut', 30)->default('IMPAYE');
            $table->foreignId('disciple_id')->constrained('disciples')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['disciple_id', 'mois', 'annee']);
            $table->index(['annee', 'mois']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotisations');
    }
};
