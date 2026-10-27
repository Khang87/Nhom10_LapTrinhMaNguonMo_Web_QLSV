<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng điểm chuyển từ "một cột tổng kết" sang ba cột thành phần theo trọng số 10/20/70
 * (SPEC-grading). Điểm tổng kết không lưu nữa mà GradingScale tính lại, nên đổi trọng số
 * sau này không phải backfill.
 *
 * 15 dòng điểm cũ chỉ có một con số. Để điểm của sinh viên không bị giảm còn 70% khi lên
 * wave này, mỗi dòng cũ được ghi cùng một giá trị vào cả ba cột. Ba trọng số cộng lại
 * đúng 1.0 nên điểm tổng kết tính ra trả lại đúng giá trị cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->foreignId('enrollment_id')->nullable()->after('user_id')
                ->constrained('enrollments')->cascadeOnDelete();
            $table->decimal('attendance_score', 4, 2)->nullable()->after('course_offering_id');
            $table->decimal('midterm_score', 4, 2)->nullable()->after('attendance_score');
        });

        $this->copyLegacyScoreIntoComponents();
        $this->linkEnrollments();

        Schema::table('grades', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('final_score');
            $table->foreignId('updated_by')->nullable()->after('is_published')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->decimal('final_score', 4, 2)->nullable(true)->change();
        });

        // Điểm cũ đã hiển thị với sinh viên từ trước, không được ẩn đi sau migration.
        DB::table('grades')->whereNotNull('final_score')->update(['is_published' => true]);
    }

    /**
     * Khôi phục lại đúng một cột tổng kết; enrollment_id và vết người sửa mất theo.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enrollment_id');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['attendance_score', 'midterm_score', 'is_published']);
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->decimal('final_score', 5, 2)->nullable(true)->change();
        });
    }

    private function copyLegacyScoreIntoComponents(): void
    {
        DB::statement('update grades set attendance_score = final_score, midterm_score = final_score where final_score is not null');
    }

    /**
     * Ưu tiên enrollment ACTIVE; nếu dòng điểm chỉ còn ứng với lớp đã hủy thì vẫn nối được
     * vào đăng ký gần nhất để không mất vết liên kết.
     */
    private function linkEnrollments(): void
    {
        DB::statement("
            update grades g
            join enrollments e on e.course_offering_id = g.course_offering_id and e.user_id = g.user_id
            set g.enrollment_id = e.id
            where g.enrollment_id is null and e.status = 'ACTIVE'
        ");

        DB::statement('
            update grades g
            join enrollments e on e.course_offering_id = g.course_offering_id and e.user_id = g.user_id
            set g.enrollment_id = e.id
            where g.enrollment_id is null
        ');
    }
};
