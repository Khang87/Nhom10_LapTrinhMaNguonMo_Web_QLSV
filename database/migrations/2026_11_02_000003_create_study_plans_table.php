<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_id')->constrained('majors')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('plan_semester');
            $table->enum('kind', ['REQUIRED', 'ELECTIVE'])->default('REQUIRED');
            $table->timestamps();
            $table->unique(['major_id', 'subject_id'], 'study_plan_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_plans');
    }
};
