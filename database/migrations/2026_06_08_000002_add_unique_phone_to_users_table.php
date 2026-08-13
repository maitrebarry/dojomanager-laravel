<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (empty(DB::select('SHOW INDEX FROM `users` WHERE Key_name = ?', ['users_phone_unique']))) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('phone');
            });
        }
    }

    public function down(): void
    {
        if (!empty(DB::select('SHOW INDEX FROM `users` WHERE Key_name = ?', ['users_phone_unique']))) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['phone']);
            });
        }
    }
};
