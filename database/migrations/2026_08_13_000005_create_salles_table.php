<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salles', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 140);
            $table->string('adresse', 255)->nullable();
            $table->string('telephone', 40)->nullable();
            $table->decimal('mensualite', 12, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('ligue_id')->constrained('ligues')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('maitre_id')->nullable()->constrained('maitres')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salles');
    }
};
