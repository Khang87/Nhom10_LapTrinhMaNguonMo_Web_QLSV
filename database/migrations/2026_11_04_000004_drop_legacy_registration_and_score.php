<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dọn hết dấu vết của mô hình cũ (CAPABILITY_MAP bước 4): một điểm chỉ còn được tính theo
 * lớp học phần, đăng ký chỉ còn là enrollments.
 *
 * Trước khi bỏ cột phải chắc chắn mọi grade đã có course_offering_id, vì cột này sẽ thành NOT NULL
 * và (course_offering_id, user_id) là duy nhất.
 */
return new class extends Migration
{
    private const GROUP = 'TN01';

    public function up(): void
    {
        $this->linkGradesWithoutOffering();

        Schema::table('grades', function (Blueprint $table) {
            // MySQL không cho bỏ index composite khi FK user_id vẫn đang dùng nó.
            $table->index('user_id', 'grades_user_id_index');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->dropUnique('grades_user_id_subject_id_unique');
            $table->dropConstrainedForeignId('subject_id');
            $table->dropColumn('score');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->foreignId('course_offering_id')->nullable(false)->change();
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->decimal('final_score', 5, 2)->nullable(false)->change();
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->unique(['course_offering_id', 'user_id'], 'grade_offering_unique');
        });

        Schema::dropIfExists('student_subject');
    }

    /**
     * Rollback chỉ khôi phục được cấu trúc: điểm cũ của những grade đã gắn lớp học phần
     * lấy lại được qua offering, còn lịch sử đăng ký trong student_subject đã mất.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropUnique('grade_offering_unique');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->decimal('final_score', 5, 2)->nullable()->change();
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->foreignId('course_offering_id')->nullable()->change();
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 4, 2)->nullable()->after('subject_id');
        });

        foreach (DB::table('course_offerings')->get(['id', 'subject_id']) as $offering) {
            DB::table('grades')->where('course_offering_id', $offering->id)->update([
                'subject_id' => $offering->subject_id,
                'score' => DB::raw('final_score'),
            ]);
        }

        // Không dựng lại unique (user_id, subject_id): một sinh viên có thể học hai nhóm của cùng
        // một môn, nên ràng buộc cũ sẽ chặn rollback. grades_user_id_index ở lại và đủ cho FK.

        Schema::create('student_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'subject_id']);
        });

        $offerings = DB::table('course_offerings')->pluck('subject_id', 'id');

        foreach (DB::table('enrollments')->get(['user_id', 'course_offering_id']) as $enrollment) {
            $subjectId = $offerings[$enrollment->course_offering_id] ?? null;

            if ($subjectId !== null) {
                DB::table('student_subject')->insertOrIgnore([
                    'user_id' => $enrollment->user_id,
                    'subject_id' => $subjectId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Grade nào chưa có lớp học phần thì mở nốt nhóm TN01 cho môn đó ở kỳ hiện hành rồi gắn vào,
     * đúng cách migration 000003 đã xử lý cho phần lớn dữ liệu.
     */
    private function linkGradesWithoutOffering(): void
    {
        $pending = DB::table('grades')->whereNull('course_offering_id')->get(['id', 'subject_id', 'score']);

        if ($pending->isEmpty()) {
            return;
        }

        $semesterId = DB::table('semesters')->where('is_active', true)->value('id')
            ?? DB::table('semesters')->min('id');

        if ($semesterId === null) {
            throw new RuntimeException(
                'Cần ít nhất một học kỳ để gắn điểm cũ vào lớp học phần. Tạo học kỳ rồi chạy lại migration.'
            );
        }

        foreach ($pending as $grade) {
            $offeringId = DB::table('course_offerings')
                ->where('subject_id', $grade->subject_id)
                ->where('semester_id', $semesterId)
                ->where('group_code', self::GROUP)
                ->value('id');

            if ($offeringId === null) {
                $offeringId = DB::table('course_offerings')->insertGetId([
                    'subject_id' => $grade->subject_id,
                    'semester_id' => $semesterId,
                    'group_code' => self::GROUP,
                    'max_students' => 0,
                    'is_open' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('grades')->where('id', $grade->id)->update([
                'course_offering_id' => $offeringId,
                'final_score' => $grade->score,
            ]);
        }
    }
};
