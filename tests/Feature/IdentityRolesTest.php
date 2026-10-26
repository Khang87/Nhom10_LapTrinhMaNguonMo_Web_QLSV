<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\StudentClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\OfferingFixtures;
use Tests\TestCase;

class IdentityRolesTest extends TestCase
{
    use OfferingFixtures, RefreshDatabase;

    public function test_teacher_signs_in_and_lands_on_the_teacher_dashboard(): void
    {
        $teacher = User::factory()->create([
            'email' => 'giangvien@example.com',
            'password' => 'password123',
            'role' => 'teacher',
        ]);

        $this->post('/login', [
            'email' => 'giangvien@example.com',
            'password' => 'password123',
        ])->assertRedirect('/teacher/dashboard');

        $this->actingAs($teacher)->get('/')->assertRedirect('/teacher/dashboard');
        $this->actingAs($teacher)->get('/teacher/dashboard')->assertOk();
    }

    public function test_admin_and_student_sign_in_paths_are_unchanged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)->get('/')->assertRedirect('/admin/dashboard');
        $this->actingAs($student)->get('/')->assertRedirect('/student/dashboard');
    }

    public function test_roles_cannot_cross_into_another_roles_area(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)->get('/teacher/dashboard')->assertForbidden();
        $this->actingAs($student)->get('/teacher/dashboard')->assertForbidden();

        $this->actingAs($teacher)->get('/admin/students')->assertForbidden();
        $this->actingAs($teacher)->get('/admin/accounts')->assertForbidden();
        $this->actingAs($teacher)->get('/student/dashboard')->assertForbidden();
    }

    public function test_admin_creates_a_teacher_account_and_ignores_the_class_field(): void
    {
        $class = StudentClass::create([
            'class_code' => 'CTK44C',
            'class_name' => 'Công nghệ thông tin 44C',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/admin/accounts', [
            'name' => 'Nguyễn Thị Giang',
            'email' => 'newteacher@example.com',
            'password' => 'password123',
            'role' => 'teacher',
            'class_id' => $class->id,
        ])->assertRedirect(route('admin.accounts.index'));

        $account = User::where('email', 'newteacher@example.com')->firstOrFail();
        $this->assertSame('teacher', $account->role);
        $this->assertNull($account->class_id, 'Lớp chỉ dành cho sinh viên');
    }

    public function test_admin_creates_a_student_account_assigned_to_a_class(): void
    {
        $class = StudentClass::create([
            'class_code' => 'CTK44D',
            'class_name' => 'Công nghệ thông tin 44D',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/admin/accounts', [
            'name' => 'Trần Sinh Viên',
            'email' => 'newstudent@example.com',
            'password' => 'password123',
            'role' => 'student',
            'class_id' => $class->id,
        ])->assertRedirect(route('admin.accounts.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'newstudent@example.com',
            'role' => 'student',
            'class_id' => $class->id,
        ]);
    }

    public function test_editing_an_account_without_a_password_keeps_the_old_hash(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'student', 'password' => 'password123']);
        $originalHash = $target->password;

        $this->actingAs($admin)->put("/admin/accounts/{$target->id}", [
            'name' => 'Đổi tên mới',
            'email' => $target->email,
            'password' => '',
            'role' => 'teacher',
        ])->assertRedirect(route('admin.accounts.index'));

        $target->refresh();
        $this->assertSame('teacher', $target->role);
        $this->assertSame('Đổi tên mới', $target->name);
        $this->assertSame($originalHash, $target->password);
    }

    public function test_email_must_be_unique_across_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['email' => 'used@example.com', 'role' => 'student']);

        $this->actingAs($admin)->post('/admin/accounts', [
            'name' => 'Người trùng email',
            'email' => 'used@example.com',
            'password' => 'password123',
            'role' => 'teacher',
        ])->assertSessionHasErrors('email');

        $this->assertSame(2, User::count());
    }

    public function test_role_must_be_one_of_the_three_known_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/accounts', [
            'name' => 'Người ngoài hệ thống',
            'email' => 'ghost@example.com',
            'password' => 'password123',
            'role' => 'rector',
        ])->assertSessionHasErrors('role');
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->delete("/admin/accounts/{$admin->id}")->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_demote_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put("/admin/accounts/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'student',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame('admin', $admin->refresh()->role);
    }

    public function test_account_with_grades_is_not_deletable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $offering = $this->offering($this->subject('WEB101'), $this->semester(), 'TN01');
        Grade::create([
            'user_id' => $student->id,
            'course_offering_id' => $offering->id,
            'final_score' => 8.5,
        ]);

        $this->actingAs($admin)->delete("/admin/accounts/{$student->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_accounts_list_can_be_filtered_by_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['name' => 'Giang viên A', 'role' => 'teacher']);
        User::factory()->create(['name' => 'Sinh viên B', 'role' => 'student']);

        $this->actingAs($admin)->get('/admin/accounts?role=teacher')
            ->assertOk()
            ->assertSee('Giang viên A')
            ->assertDontSee('Sinh viên B');
    }

    public function test_teacher_dashboard_never_renders_admin_menu_links(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)->get('/teacher/dashboard')
            ->assertOk()
            ->assertDontSee('/admin/students');
    }

    public function test_forms_keep_javascript_out_of_inline_script_tags(): void
    {
        // CSP script-src 'self' chặn inline script nên JS phải nằm trong public/js/app.js.
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['/admin/accounts/create', '/admin/students/create'] as $path) {
            $this->assertStringNotContainsString(
                '<script>',
                $this->actingAs($admin)->get($path)->getContent(),
                "Trang {$path} vẫn còn inline script bị CSP chặn."
            );
        }
    }
}
