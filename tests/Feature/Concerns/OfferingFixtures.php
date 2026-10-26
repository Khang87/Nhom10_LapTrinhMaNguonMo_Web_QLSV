<?php

namespace Tests\Feature\Concerns;

use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;

/**
 * Dựng nhanh bộ ba kỳ học -> lớp học phần -> đăng ký cho các test liên quan tới enrollment.
 */
trait OfferingFixtures
{
    protected function subject(string $code): Subject
    {
        return Subject::create(['code' => $code, 'name' => 'Môn '.$code, 'credits' => 3]);
    }

    protected function semester(string $year = '2025-2026', int $term = 1, array $extra = []): Semester
    {
        return Semester::create($extra + [
            'academic_year' => $year,
            'term' => $term,
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-05-31',
        ]);
    }

    protected function offering(Subject $subject, Semester $semester, string $group = 'TN01', int $max = 0, array $extra = []): CourseOffering
    {
        return CourseOffering::create($extra + [
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'group_code' => $group,
            'max_students' => $max,
        ]);
    }

    protected function enroll(User $student, CourseOffering $offering): Enrollment
    {
        return Enrollment::updateOrCreate(
            ['course_offering_id' => $offering->id, 'user_id' => $student->id],
            ['status' => 'ACTIVE', 'dropped_at' => null],
        );
    }

    /** Một môn + một lớp học phần duy nhất: đủ cho những màn hình chỉ cần dữ liệu tối thiểu. */
    protected function singleOffering(string $code = 'WEB101'): CourseOffering
    {
        return $this->offering($this->subject($code), $this->semester());
    }
}
