<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('role', 40)->nullable();
            $table->unsignedBigInteger('federation_id')->nullable();
            $table->unsignedBigInteger('ligue_id')->nullable();
            $table->unsignedBigInteger('salle_id')->nullable();
            $table->string('master_name', 200)->nullable();
            $table->string('master_grade', 80)->nullable();
            $table->text('signature_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
