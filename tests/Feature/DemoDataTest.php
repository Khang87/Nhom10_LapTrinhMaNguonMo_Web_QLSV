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
use App\Models\TuitionInvoice;
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

    private const CURRENT_OFFERINGS = 17;

    private const PAST_OFFERINGS = 7;

    protected function setUp(): void
    {
        parent::setUp();
        (new DatabaseSeeder)->run();
    }

    public function test_the_catalog_is_thick_enough_to_fill_every_reference_screen(): void
    {
        $this->assertSame(8, Department::count());
        $this->assertSame(5, Major::count());
        $this->assertSame(26, Subject::count());
        $this->assertSame(6, StudentClass::count());
        $this->assertSame(82, StudyPlan::count());
        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(25, User::where('role', 'teacher')->count());
        $this->assertSame(28, User::where('role', 'student')->count());
    }

    public function test_khoa_va_chuyen_nganh_hien_du_thong_tin_lien_he(): void
    {
        $cntt = Department::where('code', 'CNTT')->sole();
        $this->assertSame('028.38161673', $cntt->phone);
        $this->assertSame('cntt@huit.edu.vn', $cntt->email);
        $this->assertStringContainsString('Tòa nhà B', $cntt->office);
        $this->assertNotEmpty($cntt->description);
        $this->assertSame(8, Department::whereNotNull('phone')->count());

        $this->assertSame(5, Major::whereNotNull('description')->count());
        $this->assertTrue(Major::where('code', 'CNPM')->sole()->department_id === $cntt->id);
    }

    public function test_giang_vien_co_hoc_vi_va_thuoc_mot_khoa(): void
    {
        $this->assertSame(25, User::where('role', 'teacher')->whereNotNull('degree')->count());
        $this->assertSame(25, User::where('role', 'teacher')->whereNotNull('department_id')->count());

        $teacher = User::where('email', 'annv@huit.edu.vn')->sole();
        $this->assertSame('GV001', $teacher->teacher_code);
        $this->assertSame('Thạc sĩ Khoa học Máy tính', $teacher->degree);
        $this->assertSame('CNTT', $teacher->department->code);
    }

    public function test_sinh_vien_co_ly_lich_trich_ngang_day_du(): void
    {
        $hung = $this->studentByCode('20240005');
        $this->assertSame('095205003219', $hung->id_card_number);
        $this->assertSame('Cà Mau', $hung->id_card_issued_place);
        $this->assertSame('Kinh', $hung->ethnicity);
        $this->assertSame('Việt Nam', $hung->nationality);
        $this->assertSame('KV2', $hung->admission_region);
        $this->assertSame('Chính quy', $hung->training_type);
        $this->assertSame('Đại học', $hung->education_level);
        $this->assertStringContainsString('Lê Trọng Tấn', $hung->contact_address);

        // Ba hồ sơ để trống có chủ đích: bộ lọc "chưa cập nhật" còn dữ liệu để hiển thị.
        $this->assertNull($this->studentByCode('20240027')->dob);
        $this->assertNull($this->studentByCode('20240027')->id_card_number);
        $this->assertSame(1, User::where('role', 'student')->whereNull('class_id')->count());
    }

    public function test_the_academic_calendar_spans_two_years_with_one_running_semester(): void
    {
        $this->assertSame(6, Semester::count());
        $this->assertSame(1, Semester::where('is_active', true)->count());

        $current = Semester::current();
        $this->assertTrue($current->isRegistrationOpen(), 'Kỳ hiện hành phải đang mở cổng để màn hình đăng ký còn dùng được.');
        $this->assertSame(self::CURRENT_OFFERINGS, CourseOffering::where('semester_id', $current->id)->count());
        $this->assertSame(1, CourseOffering::where('semester_id', $current->id)->whereNull('teacher_id')->count(),
            'Còn đúng một lớp chưa phân công giảng viên để màn hình học vụ gợi ý.');

        $withHistory = Semester::whereKeyNot($current->id)->has('offerings')->get();
        $this->assertCount(1, $withHistory, 'Chỉ một kỳ đã đóng có lớp để dựng lịch sử học tập.');
        $this->assertFalse($withHistory->first()->isRegistrationOpen(), 'Kỳ trước đã đóng cổng, chỉ còn là lịch sử.');
        $this->assertSame(self::PAST_OFFERINGS, CourseOffering::where('semester_id', $withHistory->first()->id)->count());

        $this->assertSame(
            ['Học kỳ 1', 'Học kỳ 2', 'Học kỳ hè', 'Học kỳ 1', 'Học kỳ 2', 'Học kỳ hè'],
            Semester::orderBy('academic_year')->orderBy('term')->get()
                ->map(fn (Semester $semester) => $semester->term_label)->all()
        );
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
        $this->assertSame(0, $this->activeCountIn('GRAD101', 'TN01'), 'Lớp chưa mở cổng thì không ai được đứng tên.');
        $this->assertSame(0, $this->activeCountIn('WEB101', 'TN02'), 'Lớp đã đủ sĩ số thì không ai được đứng tên.');
        $this->assertSame(0, $this->activeCountIn('KHMT102', 'TN01'), 'KHMT102 cần KHMT101 mà môn đó đang học cùng kỳ, chưa ai đạt.');
        $this->assertGreaterThan(0, $this->activeCountIn('WEB101', 'TN01'), 'Môn song hành phải có người học cùng môn kia trong kỳ.');

        $failed = User::where('student_code', '20240007')->sole();
        $this->assertSame(['MKT101', 'NOSQL101'], $failed->offerings()
            ->wherePivot('status', 'ACTIVE')
            ->where('course_offerings.semester_id', Semester::current()->id)
            ->with('subject')->get()
            ->map(fn (CourseOffering $offering) => $offering->subject->code)
            ->sort()->values()->all(), 'Em trượt DS101 chỉ được xếp vào môn không cần tiên quyết và không song hành với WEB101.');
    }

    public function test_students_out_of_study_have_dropped_enrollments_and_no_records(): void
    {
        foreach (['20240010', '20240024'] as $code) {
            $student = User::where('student_code', $code)->sole();
            $statuses = $student->enrollments()
                ->whereHas('offering', fn ($q) => $q->where('semester_id', Semester::current()->id))
                ->pluck('status')->unique()->all();

            $this->assertNotEmpty($statuses);
            $this->assertSame(['DROPPED'], $statuses, $code.' nghỉ học thì mọi lớp của kỳ này phải ở trạng thái DROPPED.');
            $this->assertSame(0, Attendance::whereIn('enrollment_id', $student->enrollments()->pluck('id'))->count());
        }

        $graduated = User::where('student_code', '20240025')->sole();
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
    /**
     * Học phí chỉ có thông báo khi nhà trường cấu hình đơn giá một tín chỉ. Chưa cấu hình thì
     * seeder không được tự bịa ra dòng nào, vì màn hình học phí phải báo "chưa phát hành".
     */
    public function test_there_is_no_tuition_notice_until_the_school_sets_a_credit_price(): void
    {
        $this->assertSame(0, TuitionInvoice::count());
    }

    public function test_tuition_notices_are_issued_and_partly_collected_once_credit_price_is_set(): void
    {
        config(['academic.tuition_per_credit' => 550000]);
        (new DatabaseSeeder)->run();

        $current = Semester::current();
        $invoices = TuitionInvoice::with('student')->where('semester_id', $current->id)->get();

        $this->assertGreaterThan(0, $invoices->count(), 'Kỳ hiện hành phải có thông báo học phí cho người đang học.');
        $this->assertSame(9 * 550000, $invoices->first(fn ($i) => $i->student->student_code === '20240001')->amount);
        $this->assertSame('QLSV20240001-HK1', $invoices->first(fn ($i) => $i->student->student_code === '20240001')->content);

        $this->assertGreaterThan(0, $invoices->whereNotNull('paid_at')->count(), 'Phải có người đã nộp để màn hình còn trạng thái thu.');
        $this->assertGreaterThan(0, $invoices->whereNull('paid_at')->count(), 'Và phải còn người chưa nộp.');

        // Kỳ đã đóng: toàn bộ học phí tất toán, không còn thông báo mở.
        $past = Semester::whereKeyNot($current->id)->has('offerings')->sole();
        $this->assertSame(0, TuitionInvoice::where('semester_id', $past->id)->whereNull('paid_at')->count());

        $before = $this->snapshot();
        (new DatabaseSeeder)->run();
        $this->assertSame($before, $this->snapshot(), 'Seeder học phí cũng phải idempotent.');
    }

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
        $teacher = User::where('email', 'annv@huit.edu.vn')->sole();
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
            'invoices' => TuitionInvoice::count(),
        ];
    }
}
