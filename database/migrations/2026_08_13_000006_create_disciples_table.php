<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciples', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 120);
            $table->string('prenom', 120);
            $table->string('sexe', 10)->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('date_lieu_naissance', 255)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->date('date_inscription');
            $table->foreignId('grade_id')->nullable()->constrained('grades')->cascadeOnUpdate()->nullOnDelete();
            $table->date('date_obtention_grade')->nullable();
            $table->string('telephone', 40)->nullable();
            $table->string('nmle', 50)->nullable();
            $table->string('photo', 255)->nullable();
            $table->string('nom_complet', 120)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('salle_id')->constrained('salles')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['salle_id', 'grade_id']);
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciples');
    }
};
