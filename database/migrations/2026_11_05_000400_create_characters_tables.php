<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 2 : bottin des avatars, réservations, personnages, validation, prédéfinis.
     */
    public function up(): void
    {
        // Personnalités réelles servant d'avatar (le bottin ne stocke que des noms).
        Schema::create('faceclaims', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            // Nom normalisé : « Élise Dupont » = « elise dupont ». Unicité garantie par la base.
            $table->string('name_key', 120)->unique();
            $table->string('category', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('predefined_characters', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('title', 150);
            $table->string('first_name', 60)->nullable();
            $table->string('last_name', 60)->nullable();
            $table->foreignId('species_id')->nullable()->constrained('species')->nullOnDelete();
            $table->foreignId('family_id')->nullable()->constrained('families')->nullOnDelete();
            $table->string('age_range', 40)->nullable();
            $table->string('suggested_faceclaims', 255)->nullable();
            $table->text('summary')->nullable();
            $table->longText('identity')->nullable();
            $table->longText('history')->nullable();
            $table->longText('personality')->nullable();
            $table->longText('relations')->nullable();
            $table->longText('story_hooks')->nullable();
            $table->string('status', 20)->default('ouvert')->index();
            // Personnage qui a repris ce rôle (lien simple, sans contrainte : les deux tables se référencent).
            $table->unsignedBigInteger('character_id')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug', 80)->unique();
            $table->string('first_name', 60);
            $table->string('last_name', 60)->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->unsignedSmallInteger('apparent_age')->nullable();
            $table->foreignId('species_id')->nullable()->constrained('species')->nullOnDelete();
            $table->foreignId('family_id')->nullable()->constrained('families')->nullOnDelete();
            $table->string('profession', 120)->nullable();
            $table->string('quote', 300)->nullable();
            $table->foreignId('faceclaim_id')->nullable()->constrained('faceclaims')->nullOnDelete();
            $table->string('avatar_path')->nullable();
            $table->string('avatar_credit', 255)->nullable();
            $table->longText('history')->nullable();
            $table->longText('personality')->nullable();
            $table->foreignId('predefined_character_id')->nullable()->constrained('predefined_characters')->nullOnDelete();
            $table->string('status', 30)->default('brouillon')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });

        Schema::create('faceclaim_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faceclaim_id')->constrained('faceclaims')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->string('status', 20)->index();
            // Vaut l'identifiant de la célébrité tant que la demande est active, NULL sinon :
            // l'index unique interdit physiquement deux demandes actives pour la même personne.
            $table->unsignedBigInteger('active_key')->nullable()->storedAs(
                "CASE WHEN status IN ('en_attente', 'reservee', 'prise') THEN faceclaim_id ELSE NULL END"
            );
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('extended_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->unique('active_key');
        });

        Schema::create('character_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision', 30);
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_reviews');
        Schema::dropIfExists('faceclaim_claims');
        Schema::dropIfExists('characters');
        Schema::dropIfExists('predefined_characters');
        Schema::dropIfExists('faceclaims');
    }
};
