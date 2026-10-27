<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\LectureSlot;
use App\Models\Semester;
use App\Models\User;
use App\Support\AttendanceRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Feature\Concerns\OfferingFixtures;
use Tests\Feature\Concerns\RendersHtml;
use Tests\TestCase;

/**
 * Wave 7.4 — màn hình điểm danh nhanh của giảng viên: lưới tuần, bỏ check người vắng,
 * lưu một lần cho cả lớp và chỉ lớp mình mới được chấm.
 */
class TeacherAttendanceTest extends TestCase
{
    use OfferingFixtures, RefreshDatabase, RendersHtml;

    public function test_a_teacher_sees_the_week_grid_of_their_own_class(): void
    {
        $teacher = $this->teacher();
        $offering = $this->managedBy($teacher, $semester = $this->semester());
        $this->slot($offering, 4, '07:00', '09:30', 'A101');
        $this->enroll($this->student('Nguyễn An'), $offering);

        $this->actingAs($teacher)
            ->get(route('teacher.course-offerings.attendance', ['offering' => $offering, 'week' => '2026-10-26']))
            ->assertOk()
            ->assertSee('Sĩ số đang học')
            ->assertSee('Buổi đã điểm danh')
            ->assertSee('07:00–09:30')
            ->assertSee('A101')
            ->assertSee('Chưa điểm danh')
            ->assertSee('Chưa có dữ liệu')
            ->assertSee('Tuần 26/10 – 31/10/2026');
    }

    public function test_a_slot_only_shows_in_the_weeks_it_covers(): void
    {
        $teacher = $this->teacher();
        $offering = $this->managedBy($teacher, $this->semester());
        $this->slot($offering, 4, '13:00', '15:00', 'B202', ['starts_on' => '2026-11-02', 'ends_on' => '2026-11-30']);

        $this->actingAs($teacher)
            ->get(route('teacher.course-offerings.attendance', ['offering' => $offering, 'week' => '2026-10-26']))
            ->assertOk()
            ->assertDontSee('13:00–15:00');

        $this->actingAs($teacher)
            ->get(route('teacher.course-offerings.attendance', ['offering' => $offering, 'week' => '2026-11-02']))
            ->assertOk()
            ->assertSee('13:00–15:00');
    }

    public function test_attending_a_session_records_one_line_per_student(): void
    {
        $teacher = $this->teacher();
        $offering = $this->managedBy($teacher, $this->semester());

        foreach (range(1, 15) as $number) {
            $this->enroll($this->student('Sinh viên '.$number), $offering);
        }

        $this->actingAs($teacher)
            ->post($this->storeRoute($offering), ['session_date' => '2026-10-27', 'statuses' => []])
            ->assertRedirect(route('teacher.course-offerings.attendance.session.create', [
                'offering' => $offering,
                'date' => '2026-10-27',
            ]));

        $this->assertSame(15, Attendance::count());
        $this->assertSame(15, Attendance::where('status', Attendance::PRESENT)->count());
        $this->assertSame(15, $offering->attendanceSessions()->sole()->records()->count());
    }

    public function test_unchecked_students_are_absent_and_their_rate_drops(): void
    {
        $teacher = $this->teacher();
        $offering = $this->managedBy($teacher, $this->semester());
        $absent = $this->theClass($offering, ['Vắng 1', 'Vắng 2', 'Vắng 3']);
        $chronic = $this->theClass($offering, ['Vắng nhiều'])->first();
        $present = $this->theClass($offering, ['Có mặt 5']);
        $everyone = $absent->push($chronic)->merge($present);

        // Ba buổi đầu cả lớp có mặt, buổi tư một bạn nghỉ, buổi năm bỏ check ba bạn.
        $schedule = [
            '2026-11-01' => [],
            '2026-11-02' => [],
            '2026-11-03' => [],
            '2026-11-04' => [$chronic],
            '2026-11-05' => $absent->push($chronic)->all(),
        ];

        foreach ($schedule as $date => $missed) {
            $this->actingAs($teacher)->post($this->storeRoute($offering), [
                'session_date' => $date,
                'statuses' => $this->absentOnly($everyone, collect($missed)),
            ])->assertSessionHas('success');
        }

        $this->assertSame(25, Attendance::count());
        $this->assertSame(5, Attendance::where('status', Attendance::ABSENT)->count());

        $this->assertSame(80.0, $this->rateOf($absent->first()), 'Bỏ check 1/5 buổi ⇒ 80%, đúng ngưỡng.');
        $this->assertSame(60.0, $this->rateOf($chronic), 'Vắng 2/5 buổi ⇒ dưới ngưỡng.');
        $this->assertSame(100.0, $this->rateOf($present->first()));

        $this->actingAs($teacher)
            ->get(route('teacher.course-offerings.attendance.session.create', [
                'offering' => $offering,
                'date' => '2026-11-05',
            ]))
            ->assertOk()
            ->assertSee('Có mặt 4/5 buổi')
            ->assertSee('Dưới ngưỡng 80%')
            ->assertSee('Buổi này đã điểm danh 5/5 sinh viên');
    }

