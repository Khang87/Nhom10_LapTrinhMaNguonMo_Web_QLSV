<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CurriculumFramework;
use App\Models\Enrollment;
use App\Models\Lecturer;
use App\Models\SemesterEvaluation;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\TuitionInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HuitWeeklyTimetableAndScholarshipTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $lecturerUser;
    private User $studentUser;
    private Student $student;
    private Lecturer $lecturer;
    private Course $course;
    private CourseSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'role' => 'admin',
        ]);

        $this->lecturerUser = User::factory()->create([
            'username' => 'GV001',
            'role' => 'lecturer',
        ]);
        $this->lecturer = Lecturer::create([
            'user_id' => $this->lecturerUser->id,
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
            'class_id' => $class->id,
        ]);
        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'student_code' => '2001230283',
            'full_name' => 'Bùi Quốc Hùng',
            'class_code' => '14DHTH04',
            'specialization_name' => 'Công nghệ phần mềm',
            'phone' => '0942367010',
            'email' => 'buiquochung0942@gmail.com',
            'contact_address' => '140 Lê Trọng Tấn',
        ]);

        $this->course = Course::create([
            'course_code' => 'OPEN101',
            'course_name' => 'Lập trình mã nguồn mở',
            'credits' => 3,
            'tuition_fee_per_credit' => 550000,
        ]);

        $this->section = CourseSection::create([
            'section_code' => '010110197801',
            'course_id' => $this->course->id,
            'lecturer_id' => $this->lecturer->id,
            'semester_code' => 'HK1_2026_2027',
            'day_of_week' => 2,
            'start_period' => 1,
            'end_period' => 3,
            'room_name' => 'B201 - Cơ sở 140 Lê Trọng Tấn',
        ]);

        Enrollment::create([
            'student_id' => $this->student->id,
            'section_id' => $this->section->id,
            'midterm_score' => 8.5,
            'final_score' => 9.0,
            'total_score_10' => 8.85,
            'total_score_4' => 4.0,
            'letter_grade' => 'A',
        ]);
    }

    public function test_student_can_view_weekly_timetable(): void
    {
        $today = Carbon::now()->startOfWeek(Carbon::MONDAY);

        ClassSchedule::create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id,
            'lecturer_id' => $this->lecturer->id,
            'class_group_name' => '14DHTH04',
            'subject_name' => 'Lập trình mã nguồn mở',
            'lecturer_name' => 'ThS. Nguyễn Văn A',
            'schedule_date' => $today->toDateString(),
            'start_period' => 1,
            'end_period' => 3,
            'room_info' => 'B201 - 140 Lê Trọng Tấn',
            'schedule_type' => 'THEORY',
        ]);

        $response = $this->actingAs($this->studentUser)->get('/student/schedule');
        $response->assertOk()
            ->assertSee('Lịch học, lịch thi theo tuần')
            ->assertSee('Ca học')
            ->assertSee('Lập trình mã nguồn mở')
            ->assertSee('B201 - 140 Lê Trọng Tấn')
            ->assertSee('ThS. Nguyễn Văn A');
    }

    public function test_schedule_filter_by_exam_and_study(): void
    {
        $today = Carbon::now()->startOfWeek(Carbon::MONDAY);

        ClassSchedule::create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id,
            'class_group_name' => '14DHTH04',
            'subject_name' => 'Lịch học bình thường',
            'lecturer_name' => 'ThS. Nguyễn Văn A',
            'schedule_date' => $today->toDateString(),
            'start_period' => 1,
            'end_period' => 3,
            'room_info' => 'A101',
            'schedule_type' => 'THEORY',
        ]);

        ClassSchedule::create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id,
            'class_group_name' => '14DHTH04',
            'subject_name' => 'Thi kết thúc học phần',
            'lecturer_name' => 'Ban Khảo thí',
            'schedule_date' => $today->copy()->addDay()->toDateString(),
            'start_period' => 7,
            'end_period' => 9,
            'room_info' => 'C302',
            'schedule_type' => 'EXAM',
        ]);

        // Lọc lịch thi: chỉ thấy Lịch thi
        $responseExam = $this->actingAs($this->studentUser)->get('/student/schedule?filter=exam');
        $responseExam->assertOk()
            ->assertSee('Thi kết thúc học phần')
            ->assertDontSee('Lịch học bình thường');

        // Lọc lịch học: không thấy Lịch thi
        $responseStudy = $this->actingAs($this->studentUser)->get('/student/schedule?filter=study');
        $responseStudy->assertOk()
            ->assertSee('Lịch học bình thường')
            ->assertDontSee('Thi kết thúc học phần');
    }

    public function test_lecturer_can_update_schedule_to_suspended_or_online(): void
    {
        $today = Carbon::now()->startOfWeek(Carbon::MONDAY);

        $schedule = ClassSchedule::create([
            'section_id' => $this->section->id,
            'course_id' => $this->course->id,
            'lecturer_id' => $this->lecturer->id,
            'class_group_name' => '14DHTH04',
            'subject_name' => 'Lập trình mã nguồn mở',
            'lecturer_name' => 'ThS. Nguyễn Văn A',
            'schedule_date' => $today->toDateString(),
            'start_period' => 1,
            'end_period' => 3,
            'room_info' => 'B201',
            'schedule_type' => 'THEORY',
        ]);

        // Giảng viên báo tạm ngưng
        $response = $this->actingAs($this->lecturerUser)->post("/schedules/{$schedule->id}/update-status", [
            'schedule_type' => 'SUSPENDED',
            'note' => 'Bận họp đột xuất',
        ]);
        $response->assertRedirect();

        $schedule->refresh();
        $this->assertEquals('SUSPENDED', $schedule->schedule_type);
        $this->assertEquals('Bận họp đột xuất', $schedule->note);

        // Giảng viên chuyển sang học online
        $this->actingAs($this->lecturerUser)->post("/schedules/{$schedule->id}/update-status", [
            'schedule_type' => 'ONLINE',
            'meeting_link' => 'https://meet.google.com/test-meet',
            'note' => 'Học Meet',
        ]);

        $schedule->refresh();
        $this->assertEquals('ONLINE', $schedule->schedule_type);
        $this->assertEquals('https://meet.google.com/test-meet', $schedule->meeting_link);
    }

    public function test_student_profile_and_contact_update(): void
    {
        $response = $this->actingAs($this->studentUser)->get('/student/profile');
        $response->assertOk()
            ->assertSee('THÔNG TIN HỌC VẤN')
            ->assertSee('THÔNG TIN CÁ NHÂN')
            ->assertSee('2001230283')
            ->assertSee('14DHTH04');

        $updateResponse = $this->actingAs($this->studentUser)->post('/student/profile/contact', [
            'phone' => '0988776655',
            'email' => 'hungnew@gmail.com',
            'contact_address' => 'Tân Phú, TP.HCM',
        ]);
        $updateResponse->assertRedirect();

        $this->student->refresh();
        $this->assertEquals('0988776655', $this->student->phone);
        $this->assertEquals('hungnew@gmail.com', $this->student->email);
    }

    public function test_student_tuition_and_viet_qr(): void
    {
        TuitionInvoice::create([
            'student_id' => $this->student->id,
            'semester_code' => 'HK1_2026_2027',
            'total_credits' => 15,
            'total_amount' => 8250000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
            'payment_code' => 'HUIT 2001230283 HK12627',
        ]);

        $response = $this->actingAs($this->studentUser)->get('/student/tuition');
        $response->assertOk()
            ->assertSee('8.250.000 VNĐ')
            ->assertSee('HUIT 2001230283 HK12627')
            ->assertSee('img.vietqr.io');
    }

    public function test_curriculum_framework_view(): void
    {
        CurriculumFramework::create([
            'specialization_name' => 'Công nghệ phần mềm',
            'semester_order' => 1,
            'course_id' => $this->course->id,
            'is_mandatory' => true,
        ]);

        $response = $this->actingAs($this->studentUser)->get('/student/curriculum');
        $response->assertOk()
            ->assertSee('HỌC KỲ 1')
            ->assertSee('Lập trình mã nguồn mở')
            ->assertSee('Bắt buộc');
    }

    public function test_admin_scholarship_evaluation_workflow(): void
    {
        SemesterEvaluation::create([
            'student_id' => $this->student->id,
            'semester_code' => 'HK1_2026_2027',
            'total_credits' => 15,
            'min_course_score' => 8.0,
            'training_score' => 92,
            'semester_gpa_10' => 8.85,
            'semester_gpa_4' => 4.0,
        ]);

        // Chạy thuật toán xét học bổng
        $evalResponse = $this->actingAs($this->adminUser)->post('/admin/scholarships/evaluate', [
            'semester' => 'HK1_2026_2027',
        ]);
        $evalResponse->assertRedirect();

        // Kiểm tra kết quả trong DB: đạt 80% (Xuất sắc)
        $eval = SemesterEvaluation::where('student_id', $this->student->id)->first();
        $this->assertTrue($eval->is_eligible);
        $this->assertEquals('80%', $eval->scholarship_type);
        $this->assertGreaterThanOrEqual(88.0, (float)$eval->scholarship_score);

        // Công bố kết quả cho sinh viên
        $this->actingAs($this->adminUser)->post('/admin/scholarships/publish', [
            'semester' => 'HK1_2026_2027',
        ]);

        // Sinh viên vào xem kết quả
        $studentView = $this->actingAs($this->studentUser)->get('/student/scholarships');
        $studentView->assertOk()
            ->assertSee('Chúc mừng bạn đã đạt Học bổng 80%!')
            ->assertSee('92 điểm');
    }
}
