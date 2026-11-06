<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immersion : archives municipales, petites annonces, journaux personnels,
 * habitations, enquêtes et indices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipal_records', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('title', 180);
            $table->string('category', 20)->index();
            $table->string('reference', 40)->nullable();
            $table->date('document_date')->nullable()->index();
            $table->string('excerpt', 600)->nullable();
            $table->longText('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_credit')->nullable();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('classified_ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->string('character_name', 120);
            $table->string('category', 20)->index();
            $table->string('title', 120);
            $table->text('body');
            $table->string('contact', 200)->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 150)->nullable();
            $table->string('rp_date', 80)->nullable();
            $table->longText('body');
            $table->boolean('is_private')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['character_id', 'is_private']);
        });

        Schema::create('residences', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 150);
            $table->string('kind', 20)->default('maison');
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->unsignedTinyInteger('capacity')->default(2);
            $table->boolean('is_open')->default(true);
            $table->string('summary', 400)->nullable();
            $table->longText('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_credit')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->foreignId('residence_id')->nullable()->after('family_id')->constrained('residences')->nullOnDelete();
        });

        Schema::create('investigations', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('title', 180);
            $table->string('status', 12)->default('ouverte')->index();
            $table->string('summary', 600)->nullable();
            $table->longText('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_credit')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('clues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('title', 180);
            $table->longText('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_credit')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_shareable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('character_clue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clue_id')->constrained('clues')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('shared_by_character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->timestamps();
            $table->unique(['clue_id', 'character_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_clue');
        Schema::dropIfExists('clues');
        Schema::dropIfExists('investigations');
        Schema::table('characters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('residence_id');
        });
        Schema::dropIfExists('residences');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('classified_ads');
        Schema::dropIfExists('municipal_records');
    }
};
