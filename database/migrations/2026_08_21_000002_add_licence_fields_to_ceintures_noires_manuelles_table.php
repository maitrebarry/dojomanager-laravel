<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ceintures_noires_manuelles', function (Blueprint $table) {
            $table->date('date_naissance')->nullable()->after('sexe');
            $table->string('date_lieu_naissance', 255)->nullable()->after('date_naissance');
            $table->string('adresse', 255)->nullable()->after('date_lieu_naissance');
            $table->string('telephone', 40)->nullable()->after('adresse');
            $table->string('nmle', 50)->nullable()->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('ceintures_noires_manuelles', function (Blueprint $table) {
            $table->dropColumn(['date_naissance', 'date_lieu_naissance', 'adresse', 'telephone', 'nmle']);
        });
    }
};
