<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Activer / désactiver un compte
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('actif')->default(true);
        });

        // Semestre et crédits d'un module (avant : inventés par le front)
        Schema::table('modules', function (Blueprint $table) {
            $table->string('semestre', 20)->nullable();
            $table->unsignedTinyInteger('credits')->default(6);
        });

        // « Vider mon historique » masque les lignes sans effacer l'audit admin
        Schema::table('historiques', function (Blueprint $table) {
            $table->boolean('masque')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('actif'));
        Schema::table('modules', fn (Blueprint $t) => $t->dropColumn(['semestre', 'credits']));
        Schema::table('historiques', fn (Blueprint $t) => $t->dropColumn('masque'));
    }
};
