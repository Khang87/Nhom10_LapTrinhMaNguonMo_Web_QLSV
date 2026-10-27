<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\LectureSlot;
use App\Models\Semester;
use App\Models\User;
use App\Support\TimetableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\OfferingFixtures;
use Tests\TestCase;

/**
 * Wave 7.5 — thời khóa biểu dựng từ lecture_slots (SPEC-attendance-schedule).
 */
class TimetableTest extends TestCase
{
    use OfferingFixtures, RefreshDatabase;

    /** Một kỳ dùng chung cả test: bảng học kỳ ràng buộc (năm học, số kỳ) nên không tạo hai lần. */
    private ?Semester $term = null;

    public function test_the_grid_holds_every_weekday_even_the_empty_ones(): void
    {
        $offering = $this->timetableClass();
        $this->slot($offering, 3, '07:00', '09:30');
        $this->slot($offering, 5, '07:00', '09:30');

        $grid = TimetableService::grid(TimetableService::forOffering($offering->id));

        $this->assertSame([2, 3, 4, 5, 6, 7], array_keys($grid));
        $this->assertCount(1, $grid[3]);
        $this->assertCount(1, $grid[5]);
        $this->assertEmpty($grid[2]);
    }

    public function test_a_student_sees_only_the_slots_of_classes_still_enrolled_in(): void
    {
        $student = $this->student('Nguyễn An');
        $kept = $this->timetableClass();
        $dropped = $this->timetableClass();
        $this->slot($kept, 3, '07:00', '09:30');
        $this->slot($dropped, 4, '07:00', '09:30');
        $this->enroll($student, $kept);
        $this->enroll($student, $dropped)->update(['status' => 'DROPPED', 'dropped_at' => now()]);

        $slots = TimetableService::forStudent($student);

        $this->assertSame([$kept->id], $slots->pluck('course_offering_id')->unique()->all());

        $dropped->enrollments()->where('user_id', $student->id)->update(['status' => 'ACTIVE']);
        $this->assertSame(2, TimetableService::forStudent($student)->count());
    }

    public function test_the_semester_filter_keeps_one_term(): void
    {
        $student = $this->student('Nguyễn An');
        $first = $this->timetableClass($this->semester('2025-2026', 1));
        $second = $this->timetableClass($this->semester('2025-2026', 2));
        $this->slot($first, 3, '07:00', '09:30');
        $this->slot($second, 4, '07:00', '09:30');
        $this->enroll($student, $first);
        $this->enroll($student, $second);

        $this->assertSame(2, TimetableService::forStudent($student)->count());
        $this->assertSame(
            [$first->id],
            TimetableService::forStudent($student, $first->semester)->pluck('course_offering_id')->all(),
        );
    }

    public function test_a_teacher_sees_the_slots_of_every_class_they_teach(): void
    {
        $teacher = $this->teacher('Giảng viên A');
        $own = $this->timetableClass(teacher: $teacher);
        $other = $this->timetableClass(teacher: $this->teacher('Giảng viên B'));
        $this->slot($own, 3, '07:00', '09:30');
        $this->slot($own, 3, '13:00', '15:00');
        $this->slot($other, 3, '07:00', '09:30');

        $this->assertSame(2, TimetableService::forTeacher($teacher)->count());
    }

    public function test_two_classes_at_the_same_time_are_a_conflict(): void
    {
        $student = $this->student('Nguyễn An');
        $a = $this->timetableClass();
        $b = $this->timetableClass();
        $c = $this->timetableClass();
        $first = $this->slot($a, 3, '07:00', '09:30');
        $clash = $this->slot($b, 3, '09:00', '11:00');
        $this->slot($c, 3, '13:00', '15:00');
        $this->slot($c, 4, '07:00', '09:30');

        foreach ([$a, $b, $c] as $offering) {
            $this->enroll($student, $offering);
        }

        $conflicts = TimetableService::conflicts(TimetableService::forStudent($student));

        $this->assertCount(1, $conflicts);
        $this->assertSame(
            [$first->id, $clash->id],
            [$conflicts[0]['first']->id, $conflicts[0]['second']->id],
            'Sát giờ (kết thúc đúng lúc bắt đầu) không phải trùng lịch.',
        );
        $this->assertSame(
            [$clash->id],
            TimetableService::overlapping(TimetableService::forOffering($a->id), TimetableService::forOffering($b->id))
                ->pluck('id')->all(),
        );
        $this->assertEmpty(
            TimetableService::overlapping(TimetableService::forOffering($a->id), TimetableService::forOffering($c->id)),
            'Slot khác ngày thì không tính là trùng dù cùng khung giờ.'
        );
    }

