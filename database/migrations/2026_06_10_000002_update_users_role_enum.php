<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            DB::statement(
                "ALTER TABLE `users` MODIFY `role` ENUM('superadmin', 'president', 'vpresident', 'segal', 'dtn', 'admin', 'manager', 'user', 'guest') NOT NULL DEFAULT 'user'"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            DB::statement(
                "ALTER TABLE `users` MODIFY `role` ENUM('superadmin', 'admin', 'manager', 'user', 'guest') NOT NULL DEFAULT 'user'"
            );
        }
    }
};
