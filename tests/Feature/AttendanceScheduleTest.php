<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\LectureSlot;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\OfferingFixtures;
use Tests\TestCase;

/**
 * Wave 7 — lịch lên lớp và điểm danh (SPEC-attendance-schedule).
 */
class AttendanceScheduleTest extends TestCase
{
    use OfferingFixtures, RefreshDatabase;

    public function test_a_class_can_hold_several_weekly_slots(): void
    {
        $offering = $this->singleOffering('TKB01');

        $monday = LectureSlot::create([
            'course_offering_id' => $offering->id,
            'week_day' => 3,
            'start_time' => '07:00',
            'end_time' => '09:30',
            'room' => 'A101',
        ]);
        $friday = LectureSlot::create([
            'course_offering_id' => $offering->id,
            'week_day' => 5,
            'start_time' => '07:00',
            'end_time' => '09:30',
            'room' => 'A102',
        ]);

        $this->assertSame(2, $offering->lectureSlots()->count());
        $this->assertSame('Thứ 3 · 07:00–09:30', $monday->label());
        $this->assertSame('Thứ 5 · 07:00–09:30', $friday->label());
        $this->assertSame('A101', $offering->lectureSlots()->find($monday->id)->room);
    }

    public function test_the_same_day_and_start_time_is_taken_once_per_class(): void
    {
        $offering = $this->singleOffering('TKB01');

        LectureSlot::create([
            'course_offering_id' => $offering->id,
            'week_day' => 3,
            'start_time' => '07:00',
            'end_time' => '09:30',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        LectureSlot::create([
            'course_offering_id' => $offering->id,
            'week_day' => 3,
            'start_time' => '07:00',
            'end_time' => '10:30',
        ]);
    }

    public function test_two_classes_share_the_same_slot_time(): void
    {
        $first = $this->offering($this->subject('KT01'), $this->semester());
        $second = $this->offering($this->subject('KT02'), $first->semester);

        foreach ([$first, $second] as $offering) {
            LectureSlot::create([
                'course_offering_id' => $offering->id,
                'week_day' => 4,
                'start_time' => '13:00',
                'end_time' => '15:00',
            ]);
        }

        $this->assertSame(2, LectureSlot::where('week_day', 4)->where('start_time', '13:00')->count());
    }

    public function test_a_slot_can_be_limited_to_a_range_of_weeks(): void
    {
        $offering = $this->singleOffering('TKB01');

        $slot = LectureSlot::create([
            'course_offering_id' => $offering->id,
            'week_day' => 6,
            'start_time' => '17:00',
            'end_time' => '19:00',
            'starts_on' => '2026-01-05',
            'ends_on' => '2026-03-30',
        ]);

        $this->assertSame('2026-01-05', $slot->starts_on->toDateString());
        $this->assertSame('2026-03-30', $slot->ends_on->toDateString());

        $openEnded = LectureSlot::create([
            'course_offering_id' => $offering->id,
            'week_day' => 2,
            'start_time' => '07:00',
            'end_time' => '09:00',
        ]);

        $this->assertNull($openEnded->starts_on);
        $this->assertNull($openEnded->ends_on);
    }

    public function test_attending_the_same_session_twice_updates_instead_of_duplicating(): void
    {
        $offering = $this->singleOffering('TKB01');
        $enrollment = $this->enroll($this->student('Nguyễn Văn A'), $offering);

        $this->attend($enrollment, '2026-10-20');
        $this->attend($enrollment, '2026-10-20', Attendance::ABSENT);

        $this->assertSame(1, Attendance::count());
        $this->assertSame(1, AttendanceSession::count());
        $this->assertSame(Attendance::ABSENT, Attendance::firstOrFail()->status);
    }

    public function test_one_session_line_per_student_of_the_class(): void
    {
        $offering = $this->singleOffering('TKB01');

        foreach (range(1, 15) as $number) {
            $this->attend($this->enroll($this->student('Sinh viên '.$number), $offering), '2026-10-20');
        }

        $this->assertSame(15, Attendance::count());
        $this->assertSame(15, $offering->attendanceSessions()->firstOrFail()->records()->count());
    }
}
