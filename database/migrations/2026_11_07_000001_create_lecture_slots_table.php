<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lịch lên lớp của một lớp học phần (SPEC-attendance-schedule). Một offering có thể có nhiều
 * slot — ví dụ Thứ 3 và Thứ 5 cùng khung 07:00. "Số tiết/tuần" không lưu thành cột vì suy ra
 * từ count() của chính bảng này.
 */
/**
 * Một buổi học định kỳ trong tuần của lớp học phần: tuần nào có lịch, tiết nào, phòng nào.
 * starts_on/ends_on cho phép đổi lịch giữa chừng mà không mất dữ liệu cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecture_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('week_day');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 50)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
            $table->unique(['course_offering_id', 'week_day', 'start_time'], 'slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecture_slots');
    }
};
