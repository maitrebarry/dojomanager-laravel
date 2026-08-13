<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_passage_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('date_session');
            $table->string('lieu', 180);
            $table->enum('type_grade', ['KEUP', 'DAN']);
            $table->decimal('frais_participation', 12, 2)->default(0);
            $table->string('type_notation', 10)->default('NOTE'); // NOTE | ADMIS
            $table->unsignedBigInteger('federation_id')->nullable();
            $table->unsignedBigInteger('ligue_id')->nullable();
            $table->string('annonce', 500)->nullable();
            $table->boolean('finalisee')->default(false);
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_passage_sessions');
    }
};
