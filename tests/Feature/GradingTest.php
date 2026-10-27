<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\OfferingFixtures;
use Tests\Feature\Concerns\RendersHtml;
use Tests\TestCase;

/**
 * Nhóm yêu cầu 6: điểm thành phần 10/20/70, quyền nhập điểm của giảng viên, công bố điểm,
 * bảng điểm kèm GPA và nút "Xét duyệt" tình trạng học tập.
 */
class GradingTest extends TestCase
{
    use OfferingFixtures, RefreshDatabase, RendersHtml;

    public function test_a_teacher_opens_the_component_grid_of_their_own_offering(): void
    {
        $teacher = $this->teacher();
        $offering = $this->offering($this->subject('WEB101'), $this->gate(), 'TN01', 0, ['teacher_id' => $teacher->id]);
        $this->enroll($this->student('Nguyễn An'), $offering);

        $this->actingAs($teacher)
            ->get(route('teacher.course-offerings.grades', $offering))
            ->assertOk()
            ->assertSee('Nguyễn An')
            ->assertSee('Chuyên cần (10%)')
            ->assertSee('Giữa kỳ (20%)')
            ->assertSee('Cuối kỳ (70%)')
            ->assertSee('Lưu tất cả')
            ->assertSee('Chưa công bố');
    }

    public function test_a_teacher_cannot_open_or_save_the_grid_of_another_teachers_offering(): void
    {
        $owner = $this->teacher('Giảng viên A');
        $intruder = $this->teacher('Giảng viên B');
        $offering = $this->offering($this->subject('WEB101'), $this->gate(), 'TN01', 0, ['teacher_id' => $owner->id]);
        $student = $this->student();
        $enrollment = $this->enroll($student, $offering);

        $this->actingAs($intruder)
            ->get(route('teacher.course-offerings.grades', $offering))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->post(route('teacher.course-offerings.grades.update', $offering), [
                'final_score' => [$student->id => 9],
            ])
            ->assertForbidden();

        $this->assertSame(0, Grade::where('enrollment_id', $enrollment->id)->count());
    }

    public function test_a_teacher_saves_all_three_components_for_the_whole_class_in_one_request(): void
    {
        $teacher = $this->teacher();
        $offering = $this->offering($this->subject('WEB101'), $this->gate(), 'TN01', 0, ['teacher_id' => $teacher->id]);
        $first = $this->student('Nguyễn An');
        $second = $this->student('Trần Bình');
        $firstEnrollment = $this->enroll($first, $offering);
        $this->enroll($second, $offering);

        $this->actingAs($teacher)->post(route('teacher.course-offerings.grades.update', $offering), [
            'attendance_score' => [$first->id => 8, $second->id => 9],
            'midterm_score' => [$first->id => 7, $second->id => 6],
            'final_score' => [$first->id => 6, $second->id => 5],
        ])->assertRedirect(route('teacher.course-offerings.grades', $offering))->assertSessionHas('success');

        $firstGrade = Grade::where('user_id', $first->id)->sole();
        $this->assertSame('8.00', (string) $firstGrade->attendance_score);
        $this->assertSame('7.00', (string) $firstGrade->midterm_score);
        $this->assertSame(6.4, $firstGrade->subjectScore());
        $this->assertSame($firstEnrollment->id, $firstGrade->enrollment_id);
        $this->assertSame($teacher->id, $firstGrade->updated_by);
        $this->assertSame(2, Grade::count());
    }

    public function test_cc8_gk7_ck6_lands_on_6_4_which_is_a_c_and_3_0(): void
    {
        $teacher = $this->teacher();
        $offering = $this->offering($this->subject('WEB101'), $this->gate(), 'TN01', 0, ['teacher_id' => $teacher->id]);
        $student = $this->student();
        $this->enroll($student, $offering);

        $this->actingAs($teacher)->post(route('teacher.course-offerings.grades.update', $offering), [
            'attendance_score' => [$student->id => 8],
            'midterm_score' => [$student->id => 7],
            'final_score' => [$student->id => 6],
        ]);

        $grid = $this->actingAs($teacher)
            ->get(route('teacher.course-offerings.grades', $offering))
            ->assertOk();

        $this->assertSame(6.4, Grade::sole()->subjectScore());
        $this->assertStringContainsString('6.40', $this->rowsOnly($grid->getContent()));
        $this->assertStringContainsString('C', $this->rowsOnly($grid->getContent()));
    }

