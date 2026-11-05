<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relations entre personnages, fil « Murmures », événements de l'équipe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('target_character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('kind', 12);
            $table->string('label', 60)->nullable();
            $table->string('status', 10)->default('proposee')->index();
            // Chaque joueur rédige sa propre description du lien.
            $table->text('description')->nullable();
            $table->text('target_description')->nullable();
            $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['target_character_id', 'status']);
            $table->index(['character_id', 'status']);
        });

        Schema::create('feed_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->string('character_name', 130);
            $table->string('kind', 10)->default('pensee');
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_credit')->nullable();
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
        });

        Schema::create('feed_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_post_id')->constrained('feed_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->string('character_name', 130);
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['feed_post_id', 'id']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('title', 150);
            $table->string('summary', 500)->nullable();
            $table->text('body')->nullable();
            $table->string('rp_date', 80)->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('status', 10)->default('annonce')->index();
            $table->boolean('registrations_open')->default(true);
            $table->string('image_path')->nullable();
            $table->string('image_credit')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['event_id', 'character_id']);
        });

        Schema::table('scenes', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('location_id')->constrained('events')->nullOnDelete();
        });

        Schema::table('gazette_articles', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('location_id')->constrained('events')->nullOnDelete();
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->string('banner_path')->nullable()->after('avatar_credit');
            $table->string('banner_credit')->nullable()->after('banner_path');
            $table->string('accent_color', 20)->nullable()->after('dialogue_color');
            $table->string('profile_layout', 12)->default('classique')->after('accent_color');
            $table->string('absence_note', 200)->nullable()->after('profile_layout');
            $table->date('absence_until')->nullable()->after('absence_note');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['banner_path', 'banner_credit', 'accent_color', 'profile_layout', 'absence_note', 'absence_until']);
        });

        Schema::table('gazette_articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
        });

        Schema::table('scenes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
        });

        Schema::dropIfExists('event_participants');
        Schema::dropIfExists('events');
        Schema::dropIfExists('feed_comments');
        Schema::dropIfExists('feed_posts');
        Schema::dropIfExists('relationships');
    }
};
