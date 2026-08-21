<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('date_naissance')->nullable()->after('grade_id');
            $table->string('date_lieu_naissance', 255)->nullable()->after('date_naissance');
            $table->string('adresse', 255)->nullable()->after('date_lieu_naissance');
            $table->string('matricule', 50)->nullable()->unique()->after('adresse');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['matricule']);
            $table->dropColumn(['date_naissance', 'date_lieu_naissance', 'adresse', 'matricule']);
        });
    }
};
