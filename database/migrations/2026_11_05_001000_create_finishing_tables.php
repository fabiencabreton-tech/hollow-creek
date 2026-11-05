<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Finitions : index de recherche, recensement d'activité, archives secrètes de l'équipe.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Index plein texte (MariaDB) : ignorés ailleurs, la recherche passe alors par LIKE.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('characters', fn (Blueprint $table) => $table->fullText(['first_name', 'last_name', 'profession'], 'characters_recherche'));
            Schema::table('scenes', fn (Blueprint $table) => $table->fullText(['title'], 'scenes_recherche'));
            Schema::table('faceclaims', fn (Blueprint $table) => $table->fullText(['name'], 'faceclaims_recherche'));
            Schema::table('gazette_articles', fn (Blueprint $table) => $table->fullText(['title', 'excerpt'], 'gazette_recherche'));
        }

        Schema::create('censuses', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('message')->nullable();
            $table->timestamp('ends_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('census_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('census_id')->constrained('censuses')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['census_id', 'character_id']);
        });

        Schema::create('lore_entries', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('category', 60)->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lore_entries');
        Schema::dropIfExists('census_responses');
        Schema::dropIfExists('censuses');

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('gazette_articles', fn (Blueprint $table) => $table->dropFullText('gazette_recherche'));
            Schema::table('faceclaims', fn (Blueprint $table) => $table->dropFullText('faceclaims_recherche'));
            Schema::table('scenes', fn (Blueprint $table) => $table->dropFullText('scenes_recherche'));
            Schema::table('characters', fn (Blueprint $table) => $table->dropFullText('characters_recherche'));
        }
    }
};
