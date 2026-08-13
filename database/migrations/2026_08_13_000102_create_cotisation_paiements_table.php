<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotisation_paiements', function (Blueprint $table) {
            $table->id();
            $table->decimal('montant', 12, 2);
            $table->string('mode_paiement', 30);
            $table->string('reference_paiement', 80)->nullable();
            $table->date('date_paiement');
            $table->string('note', 255)->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->foreignId('cotisation_id')->constrained('cotisations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotisation_paiements');
    }
};
