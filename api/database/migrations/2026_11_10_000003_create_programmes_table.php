<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programmes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filiere_id')->constrained('filieres')->cascadeOnDelete();
            $table->unsignedTinyInteger('semestre'); // 1 à 6
            $table->string('file_path');
            $table->timestamps();
            $table->unique(['filiere_id', 'semestre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programmes');
    }
};