    public function test_attending_the_same_day_again_updates_instead_of_duplicating(): void
    {
        $teacher = $this->teacher();
        $offering = $this->managedBy($teacher, $this->semester());
        $firstEnrollment = $this->enroll($this->student('Nguyễn An'), $offering);
        $this->enroll($this->student('Trần Bình'), $offering);

        $this->actingAs($teacher)->post($this->storeRoute($offering), ['session_date' => '2026-10-27']);
        $this->actingAs($teacher)->post($this->storeRoute($offering), [
            'session_date' => '2026-10-27',
            'note' => 'Đi thực tế',
            'statuses' => [$firstEnrollment->id => Attendance::ABSENT],
        ]);

        $session = $offering->attendanceSessions()->sole();
        $this->assertSame(2, Attendance::count());
        $this->assertSame(1, AttendanceSession::count());
        $this->assertSame('Đi thực tế', $session->note);
        $this->assertSame($teacher->id, $session->recorded_by);
        $this->assertSame(Attendance::ABSENT, Attendance::where('enrollment_id', $firstEnrollment->id)->sole()->status);
    }

    public function test_a_session_without_data_shows_no_rate_yet(): void
    {
        $teacher = $this->teacher();
        $offering = $this->managedBy($teacher, $this->semester());
        $this->enroll($this->student('Nguyễn An'), $offering);

        $this->actingAs($teacher)
            ->get(route('teacher.course-offerings.attendance.session.create', [
                'offering' => $offering,
                'date' => '2026-10-27',
            ]))
            ->assertOk()
            ->assertSee('Nguyễn An')
            ->assertSee('Chưa có dữ liệu')
            ->assertSee('Vắng buổi này')
            ->assertDontSee('0%');
    }

    public function test_a_teacher_cannot_open_or_save_another_teachers_class(): void
    {
        $owner = $this->teacher('Giảng viên A');
        $intruder = $this->teacher('Giảng viên B');
        $offering = $this->managedBy($owner, $this->semester());
        $enrollment = $this->enroll($this->student('Nguyễn An'), $offering);

        $this->actingAs($intruder)
            ->get(route('teacher.course-offerings.attendance', ['offering' => $offering, 'week' => '2026-10-26']))
            ->assertForbidden();
        $this->actingAs($intruder)
            ->get(route('teacher.course-offerings.attendance.session.create', [
                'offering' => $offering,
                'date' => '2026-10-27',
            ]))
            ->assertForbidden();
        $this->actingAs($intruder)
            ->post($this->storeRoute($offering), ['session_date' => '2026-10-27'])
            ->assertForbidden();

        $this->assertSame(0, Attendance::where('enrollment_id', $enrollment->id)->count());
        $this->assertSame(0, AttendanceSession::count());
    }

    public function test_a_student_cannot_open_the_attendance_screen(): void
    {
        $offering = $this->managedBy($this->teacher(), $this->semester());

        $this->actingAs($this->student())
            ->get(route('teacher.course-offerings.attendance', $offering))
            ->assertForbidden();
    }

    public function test_only_a_known_status_is_accepted(): void
    {
        $teacher = $this->teacher();
        $offering = $this->managedBy($teacher, $this->semester());
        $enrollment = $this->enroll($this->student('Nguyễn An'), $offering);

        $this->actingAs($teacher)
            ->post($this->storeRoute($offering), [
                'session_date' => '2026-10-27',
                'statuses' => [$enrollment->id => 'LATE'],
            ])
            ->assertSessionHasErrors('statuses.'.$enrollment->id);

        $this->assertSame(0, Attendance::count());
    }

    public function test_dropped_students_are_not_attended(): void
    {
        $teacher = $this->teacher();
        $offering = $this->managedBy($teacher, $this->semester());
        $this->enroll($this->student('Nguyễn An'), $offering);
        $dropped = $this->enroll($this->student('Trần Bình'), $offering);
        $dropped->update(['status' => 'DROPPED', 'dropped_at' => now()]);

        $this->actingAs($teacher)
            ->post($this->storeRoute($offering), ['session_date' => '2026-10-27'])
            ->assertSessionHas('success', 'Đã điểm danh 1 sinh viên của buổi 27/10/2026.');

        $this->assertSame(1, Attendance::count());
        $this->assertSame(0, Attendance::where('enrollment_id', $dropped->id)->count());

        $this->actingAs($teacher)
            ->get(route('teacher.course-offerings.attendance.session.create', [
                'offering' => $offering,
                'date' => '2026-10-27',
            ]))
            ->assertOk()
            ->assertDontSee('Trần Bình');
    }