    public function test_a_student_timetable_shows_one_cell_per_slot(): void
    {
        $student = $this->student('Nguyễn An');
        $offering = $this->timetableClass();
        $this->slot($offering, 3, '07:00', '09:30');
        $this->slot($offering, 5, '13:00', '15:00');
        $this->enroll($student, $offering);

        $this->actingAs($student)
            ->get(route('student.timetable'))
            ->assertOk()
            ->assertSee('Thời khóa biểu')
            ->assertSee('07:00–09:30')
            ->assertSee('13:00–15:00')
            ->assertSee($offering->subject->label())
            ->assertSee('Học kỳ 1 năm học 2025-2026 (hiện hành)');
    }

    public function test_a_dropped_class_leaves_the_timetable(): void
    {
        $student = $this->student('Nguyễn An');
        $offering = $this->timetableClass();
        $this->slot($offering, 3, '07:00', '09:30');
        $enrollment = $this->enroll($student, $offering);

        $this->actingAs($student)->get(route('student.timetable'))
            ->assertOk()
            ->assertSee('07:00–09:30');

        $enrollment->update(['status' => 'DROPPED', 'dropped_at' => now()]);

        $this->actingAs($student)->get(route('student.timetable'))
            ->assertOk()
            ->assertDontSee('07:00–09:30');
    }

    public function test_the_timetable_warns_about_overlapping_classes(): void
    {
        $student = $this->student('Nguyễn An');
        $first = $this->timetableClass();
        $second = $this->timetableClass();
        $this->slot($first, 3, '07:00', '09:30');
        $this->slot($second, 3, '09:00', '11:00');
        $this->enroll($student, $first);
        $this->enroll($student, $second);

        $this->actingAs($student)
            ->get(route('student.timetable'))
            ->assertOk()
            ->assertSee('cặp trùng giờ')
            ->assertSee($second->subject->label());
    }

    public function test_the_registration_page_warns_before_a_clashing_class_is_chosen(): void
    {
        $term = $this->gate();
        $mine = $this->offering($this->subject('TK111'), $term);
        $clash = $this->offering($this->subject('TK222'), $term);
        $spare = $this->offering($this->subject('TK333'), $term);
        $this->slot($mine, 3, '07:00', '09:30');
        $this->slot($clash, 3, '09:00', '11:00');
        $this->slot($spare, 4, '07:00', '09:30');
        $student = $this->student('Nguyễn An');
        $this->enroll($student, $mine);

        $this->actingAs($student)
            ->get(route('student.course-offerings.index'))
            ->assertOk()
            ->assertSee('Trùng giờ 1 buổi')
            // Lớp học cùng giờ nhưng khác ngày thì không bị cảnh báo.
            ->assertDontSee('Trùng giờ 2 buổi');
    }

    public function test_a_teacher_timetable_links_each_class_to_its_attendance_screen(): void
    {
        $teacher = $this->teacher('Giảng viên A');
        $offering = $this->timetableClass(teacher: $teacher);
        $this->slot($offering, 4, '07:00', '09:30');
        $this->timetableClass(teacher: $this->teacher('Giảng viên B'));

        $this->actingAs($teacher)
            ->get(route('teacher.timetable'))
            ->assertOk()
            ->assertSee('Điểm danh')
            ->assertSee('/teacher/course-offerings/'.$offering->id.'/attendance', false)
            ->assertDontSee('Giảng viên B');
    }

    public function test_admin_picks_a_class_and_downloads_its_timetable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mine = $this->timetableClass();
        $this->slot($mine, 3, '07:00', '09:30');
        $chosen = $this->timetableClass();
        $this->slot($chosen, 6, '17:00', '19:00');

        $this->actingAs($admin)
            ->get(route('admin.timetable.index', ['offering_id' => $chosen->id]))
            ->assertOk()
            ->assertSee('17:00–19:00')
            ->assertDontSee('07:00–09:30');

        $csv = $this->actingAs($admin)
            ->get(route('admin.timetable.export', ['offering_id' => $chosen->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Thứ 6', $csv);
        $this->assertStringContainsString('17:00–19:00', $csv);
        $this->assertStringContainsString($chosen->subject->code, $csv);
        $this->assertStringContainsString('Giảng viên', $csv, 'Cột tiêu đề phải có trong dòng đầu.');
        $this->assertStringNotContainsString('07:00', $csv, 'CSV một lớp không được chứa slot của lớp khác.');
    }

    public function test_a_student_cannot_open_the_admin_timetable(): void
    {
        $this->actingAs($this->student())
            ->get(route('admin.timetable.index'))
            ->assertForbidden();
    }

    private function timetableClass(?Semester $semester = null, ?User $teacher = null): CourseOffering
    {
        return $this->offering(
            $this->subject('TK'.random_int(100000, 999999)),
            $semester ?? $this->term ??= $this->semester(),
            'TN01',
            0,
            ['teacher_id' => $teacher?->id ?? $this->teacher()->id],
        );
    }

    private function slot(CourseOffering $offering, int $weekDay, string $start, string $end): LectureSlot
    {
        return LectureSlot::create([
            'course_offering_id' => $offering->id,
            'week_day' => $weekDay,
            'start_time' => $start,
            'end_time' => $end,
            'room' => 'A'.random_int(100, 999),
        ]);
    }
}
