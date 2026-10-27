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
        // 1. Hồ sơ chi tiết sinh viên
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('student_code', 20)->unique(); // MSSV: 2001230283
            $table->string('full_name', 100);
            $table->string('gender', 10)->default('Nam');
            $table->string('avatar_url')->nullable();

            // Thông tin học vấn
            $table->string('study_status', 50)->default('Đang học');
            $table->string('profile_code', 30)->nullable();
            $table->date('enrollment_date')->nullable();
            $table->string('class_code', 20)->default('14DHTH04');
            $table->string('campus', 100)->default('ĐHCT TP.HCM');
            $table->string('education_level', 50)->default('Đại học');
            $table->string('training_type', 50)->default('Chính quy');
            $table->string('faculty_name', 100)->default('Khoa Công nghệ Thông tin');
            $table->string('major_name', 100)->default('Công nghệ thông tin');
            $table->string('specialization_name', 100)->default('Công nghệ phần mềm');
            $table->integer('cohort_year')->default(2023);

            // Thông tin cá nhân
            $table->date('dob')->nullable();
            $table->string('ethnicity', 50)->default('Kinh');
            $table->string('religion', 50)->default('Không');
            $table->string('nationality', 50)->default('Việt Nam');
            $table->string('region', 50)->nullable();
            $table->string('cccd_number', 20)->nullable()->unique();
            $table->date('cccd_issue_date')->nullable();
            $table->string('cccd_issue_place', 100)->nullable();
            $table->string('priority_object', 100)->nullable();
            $table->string('policy_area', 100)->nullable();
            $table->date('youth_union_date')->nullable();
            $table->date('party_date')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('contact_address')->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->text('permanent_residence')->nullable();
            $table->timestamps();
        });

        // 2. Hồ sơ Giảng viên
        Schema::create('lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('lecturer_code', 20)->unique();
            $table->string('full_name', 100);
            $table->string('degree', 50)->default('Thạc sĩ');
            $table->string('faculty_name', 100)->default('Khoa Công nghệ Thông tin');
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->timestamps();
        });

        // 3. Môn học chi tiết
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code', 20)->unique();
            $table->string('course_name', 150);
            $table->unsignedTinyInteger('credits')->default(3);
            $table->decimal('tuition_fee_per_credit', 10, 2)->default(550000);
            $table->timestamps();
        });

        // 4. Chương trình khung (Semesters 1 -> 8)
        Schema::create('curriculum_framework', function (Blueprint $table) {
            $table->id();
            $table->string('specialization_name', 100)->default('Công nghệ phần mềm');
            $table->unsignedTinyInteger('semester_order'); // 1 đến 8
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();
        });

        // 5. Lớp học phần mở trong kỳ
        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_code', 30)->unique(); // VD: 010110197808
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('lecturer_id')->nullable()->constrained('lecturers')->nullOnDelete();
            $table->string('semester_code', 20)->default('HK1_2026_2027');
            $table->integer('max_capacity')->default(60);
            $table->unsignedTinyInteger('day_of_week')->default(2); // 2: Thứ 2 -> 8: Chủ nhật
            $table->unsignedTinyInteger('start_period')->default(1);
            $table->unsignedTinyInteger('end_period')->default(3);
            $table->string('room_name', 50)->default('A110 - Phòng máy tính - 140 Lê Trọng Tấn');
            $table->dateTime('exam_date')->nullable();
            $table->string('exam_room', 50)->nullable();
            $table->timestamps();
        });

        // 6. Lịch học, lịch thi theo tuần (Bảng trọng tâm)
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->nullable()->constrained('course_sections')->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignId('lecturer_id')->nullable()->constrained('lecturers')->nullOnDelete();
            $table->string('class_group_name', 30)->default('14DHTH04'); // VD: 14DHTH08
            $table->string('section_code', 30)->nullable();
            $table->string('subject_name', 150);
            $table->string('lecturer_name', 100);
            $table->date('schedule_date');
            $table->string('shift', 20)->default('MORNING'); // MORNING, AFTERNOON, EVENING
            $table->unsignedTinyInteger('start_period');
            $table->unsignedTinyInteger('end_period');
            $table->string('room_info', 100);
            $table->string('meeting_link')->nullable(); // Khi học Online
            $table->string('schedule_type', 20)->default('THEORY'); // THEORY, PRACTICE, ONLINE, EXAM, SUSPENDED
            $table->text('note')->nullable(); // Lý do tạm ngưng hoặc ghi chú
            $table->timestamps();
        });

        // 7. Đăng ký học phần & Điểm số sinh viên
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('course_sections')->cascadeOnDelete();
            $table->string('registration_type', 20)->default('FIRST_TIME'); // FIRST_TIME, RETAKE, IMPROVEMENT
            $table->decimal('midterm_score', 4, 2)->nullable();
            $table->decimal('final_score', 4, 2)->nullable();
            $table->decimal('total_score_10', 4, 2)->nullable();
            $table->decimal('total_score_4', 3, 2)->nullable();
            $table->string('letter_grade', 5)->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'section_id']);
        });

        // 8. Đánh giá học kỳ, rèn luyện & xét học bổng
        Schema::create('semester_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('semester_code', 20)->default('HK1_2026_2027');
            $table->integer('total_credits')->default(0);
            $table->decimal('min_course_score', 4, 2)->default(0);
            $table->integer('training_score')->default(80); // 0 đến 100
            $table->decimal('semester_gpa_10', 4, 2)->nullable();
            $table->decimal('semester_gpa_4', 3, 2)->nullable();
            $table->boolean('is_eligible')->default(false);
            $table->string('ineligible_reason')->nullable();
            $table->string('scholarship_type', 20)->default('NONE'); // NONE, 40%, 60%, 80%
            $table->decimal('scholarship_score', 5, 2)->nullable();
            $table->string('scholarship_status', 20)->default('DRAFT'); // DRAFT, PUBLISHED
            $table->timestamps();
            $table->unique(['student_id', 'semester_code']);
        });

        // 9. Công nợ học phí & VietQR
        Schema::create('tuition_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('semester_code', 20)->default('HK1_2026_2027');
            $table->integer('total_credits')->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('UNPAID'); // UNPAID, PARTIAL, PAID
            $table->string('payment_code', 60)->unique(); // VD: HUIT 2001230283 HK12627
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tuition_invoices');
        Schema::dropIfExists('semester_evaluations');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('course_sections');
        Schema::dropIfExists('curriculum_framework');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('lecturers');
        Schema::dropIfExists('students');
    }
};
