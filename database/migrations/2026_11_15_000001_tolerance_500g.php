<?php

use App\Models\Competition;
use App\Services\Competition\CategoryAssigner;
use App\Services\Competition\DrawService;
use App\Support\Discipline;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Règle de pesée du taekwondo : limite de la catégorie + 500 g. Les compétitions restées
 * sur l'ancien réglage par défaut (5 g) prennent la tolérance de leur discipline, et
 * leurs athlètes sont reclassés (hors catégories au tirage validé).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedSmallInteger('tolerance_g')->default(500)->change();
        });

        Competition::with('federation')->where('tolerance_g', 5)->get()->each(function (Competition $competition) {
            $competition->update(['tolerance_g' => Discipline::forCompetition($competition)['tolerance_g']]);
            (new CategoryAssigner($competition->fresh()))->reassignAll(app(DrawService::class));
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedSmallInteger('tolerance_g')->default(5)->change();
        });
    }
};
