<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combats', function (Blueprint $table) {
            $table->id();
            $table->string('tour', 50);
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('combattant1_id')->nullable()->constrained('disciples')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('combattant2_id')->nullable()->constrained('disciples')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('vainqueur_id')->nullable()->constrained('disciples')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combats');
    }
};
