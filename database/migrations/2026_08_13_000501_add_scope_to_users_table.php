<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Le rôle passe en VARCHAR pour accepter les rôles multi-tenant (federation/ligue/maitre)
        DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(40) NOT NULL DEFAULT 'user'");

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('federation_id')->nullable()->after('role');
            $table->unsignedBigInteger('ligue_id')->nullable()->after('federation_id');
            $table->unsignedBigInteger('salle_id')->nullable()->after('ligue_id');
            $table->unsignedBigInteger('grade_id')->nullable()->after('salle_id');
            $table->string('fonction', 60)->nullable()->after('grade_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['federation_id', 'ligue_id', 'salle_id', 'grade_id', 'fonction']);
        });
    }
};
