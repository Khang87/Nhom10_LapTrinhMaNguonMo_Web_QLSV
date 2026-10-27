<?php

namespace Tests\Feature\Concerns;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Major;
use App\Models\Semester;
use App\Models\StudentClass;
use App\Models\StudyPlan;
use App\Models\Subject;
use App\Models\User;

/**
 * Dựng nhanh người dùng, kỳ học -> lớp học phần -> đăng ký -> điểm cho các nghiệp vụ đào tạo.
 */
trait OfferingFixtures
{
    protected function student(string $name = 'Sinh viên A'): User
    {
        return User::factory()->create(['role' => 'student', 'name' => $name]);
    }

    protected function teacher(string $name = 'Giảng viên'): User
    {
        return User::factory()->create(['role' => 'teacher', 'name' => $name]);
    }

    protected function subject(string $code): Subject
    {
        return $this->subjectWith($code, 3);
    }

    /** Cùng một môn nhưng chọn được số tín chỉ, cần cho GPA có trọng số. */
    protected function subjectWith(string $code, int $credits): Subject
    {
        return Subject::create(['code' => $code, 'name' => 'Môn '.$code, 'credits' => $credits]);
    }

    /**
     * Chuyên ngành kèm khung chương trình: mọi môn truyền vào đều thành môn bắt buộc,
     * nên tổng tín chỉ khung đúng bằng tổng tín chỉ của chúng.
     */
    protected function majorWithPlan(Subject ...$subjects): Major
    {
        $major = Major::create([
            'department_id' => Department::create(['code' => 'KHOA1', 'name' => 'Khoa Công nghệ thông tin'])->id,
            'code' => 'NGANH1',
            'name' => 'Ngành Kỹ thuật phần mềm',
        ]);

        foreach ($subjects as $subject) {
            StudyPlan::create([
                'major_id' => $major->id,
                'subject_id' => $subject->id,
                'plan_semester' => 1,
                'kind' => 'REQUIRED',
            ]);
        }

        return $major;
    }

    /** Lớp hành chính của một chuyên ngành: sinh viên suy ra tín chỉ khung qua lớp này. */
    protected function adminClass(Major $major): StudentClass
    {
        return StudentClass::create([
            'class_code' => 'CN24',
            'class_name' => 'Cử nhân K24',
            'intake_year' => 2024,
            'department_id' => $major->department_id,
            'major_id' => $major->id,
        ]);
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

    /**
     * Học kỳ có cổng đăng ký đang mở, mặc định từ hôm qua tới ngày mai.
     *
     * @param  array<string, mixed>  $extra
     */
    protected function gate(array $extra = []): Semester
    {
        return $this->semester('2025-2026', 1, array_merge([
            'registration_starts_at' => now()->subDay(),
            'registration_ends_at' => now()->addDay(),
        ], $extra));
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

    /**
     * Ghi một lượt điểm danh của một đăng ký trong một buổi: (lớp, ngày) thành một
     * attendance_session nếu chưa có, dòng điểm danh upsert theo (buổi, đăng ký).
     */
    protected function attend(Enrollment $enrollment, string $date, string $status = Attendance::PRESENT): Attendance
    {
        $session = AttendanceSession::updateOrCreate(
            ['course_offering_id' => $enrollment->course_offering_id, 'session_date' => $date],
            ['recorded_by' => CourseOffering::find($enrollment->course_offering_id)?->teacher_id],
        );

        return Attendance::updateOrCreate(
            ['attendance_session_id' => $session->id, 'enrollment_id' => $enrollment->id],
            ['course_offering_id' => $enrollment->course_offering_id, 'status' => $status],
        );
    }

    /**
     * Ghi một điểm tổng kết: ba cột thành phần cùng nhận giá trị này để
     * GradingScale::subjectScore() (10/20/70) trả lại đúng điểm mà test cần.
     */
    protected function grade(User $student, CourseOffering $offering, float $total, bool $published = true): Grade
    {
        return Grade::updateOrCreate(
            ['course_offering_id' => $offering->id, 'user_id' => $student->id],
            [
                'enrollment_id' => Enrollment::where('course_offering_id', $offering->id)
                    ->where('user_id', $student->id)->value('id'),
                'attendance_score' => $total,
                'midterm_score' => $total,
                'final_score' => $total,
                'is_published' => $published,
            ],
        );
    }

    /** Một môn + một lớp học phần duy nhất: đủ cho những màn hình chỉ cần dữ liệu tối thiểu. */
    protected function singleOffering(string $code = 'WEB101'): CourseOffering
    {
        return $this->offering($this->subject($code), $this->semester());
    }
}
