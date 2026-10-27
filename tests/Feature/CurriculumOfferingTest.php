<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\StudyPlan;
use App\Models\User;
use App\Support\CurriculumOfferings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Feature\Concerns\OfferingFixtures;
use Tests\TestCase;

/**
 * Ý tưởng màn hình "Mở lớp theo khung": admin chọn khung + học kỳ rồi mở một loạt lớp học phần.
 */
class CurriculumOfferingTest extends TestCase
{
    use OfferingFixtures, RefreshDatabase;

    public function test_the_preview_separates_opened_subjects_from_those_still_missing(): void
    {
        $major = $this->majorWithPlan($web = $this->subjectWith('WEB101', 3), $this->subjectWith('DB101', 3));
        $term = $this->semester();
        $this->offering($web, $term, 'TN01');

        $this->actingAs($this->admin())
            ->get(route('admin.course-offerings.open-by-plan', [
                'major_id' => $major->id,
                'semester_id' => $term->id,
                'plan_semester' => 1,
                'group_code' => 'TN01',
            ]))
            ->assertOk()
            ->assertSee('WEB101')
            ->assertSee('DB101')
            ->assertSee('2 môn · 6 TC · mở thêm được 1 lớp.')
            ->assertSee('Chưa mở');
    }

    public function test_only_the_chosen_study_semester_of_the_framework_is_listed(): void
    {
        $major = $this->majorWithPlan($first = $this->subjectWith('WEB101', 3));
        $other = $this->subjectWith('SEC201', 3);
        StudyPlan::create([
            'major_id' => $major->id,
            'subject_id' => $other->id,
            'plan_semester' => 2,
            'kind' => 'ELECTIVE',
        ]);
        $term = $this->semester();

        $firstTerm = CurriculumOfferings::preview($major, $term, 1, 'TN01');
        $secondTerm = CurriculumOfferings::preview($major, $term, 2, 'TN01');

        $this->assertSame(['WEB101'], $this->codes($firstTerm));
        $this->assertSame(['SEC201'], $this->codes($secondTerm));
        $this->assertSame(3, CurriculumOfferings::credits($firstTerm), 'Tín chỉ quy về môn của khung, không quy về lớp.');
        $this->assertSame('ELECTIVE', $secondTerm->first()['plan']->kind);
        $this->assertSame($first->id, $firstTerm->first()['plan']->subject_id);
        $this->assertSame('TN01', $firstTerm->first()['group_code']);
    }

    public function test_the_batch_opens_every_selected_subject_with_one_group_and_capacity(): void
    {
        $major = $this->majorWithPlan($web = $this->subjectWith('WEB101', 3), $db = $this->subjectWith('DB101', 3));
        $term = $this->semester();
        $this->offering($web, $term, 'TN01');

        $this->actingAs($this->admin())
            ->post(route('admin.course-offerings.open-by-plan.store'), [
                'major_id' => $major->id,
                'semester_id' => $term->id,
                'plan_semester' => 1,
                'group_code' => 'TN02',
                'max_students' => 40,
                'is_open' => '1',
                'subjects' => [$db->id],
            ])
            ->assertRedirect(route('admin.course-offerings.index'))
            ->assertSessionHas('success', 'Đã mở 1 lớp học phần nhóm TN02 cho '.$major->name
                .' (học kỳ đào tạo 1) trong '.$term->label.'.');

        $this->assertSame(2, CourseOffering::where('semester_id', $term->id)->count());

        $created = CourseOffering::where('subject_id', $db->id)->sole();
        $this->assertSame('TN02', $created->group_code);
        $this->assertSame(40, $created->max_students);
        $this->assertTrue($created->is_open);
        $this->assertNull($created->teacher_id, 'Giảng viên phân công sau, khi mở hàng loạt.');
        $this->assertSame(1, CourseOffering::where('subject_id', $web->id)->count(), 'Môn đã có lớp không bị mở thêm.');
    }

