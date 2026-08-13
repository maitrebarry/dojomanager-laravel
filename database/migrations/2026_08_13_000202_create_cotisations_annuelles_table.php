<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotisations_annuelles', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('annee');
            $table->decimal('montant', 12, 2);
            $table->string('type', 20)->default('CEINTURE_NOIRE');
            $table->unsignedBigInteger('federation_id')->nullable();
            $table->unsignedBigInteger('ligue_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['annee', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotisations_annuelles');
    }
};
