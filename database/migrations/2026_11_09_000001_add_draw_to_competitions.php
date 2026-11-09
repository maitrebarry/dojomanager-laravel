<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Compétition (combat / kyorugi) : périmètre ligue/fédération, catégories,
 * athlètes inscrits (disciples ou saisis librement) et tableaux de tirage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->foreignId('federation_id')->nullable()->after('type')->constrained('federations')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('ligue_id')->nullable()->after('federation_id')->constrained('ligues')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->after('ligue_id')->constrained('users')->cascadeOnUpdate()->nullOnDelete();
        });

        Schema::create('competition_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('groupe', 80);              // ex : Seniors, Juniors, Minimes
            $table->string('sexe', 1)->nullable();     // M / F (null = mixte)
            $table->unsignedTinyInteger('age_min')->nullable();
            $table->unsignedTinyInteger('age_max')->nullable();
            $table->decimal('poids_max', 5, 1)->nullable(); // null = « + » (sans limite)
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamp('tirage_at')->nullable();
            $table->boolean('tirage_valide')->default(false);
            $table->timestamps();
        });

        Schema::create('competition_athletes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('competition_categories')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('disciple_id')->nullable()->constrained('disciples')->cascadeOnUpdate()->nullOnDelete();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('sexe', 1)->nullable();
            $table->date('date_naissance')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->decimal('poids', 5, 1);
            $table->string('club', 150)->nullable();
            $table->unsignedTinyInteger('tete_de_serie')->nullable();
            $table->timestamps();

            $table->index(['competition_id', 'category_id']);
        });

        Schema::table('combats', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('competition_id')->constrained('competition_categories')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedTinyInteger('round')->nullable()->after('category_id');      // 1 = premier tour
            $table->unsignedSmallInteger('position')->nullable()->after('round');        // place dans le tour (0..)
            $table->unsignedSmallInteger('numero')->nullable()->after('position');       // numéro d'appel
            $table->foreignId('athlete1_id')->nullable()->after('numero')->constrained('competition_athletes')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('athlete2_id')->nullable()->after('athlete1_id')->constrained('competition_athletes')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('vainqueur_athlete_id')->nullable()->after('athlete2_id')->constrained('competition_athletes')->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('exempt')->default(false)->after('vainqueur_athlete_id'); // 1er tour sans adversaire
        });
    }

    public function down(): void
    {
        Schema::table('combats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('athlete1_id');
            $table->dropConstrainedForeignId('athlete2_id');
            $table->dropConstrainedForeignId('vainqueur_athlete_id');
            $table->dropColumn(['round', 'position', 'numero', 'exempt']);
        });

        Schema::dropIfExists('competition_athletes');
        Schema::dropIfExists('competition_categories');

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('federation_id');
            $table->dropConstrainedForeignId('ligue_id');
            $table->dropConstrainedForeignId('created_by_user_id');
        });
    }
};
