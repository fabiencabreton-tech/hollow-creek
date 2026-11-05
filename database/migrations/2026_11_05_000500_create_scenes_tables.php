<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scènes RP : participants, réponses, versions des réponses modifiées, brouillons.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->string('dialogue_color', 20)->nullable()->after('quote');
        });

        Schema::table('users', function (Blueprint $table) {
            // E-mails des scènes : toutes les réponses, seulement quand c'est son tour, ou aucun.
            $table->string('scene_mail', 10)->default('toutes')->after('remember_token');
        });

        Schema::create('scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('title', 150);
            $table->string('rp_date', 80)->nullable();
            $table->string('visibility', 10)->default('publique');
            $table->boolean('is_open')->default(false);
            $table->json('content_warnings')->nullable();
            $table->string('turn_mode', 10)->default('libre');
            $table->string('status', 12)->default('en_cours')->index();
            $table->foreignId('next_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_post_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('posts_count')->default(0);
            $table->unsignedInteger('words_count')->default(0);
            $table->timestamp('last_post_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['visibility', 'status']);
        });

        Schema::create('scene_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')->constrained('scenes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->string('status', 10)->default('invite');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedBigInteger('last_read_post_id')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            // Un joueur entre dans une scène avec un seul de ses personnages.
            $table->unique(['scene_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')->constrained('scenes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('character_id')->nullable()->constrained('characters')->nullOnDelete();
            // Nom du personnage au moment de l'écriture : l'histoire reste lisible même si le compte disparaît.
            $table->string('character_name', 130);
            $table->mediumText('body');
            $table->unsignedInteger('word_count')->default(0);
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['scene_id', 'id']);
        });

        Schema::create('post_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->mediumText('body');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('scene_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('scene_id')->constrained('scenes')->cascadeOnDelete();
            $table->mediumText('body');
            $table->timestamps();

            // Un brouillon par compte et par scène.
            $table->unique(['user_id', 'scene_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scene_drafts');
        Schema::dropIfExists('post_revisions');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('scene_participants');
        Schema::dropIfExists('scenes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('scene_mail');
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('dialogue_color');
        });
    }
};
