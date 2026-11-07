<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refonte « Brume » : dossiers de la Gazette (plusieurs articles sur un même sujet) et
 * écriture japonaise facultative des espèces (幽霊 pour Yūrei…).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gazette_articles', function (Blueprint $table) {
            $table->string('dossier', 80)->nullable()->index();
        });

        Schema::table('species', function (Blueprint $table) {
            $table->string('japanese_name', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('gazette_articles', function (Blueprint $table) {
            $table->dropIndex(['dossier']);
            $table->dropColumn('dossier');
        });

        Schema::table('species', function (Blueprint $table) {
            $table->dropColumn('japanese_name');
        });
    }
};
