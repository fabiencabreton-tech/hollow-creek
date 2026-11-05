<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bulle de contact : dossiers de demande et échanges ; demandes d'avatar sans compte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 60);
            $table->string('email', 190);
            $table->string('motif', 20);
            $table->string('subject', 150);
            $table->string('status', 10)->default('ouvert')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            // Lien de suivi secret : seul son empreinte est gardée.
            $table->string('token_hash', 64)->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_ticket_id')->constrained('contact_tickets')->cascadeOnDelete();
            $table->string('author', 10);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('faceclaim_claims', function (Blueprint $table) {
            $table->string('guest_email', 190)->nullable()->after('user_id');
            $table->string('guest_pseudo', 30)->nullable()->after('guest_email');
            $table->string('confirmation_hash', 64)->nullable()->after('guest_pseudo');
            $table->timestamp('confirmed_at')->nullable()->after('confirmation_hash');

            $table->index('guest_email');
        });
    }

    public function down(): void
    {
        Schema::table('faceclaim_claims', function (Blueprint $table) {
            $table->dropIndex(['guest_email']);
            $table->dropColumn(['guest_email', 'guest_pseudo', 'confirmation_hash', 'confirmed_at']);
        });

        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('contact_tickets');
    }
};
