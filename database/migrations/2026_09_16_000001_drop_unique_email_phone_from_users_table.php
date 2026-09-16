<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Plusieurs comptes (fédération/ligue/maître/disciple) peuvent désormais partager le
// même email ou le même numéro de téléphone (ex. téléphone du foyer). Le téléphone
// servant d'identifiant de connexion, un numéro partagé par plusieurs comptes déclenche
// désormais un choix de compte à la connexion (cf. AuthController::chooseAccount).
return new class extends Migration
{
    public function up(): void
    {
        if (!empty(DB::select('SHOW INDEX FROM `users` WHERE Key_name = ?', ['users_email_unique']))) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });
        }

        if (!empty(DB::select('SHOW INDEX FROM `users` WHERE Key_name = ?', ['users_phone_unique']))) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['phone']);
            });
        }
    }

    public function down(): void
    {
        if (empty(DB::select('SHOW INDEX FROM `users` WHERE Key_name = ?', ['users_email_unique']))) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('email');
            });
        }

        if (empty(DB::select('SHOW INDEX FROM `users` WHERE Key_name = ?', ['users_phone_unique']))) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('phone');
            });
        }
    }
};
