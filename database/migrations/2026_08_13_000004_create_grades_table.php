<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->integer('niveau');
            $table->string('nom_grade', 120);
            $table->string('ceinture', 60);
            $table->foreignId('federation_id')->constrained('federations')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('type_grade', ['KEUP', 'DAN']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
