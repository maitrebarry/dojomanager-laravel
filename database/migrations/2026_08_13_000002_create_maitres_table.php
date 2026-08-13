<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maitres', function (Blueprint $table) {
            $table->id();
            $table->string('nom_complet', 120);
            $table->string('telephone', 40)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('grade', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maitres');
    }
};
