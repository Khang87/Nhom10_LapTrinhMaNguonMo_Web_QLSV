<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Gom dữ liệu EnrollmentPolicy cần vào một mảng duy nhất. Tách khỏi policy để policy là
 * hàm thuần test được bằng fixture, và để trang danh sách dựng ngữ cảnh cho nhiều lớp
 * chỉ bằng vài truy vấn.
 */
class EnrollmentContext
{
    /**
     * @return array{credits_in_semester: int, passed_subject_ids: list<int>, active_subject_ids: list<int>, blocking_prerequisites: list<array{code: string, name: string, required_score: float, actual_score: float|null}>, seats_left: int, already_active: bool}
     */
    public static function for(User $student, CourseOffering $offering): array
    {
        return self::build($student, $offering, self::activeEnrollments($student), self::scores($student));
    }

    /**
     * Ngữ cảnh cho cả danh sách lớp: điểm, môn đã đạt và đăng ký đang học chỉ hỏi một lần.
     *
     * @param  Collection<CourseOffering>  $offerings
     * @return array<int, array<string, mixed>> khóa là id lớp học phần
     */
    public static function collect(User $student, Collection $offerings): array
    {
        $active = self::activeEnrollments($student);
        $scores = self::scores($student);

        return $offerings
            ->mapWithKeys(fn (CourseOffering $offering) => [
                $offering->id => self::build($student, $offering, $active, $scores),
            ])
            ->all();
    }

    /**
     * Những môn sinh viên đã đạt ngưỡng điểm tiên quyết, dùng cho cả policy lẫn cột tín chỉ.
     *
     * @return Collection<int, Subject>
     */
    public static function passedSubjects(User $student): Collection
    {
        return self::scores($student)[0];
    }

    /**
     * Tín chỉ đã tích luỹ: chỉ tính môn đã có điểm tổng kết đạt.
     */
    public static function accumulatedCredits(User $student): int
    {
        return (int) self::passedSubjects($student)->sum('credits');
    }

    /**
     * Ngữ cảnh cho quy tắc hủy: cổng của lớp và dữ liệu điểm danh/điểm đã có hay chưa.
     * Điểm danh vẫn khóa theo môn (Wave 7 mới re-key theo lớp học phần).
     *
     * @return array{gate_open: bool, has_record: bool}
     */
    public static function forDrop(Enrollment $enrollment): array
    {
        $enrollment->loadMissing('offering');

        return [
            'gate_open' => $enrollment->offering?->isRegistrationOpen() === true,
            'has_record' => Grade::where('user_id', $enrollment->user_id)
                ->where('course_offering_id', $enrollment->course_offering_id)
                ->exists()
                || Attendance::where('user_id', $enrollment->user_id)
                    ->where('subject_id', $enrollment->offering?->subject_id)
                    ->exists(),
        ];
    }

    /**
     * @return array{0: Collection<int, Subject>, 1: Collection<int, object>}
     */
    private static function scores(User $student): array
    {
        $scores = PrerequisiteRules::scoreMap($student);

        $passed = Subject::whereIn('id', $scores
            ->where('final_score', '>=', EnrollmentPolicy::PASS_SCORE)
            ->keys())->get();

        return [$passed, $scores];
    }

    /**
     * @return Collection<int, Enrollment> mọi enrollment ACTIVE của sinh viên, kèm lớp của nó
     */
    private static function activeEnrollments(User $student): Collection
    {
        return Enrollment::where('user_id', $student->id)
            ->where('status', 'ACTIVE')
            ->with('offering')
            ->get();
    }

    /**
     * @param  Collection<int, Enrollment>  $active
     * @param  array{0: Collection<int, Subject>, 1: Collection<int, object>}  $scores
     * @return array<string, mixed>
     */
    private static function build(User $student, CourseOffering $offering, Collection $active, array $scores): array
    {
        [$passed, $gradeScores] = $scores;
        $offering->loadMissing(['subject.prerequisites', 'subject.concurrents', 'subject.concurrentOf', 'semester']);

        $sameSemester = $active->filter(
            fn (Enrollment $enrollment) => $enrollment->offering?->semester_id === $offering->semester_id,
        );

        return [
            'credits_in_semester' => (int) $sameSemester->sum(
                fn (Enrollment $enrollment) => (int) ($enrollment->offering?->subject?->credits ?? 0),
            ),
            'passed_subject_ids' => $passed->pluck('id')->all(),
            'active_subject_ids' => $sameSemester->pluck('offering.subject_id')->filter()->unique()->values()->all(),
            'blocking_prerequisites' => PrerequisiteRules::blockingPrerequisites(
                $student,
                $offering->subject,
                $passed,
                EnrollmentPolicy::PASS_SCORE,
                $gradeScores,
            ),
            'seats_left' => $offering->seatsLeft(),
            'already_active' => $sameSemester->contains('course_offering_id', $offering->id),
        ];
    }
}
