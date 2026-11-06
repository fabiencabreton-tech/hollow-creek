<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les grandes familles de Tsukimori : fiches détaillées, liens entre familles,
 * arbres généalogiques, demandes d'intégration et préliens familiaux.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->string('japanese_name', 40)->nullable()->after('name');
            $table->string('motto', 160)->nullable()->after('japanese_name');
            $table->foreignId('district_id')->nullable()->after('motto')->constrained('districts')->nullOnDelete();
            $table->string('district_note', 160)->nullable()->after('district_id');
            $table->text('activities')->nullable()->after('history');
            $table->string('reputation', 255)->nullable()->after('activities');
            $table->longText('kirisawa_link')->nullable()->after('reputation');
            $table->longText('rp_notes')->nullable()->after('kirisawa_link');
            $table->longText('secret_notes')->nullable()->after('open_roles');
            $table->boolean('is_major')->default(false)->after('secret_notes');
            $table->boolean('is_open')->default(true)->after('is_major');
        });

        Schema::create('family_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('other_family_id')->constrained('families')->cascadeOnDelete();
            $table->text('summary');
            $table->timestamps();
            $table->unique(['family_id', 'other_family_id']);
        });

        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('character_id')->nullable()->unique()->constrained('characters')->nullOnDelete();
            $table->string('name', 120);
            $table->string('note', 300)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('family_members')->nullOnDelete();
            $table->foreignId('second_parent_id')->nullable()->constrained('family_members')->nullOnDelete();
            $table->foreignId('spouse_id')->nullable()->constrained('family_members')->nullOnDelete();
            $table->boolean('is_deceased')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('family_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('family_id')->nullable()->constrained('families')->cascadeOnDelete();
            $table->string('proposed_name', 80)->nullable();
            $table->string('proposed_japanese_name', 40)->nullable();
            $table->text('proposed_summary')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 12)->default('en-attente')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('response')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('family_prelinks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('relation', 20);
            $table->string('title', 120);
            $table->text('description');
            $table->string('age_range', 40)->nullable();
            $table->string('faceclaims', 200)->nullable();
            $table->string('status', 10)->default('ouvert')->index();
            $table->foreignId('taken_character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_prelinks');
        Schema::dropIfExists('family_requests');
        Schema::dropIfExists('family_members');
        Schema::dropIfExists('family_relations');
        Schema::table('families', function (Blueprint $table) {
            $table->dropConstrainedForeignId('district_id');
            $table->dropColumn(['japanese_name', 'motto', 'district_note', 'activities', 'reputation', 'kirisawa_link', 'rp_notes', 'secret_notes', 'is_major', 'is_open']);
        });
    }
};
