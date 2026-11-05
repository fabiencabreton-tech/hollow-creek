<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications sur le site, préférences par type, blocages, présence de l'équipe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('title', 255);
            $table->string('body', 500)->nullable();
            $table->string('url', 500);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('mailed_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'read_at']);
            $table->index(['mailed_at', 'created_at']);
        });

        Schema::create('user_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'blocked_user_id']);
            $table->index('blocked_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('remember_token');
            $table->timestamp('last_seen_at')->nullable()->after('notification_preferences');
        });

        // Reprise du réglage de la phase 3 (« e-mails des scènes »).
        foreach (DB::table('users')->whereIn('scene_mail', ['tour', 'aucune'])->get(['id', 'scene_mail']) as $user) {
            $preferences = $user->scene_mail === 'aucune'
                ? ['scene_reponse' => 'aucun', 'scene_tour' => 'aucun']
                : ['scene_reponse' => 'aucun'];

            DB::table('users')->where('id', $user->id)->update(['notification_preferences' => json_encode($preferences)]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('scene_mail');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('scene_mail', 10)->default('toutes');
            $table->dropColumn(['notification_preferences', 'last_seen_at']);
        });

        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('user_notifications');
    }
};