    public function test_a_group_already_taken_by_the_same_subject_is_never_duplicated(): void
    {
        $major = $this->majorWithPlan($web = $this->subjectWith('WEB101', 3), $db = $this->subjectWith('DB101', 3));
        $term = $this->semester();
        $this->offering($web, $term, 'TN01');

        // Ô checkbox của WEB101 bị disabled nhưng đơn gửi vẫn liệt kê cả hai môn.
        $this->actingAs($this->admin())
            ->post(route('admin.course-offerings.open-by-plan.store'), [
                'major_id' => $major->id,
                'semester_id' => $term->id,
                'plan_semester' => 1,
                'group_code' => 'TN01',
                'max_students' => 0,
                'subjects' => [$web->id, $db->id],
            ])
            ->assertRedirect(route('admin.course-offerings.index'))
            ->assertSessionHas('success', 'Đã mở 1 lớp học phần nhóm TN01 cho '.$major->name
                .' (học kỳ đào tạo 1) trong '.$term->label.'.');

        $this->assertSame(2, CourseOffering::count());
        $this->assertSame(1, CourseOffering::where('subject_id', $web->id)->count());
        $this->assertSame('TN01', CourseOffering::where('subject_id', $db->id)->value('group_code'));
    }

    public function test_nothing_is_opened_when_the_selection_is_already_covered(): void
    {
        $major = $this->majorWithPlan($web = $this->subjectWith('WEB101', 3));
        $term = $this->semester();
        $this->offering($web, $term, 'TN01');

        $this->actingAs($this->admin())
            ->post(route('admin.course-offerings.open-by-plan.store'), [
                'major_id' => $major->id,
                'semester_id' => $term->id,
                'plan_semester' => 1,
                'group_code' => 'TN01',
                'max_students' => 0,
                'subjects' => [$web->id],
            ])
            ->assertSessionHas('error', 'Không mở được lớp nào: mọi môn bạn chọn đã có lớp nhóm TN01 trong '
                .$term->label.'.');

        $this->assertSame(1, CourseOffering::count());
    }

    public function test_an_empty_selection_and_an_out_of_range_capacity_are_both_rejected(): void
    {
        $major = $this->majorWithPlan($web = $this->subjectWith('WEB101', 3));
        $term = $this->semester();
        $payload = [
            'major_id' => $major->id,
            'semester_id' => $term->id,
            'plan_semester' => 1,
            'group_code' => 'TN01',
        ];

        $this->actingAs($this->admin())
            ->post(route('admin.course-offerings.open-by-plan.store'), $payload + ['max_students' => 0])
            ->assertSessionHasErrors('subjects');

        $this->actingAs($this->admin())
            ->post(route('admin.course-offerings.open-by-plan.store'), $payload + [
                'max_students' => 70000,
                'subjects' => [$web->id],
            ])
            ->assertSessionHasErrors('max_students', 'Sĩ số tối đa từ 0 tới 65535; nhập 0 khi lớp không giới hạn sĩ số.');

        $this->assertSame(0, CourseOffering::count());
    }

    public function test_only_an_admin_uses_the_batch_screen(): void
    {
        $this->actingAs($this->teacher())->get(route('admin.course-offerings.open-by-plan'))->assertForbidden();
        $this->actingAs($this->student())->get(route('admin.course-offerings.open-by-plan'))->assertForbidden();
        $this->actingAs($this->teacher())->post(route('admin.course-offerings.open-by-plan.store'), [
            'major_id' => 1, 'semester_id' => 1, 'plan_semester' => 1, 'group_code' => 'TN01',
        ])->assertForbidden();
    }

    /** @param Collection<int, array{plan: StudyPlan}> $rows @return list<string> */
    private function codes(Collection $rows): array
    {
        return $rows->pluck('plan.subject.code')->all();
    }

    public function test_a_non_string_group_in_the_url_falls_back_to_the_default(): void
    {
        $major = $this->majorWithPlan($web = $this->subjectWith('WEB101', 3));
        $term = $this->semester();
        $this->offering($web, $term, 'TN01');

        $this->actingAs($this->admin())
            ->get(route('admin.course-offerings.open-by-plan', [
                'major_id' => $major->id,
                'semester_id' => $term->id,
                'plan_semester' => 1,
                'group_code' => ['TN01', 'TN02'],
            ]))
            ->assertOk()
            ->assertSee('1 môn · 3 TC · mở thêm được 0 lớp.')
            ->assertSee('value="TN01"', false);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
