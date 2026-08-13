<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligues', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 120);
            $table->string('region', 255)->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('federation_id')->constrained('federations')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligues');
    }
};
