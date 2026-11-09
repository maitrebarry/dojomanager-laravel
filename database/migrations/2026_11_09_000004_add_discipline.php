<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plusieurs arts martiaux : chaque fédération a une discipline (profil dans
 * config/disciplines.php). Une compétition choisit sa règle du bronze, et un tableau
 * peut avoir un match pour la 3e place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('federations', function (Blueprint $table) {
            $table->string('discipline', 30)->default('taekwondo')->after('sigle');
        });
        // Fédérations déjà enregistrées : celles du Kyokushin reçoivent leur profil.
        DB::table('federations')->where('nom', 'like', '%kyok%')->update(['discipline' => 'kyokushin']);

        Schema::table('competitions', function (Blueprint $table) {
            $table->string('bronze', 10)->default('deux')->after('type'); // deux | match
        });

        Schema::table('combats', function (Blueprint $table) {
            $table->boolean('troisieme_place')->default(false)->after('exempt');
        });
    }

    public function down(): void
    {
        Schema::table('combats', fn (Blueprint $table) => $table->dropColumn('troisieme_place'));
        Schema::table('competitions', fn (Blueprint $table) => $table->dropColumn('bronze'));
        Schema::table('federations', fn (Blueprint $table) => $table->dropColumn('discipline'));
    }
};
