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
        // 1. Tạo bảng chuyên ngành
        if (!Schema::hasTable('specializations')) {
            Schema::create('specializations', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();
                $table->string('name', 100);
                $table->string('faculty_name', 100)->default('Khoa Công nghệ Thông tin');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 2. Bổ sung specialization_id cho students
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'specialization_id')) {
                $table->foreignId('specialization_id')->nullable()->constrained('specializations')->nullOnDelete();
            }
        });

        // 3. Bổ sung specialization_id cho lecturers
        Schema::table('lecturers', function (Blueprint $table) {
            if (!Schema::hasColumn('lecturers', 'specialization_id')) {
                $table->foreignId('specialization_id')->nullable()->constrained('specializations')->nullOnDelete();
            }
        });

        // 4. Bổ sung specialization_id cho curriculum_framework
        Schema::table('curriculum_framework', function (Blueprint $table) {
            if (!Schema::hasColumn('curriculum_framework', 'specialization_id')) {
                $table->foreignId('specialization_id')->nullable()->constrained('specializations')->cascadeOnDelete();
            }
        });

        // 5. Bổ sung điểm quá trình cho enrollments
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'process_score')) {
                $table->decimal('process_score', 4, 2)->nullable()->after('registration_type');
            }
        });

        // 6. Bảng điểm danh buổi học chi tiết
        if (!Schema::hasTable('section_attendances')) {
            Schema::create('section_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('section_id')->constrained('course_sections')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->date('attendance_date');
                $table->enum('status', ['present', 'absent', 'late'])->default('present');
                $table->string('note')->nullable();
                $table->timestamps();

                $table->unique(['section_id', 'student_id', 'attendance_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_attendances');

        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'process_score')) {
                $table->dropColumn('process_score');
            }
        });

        Schema::table('curriculum_framework', function (Blueprint $table) {
            if (Schema::hasColumn('curriculum_framework', 'specialization_id')) {
                $table->dropConstrainedForeignId('specialization_id');
            }
        });

        Schema::table('lecturers', function (Blueprint $table) {
            if (Schema::hasColumn('lecturers', 'specialization_id')) {
                $table->dropConstrainedForeignId('specialization_id');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'specialization_id')) {
                $table->dropConstrainedForeignId('specialization_id');
            }
        });

        Schema::dropIfExists('specializations');
    }
};