    public function test_an_empty_cell_clears_the_score_instead_of_keeping_the_old_value(): void
    {
        $teacher = $this->teacher();
        $offering = $this->offering($this->subject('WEB101'), $this->gate(), 'TN01', 0, ['teacher_id' => $teacher->id]);
        $student = $this->student();
        $enrollment = $this->enroll($student, $offering);
        $this->grade($student, $offering, 8);

        $this->actingAs($teacher)->post(route('teacher.course-offerings.grades.update', $offering), [
            'attendance_score' => [$student->id => 8],
            'midterm_score' => [$student->id => ''],
            'final_score' => [$student->id => 6],
        ])->assertSessionMissing('errors');

        $grade = Grade::sole();
        $this->assertNull($grade->midterm_score, 'Ô trống có nghĩa là chưa có điểm, không phải giữ điểm cũ.');
        $this->assertSame(5.0, $grade->subjectScore());
        $this->assertDatabaseHas('grades', ['id' => $grade->id, 'enrollment_id' => $enrollment->id]);
    }

    public function test_a_row_with_no_score_yet_shows_a_dash_instead_of_zero(): void
    {
        $teacher = $this->teacher();
        $offering = $this->offering($this->subject('WEB101'), $this->gate(), 'TN01', 0, ['teacher_id' => $teacher->id]);
        $this->enroll($this->student('Nguyễn An'), $offering);

        $grid = $this->actingAs($teacher)
            ->get(route('teacher.course-offerings.grades', $offering))
            ->assertOk();

        $this->assertStringContainsString('—', $this->rowsOnly($grid->getContent()));
    }

    public function test_the_grid_header_reports_capacity_class_average_and_failed_subjects(): void
    {
        $teacher = $this->teacher();
        $offering = $this->offering($this->subject('WEB101'), $this->gate(), 'TN01', 0, ['teacher_id' => $teacher->id]);
        $good = $this->student('Nguyễn An');
        $bad = $this->student('Trần Bình');
        $this->enroll($good, $offering);
        $this->enroll($bad, $offering);
        $this->grade($good, $offering, 8.0);
        $this->grade($bad, $offering, 4.0);

        $page = $this->actingAs($teacher)
            ->get(route('teacher.course-offerings.grades', $offering))
            ->assertOk()
            ->assertSee('Sĩ số: 2')
            ->assertSee('Trung bình lớp: 6.00')
            ->assertSee('Môn F: 1');

        $this->assertStringNotContainsString('Trung bình lớp: 6.0', $this->rowsOnly($page->getContent()));
    }

    public function test_grades_are_only_written_for_students_of_that_offering(): void
    {
        $teacher = $this->teacher();
        $offering = $this->offering($this->subject('WEB101'), $this->gate(), 'TN01', 0, ['teacher_id' => $teacher->id]);
        $insider = $this->student();
        $this->enroll($insider, $offering);
        $outsider = $this->student('Sinh viên lớp khác');

        $this->actingAs($teacher)->post(route('teacher.course-offerings.grades.update', $offering), [
            'final_score' => [$insider->id => 7, $outsider->id => 10],
        ])->assertSessionHas('success');

        $this->assertSame(1, Grade::count());
        $this->assertSame($insider->id, Grade::sole()->user_id);
    }

    public function test_an_out_of_range_score_is_rejected_and_nothing_is_saved(): void
    {
        $teacher = $this->teacher();
        $offering = $this->offering($this->subject('WEB101'), $this->gate(), 'TN01', 0, ['teacher_id' => $teacher->id]);
        $student = $this->student();
        $this->enroll($student, $offering);

        $this->actingAs($teacher)->post(route('teacher.course-offerings.grades.update', $offering), [
            'final_score' => [$student->id => 10.5],
        ])->assertSessionHasErrors('final_score.'.$student->id);

        $this->assertSame(0, Grade::count());
    }

    public function test_publishing_is_refused_while_the_final_exam_score_is_missing(): void
    {
        $teacher = $this->teacher();
        $offering = $this->offering($this->subject('WEB101'), $this->gate(), 'TN01', 0, ['teacher_id' => $teacher->id]);
        $student = $this->student('Nguyễn An');
        $enrollment = $this->enroll($student, $offering);
        Grade::create(['user_id' => $student->id, 'enrollment_id' => $enrollment->id, 'course_offering_id' => $offering->id, 'attendance_score' => 8]);

        $this->actingAs($teacher)
            ->post(route('teacher.course-offerings.grades.publish', $offering))
            ->assertRedirect(route('teacher.course-offerings.grades', $offering))
            ->assertSessionHas('error');

        $this->assertFalse(Grade::sole()->is_published);
    }

