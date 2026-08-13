<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_passage_tariffs', function (Blueprint $table) {
            $table->id();
            $table->enum('type_grade', ['KEUP', 'DAN']);
            $table->unsignedBigInteger('federation_id')->nullable();
            $table->unsignedBigInteger('ligue_id')->nullable();
            $table->unsignedBigInteger('grade_id')->nullable();
            $table->string('tarif_label', 140);
            $table->string('ceinture_keys', 255)->nullable();
            $table->decimal('montant', 10, 2);
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_passage_tariffs');
    }
};
