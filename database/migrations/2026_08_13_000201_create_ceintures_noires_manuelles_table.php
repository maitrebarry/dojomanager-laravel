<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ceintures_noires_manuelles', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 120);
            $table->string('prenom', 120);
            $table->string('sexe', 10)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('federation_id')->constrained('federations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('ligue_id')->nullable()->constrained('ligues')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('salle_id')->nullable()->constrained('salles')->cascadeOnUpdate()->nullOnDelete();
            $table->date('date_obtention_grade');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ceintures_noires_manuelles');
    }
};