    public function test_students_only_see_their_scores_once_the_teacher_publishes_them(): void
    {
        $teacher = $this->teacher();
        $offering = $this->offering($this->subject('WEB101'), $this->gate(), 'TN01', 0, ['teacher_id' => $teacher->id]);
        $student = $this->student('Nguyễn An');
        $this->enroll($student, $offering);
        $this->grade($student, $offering, 8.5, published: false);

        $this->actingAs($student)->get('/student/grades')
            ->assertOk()
            ->assertSee('Chưa công bố')
            ->assertDontSee('8.50');

        $this->actingAs($teacher)
            ->post(route('teacher.course-offerings.grades.publish', $offering))
            ->assertSessionHas('success');

        $this->assertSame(1, Grade::where('is_published', true)->count());

        $this->actingAs($student)->get('/student/grades')
            ->assertOk()
            ->assertSee('8.50');
    }

    public function test_a_student_cannot_reach_the_teacher_grade_grid(): void
    {
        $teacher = $this->teacher();
        $offering = $this->offering($this->subject('WEB101'), $this->gate(), 'TN01', 0, ['teacher_id' => $teacher->id]);

        $this->actingAs($this->student())->get(route('teacher.course-offerings.grades', $offering))->assertForbidden();
    }

    public function test_the_student_table_shows_components_letter_and_four_point_score(): void
    {
        $offering = $this->offering($this->subject('WEB101'), $this->gate());
        $student = $this->student();
        $enrollment = $this->enroll($student, $offering);
        Grade::create([
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'course_offering_id' => $offering->id,
            'attendance_score' => 8,
            'midterm_score' => 7,
            'final_score' => 6,
            'is_published' => true,
        ]);

        $rows = $this->rowsOnly($this->actingAs($student)->get('/student/grades')->assertOk()->getContent());

        foreach (['8.00', '7.00', '6.00', '6.40', 'C', '3.00'] as $expected) {
            $this->assertStringContainsString($expected, $rows);
        }
    }

    public function test_the_student_page_summarises_gpa_credits_and_classification(): void
    {
        $student = $this->student();
        $threeCredits = $this->subjectWith('ADV101', 3);
        $twoCredits = $this->subjectWith('CQ101', 2);
        $current = $this->offering($threeCredits, $this->gate(), 'TN01');
        $past = $this->offering($twoCredits, $this->semester('2025-2026', 2), 'TN01');

        $this->enroll($student, $current);
        $this->enroll($student, $past);
        $this->grade($student, $current, 9.0);
        $this->grade($student, $past, 6.4);

        $this->actingAs($student)->get('/student/grades')
            ->assertOk()
            ->assertSee('Trung bình tích lũy: hệ 4 = 3.60, hệ 10 = 7.96, tín chỉ đã tích lũy = 5 TC, xếp loại = Khá')
            ->assertSee('ADV101')
            ->assertSee('CQ101');
    }

    public function test_the_history_page_keeps_dropped_classes_and_retakes(): void
    {
        $gate = $this->gate();
        $subject = $this->subject('WEB101');
        $student = $this->student();

        $droppedEnrollment = $this->enroll($student, $this->offering($subject, $gate, 'TN01'));
        $this->grade($student, $droppedEnrollment->offering, 6.0);
        $droppedEnrollment->update(['status' => 'DROPPED', 'dropped_at' => now()]);

        $active = $this->offering($subject, $gate, 'TN02');
        $this->enroll($student, $active);
        $this->grade($student, $active, 8.5);

        $current = $this->rowsOnly($this->actingAs($student)->get('/student/grades')->assertOk()->getContent());
        $this->assertStringContainsString('TN02', $current);
        $this->assertStringNotContainsString('TN01', $current, 'Lớp đã hủy không còn ở bảng điểm hiện hành.');

        $history = $this->rowsOnly($this->actingAs($student)->get('/student/grades/history')->assertOk()->getContent());
        $this->assertStringContainsString('TN01', $history);
        $this->assertStringContainsString('Đã hủy (DROPPED)', $history);
        $this->assertStringContainsString('Đang học (ACTIVE)', $history);
    }

    public function test_the_admin_grade_screen_records_three_components_and_a_gpa_column(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $past = $this->semester('2024-2025', 2);
        $old = $this->offering($this->subjectWith('OLD101', 3), $past);
        $current = $this->offering($this->subject('WEB101'), $this->gate());
        $student = $this->student();
        $enrollment = $this->enroll($student, $current);
        $this->grade($student, $old, 4.0);

        $this->actingAs($admin)->post('/admin/grades', [
            'user_id' => $student->id,
            'course_offering_id' => $current->id,
            'attendance_score' => 9,
            'midterm_score' => 8,
            'final_score' => 7,
        ])->assertRedirect('/admin/grades')->assertSessionHas('success');

        $grade = Grade::where('course_offering_id', $current->id)->sole();
        $this->assertSame(7.4, $grade->subjectScore());
        $this->assertSame($admin->id, $grade->updated_by, 'Admin sửa điểm cũng phải để lại vết.');
        $this->assertSame($enrollment->id, $grade->enrollment_id);

        $list = $this->actingAs($admin)->get('/admin/grades')->assertOk()->assertSee('GPA hệ 4')->getContent();
        $this->assertStringContainsString('7.40', $this->rowsOnly($list));

        $filtered = $this->actingAs($admin)->get('/admin/grades?semester_id='.$past->id)->assertOk()->getContent();
        $this->assertStringContainsString('OLD101', $this->rowsOnly($filtered));
        $this->assertStringNotContainsString('WEB101', $this->rowsOnly($filtered));
    }

