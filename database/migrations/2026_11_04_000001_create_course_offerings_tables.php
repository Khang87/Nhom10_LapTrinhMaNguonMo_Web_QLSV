<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20);
            $table->unsignedTinyInteger('term');
            $table->string('slug', 40)->unique();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->timestamp('registration_starts_at')->nullable();
            $table->timestamp('registration_ends_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->unique(['academic_year', 'term']);
        });

        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->restrictOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('group_code', 20);
            $table->string('room', 50)->nullable();
            $table->unsignedSmallInteger('max_students')->default(0);
            $table->boolean('is_open')->default(true);
            $table->timestamps();
            $table->unique(['subject_id', 'semester_id', 'group_code'], 'offering_unique');
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['ACTIVE', 'DROPPED'])->default('ACTIVE');
            $table->timestamp('dropped_at')->nullable();
            $table->timestamps();
            $table->unique(['course_offering_id', 'user_id'], 'enrollment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('course_offerings');
        Schema::dropIfExists('semesters');
    }
};
