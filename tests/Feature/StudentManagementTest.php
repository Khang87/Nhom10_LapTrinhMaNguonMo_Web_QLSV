<?php

namespace Tests\Feature;

use App\Models\StudentClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_are_redirected_to_the_dashboard_for_their_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)->get('/')->assertRedirect('/admin/dashboard');
        $this->actingAs($student)->get('/')->assertRedirect('/student/dashboard');
    }

    public function test_students_cannot_open_admin_class_management(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/classes')->assertForbidden();
    }

    public function test_guests_are_sent_to_login_and_login_redirects_by_role(): void
    {
        $this->get('/admin/classes')->assertRedirect('/login');

        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])->assertRedirect('/admin/dashboard');
    }

    public function test_login_page_includes_basic_browser_security_headers(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_login_page_uses_the_huit_background(): void
    {
        $this->withoutVite()
            ->get('/login')
            ->assertOk()
            ->assertDontSee('<nav', false)
            ->assertSee('class="login-page min-vh-100', false)
            ->assertSee('/images/HUIT_logo.png')
            ->assertSee('Quản lý sinh viên')
            ->assertDontSee('Ghi nhớ đăng nhập');

        $this->assertFileExists(public_path('images/HUIT_logo.png'));
    }

    public function test_admin_can_create_and_search_classes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/classes', [
            'class_code' => 'CTK44A',
            'class_name' => 'Công nghệ thông tin 44A',
            'room' => 'A101',
        ])->assertRedirect('/admin/classes');

        $this->actingAs($admin)->get('/admin/classes?search=44A')
            ->assertOk()
            ->assertSee('Công nghệ thông tin 44A');
    }

    public function test_class_name_cannot_exceed_one_hundred_characters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/classes', [
            'class_code' => 'CTK44A',
            'class_name' => str_repeat('A', 101),
            'room' => 'A101',
        ])->assertSessionHasErrors('class_name');
    }

    public function test_student_can_register_for_a_subject_only_once_and_unregister(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::create([
            'code' => 'WEB101',
            'name' => 'Lập trình Web',
            'credits' => 3,
        ]);

        $this->actingAs($student)->post("/student/subjects/{$subject->id}")
            ->assertRedirect('/student/subjects');
        $this->post("/student/subjects/{$subject->id}")
            ->assertRedirect('/student/subjects');

        $this->assertDatabaseCount('student_subject', 1);
        $this->get('/student/subjects')
            ->assertOk()
            ->assertSee('Lập trình Web');

        $this->delete("/student/subjects/{$subject->id}")
            ->assertRedirect('/student/subjects');

        $this->assertDatabaseCount('student_subject', 0);
    }

    public function test_student_can_view_their_own_grade(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::create([
            'code' => 'DB101',
            'name' => 'Cơ sở dữ liệu',
            'credits' => 3,
        ]);

        $student->subjects()->attach($subject);
        $student->grades()->create([
            'subject_id' => $subject->id,
            'score' => 8.5,
        ]);

        $this->actingAs($student)->get('/student/grades')
            ->assertOk()
            ->assertSee('Cơ sở dữ liệu')
            ->assertSee('8.5');
    }

    public function test_admin_can_record_a_grade_only_for_a_registered_subject(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::create([
            'code' => 'MATH101',
            'name' => 'Toán rời rạc',
            'credits' => 3,
        ]);
        $student->subjects()->attach($subject);

        $this->actingAs($admin)->post('/admin/grades', [
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'score' => 8.25,
        ])->assertRedirect('/admin/grades');

        $this->assertDatabaseHas('grades', [
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'score' => 8.25,
        ]);
        $this->get('/admin/grades')->assertOk()->assertSee('Toán rời rạc');
    }

    public function test_admin_can_record_attendance_for_a_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::create([
            'code' => 'NET101',
            'name' => 'Mạng máy tính',
            'credits' => 3,
        ]);
        $student->subjects()->attach($subject);

        $this->actingAs($admin)->post('/admin/attendances', [
            'date' => '2026-10-20',
            'attendances' => [
                $student->id => [
                    $subject->id => 'present',
                ],
            ],
        ])->assertRedirect('/admin/attendances?date=2026-10-20');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'date' => '2026-10-20 00:00:00',
            'status' => 'present',
        ]);
        $this->post('/admin/attendances', [
            'date' => '2026-10-20',
            'attendances' => [
                $student->id => [
                    $subject->id => 'absent',
                ],
            ],
        ])->assertRedirect('/admin/attendances?date=2026-10-20');

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'date' => '2026-10-20 00:00:00',
            'status' => 'absent',
        ]);
        $this->get('/admin/attendances?date=2026-10-20')
            ->assertOk()
            ->assertSee('NET101')
            ->assertSee('Vắng');
    }

    public function test_class_deletion_does_not_delete_assigned_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $class = StudentClass::create([
            'class_code' => 'CTK44B',
            'class_name' => 'Công nghệ thông tin 44B',
            'room' => 'A102',
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'class_id' => $class->id,
        ]);

        $this->actingAs($admin)->delete("/admin/classes/{$class->id}")
            ->assertRedirect('/admin/classes');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'class_id' => null,
        ]);
    }

    public function test_role_specific_management_pages_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        foreach ([
            '/admin/classes',
            '/admin/subjects',
            '/admin/students',
            '/admin/grades',
            '/admin/attendances',
        ] as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }

        $this->actingAs($student)->get('/student/dashboard')
            ->assertOk()
            ->assertSee('Chưa xếp lớp')
            ->assertSee('Chưa có điểm được cập nhật.');
        $this->actingAs($student)->get('/student/subjects')
            ->assertOk()
            ->assertSee('id="sidebar"', false)
            ->assertSee('Đăng ký học phần')
            ->assertDontSee('Quản lý lớp học');
        $this->actingAs($student)->get('/student/grades')
            ->assertOk()
            ->assertSee('id="sidebar"', false)
            ->assertSee('Kết quả học tập')
            ->assertDontSee('Quản lý lớp học');
    }

    public function test_spark_layout_is_role_specific_for_admin_and_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)->get('/admin/classes')
            ->assertOk()
            ->assertSee('id="sidebar"', false)
            ->assertSee('vendor/spark-admin/assets/css/main.css')
            ->assertSee(route('admin.classes.index'))
            ->assertSee('Quản lý sinh viên');

        $class = StudentClass::create([
            'class_code' => 'CTK45A',
            'class_name' => 'Công nghệ thông tin 45A',
            'room' => 'B201',
        ]);
        $student->update(['class_id' => $class->id]);
        $subject = Subject::create([
            'code' => 'WEB101',
            'name' => 'Lập trình Web',
            'credits' => 3,
        ]);
        $student->subjects()->attach($subject);
        $student->grades()->create([
            'subject_id' => $subject->id,
            'score' => 8.5,
        ]);
        $student->attendances()->create([
            'subject_id' => $subject->id,
            'date' => '2026-10-20',
            'status' => 'present',
        ]);

        $this->actingAs($student)->get('/student/dashboard')
            ->assertOk()
            ->assertSee('id="sidebar"', false)
            ->assertSee(route('student.subjects.index'))
            ->assertSee(route('student.grades.index'))
            ->assertSee('Công nghệ thông tin 45A')
            ->assertSee('Môn đã đăng ký')
            ->assertSee('Điểm đã công bố')
            ->assertSee('Có mặt')
            ->assertSee('Lập trình Web')
            ->assertSee('8.50')
            ->assertDontSee('id="mainNavbar"', false)
            ->assertDontSee('Tra cứu công nợ')
            ->assertDontSee('Thanh toán trực tuyến')
            ->assertDontSee('Phiếu thu tổng hợp')
            ->assertDontSee('Hộp thư sinh viên');

        $class = StudentClass::create([
            'class_code' => 'TEST01',
            'class_name' => 'Lớp kiểm thử',
            'room' => 'A101',
        ]);
        $subject = Subject::create([
            'code' => 'TEST101',
            'name' => 'Môn kiểm thử',
            'credits' => 3,
        ]);
        $managedStudent = User::factory()->create([
            'role' => 'student',
            'class_id' => $class->id,
        ]);

        foreach ([
            '/admin/classes/create',
            "/admin/classes/{$class->id}/edit",
            '/admin/subjects/create',
            "/admin/subjects/{$subject->id}/edit",
            '/admin/students/create',
            "/admin/students/{$managedStudent->id}/edit",
        ] as $path) {
            $this->actingAs($admin)->get($path)
                ->assertOk()
                ->assertSee('id="sidebar"', false);
        }

        $this->assertFileExists(public_path('vendor/spark-admin/assets/css/main.css'));
        $this->assertFileExists(public_path('vendor/spark-admin/assets/libs/bootstrap-icons/fonts/bootstrap-icons.woff2'));
        $this->assertFileExists(public_path('css/spark-layout.css'));
        $this->assertFileExists(public_path('js/spark-layout.js'));
    }
}
