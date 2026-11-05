<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 1 : univers et vitrine — espèces, familles, quartiers, lieux et Gazette.
     * Tous les textes s'écrivent en Markdown dans l'administration.
     */
    public function up(): void
    {
        Schema::create('species', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 80);
            // Traitement visuel des portraits : humain, revenant, fantome, medium, sorciere, chasseur.
            $table->string('treatment', 20)->default('humain');
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->longText('abilities')->nullable();
            $table->longText('limits')->nullable();
            $table->longText('story_hooks')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_credit', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 80);
            $table->text('summary')->nullable();
            $table->longText('history')->nullable();
            $table->longText('open_roles')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_credit', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 100);
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_credit', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('kind', 60)->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_credit', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['district_id', 'sort_order']);
        });

        Schema::create('gazette_articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 180);
            $table->string('rubric', 30)->index();
            $table->string('byline', 100)->default('La rédaction');
            $table->string('rp_date', 80)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('image_path')->nullable();
            $table->string('image_credit', 255)->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->string('status', 20)->default('brouillon')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gazette_articles');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('families');
        Schema::dropIfExists('species');
    }
};