    public function test_the_student_page_suggests_a_review_and_the_admin_confirms_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $this->student('Nguyễn An');
        $offering = $this->offering($this->subject('WEB101'), $this->gate());
        $this->enroll($student, $offering);
        $this->grade($student, $offering, 3.0);

        $this->actingAs($admin)->get('/admin/students/'.$student->id)
            ->assertOk()
            ->assertSee('GPA hệ 4 0.00 dưới ngưỡng cảnh báo học vụ 2.00.')
            ->assertSee('Gợi ý: Cảnh báo học vụ (AT_RISK)')
            ->assertSee('Xét duyệt');

        $this->actingAs($admin)->post(route('admin.students.academic-status.update', $student), [
            'academic_status' => 'AT_RISK',
        ])->assertRedirect('/admin/students/'.$student->id)->assertSessionHas('success');

        $student->refresh();
        $this->assertSame('AT_RISK', $student->academic_status);
        $this->assertNotNull($student->academic_status_updated_at);
    }

    public function test_the_review_suggests_graduation_once_the_program_credits_are_reached(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = $this->subjectWith('WEB101', 2);
        $major = $this->majorWithPlan($subject);
        $student = $this->student('Trần Bình');
        $student->update(['class_id' => $this->adminClass($major)->id]);

        $offering = $this->offering($subject, $this->gate());
        $this->enroll($student, $offering);
        $this->grade($student, $offering, 8.0);

        $this->actingAs($admin)->get('/admin/students/'.$student->id)
            ->assertOk()
            ->assertSee('Gợi ý: Tốt nghiệp (GRADUATED)')
            ->assertSee('đủ điều kiện tốt nghiệp');
    }

    public function test_the_review_keeps_the_student_in_study_when_no_rule_fires(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $this->student('Lý Công');
        $offering = $this->offering($this->subject('WEB101'), $this->gate());
        $this->enroll($student, $offering);
        $this->grade($student, $offering, 7.0);

        $this->actingAs($admin)->get('/admin/students/'.$student->id)
            ->assertOk()
            ->assertSee('Không có môn F, đủ điều kiện lên lớp năm học tiếp.');

        $this->assertSame('STUDYING', $student->fresh()->academic_status);
        $this->assertNull($student->academic_status_updated_at, 'Gợi ý không tự đổi tình trạng.');
    }

    public function test_low_attendance_alone_is_enough_to_suggest_academic_warning(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $this->student('Đỗ Minh');
        $offering = $this->offering($this->subject('WEB101'), $this->gate());
        $enrollment = $this->enroll($student, $offering);
        $this->grade($student, $offering, 7.0);

        $this->attend($enrollment, '2026-03-02');
        $this->attend($enrollment, '2026-03-09');
        $this->attend($enrollment, '2026-03-16');
        $this->attend($enrollment, '2026-03-23', Attendance::ABSENT);

        $this->actingAs($admin)->get('/admin/students/'.$student->id)
            ->assertOk()
            ->assertSee('Gợi ý: Cảnh báo học vụ (AT_RISK)')
            ->assertSee('Tỷ lệ chuyên cần 75% dưới ngưỡng 80%.');

        $this->attend($enrollment, '2026-03-30');

        $this->actingAs($admin)->get('/admin/students/'.$student->id)
            ->assertOk()
            ->assertSee('Không có môn F, đủ điều kiện lên lớp năm học tiếp.')
            ->assertDontSee('dưới ngưỡng 80%.');
    }

    public function test_the_review_rejects_a_status_that_is_not_in_the_dictionary(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $this->student();

        $this->actingAs($admin)
            ->post(route('admin.students.academic-status.update', $student), ['academic_status' => 'EXPELLED'])
            ->assertSessionHasErrors('academic_status');

        $this->assertSame('STUDYING', $student->fresh()->academic_status);
    }

    public function test_a_teacher_cannot_open_the_academic_review_screen(): void
    {
        $student = $this->student();

        $this->actingAs($this->teacher())->get('/admin/students/'.$student->id)->assertForbidden();
    }
}
