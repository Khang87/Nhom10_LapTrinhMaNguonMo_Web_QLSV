<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CurriculumFramework;
use App\Models\Enrollment;
use App\Models\Lecturer;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\TuitionInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseRegistrationAndCollisionTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $studentUser;
    private Student $student;
    private Lecturer $lecturer;
    private Semester $currentSemester;
    private Semester $pastSemester1;
    private Semester $pastSemester2;
    private Course $course1;
    private Course $course2;
    private Course $course3;
    private Course $failedCourse;
    private Course $passedCourse;
    private CourseSection $sectionMonMorning1_3;
    private CourseSection $sectionMonOverlap3_5;
    private CourseSection $sectionMonAfternoon7_9;
    private CourseSection $sectionTue1_3;
    private CourseSection $sectionRetake;
    private CourseSection $sectionImprovement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'role' => 'admin',
        ]);

        $lecturerUser = User::factory()->create([
            'username' => 'GV001',
            'role' => 'lecturer',
        ]);
        $this->lecturer = Lecturer::create([
            'user_id' => $lecturerUser->id,
            'lecturer_code' => 'GV001',
            'full_name' => 'ThS. Nguyễn Văn A',
        ]);

        $class = StudentClass::create([
            'class_code' => '14DHTH04',
            'class_name' => 'Đại học Tin học 04',
        ]);

        $this->studentUser = User::factory()->create([
            'username' => '2001230283',
            'name' => 'Bùi Quốc Hùng',
            'email' => 'buiquochung0942@gmail.com',
            'role' => 'student',
        ]);

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'student_code' => '2001230283',
            'full_name' => 'Bùi Quốc Hùng',
            'gender' => 'Nam',
            'class_code' => '14DHTH04',
            'study_status' => 'Đang học',
            'major_name' => 'Công nghệ thông tin',
            'specialization_name' => 'Công nghệ phần mềm',
        ]);

        // Tạo các học kỳ theo tiến độ
        $this->pastSemester1 = Semester::create([
            'semester_code' => 'HK1_2025_2026',
            'semester_name' => 'Học kỳ 1 (2025 - 2026)',
            'academic_year' => '2025-2026',
            'term_type' => 'TERM_1',
            'order_index' => 1,
            'start_date' => Carbon::now()->subMonths(12)->toDateString(),
            'end_date' => Carbon::now()->subMonths(8)->toDateString(),
            'registration_start_date' => Carbon::now()->subMonths(14),
            'registration_end_date' => Carbon::now()->subMonths(12),
            'is_registration_open' => false,
        ]);

        $this->pastSemester2 = Semester::create([
            'semester_code' => 'HK2_2025_2026',
            'semester_name' => 'Học kỳ 2 (2025 - 2026)',
            'academic_year' => '2025-2026',
            'term_type' => 'TERM_2',
            'order_index' => 2,
            'start_date' => Carbon::now()->subMonths(6)->toDateString(),
            'end_date' => Carbon::now()->subMonths(2)->toDateString(),
            'registration_start_date' => Carbon::now()->subMonths(8),
            'registration_end_date' => Carbon::now()->subMonths(6),
            'is_registration_open' => false,
        ]);

        // Kỳ hiện tại: Năm 2 HK1, mở đăng ký trước 2 tháng (đang mở)
        $this->currentSemester = Semester::create([
            'semester_code' => 'HK1_2026_2027',
            'semester_name' => 'Học kỳ 1 (2026 - 2027)',
            'academic_year' => '2026-2027',
            'term_type' => 'TERM_1',
            'order_index' => 4,
            'start_date' => Carbon::now()->addDays(10)->toDateString(),
            'end_date' => Carbon::now()->addMonths(4)->toDateString(),
            'registration_start_date' => Carbon::now()->subMonth(), // Đã mở từ 1 tháng trước
            'registration_end_date' => Carbon::now()->addDays(5),   // Còn 5 ngày
            'is_registration_open' => true,
        ]);

        $this->course1 = Course::create([
            'course_code' => '0101000867',
            'course_name' => 'Lập trình mã nguồn mở',
            'credits' => 3,
            'tuition_fee_per_credit' => 550000,
        ]);

        $this->course2 = Course::create([
            'course_code' => '0101000868',
            'course_name' => 'Dữ liệu NoSQL',
            'credits' => 3,
            'tuition_fee_per_credit' => 550000,
        ]);

        $this->course3 = Course::create([
            'course_code' => '0101000869',
            'course_name' => 'Internet of Things',
            'credits' => 2,
            'tuition_fee_per_credit' => 550000,
        ]);

        $this->failedCourse = Course::create([
            'course_code' => 'DS101',
            'course_name' => 'Cấu trúc dữ liệu và giải thuật',
            'credits' => 4,
            'tuition_fee_per_credit' => 550000,
        ]);

        $this->passedCourse = Course::create([
            'course_code' => 'NET101',
            'course_name' => 'Mạng máy tính',
            'credits' => 2,
            'tuition_fee_per_credit' => 550000,
        ]);

        // Tạo lịch sử quá khứ cho sinh viên:
        // 1. Môn rớt ở HK1_2025_2026: DS101 (Score 3.5, Grade F)
        $pastSectionFail = CourseSection::create([
            'section_code' => '010110192501',
            'course_id' => $this->failedCourse->id,
            'semester_code' => 'HK1_2025_2026',
            'specialization_name' => 'Công nghệ phần mềm',
            'max_capacity' => 60,
            'day_of_week' => 2,
            'start_period' => 1,
            'end_period' => 4,
            'room_name' => 'A101',
        ]);
        Enrollment::create([
            'student_id' => $this->student->id,
            'section_id' => $pastSectionFail->id,
            'total_score_10' => 3.5,
            'letter_grade' => 'F',
            'registration_type' => 'FIRST_TIME',
        ]);

        // 2. Môn đạt ở HK2_2025_2026: NET101 (Score 5.5, Grade C)
        $pastSectionPass = CourseSection::create([
            'section_code' => '010110192503',
            'course_id' => $this->passedCourse->id,
            'semester_code' => 'HK2_2025_2026',
            'specialization_name' => 'Công nghệ phần mềm',
            'max_capacity' => 60,
            'day_of_week' => 4,
            'start_period' => 7,
            'end_period' => 9,
            'room_name' => 'C301',
        ]);
        Enrollment::create([
            'student_id' => $this->student->id,
            'section_id' => $pastSectionPass->id,
            'total_score_10' => 5.5,
            'letter_grade' => 'C',
            'registration_type' => 'FIRST_TIME',
        ]);

        // Section 1 trong kỳ hiện tại: Thứ 2, Tiết 1 - 3
        $this->sectionMonMorning1_3 = CourseSection::create([
            'section_code' => '010110197801',
            'course_id' => $this->course1->id,
            'lecturer_id' => $this->lecturer->id,
            'semester_code' => 'HK1_2026_2027',
            'specialization_name' => 'Công nghệ phần mềm',
            'max_capacity' => 60,
            'day_of_week' => 2,
            'start_period' => 1,
            'end_period' => 3,
            'room_name' => 'A110 - Phòng máy tính',
        ]);

        // Section 2: Thứ 2, Tiết 3 - 5 (Trùng tiết 3 với Section 1!)
        $this->sectionMonOverlap3_5 = CourseSection::create([
            'section_code' => '010110197802',
            'course_id' => $this->course2->id,
            'lecturer_id' => $this->lecturer->id,
            'semester_code' => 'HK1_2026_2027',
            'specialization_name' => 'Công nghệ phần mềm',
            'max_capacity' => 60,
            'day_of_week' => 2,
            'start_period' => 3,
            'end_period' => 5,
            'room_name' => 'B204',
        ]);

        // Section 3: Thứ 2, Tiết 7 - 9
        $this->sectionMonAfternoon7_9 = CourseSection::create([
            'section_code' => '010110197803',
            'course_id' => $this->course2->id,
            'lecturer_id' => $this->lecturer->id,
            'semester_code' => 'HK1_2026_2027',
            'specialization_name' => 'Công nghệ phần mềm',
            'max_capacity' => 60,
            'day_of_week' => 2,
            'start_period' => 7,
            'end_period' => 9,
            'room_name' => 'B205',
        ]);

        // Section 4: Thứ 3, Tiết 1 - 3
        $this->sectionTue1_3 = CourseSection::create([
            'section_code' => '010110197804',
            'course_id' => $this->course3->id,
            'lecturer_id' => $this->lecturer->id,
            'semester_code' => 'HK1_2026_2027',
            'specialization_name' => 'Công nghệ phần mềm',
            'max_capacity' => 60,
            'day_of_week' => 3,
            'start_period' => 1,
            'end_period' => 3,
            'room_name' => 'C301',
        ]);

        // Section mở cho môn Học lại (DS101) trong kỳ hiện tại
        $this->sectionRetake = CourseSection::create([
            'section_code' => '010110197807',
            'course_id' => $this->failedCourse->id,
            'lecturer_id' => $this->lecturer->id,
            'semester_code' => 'HK1_2026_2027',
            'specialization_name' => 'Công nghệ phần mềm',
            'max_capacity' => 60,
            'day_of_week' => 6,
            'start_period' => 1,
            'end_period' => 4,
            'room_name' => 'C201',
        ]);

        // Section mở cho môn Học cải thiện (NET101) trong kỳ hiện tại
        $this->sectionImprovement = CourseSection::create([
            'section_code' => '010110197805',
            'course_id' => $this->passedCourse->id,
            'lecturer_id' => $this->lecturer->id,
            'semester_code' => 'HK1_2026_2027',
            'specialization_name' => 'Công nghệ phần mềm',
            'max_capacity' => 60,
            'day_of_week' => 5,
            'start_period' => 7,
            'end_period' => 9,
            'room_name' => 'B102',
        ]);
    }

    public function test_student_can_view_registration_page_with_top_tabs_and_semester_filter(): void
    {
        $response = $this->actingAs($this->studentUser)->get(route('student.subjects.index'));

        $response->assertOk();
        $response->assertSee('Đăng ký học phần trực tuyến');
        $response->assertSee('Học kỳ 1 (2026 - 2027)');
        $response->assertSee('Học mới');
        $response->assertSee('Học lại');
        $response->assertSee('Học cải thiện');
        $response->assertSee('Cấu trúc dữ liệu và giải thuật'); // Môn học lại
        $response->assertSee('Mạng máy tính'); // Môn học cải thiện
        $response->assertSee('Option 1');
    }

    public function test_student_can_register_first_time_course_and_tuition_is_updated(): void
    {
        $response = $this->actingAs($this->studentUser)->post(
            route('student.subjects.register', $this->sectionMonMorning1_3->id),
            [
                'section_id' => $this->sectionMonMorning1_3->id,
                'registration_type' => 'FIRST_TIME',
            ]
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'section_id' => $this->sectionMonMorning1_3->id,
            'registration_type' => 'FIRST_TIME',
        ]);

        // Học phí: 3 tín chỉ * 550,000 = 1,650,000
        $this->assertDatabaseHas('tuition_invoices', [
            'student_id' => $this->student->id,
            'semester_code' => 'HK1_2026_2027',
            'total_credits' => 3,
            'total_amount' => 1650000,
        ]);
    }

    public function test_student_can_register_retake_and_improvement_options(): void
    {
        // Đăng ký học lại môn rớt DS101
        $responseRetake = $this->actingAs($this->studentUser)->post(
            route('student.subjects.register', $this->sectionRetake->id),
            [
                'section_id' => $this->sectionRetake->id,
                'registration_type' => 'RETAKE',
            ]
        );

        $responseRetake->assertSessionHas('success');
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'section_id' => $this->sectionRetake->id,
            'registration_type' => 'RETAKE',
        ]);

        // Đăng ký học cải thiện môn đạt NET101
        $responseImprove = $this->actingAs($this->studentUser)->post(
            route('student.subjects.register', $this->sectionImprovement->id),
            [
                'section_id' => $this->sectionImprovement->id,
                'registration_type' => 'IMPROVEMENT',
            ]
        );

        $responseImprove->assertSessionHas('success');
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'section_id' => $this->sectionImprovement->id,
            'registration_type' => 'IMPROVEMENT',
        ]);
    }

    public function test_schedule_collision_detection_blocks_overlapping_course(): void
    {
        // 1. Sinh viên đã đăng ký môn 1: Thứ 2, Tiết 1 - 3
        Enrollment::create([
            'student_id' => $this->student->id,
            'section_id' => $this->sectionMonMorning1_3->id,
            'registration_type' => 'FIRST_TIME',
        ]);

        // 2. Sinh viên cố gắng đăng ký môn 2: Thứ 2, Tiết 3 - 5 (Trùng tiết 3!)
        $response = $this->actingAs($this->studentUser)->post(
            route('student.subjects.register', $this->sectionMonOverlap3_5->id),
            [
                'section_id' => $this->sectionMonOverlap3_5->id,
                'registration_type' => 'FIRST_TIME',
            ]
        );

        $response->assertSessionHas('error');
        $errorMessage = session('error');
        $this->assertStringContainsString('KHÔNG THỂ ĐĂNG KÝ', $errorMessage);
        $this->assertStringContainsString('TRÙNG LỊCH HỌC', $errorMessage);
        $this->assertStringContainsString('Thứ 2', $errorMessage);

        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $this->student->id,
            'section_id' => $this->sectionMonOverlap3_5->id,
        ]);
    }

    public function test_registration_is_blocked_when_registration_window_is_closed(): void
    {
        // Đóng đợt đăng ký của kỳ hiện tại
        $this->currentSemester->update(['is_registration_open' => false]);

        $response = $this->actingAs($this->studentUser)->post(
            route('student.subjects.register', $this->sectionMonMorning1_3->id),
            [
                'section_id' => $this->sectionMonMorning1_3->id,
                'registration_type' => 'FIRST_TIME',
            ]
        );

        $response->assertSessionHas('error');
        $this->assertStringContainsString('hiện đang đóng', session('error'));

        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $this->student->id,
            'section_id' => $this->sectionMonMorning1_3->id,
        ]);
    }

    public function test_unregister_is_blocked_when_registration_window_is_closed(): void
    {
        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'section_id' => $this->sectionMonMorning1_3->id,
            'registration_type' => 'FIRST_TIME',
        ]);

        // Đóng đợt đăng ký
        $this->currentSemester->update(['is_registration_open' => false]);

        $response = $this->actingAs($this->studentUser)->delete(
            route('student.subjects.unregister', $enrollment->id)
        );

        $response->assertSessionHas('error');
        $this->assertStringContainsString('đã kết thúc', session('error'));

        // Bản ghi vẫn còn nguyên
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id]);
    }

    public function test_admin_can_manage_semesters_and_toggle_registration(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.semesters.index'));
        $response->assertOk();
        $response->assertSee('Quản lý Học kỳ', false);

        // Toggle đợt đăng ký
        $responseToggle = $this->actingAs($this->adminUser)->post(route('admin.semesters.toggle', $this->currentSemester->id));
        $responseToggle->assertRedirect(route('admin.semesters.index'));
        $this->assertFalse((bool)$this->currentSemester->fresh()->is_registration_open);

        $responseToggle2 = $this->actingAs($this->adminUser)->post(route('admin.semesters.toggle', $this->currentSemester->id));
        $responseToggle2->assertRedirect(route('admin.semesters.index'));
        $this->assertTrue((bool)$this->currentSemester->fresh()->is_registration_open);
    }

    public function test_admin_can_open_sections_by_curriculum_and_on_demand(): void
    {
        CurriculumFramework::create([
            'specialization_name' => 'Công nghệ phần mềm',
            'semester_order' => 3,
            'course_id' => $this->course3->id,
            'is_mandatory' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.course-sections.open-by-curriculum'), [
            'semester_code' => 'HK1_2026_2027',
            'specialization_name' => 'Công nghệ phần mềm',
            'semester_order' => 3,
        ]);

        $response->assertSessionHas('success');

        // Thêm lớp học phần theo đề nghị sinh viên
        $responseStore = $this->actingAs($this->adminUser)->post(route('admin.course-sections.store'), [
            'section_code' => '0101999999',
            'course_id' => $this->course2->id,
            'semester_code' => 'HK1_2026_2027',
            'specialization_name' => 'Công nghệ phần mềm',
            'max_capacity' => 50,
            'day_of_week' => 5,
            'start_period' => 1,
            'end_period' => 3,
            'room_name' => 'B301',
        ]);

        $responseStore->assertSessionHas('success');
        $this->assertDatabaseHas('course_sections', ['section_code' => '0101999999']);
    }

    public function test_student_can_select_different_semester_and_see_registered_courses_and_options_and_toast(): void
    {
        // 1. Truy cập xem kỳ quá khứ HK1_2025_2026 (đã có môn DS101 trong setUp)
        $responsePast = $this->actingAs($this->studentUser)
            ->get(route('student.subjects.index', ['semester' => 'HK1_2025_2026']));

        $responsePast->assertOk();
        $responsePast->assertSee('Danh sách học phần đã đăng ký - Học kỳ 1 (2025 - 2026)');
        $responsePast->assertSee('Cấu trúc dữ liệu và giải thuật');
        $responsePast->assertSee('ĐỢT ĐĂNG KÝ HỌC PHẦN CHO HỌC KỲ NÀY HIỆN ĐANG ĐÓNG');

        // 2. Truy cập kỳ hiện tại: Danh sách các tab chỉ chứa concise labels: "Học mới", "Học lại", "Học cải thiện"
        $responseCurrent = $this->actingAs($this->studentUser)
            ->get(route('student.subjects.index', ['semester' => 'HK1_2026_2027']));

        $responseCurrent->assertOk();
        $responseCurrent->assertSee('Học mới');
        $responseCurrent->assertSee('Học lại');
        $responseCurrent->assertSee('Học cải thiện');
        $responseCurrent->assertSee('Option 1');

        // 3. Kiểm tra Toast popup tự động biến mất khi có thông báo
        $responseToast = $this->actingAs($this->studentUser)
            ->withSession(['success' => 'Đăng ký thành công môn học!'])
            ->get(route('student.subjects.index'));

        $responseToast->assertOk();
        $responseToast->assertSee('id="huitToast"', false);
        $responseToast->assertSee('dismissHuitToast', false);
        $responseToast->assertSee('Đăng ký thành công môn học!');
    }
}
