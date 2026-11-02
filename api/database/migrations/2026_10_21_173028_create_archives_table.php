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
        Schema::create('archives', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->enum('type', ['devoir', 'test', 'examen', 'autre']);
            
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annee_academiques')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('file_path');
            $table->string('file_name_original');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type');

            $table->unsignedInteger('downloads_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archives');
    }
};
