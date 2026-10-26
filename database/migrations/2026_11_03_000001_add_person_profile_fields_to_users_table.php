<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_code', 20)->nullable()->unique()->after('role');
            $table->string('teacher_code', 20)->nullable()->unique()->after('student_code');
            $table->date('dob')->nullable()->after('teacher_code');
            $table->enum('gender', ['MALE', 'FEMALE', 'OTHER'])->nullable()->after('dob');
            $table->string('hometown', 200)->nullable()->after('gender');
            $table->string('phone', 20)->nullable()->after('hometown');
            $table->enum('academic_status', ['STUDYING', 'AT_RISK', 'SUSPENDED', 'GRADUATED', 'DROPPED'])
                ->nullable()
                ->default('STUDYING')
                ->after('phone');
        });

        // Duyệt 2026-10-25: xóa lớp hành chính không được xóa âm thầm hồ sơ sinh viên.
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->dropUnique(['student_code']);
            $table->dropUnique(['teacher_code']);
            $table->dropColumn(['student_code', 'teacher_code', 'dob', 'gender', 'hometown', 'phone', 'academic_status']);
        });
    }
};
