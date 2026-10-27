<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Re-key `attendances` từ (user, môn, ngày) sang (buổi học, đăng ký) — SPEC-attendance-schedule.
 *
 * Mỗi dòng cũ được móc về một đăng ký đang/đã học cùng môn đó, và mỗi (lớp, ngày) cũ thành
 * một attendance_session. Dòng không còn đăng ký tương ứng (sinh viên đã bị xóa khỏi lớp trước
 * wave 4) bị loại vì enrollment_id là khóa ngoại bắt buộc.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attendances', 'subject_id')) {
            return;
        }

        $legacy = DB::table('attendances')
            ->get(['user_id', 'subject_id', 'date', 'status', 'created_at', 'updated_at']);

        Schema::drop('attendances');
        $this->createRekeyedTable();

        foreach ($legacy as $row) {
            $enrollment = $this->findEnrollment((int) $row->user_id, (int) $row->subject_id);

            if ($enrollment === null) {
                continue;
            }

            DB::table('attendances')->insertOrIgnore([
                'attendance_session_id' => $this->sessionId($enrollment->course_offering_id, $row->date),
                'course_offering_id' => $enrollment->course_offering_id,
                'enrollment_id' => $enrollment->id,
                'status' => strtoupper($row->status),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        $newRows = Schema::hasTable('attendances')
            ? DB::table('attendances')
                ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendances.attendance_session_id')
                ->join('enrollments', 'enrollments.id', '=', 'attendances.enrollment_id')
                ->join('course_offerings', 'course_offerings.id', '=', 'enrollments.course_offering_id')
                ->get([
                    'enrollments.user_id',
                    'course_offerings.subject_id',
                    'attendance_sessions.session_date as date',
                    'attendances.status',
                    'attendances.created_at',
                    'attendances.updated_at',
                ])
            : collect();

        Schema::drop('attendances');

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['present', 'absent']);
            $table->timestamps();
            $table->unique(['user_id', 'subject_id', 'date']);
        });

        foreach ($newRows as $row) {
            DB::table('attendances')->insertOrIgnore([
                'user_id' => $row->user_id,
                'subject_id' => $row->subject_id,
                'date' => $row->date,
                'status' => strtolower($row->status),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    private function createRekeyedTable(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['PRESENT', 'ABSENT']);
            $table->timestamps();
            $table->unique(['attendance_session_id', 'enrollment_id'], 'attendance_session_student_unique');
        });
    }

    /**
     * Đăng ký gần nhất của sinh viên này ở môn đó: dữ liệu cũ chỉ ghi theo môn, không theo nhóm.
     */
    private function findEnrollment(int $userId, int $subjectId): ?object
    {
        return DB::table('enrollments')
            ->join('course_offerings', 'course_offerings.id', '=', 'enrollments.course_offering_id')
            ->where('enrollments.user_id', $userId)
            ->where('course_offerings.subject_id', $subjectId)
            ->orderBy('enrollments.id')
            ->select('enrollments.id', 'enrollments.course_offering_id')
            ->first();
    }

    private function sessionId(int $offeringId, string $date): int
    {
        $existing = DB::table('attendance_sessions')
            ->where('course_offering_id', $offeringId)
            ->where('session_date', $date)
            ->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        return (int) DB::table('attendance_sessions')->insertGetId([
            'course_offering_id' => $offeringId,
            'session_date' => $date,
            'recorded_by' => DB::table('course_offerings')->where('id', $offeringId)->value('teacher_id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