    public function test_the_grid_links_a_slot_to_its_session_form(): void
    {
        $teacher = $this->teacher();
        $offering = $this->managedBy($teacher, $this->semester());
        $slot = $this->slot($offering, 4, '07:00', '09:30', 'A101');

        $this->actingAs($teacher)
            ->get(route('teacher.course-offerings.attendance.session.create', [
                'offering' => $offering,
                'date' => '2026-10-28',
                'slot' => $slot->id,
            ]))
            ->assertOk()
            ->assertSee('Thứ 4 · 07:00–09:30')
            ->assertSee('Ngày vào học');
    }

    public function test_attending_from_a_slot_line_keeps_the_slot_on_the_session(): void
    {
        $teacher = $this->teacher();
        $offering = $this->managedBy($teacher, $this->semester());
        $slot = $this->slot($offering, 4, '07:00', '09:30', 'A101');
        $this->enroll($this->student('Nguyễn An'), $offering);

        $this->actingAs($teacher)->post($this->storeRoute($offering), [
            'session_date' => '2026-10-28',
            'lecture_slot_id' => $slot->id,
        ])->assertSessionHas('success');

        $this->assertSame($slot->id, $offering->attendanceSessions()->sole()->lecture_slot_id);

        $this->actingAs($teacher)
            ->get(route('teacher.course-offerings.attendance', ['offering' => $offering, 'week' => '2026-10-26']))
            ->assertOk()
            ->assertSee('Đã điểm danh 1/1');
    }

    public function test_a_slot_of_another_class_is_ignored_when_saving(): void
    {
        $teacher = $this->teacher();
        $offering = $this->managedBy($teacher, $semester = $this->semester());
        $otherSlot = $this->slot($this->managedBy($teacher, $semester), 4, '07:00', '09:30', 'A101');
        $this->enroll($this->student('Nguyễn An'), $offering);

        $this->actingAs($teacher)->post($this->storeRoute($offering), [
            'session_date' => '2026-10-28',
            'lecture_slot_id' => $otherSlot->id,
        ])->assertSessionHas('success');

        $this->assertNull($offering->attendanceSessions()->sole()->lecture_slot_id);
    }

    /**
     * Dựng lớp có giảng viên phụ trách. Học kỳ truyền vào để một test dựng nhiều lớp
     * không đâm va ràng buộc (năm học, số kỳ) của bảng học kỳ.
     */
    private function managedBy(User $teacher, Semester $semester): CourseOffering
    {
        return $this->offering($this->subject('DD'.random_int(100000, 999999)), $semester, 'TN01', 0, [
            'teacher_id' => $teacher->id,
        ]);
    }

    /**
     * Đăng ký cả lớp cho test, trả về đăng ký kèm sinh viên để dùng làm khóa cho `statuses`.
     *
     * @param  array<int, string>  $names
     * @return Collection<int, Enrollment>
     */
    private function theClass(CourseOffering $offering, array $names): Collection
    {
        return collect($names)->map(fn (string $name) => $this->enroll($this->student($name), $offering))->values();
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     * @param  Collection<int, Enrollment>  $absent
     * @return array<int, string>
     */
    private function absentOnly(Collection $enrollments, Collection $absent): array
    {
        return $enrollments->mapWithKeys(fn (Enrollment $enrollment) => [
            $enrollment->id => $absent->contains($enrollment) ? Attendance::ABSENT : Attendance::PRESENT,
        ])->all();
    }

    private function rateOf(Enrollment $enrollment): ?float
    {
        return AttendanceRate::forEnrollment($enrollment)['rate'];
    }

    private function storeRoute(CourseOffering $offering): string
    {
        return route('teacher.course-offerings.attendance.session.store', $offering);
    }

    /**
     * @param  array<string, string>  $range
     */
    private function slot(CourseOffering $offering, int $weekDay, string $start, string $end, ?string $room = null, array $range = []): LectureSlot
    {
        return LectureSlot::create([
            'course_offering_id' => $offering->id,
            'week_day' => $weekDay,
            'start_time' => $start,
            'end_time' => $end,
            'room' => $room,
        ] + $range);
    }
}
