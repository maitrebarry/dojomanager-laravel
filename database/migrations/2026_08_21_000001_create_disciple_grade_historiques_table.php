<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciple_grade_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciple_id')->constrained('disciples')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->date('date_obtention');
            $table->timestamps();

            $table->unique(['disciple_id', 'grade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciple_grade_historiques');
    }
};
