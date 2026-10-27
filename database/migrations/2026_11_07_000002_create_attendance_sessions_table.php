<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Một buổi học diễn ra trên thực tế của một lớp học phần (SPEC-attendance-schedule).
 * Điểm danh theo buổi chứ không theo ngày dương lịch chung: mỗi lớp có tối đa một buổi
 * trong một ngày, nên (offering, session_date) là duy nhất.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lecture_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->date('session_date');
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['course_offering_id', 'session_date'], 'session_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
