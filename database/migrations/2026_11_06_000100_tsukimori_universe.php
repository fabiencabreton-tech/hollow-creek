<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tsukimori : bestiaire structuré (natures, spécialisations, conditions), champs de fiche
 * propres à chaque catégorie, registre de Kirisawa, positions sur la carte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('species', function (Blueprint $table) {
            // nature : on en choisit une ; specialisation et condition : facultatives, cumulables.
            $table->string('kind', 15)->default('nature')->after('name');
            $table->foreignId('parent_id')->nullable()->after('kind')->constrained('species')->nullOnDelete();
            $table->json('allowed_natures')->nullable()->after('parent_id');
            $table->boolean('needs_death_date')->default(false)->after('allowed_natures');
            $table->boolean('needs_registry')->default(false)->after('needs_death_date');
            $table->boolean('is_rare')->default(false)->after('needs_registry');
            $table->longText('origin')->nullable()->after('description');
            $table->longText('physical')->nullable()->after('origin');
            $table->longText('longevity')->nullable()->after('physical');
            $table->longText('weaknesses')->nullable()->after('abilities');
            $table->longText('rp_notes')->nullable()->after('limits');
            $table->longText('creation_rules')->nullable()->after('rp_notes');
        });

        // Nouveaux traitements visuels des portraits.
        foreach (['revenant' => 'revenu', 'fantome' => 'yurei', 'sorciere' => 'occulte', 'chasseur' => 'humain'] as $old => $new) {
            DB::table('species')->where('treatment', $old)->update(['treatment' => $new]);
        }

        Schema::create('kirisawa_victims', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique();
            $table->string('first_name', 60)->nullable();
            $table->string('last_name', 60)->nullable();
            $table->string('role', 12)->default('patient');
            $table->unsignedTinyInteger('age_at_death')->nullable();
            $table->text('summary')->nullable();
            // Notes de l'équipe : jamais affichées sur le site.
            $table->longText('admin_notes')->nullable();
            $table->string('status', 12)->default('administratif')->index();
            $table->unsignedBigInteger('character_id')->nullable()->index();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->date('death_date')->nullable()->after('apparent_age');
            $table->text('death_circumstances')->nullable()->after('death_date');
            $table->foreignId('kirisawa_victim_id')->nullable()->after('death_circumstances')->constrained('kirisawa_victims')->nullOnDelete();
            $table->text('abilities')->nullable()->after('personality');
            $table->text('weaknesses')->nullable()->after('abilities');
            $table->text('anchor')->nullable()->after('weaknesses');
            $table->text('emotion')->nullable()->after('anchor');
            $table->json('occult_specialties')->nullable()->after('emotion');
            $table->text('curse_origin')->nullable()->after('occult_specialties');
            $table->text('curse_effects')->nullable()->after('curse_origin');
            $table->text('curse_resolution')->nullable()->after('curse_effects');
        });

        Schema::create('character_traits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('species_id')->constrained('species')->cascadeOnDelete();

            $table->unique(['character_id', 'species_id']);
        });

        Schema::table('locations', function (Blueprint $table) {
            // Position sur la carte, en pourcentage de la largeur et de la hauteur.
            $table->decimal('map_x', 5, 2)->nullable()->after('district_id');
            $table->decimal('map_y', 5, 2)->nullable()->after('map_x');
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->decimal('map_x', 5, 2)->nullable()->after('slug');
            $table->decimal('map_y', 5, 2)->nullable()->after('map_x');
        });
    }

    public function down(): void
    {
        Schema::table('districts', fn (Blueprint $table) => $table->dropColumn(['map_x', 'map_y']));
        Schema::table('locations', fn (Blueprint $table) => $table->dropColumn(['map_x', 'map_y']));
        Schema::dropIfExists('character_traits');

        Schema::table('characters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kirisawa_victim_id');
            $table->dropColumn(['death_date', 'death_circumstances', 'abilities', 'weaknesses', 'anchor', 'emotion', 'occult_specialties', 'curse_origin', 'curse_effects', 'curse_resolution']);
        });

        Schema::dropIfExists('kirisawa_victims');

        Schema::table('species', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['kind', 'allowed_natures', 'needs_death_date', 'needs_registry', 'is_rare', 'origin', 'physical', 'longevity', 'weaknesses', 'rp_notes', 'creation_rules']);
        });
    }
};
