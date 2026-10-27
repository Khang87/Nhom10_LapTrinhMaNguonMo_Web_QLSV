<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\LectureSlot;
use App\Models\Major;
use App\Models\Semester;
use App\Models\StudentClass;
use App\Models\StudyPlan;
use App\Models\Subject;
use App\Models\User;
use App\Support\AttendanceRate;
use App\Support\EnrollmentContext;
use App\Support\EnrollmentPolicy;
use App\Support\TimetableService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Ý tưởng "Seeder dữ liệu mẫu dày": dữ liệu demo phải phủ mọi màn hình và không được mâu
 * thuẫn với chính sách đăng ký của ứng dụng. Test này chạy seeder thật rồi kiểm tra các bất
 * biến đó, vì `php artisan db:seed` hỏng là hỏng cả môi trường trình diễn của môn học.
 */
class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    private const CURRENT_OFFERINGS = 14;

    private const PAST_OFFERINGS = 5;

    protected function setUp(): void
    {
        parent::setUp();
        (new DatabaseSeeder)->run();
    }

    public function test_the_catalog_is_thick_enough_to_fill_every_reference_screen(): void
    {
        $this->assertSame(3, Department::count());
        $this->assertSame(4, Major::count());
        $this->assertSame(16, Subject::count());
        $this->assertSame(5, StudentClass::count());
        $this->assertSame(33, StudyPlan::count());
        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(25, User::where('role', 'teacher')->count());
        $this->assertSame(24, User::where('role', 'student')->count());
    }

    public function test_there_are_two_semesters_and_exactly_one_of_them_is_running(): void
    {
        $this->assertSame(2, Semester::count());
        $this->assertSame(1, Semester::where('is_active', true)->count());

        $current = Semester::current();
        $this->assertTrue($current->isRegistrationOpen(), 'Kỳ hiện hành phải đang mở cổng để màn hình đăng ký còn dùng được.');
        $past = Semester::whereKeyNot($current->id)->sole();
        $this->assertFalse($past->isRegistrationOpen(), 'Kỳ trước đã đóng cổng, chỉ còn là lịch sử.');
        $this->assertSame(self::CURRENT_OFFERINGS, CourseOffering::where('semester_id', $current->id)->count());
        $this->assertSame(self::PAST_OFFERINGS, CourseOffering::where('semester_id', $past->id)->count());
    }

    public function test_every_offering_has_one_slot_and_no_two_classes_share_a_timeslot(): void
    {
        $total = self::CURRENT_OFFERINGS + self::PAST_OFFERINGS;
        $byOffering = LectureSlot::all()->groupBy('course_offering_id');

        $this->assertSame($total, $byOffering->count(), 'Mọi lớp học phần đều có lịch lên lớp.');
        $this->assertSame($total, LectureSlot::count(), 'Mỗi lớp đúng một lịch, không có bản sao.');
        $this->assertSame([], TimetableService::conflicts(TimetableService::forSemester(Semester::current())),
            'Không hai lớp nào của cùng một kỳ trùng giờ, nên không sinh viên nào bị "Trùng giờ".');
    }

    public function test_every_current_enrollment_satisfies_the_enrollment_policy(): void
    {
        $rows = $this->currentEnrollments('ACTIVE');
        $this->assertGreaterThan(50, $rows->count(), 'Kỳ hiện hành phải có đủ sĩ số demo.');

        foreach ($rows as $enrollment) {
            $context = EnrollmentContext::for($enrollment->user, $enrollment->offering);
            // Đăng ký đã tồn tại nên already_active = true; ở đây kiểm tra năm nhánh còn lại.
            $context['already_active'] = false;

            $verdict = EnrollmentPolicy::check($enrollment->user, $enrollment->offering, $context);

            $this->assertTrue($verdict['ok'], EnrollmentPolicy::reasonText($verdict)
                .' — '.$enrollment->user->student_code.' đăng ký '.$enrollment->offering->label());
        }
    }

    public function test_the_exception_classes_have_no_active_enrollment(): void
    {
        $this->assertSame(0, $this->activeCountIn('CLO201', 'TN02'), 'Lớp chưa mở cổng thì không ai được đứng tên.');
        $this->assertSame(0, $this->activeCountIn('DB101', 'TN02'), 'Lớp đã đủ sĩ số thì không ai được đứng tên.');
        $this->assertSame(0, $this->activeCountIn('AI101', 'TN01'), 'AI101 cần ADT101 mà chưa ai đạt môn đó.');
        $this->assertGreaterThan(0, $this->activeCountIn('SEC201', 'TN01'), 'Môn song hành phải có người học cùng môn kia trong kỳ.');

        $failed = User::where('student_code', '20240007')->sole();
        $this->assertSame(['ADT101', 'MOB201'], $failed->offerings()
            ->wherePivot('status', 'ACTIVE')
            ->where('course_offerings.semester_id', Semester::current()->id)
            ->with('subject')->get()
            ->map(fn (CourseOffering $offering) => $offering->subject->code)
            ->sort()->values()->all(), 'Em trượt OOP101 chỉ được xếp vào môn không cần tiên quyết.');
    }

    public function test_students_out_of_study_have_dropped_enrollments_and_no_records(): void
    {
        foreach (['20240010', '20240023'] as $code) {
            $student = User::where('student_code', $code)->sole();
            $statuses = $student->enrollments()
                ->whereHas('offering', fn ($q) => $q->where('semester_id', Semester::current()->id))
                ->pluck('status')->unique()->all();

            $this->assertNotEmpty($statuses);
            $this->assertSame(['DROPPED'], $statuses, $code.' nghỉ học thì mọi lớp của kỳ này phải ở trạng thái DROPPED.');
            $this->assertSame(0, Attendance::whereIn('enrollment_id', $student->enrollments()->pluck('id'))->count());
        }

        $graduated = User::where('student_code', '20240024')->sole();
        $this->assertSame(0, Enrollment::where('user_id', $graduated->id)->count());
        $this->assertNull($graduated->class_id);
    }

    public function test_attendance_rows_always_belong_to_a_session_of_the_same_class(): void
    {
        $rows = Attendance::with(['session.slot', 'enrollment.user', 'enrollment.offering'])->get();
        $this->assertGreaterThan(100, $rows->count());

        foreach ($rows as $attendance) {
            $this->assertContains($attendance->status, [Attendance::PRESENT, Attendance::ABSENT]);
            $this->assertNotNull($attendance->session->lecture_slot_id, 'Mọi buổi đều sinh ra từ một lịch lên lớp.');
            $this->assertSame($attendance->course_offering_id, $attendance->session->course_offering_id);
            $this->assertSame($attendance->course_offering_id, $attendance->enrollment->course_offering_id);
            $this->assertSame('student', $attendance->enrollment->user->role);
            $this->assertSame(
                $attendance->session->slot->week_day - 1,
                $attendance->session->session_date->dayOfWeek,
                'Buổi điểm danh phải rơi đúng vào ngày trong tuần mà lịch lên lớp quy định.'
            );
        }
    }

    public function test_grades_always_link_to_their_enrollment(): void
    {
        $this->assertSame(0, Grade::whereNull('enrollment_id')->count());

        foreach (Grade::with(['enrollment', 'user'])->get() as $grade) {
            $this->assertSame($grade->enrollment->user_id, $grade->user_id);
            $this->assertSame($grade->enrollment->course_offering_id, $grade->course_offering_id);
            $this->assertNotNull($grade->attendance_score);
            $this->assertNotNull($grade->midterm_score);

            if ($grade->is_published) {
                $this->assertNotNull($grade->final_score, 'Không có điểm cuối kỳ thì không được công bố.');
            }
        }

        $this->assertGreaterThan(0, Grade::whereNull('final_score')->count(), 'Kỳ hiện hành phải còn lớp chờ thi cuối kỳ.');

        $activeIds = $this->currentEnrollments('ACTIVE')->pluck('id');
        $this->assertSame($activeIds->count(), Grade::whereIn('enrollment_id', $activeIds)->count(),
            'Mọi đăng ký đang học của kỳ này đều có đúng một dòng điểm.');
    }

    public function test_a_few_students_are_deliberately_below_the_attendance_threshold(): void
    {
        $this->assertSame(50.0, AttendanceRate::forStudent($this->studentByCode('20240005')));
        $this->assertSame(75.0, AttendanceRate::forStudent($this->studentByCode('20240003')));
        $this->assertSame(100.0, AttendanceRate::forStudent($this->studentByCode('20240001')));
        $this->assertTrue(AttendanceRate::isBelowThreshold(AttendanceRate::forStudent($this->studentByCode('20240005'))));
        $this->assertFalse(AttendanceRate::isBelowThreshold(AttendanceRate::forStudent($this->studentByCode('20240001'))));
    }

    /**
     * Cơ sở dữ liệu demo thật có thể còn lịch do người dùng thêm trên màn hình admin, một lớp vì
     * thế mang hai dòng lecture_slots. Seeder phải điểm danh theo đúng lịch mình dựng, không hỏng
     * vì dòng thừa và không được coi dòng thừa là lịch của mình.
     */
    public function test_a_class_that_already_has_an_extra_schedule_still_seeds(): void
    {
        $offering = CourseOffering::where('semester_id', Semester::current()->id)
            ->whereHas('enrollments', fn ($q) => $q->where('status', 'ACTIVE'))
            ->first();
        $records = Attendance::where('course_offering_id', $offering->id)->count();

        $extra = LectureSlot::create([
            'course_offering_id' => $offering->id,
            'week_day' => 7,
            'start_time' => '07:00',
            'end_time' => '09:00',
            'room' => 'X999',
        ]);

        (new DatabaseSeeder)->run();

        $this->assertSame(2, LectureSlot::where('course_offering_id', $offering->id)->count());
        $this->assertSame($records, Attendance::where('course_offering_id', $offering->id)->count());
        $this->assertSame(0, AttendanceSession::where('lecture_slot_id', $extra->id)->count(),
            'Buổi điểm danh chỉ được móc vào lịch mà seeder tạo.');
    }

    public function test_running_the_seeder_twice_leaves_the_same_data(): void
    {
        $before = $this->snapshot();
        (new DatabaseSeeder)->run();

        $this->assertSame($before, $this->snapshot(), 'Seeder phải idempotent để chạy lại nhiều lần không tạo bản sao.');
    }

    public function test_the_admin_screens_render_with_demo_data(): void
    {
        $admin = User::where('role', 'admin')->sole();

        foreach (['admin.dashboard', 'admin.students.index', 'admin.tuition.index', 'admin.timetable.index',
            'admin.grades.index', 'admin.attendances.index', 'admin.enrollments.index',
            'admin.course-offerings.index', 'admin.study-plans.index', 'admin.subjects.index'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.students.show', $this->studentByCode('20240001')))->assertOk();
    }

    public function test_the_teacher_and_student_screens_render_with_demo_data(): void
    {
        $teacher = User::where('email', 'giangvien1@gmail.com')->sole();
        $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.timetable'))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.offerings.index'))->assertOk();

        $student = $this->studentByCode('20240001');
        foreach (['student.dashboard', 'student.course-offerings.index', 'student.timetable',
            'student.grades.index', 'student.grades.history', 'student.tuition'] as $route) {
            $this->actingAs($student)->get(route($route))->assertOk();
        }

        $this->actingAs($student)->get(route('student.timetable'))->assertSee('WEB101');
        $this->assertSame([], TimetableService::conflicts(TimetableService::forStudent($student, Semester::current())));
    }

    /** @return Collection<int, Enrollment> */
    private function currentEnrollments(string $status)
    {
        return Enrollment::with(['user', 'offering.subject.prerequisites', 'offering.subject.concurrents'])
            ->where('status', $status)
            ->whereHas('offering', fn ($q) => $q->where('semester_id', Semester::current()->id))
            ->get();
    }

    private function activeCountIn(string $subjectCode, string $group): int
    {
        return Enrollment::where('status', 'ACTIVE')
            ->whereHas('offering', fn ($q) => $q
                ->where('semester_id', Semester::current()->id)
                ->where('group_code', $group)
                ->whereHas('subject', fn ($s) => $s->where('code', $subjectCode)))
            ->count();
    }

    private function studentByCode(string $code): User
    {
        return User::where('student_code', $code)->sole();
    }

    /** @return array<string, int> */
    private function snapshot(): array
    {
        return [
            'users' => User::count(),
            'subjects' => Subject::count(),
            'offerings' => CourseOffering::count(),
            'slots' => LectureSlot::count(),
            'enrollments' => Enrollment::count(),
            'sessions' => AttendanceSession::count(),
            'attendance' => Attendance::count(),
            'grades' => Grade::count(),
            'plans' => StudyPlan::count(),
        ];
    }
}
