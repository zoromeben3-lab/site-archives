<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rôles autorisés : etudiant, chef_filiere, admin
            $table->enum('role', ['etudiant', 'chef_filiere', 'admin'])->default('etudiant');
            // Un chef de filière est rattaché à sa filière (null pour admin ou étudiant)
            $table->foreignId('filiere_id')->nullable()->constrained('filieres')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['filiere_id']);
            $table->dropColumn(['role', 'filiere_id']);
        });
    }
};
