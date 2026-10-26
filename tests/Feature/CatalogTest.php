<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Grade;
use App\Models\Major;
use App\Models\StudyPlan;
use App\Models\Subject;
use App\Models\User;
use App\Support\PrerequisiteRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\OfferingFixtures;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use OfferingFixtures, RefreshDatabase;

    public function test_admin_builds_department_major_and_subject_chain(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/departments', [
            'code' => 'CNTT',
            'name' => 'Khoa Công nghệ thông tin',
        ])->assertRedirect('/admin/departments')->assertSessionHas('success');

        $department = Department::firstOrFail();

        $this->actingAs($admin)->post('/admin/majors', [
            'department_id' => $department->id,
            'code' => 'HTTT',
            'name' => 'Hệ thống thông tin',
        ])->assertRedirect('/admin/majors');

        $major = Major::firstOrFail();

        $this->actingAs($admin)->post('/admin/subjects', [
            'code' => 'WEB101',
            'name' => 'Lập trình Web',
            'credits' => 4,
            'major_id' => $major->id,
            'description' => 'Học phần nhập môn.',
            'total_sessions' => 60,
        ])->assertRedirect('/admin/subjects');

        $this->assertDatabaseHas('subjects', [
            'code' => 'WEB101',
            'major_id' => $major->id,
            'total_sessions' => 60,
        ]);
    }

    public function test_major_requires_an_existing_department(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/majors', [
            'department_id' => 999,
            'code' => 'HTTT',
            'name' => 'Hệ thống thông tin',
        ])->assertSessionHasErrors('department_id');

        $this->assertDatabaseCount('majors', 0);
    }

    public function test_department_still_holding_majors_is_not_deletable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $department = Department::create(['code' => 'CNTT', 'name' => 'Khoa Công nghệ thông tin']);
        Major::create(['department_id' => $department->id, 'code' => 'HTTT', 'name' => 'Hệ thống thông tin']);

        $this->actingAs($admin)->delete("/admin/departments/{$department->id}")
            ->assertRedirect('/admin/departments')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
        $this->assertDatabaseCount('majors', 1);
    }

    public function test_subject_edit_form_offers_prerequisite_and_concurrent_blocks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::create(['code' => 'WEB101', 'name' => 'Lập trình Web', 'credits' => 4]);
        Subject::create(['code' => 'DB101', 'name' => 'Cơ sở dữ liệu', 'credits' => 3]);

        $this->actingAs($admin)->get("/admin/subjects/{$subject->id}/edit")
            ->assertOk()
            ->assertSee('Môn tiên quyết')
            ->assertSee('Môn song hành')
            ->assertSee('DB101 - Cơ sở dữ liệu');
    }

    public function test_prerequisite_that_closes_a_cycle_is_rejected_with_the_path(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $web = Subject::create(['code' => 'WEB101', 'name' => 'Lập trình Web', 'credits' => 4]);
        $db = Subject::create(['code' => 'DB101', 'name' => 'Cơ sở dữ liệu', 'credits' => 3]);

        $this->actingAs($admin)->put("/admin/subjects/{$db->id}", $this->subjectPayload($db, [$web->id]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($admin)->put("/admin/subjects/{$web->id}", $this->subjectPayload($web, [$db->id]))
            ->assertRedirect()
            ->assertSessionHas('error', 'Xếp tiên quyết sẽ tạo vòng lặp: WEB101 → DB101 → WEB101. Hãy bỏ một môn trong vòng này khỏi danh sách tiên quyết.');

        $this->assertDatabaseCount('subject_prerequisites', 1);
    }

    public function test_subject_cannot_be_its_own_prerequisite(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $web = Subject::create(['code' => 'WEB101', 'name' => 'Lập trình Web', 'credits' => 4]);

        $this->actingAs($admin)->put("/admin/subjects/{$web->id}", $this->subjectPayload($web, [$web->id]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('subject_prerequisites', 0);
    }

    public function test_concurrent_is_read_from_both_directions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $web = Subject::create(['code' => 'WEB101', 'name' => 'Lập trình Web', 'credits' => 4]);
        $db = Subject::create(['code' => 'DB101', 'name' => 'Cơ sở dữ liệu', 'credits' => 3]);

        $this->actingAs($admin)->put("/admin/subjects/{$web->id}", $this->subjectPayload($web, [], [$db->id]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('subject_concurrents', 1);
        $this->assertTrue($web->refresh()->concurrentSubjects->contains('code', 'DB101'));
        $this->assertTrue($db->refresh()->concurrentSubjects->contains('code', 'WEB101'));
    }

    public function test_study_plan_rejects_the_same_subject_twice_for_one_major(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $major = $this->catalog();
        $web = Subject::create(['code' => 'WEB101', 'name' => 'Lập trình Web', 'credits' => 4]);

        $this->actingAs($admin)->post('/admin/study-plans', [
            'major_id' => $major->id,
            'subject_id' => $web->id,
            'plan_semester' => 3,
            'kind' => 'REQUIRED',
        ])->assertRedirect()->assertSessionHas('success');

        $this->actingAs($admin)->post('/admin/study-plans', [
            'major_id' => $major->id,
            'subject_id' => $web->id,
            'plan_semester' => 5,
            'kind' => 'ELECTIVE',
        ])->assertSessionHasErrors('subject_id');

        $this->assertDatabaseCount('study_plans', 1);
    }

    public function test_study_plan_frame_lists_semesters_and_total_credits(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $major = $this->catalog();
        $web = Subject::create(['code' => 'WEB101', 'name' => 'Lập trình Web', 'credits' => 4]);
        $db = Subject::create(['code' => 'DB101', 'name' => 'Cơ sở dữ liệu', 'credits' => 3]);

        StudyPlan::create(['major_id' => $major->id, 'subject_id' => $db->id, 'plan_semester' => 2, 'kind' => 'REQUIRED']);
        StudyPlan::create(['major_id' => $major->id, 'subject_id' => $web->id, 'plan_semester' => 1, 'kind' => 'ELECTIVE']);

        $response = $this->actingAs($admin)->get("/admin/study-plans?major_id={$major->id}")
            ->assertOk()
            ->assertSee('Tổng tín chỉ: 7')
            ->assertSee('Tự chọn (ELECTIVE)')
            ->getContent();

        $tbodyStart = strpos($response, '<tbody>');
        $tbody = substr($response, $tbodyStart, strpos($response, '</tbody>') - $tbodyStart);

        $this->assertLessThan(
            strpos($tbody, 'DB101'),
            strpos($tbody, 'WEB101'),
            'Môn học kỳ 1 phải xuất hiện trước môn học kỳ 2.',
        );
    }

    public function test_blocking_prerequisites_lists_only_unmet_subjects_with_scores(): void
    {
        $major = $this->catalog();
        $web = Subject::create(['code' => 'WEB101', 'name' => 'Lập trình Web', 'credits' => 4, 'major_id' => $major->id]);
        $db = Subject::create(['code' => 'DB101', 'name' => 'Cơ sở dữ liệu', 'credits' => 3, 'major_id' => $major->id]);
        $math = Subject::create(['code' => 'MA001', 'name' => 'Giải tích', 'credits' => 3]);
        $web->prerequisites()->attach([$db->id, $math->id]);

        $student = User::factory()->create(['role' => 'student']);
        $offering = $this->offering($math, $this->semester(), 'TN01');
        Grade::create([
            'user_id' => $student->id,
            'course_offering_id' => $offering->id,
            'final_score' => 3.5,
        ]);

        $blocking = PrerequisiteRules::blockingPrerequisites($student, $web, collect([$db]));

        $this->assertSame([
            ['code' => 'MA001', 'name' => 'Giải tích', 'required_score' => 5.0, 'actual_score' => 3.5],
        ], $blocking);
    }

    public function test_major_still_holding_a_study_plan_is_not_deletable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $major = $this->catalog();
        $subject = Subject::create(['code' => 'WEB101', 'name' => 'Lập trình Web', 'credits' => 4]);
        $plan = StudyPlan::create([
            'major_id' => $major->id,
            'subject_id' => $subject->id,
            'plan_semester' => 1,
            'kind' => 'REQUIRED',
        ]);

        $this->actingAs($admin)->delete("/admin/majors/{$major->id}")
            ->assertRedirect('/admin/majors')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('majors', ['id' => $major->id]);
        $this->assertDatabaseHas('study_plans', ['id' => $plan->id]);
    }

    public function test_bulk_sync_updates_semester_and_kind_of_every_row(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $major = $this->catalog();
        $web = Subject::create(['code' => 'WEB101', 'name' => 'Lập trình Web', 'credits' => 4]);
        $db = Subject::create(['code' => 'DB101', 'name' => 'Cơ sở dữ liệu', 'credits' => 3]);
        $webPlan = StudyPlan::create(['major_id' => $major->id, 'subject_id' => $web->id, 'plan_semester' => 1, 'kind' => 'REQUIRED']);
        $dbPlan = StudyPlan::create(['major_id' => $major->id, 'subject_id' => $db->id, 'plan_semester' => 2, 'kind' => 'REQUIRED']);

        $this->actingAs($admin)->put('/admin/study-plans', [
            'major_id' => $major->id,
            'entries' => [
                $webPlan->id => ['plan_semester' => 5, 'kind' => 'ELECTIVE'],
                $dbPlan->id => ['plan_semester' => 6, 'kind' => 'REQUIRED'],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('study_plans', ['id' => $webPlan->id, 'plan_semester' => 5, 'kind' => 'ELECTIVE']);
        $this->assertDatabaseHas('study_plans', ['id' => $dbPlan->id, 'plan_semester' => 6, 'kind' => 'REQUIRED']);
    }

    public function test_teacher_cannot_open_catalog_pages(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        foreach (['/admin/departments', '/admin/majors', '/admin/study-plans', '/admin/subjects'] as $path) {
            $this->actingAs($teacher)->get($path)->assertForbidden();
        }
    }

    private function catalog(): Major
    {
        $department = Department::create(['code' => 'CNTT', 'name' => 'Khoa Công nghệ thông tin']);

        return Major::create(['department_id' => $department->id, 'code' => 'HTTT', 'name' => 'Hệ thống thông tin']);
    }

    /**
     * @param  array<int, int>  $prerequisites
     * @param  array<int, int>  $concurrents
     * @return array<string, mixed>
     */
    private function subjectPayload(Subject $subject, array $prerequisites, array $concurrents = []): array
    {
        return [
            'code' => $subject->code,
            'name' => $subject->name,
            'credits' => $subject->credits,
            'prerequisite_ids' => $prerequisites,
            'concurrent_ids' => $concurrents,
        ];
    }
}
