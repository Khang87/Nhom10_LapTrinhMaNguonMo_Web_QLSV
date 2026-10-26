<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\OfferingFixtures;
use Tests\TestCase;

class ExtendedRequirementsTest extends TestCase
{
    use OfferingFixtures, RefreshDatabase;

    public function test_admin_can_upload_a_student_avatar(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/students', [
            'name' => 'Hồ Thị An',
            'email' => 'an@example.com',
            'password' => 'password123',
            'avatar' => $this->pngUpload(),
        ]);

        $response->assertRedirect(route('admin.students.index'));

        $student = User::where('email', 'an@example.com')->firstOrFail();
        $this->assertNotNull($student->avatar);
        Storage::disk('public')->assertExists($student->avatar);
        $this->assertStringStartsWith('avatars/', $student->avatar);
    }

    public function test_first_avatar_upload_for_student_without_avatar(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'avatar' => null]);

        $this->actingAs($admin)->put("/admin/students/{$student->id}", [
            'name' => $student->name,
            'email' => $student->email,
            'avatar' => $this->pngUpload(),
        ])->assertRedirect(route('admin.students.index'));

        $student->refresh();
        $this->assertNotNull($student->avatar);
        Storage::disk('public')->assertExists($student->avatar);
    }

    public function test_replacing_an_avatar_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'avatar' => 'avatars/old.png']);
        Storage::disk('public')->put($student->avatar, 'old');

        $this->actingAs($admin)->put("/admin/students/{$student->id}", [
            'name' => $student->name,
            'email' => $student->email,
            'avatar' => $this->pngUpload(),
        ])->assertRedirect(route('admin.students.index'));

        $student->refresh();
        Storage::disk('public')->assertMissing('avatars/old.png');
        Storage::disk('public')->assertExists($student->avatar);
    }

    public function test_avatar_rejects_files_that_are_not_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/students', [
            'name' => 'Bùi Văn B',
            'email' => 'b@example.com',
            'password' => 'password123',
            'avatar' => UploadedFile::fake()->create('profile.pdf', 30, 'application/pdf'),
        ])->assertSessionHasErrors('avatar');

        $this->assertDatabaseMissing('users', ['email' => 'b@example.com']);
    }

    public function test_student_list_answers_ajax_search_with_json_fragments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'student', 'name' => 'Đặng Văn Có', 'email' => 'co@example.com']);
        User::factory()->create(['role' => 'student', 'name' => 'Diệp Văn Không', 'email' => 'khong@example.com']);

        $response = $this->actingAs($admin)->getJson('/admin/students?search=co@example');

        $response->assertOk()
            ->assertJsonStructure(['rows', 'pagination', 'total'])
            ->assertJsonPath('total', 1);

        $payload = $response->json();
        $this->assertStringContainsString('Đặng Văn Có', $payload['rows']);
        $this->assertStringNotContainsString('Diệp Văn Không', $payload['rows']);
    }

    public function test_student_list_still_renders_full_html_without_ajax(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'student', 'name' => 'Ê Văn Ê']);

        $this->actingAs($admin)
            ->get('/admin/students?search=Ê')
            ->assertOk()
            ->assertSee('Ê Văn Ê')
            ->assertDontSee('Diệp');
    }

    public function test_admin_can_export_grades_as_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Trần Một']);
        $subject = Subject::create(['code' => 'CS1', 'name' => 'Cơ số 1', 'credits' => 3]);
        $offering = $this->offering($subject, $this->semester(), 'TN01');
        Grade::create([
            'user_id' => $student->id,
            'course_offering_id' => $offering->id,
            'final_score' => 8.5,
        ]);

        $response = $this->actingAs($admin)->get('/admin/grades/export');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $response->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Trần Một', $csv);
        $this->assertStringContainsString("CS1\t\"Cơ số 1\"\tTN01\t3\t8,5", $csv);
    }

    public function test_admin_can_export_attendance_of_a_day_as_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Lê Hai']);
        $subject = Subject::create(['code' => 'CS2', 'name' => 'Cơ số 2', 'credits' => 2]);
        Attendance::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'date' => '2026-10-21',
            'status' => 'absent',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendances/export?date=2026-10-21');

        $response->assertOk();
        $csv = $response->getContent();
        $this->assertStringContainsString('Lê Hai', $csv);
        $this->assertStringContainsString('Vắng', $csv);
        $this->assertStringNotContainsString('Cơ số 1', $csv);
    }

    public function test_students_cannot_download_the_reports(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/grades/export')->assertForbidden();
        $this->actingAs($student)->get('/admin/attendances/export')->assertForbidden();
    }

    private function pngUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'avatar').'.png';
        imagepng(imagecreatetruecolor(64, 64), $path);

        return new UploadedFile($path, 'avatar.png', 'image/png', null, true);
    }
}
