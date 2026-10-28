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
        // 1. Thêm trạng thái nộp và duyệt bảng điểm vào course_sections
        Schema::table('course_sections', function (Blueprint $table) {
            if (!Schema::hasColumn('course_sections', 'grade_status')) {
                $table->string('grade_status', 20)->default('DRAFT')->after('exam_room'); // DRAFT, SUBMITTED, APPROVED
                $table->timestamp('grade_submitted_at')->nullable()->after('grade_status');
                $table->timestamp('grade_approved_at')->nullable()->after('grade_submitted_at');
                $table->text('grade_note')->nullable()->after('grade_approved_at');
            }
        });

        // 2. Bảng tiếp nhận & xử lý Phúc khảo / Thắc mắc điểm
        if (!Schema::hasTable('grade_appeals')) {
            Schema::create('grade_appeals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('section_id')->constrained('course_sections')->cascadeOnDelete();
                $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
                $table->string('component', 20)->default('FINAL'); // PROCESS, MIDTERM, FINAL, TOTAL
                $table->decimal('current_score', 4, 2)->nullable();
                $table->decimal('desired_score', 4, 2)->nullable();
                $table->text('reason');
                $table->string('status', 20)->default('PENDING'); // PENDING, REVIEWED, RESOLVED, REJECTED
                $table->text('lecturer_feedback')->nullable();
                $table->decimal('new_score', 4, 2)->nullable();
                $table->boolean('admin_approved')->default(false);
                $table->timestamps();
            });
        }

        // 3. Bảng yêu cầu Báo nghỉ / Báo dạy bù / Chuyển học trực tuyến
        if (!Schema::hasTable('schedule_change_requests')) {
            Schema::create('schedule_change_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lecturer_id')->constrained('lecturers')->cascadeOnDelete();
                $table->foreignId('section_id')->constrained('course_sections')->cascadeOnDelete();
                $table->foreignId('schedule_id')->nullable()->constrained('class_schedules')->nullOnDelete();
                $table->string('request_type', 30); // CANCEL_SESSION, MAKEUP_SESSION, ONLINE_SESSION
                $table->date('original_date')->nullable();
                $table->date('target_date')->nullable();
                $table->string('shift', 20)->default('MORNING');
                $table->unsignedTinyInteger('start_period')->nullable();
                $table->unsignedTinyInteger('end_period')->nullable();
                $table->string('room_name', 100)->nullable();
                $table->string('meeting_link', 255)->nullable();
                $table->text('reason');
                $table->string('status', 20)->default('PENDING'); // PENDING, APPROVED, REJECTED
                $table->text('admin_note')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_change_requests');
        Schema::dropIfExists('grade_appeals');

        Schema::table('course_sections', function (Blueprint $table) {
            $table->dropColumn(['grade_status', 'grade_submitted_at', 'grade_approved_at', 'grade_note']);
        });
    }
};
