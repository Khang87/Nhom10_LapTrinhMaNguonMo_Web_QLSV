<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unique(['subject_id', 'prerequisite_subject_id'], 'subject_prereq_unique');
        });

        Schema::create('subject_concurrents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('concurrent_subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unique(['subject_id', 'concurrent_subject_id'], 'subject_concurrent_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_concurrents');
        Schema::dropIfExists('subject_prerequisites');
    }
};
