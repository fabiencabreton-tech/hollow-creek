<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messagerie entre joueurs et signalements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 150)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'left_at']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();

            $table->index(['conversation_id', 'id']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reportable_type', 30);
            $table->unsignedBigInteger('reportable_id');
            $table->foreignId('reported_user_id')->nullable()->constrained('users')->nullOnDelete();
            // Seule la partie signalée est copiée ici : l'équipe ne lit rien d'autre.
            $table->text('snapshot');
            $table->string('reason', 20);
            $table->text('details')->nullable();
            $table->string('status', 12)->default('ouvert')->index();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
