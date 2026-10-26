<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Di cư dữ liệu cũ (CAPABILITY_MAP bước 3): mỗi môn đang có được mở thành một lớp học phần
 * nhóm TN01 trong học kỳ hiện hành, student_subject -> enrollments, grades re-key sang
 * (course_offering_id, user_id) với final_score = score.
 *
 * Chạy thuần qua DB facade để không phụ thuộc model sẽ đổi shape ở các wave sau, và idempotent:
 * chạy thêm lần nữa không tạo bản sao.
 */
return new class extends Migration
{
    private const GROUP = 'TN01';

    public function up(): void
    {
        $this->addGradeColumns();

        // Chỉ dựng học kỳ + lớp học phần khi thực sự có dữ liệu cũ; hệ thống mới tinh để
        // admin tạo học kỳ ở /admin/semesters (wave 4.5), không bịa ra học kỳ ảo.
        if (! $this->hasLegacyData()) {
            return;
        }

        $semesterId = $this->legacySemesterId();

        $this->createOfferings($semesterId);
        $this->copyEnrollments($semesterId);
        $this->rekeyGrades($semesterId);
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_offering_id');
            $table->dropColumn('final_score');
        });

        $subjectIds = DB::table('subjects')->pluck('id');
        DB::table('enrollments')
            ->whereIn('course_offering_id', DB::table('course_offerings')->whereIn('subject_id', $subjectIds)->pluck('id'))
            ->delete();
        DB::table('course_offerings')->where('group_code', self::GROUP)->delete();
        DB::table('semesters')->where('slug', $this->slug())->delete();
    }

    private function hasLegacyData(): bool
    {
        return Schema::hasTable('student_subject')
            && (DB::table('subjects')->exists()
                || DB::table('student_subject')->exists()
                || DB::table('grades')->exists());
    }

    /**
     * Học kỳ đầu của năm học hiện hành (năm học bắt đầu từ tháng 8).
     */
    private function slug(): string
    {
        $year = (int) now()->format('Y');
        $start = (int) now()->format('n') >= 8 ? $year : $year - 1;

        return 'HK1-'.$start.'-'.($start + 1);
    }

    private function legacySemesterId(): int
    {
        $slug = $this->slug();
        $existing = DB::table('semesters')->where('slug', $slug)->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        $year = substr($slug, 4);

        return (int) DB::table('semesters')->insertGetId([
            'academic_year' => $year,
            'term' => 1,
            'slug' => $slug,
            'starts_at' => substr($year, 0, 4).'-08-01',
            'ends_at' => substr($year, 0, 4).'-12-31',
            'is_active' => DB::table('semesters')->where('is_active', true)->exists() ? 0 : 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addGradeColumns(): void
    {
        if (! Schema::hasColumn('grades', 'course_offering_id')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->foreignId('course_offering_id')->nullable()->after('subject_id')->constrained()->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('grades', 'final_score')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->decimal('final_score', 5, 2)->nullable()->after('score');
            });
        }
    }

    private function createOfferings(int $semesterId): void
    {
        foreach (DB::table('subjects')->pluck('id') as $subjectId) {
            $exists = DB::table('course_offerings')
                ->where('subject_id', $subjectId)
                ->where('semester_id', $semesterId)
                ->where('group_code', self::GROUP)
                ->exists();

            if (! $exists) {
                DB::table('course_offerings')->insert([
                    'subject_id' => $subjectId,
                    'semester_id' => $semesterId,
                    'group_code' => self::GROUP,
                    // Sĩ số 0 = không giới hạn, để 15 đăng ký cũ không bị coi là vượt sĩ số.
                    'max_students' => 0,
                    'is_open' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function copyEnrollments(int $semesterId): void
    {
        $offerings = DB::table('course_offerings')
            ->where('semester_id', $semesterId)
            ->pluck('id', 'subject_id');

        $rows = DB::table('student_subject')
            ->whereIn('subject_id', $offerings->keys())
            ->get(['user_id', 'subject_id', 'created_at', 'updated_at'])
            ->map(fn ($row) => [
                'course_offering_id' => $offerings[$row->subject_id],
                'user_id' => $row->user_id,
                'status' => 'ACTIVE',
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ])
            ->all();

        DB::table('enrollments')->insertOrIgnore($rows);
    }

    private function rekeyGrades(int $semesterId): void
    {
        $offerings = DB::table('course_offerings')
            ->where('semester_id', $semesterId)
            ->pluck('id', 'subject_id');

        DB::table('grades')
            ->whereNull('course_offering_id')
            ->select(['id', 'subject_id', 'score'])
            ->chunkById(200, function ($grades) use ($offerings) {
                foreach ($grades as $grade) {
                    $offeringId = $offerings[$grade->subject_id] ?? null;

                    if ($offeringId !== null) {
                        DB::table('grades')->where('id', $grade->id)->update([
                            'course_offering_id' => $offeringId,
                            'final_score' => $grade->score,
                        ]);
                    }
                }
            });
    }
};
