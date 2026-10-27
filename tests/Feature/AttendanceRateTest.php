<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Support\AttendanceRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\OfferingFixtures;
use Tests\TestCase;

/**
 * Unit cho AttendanceRate — cần DB vì tỷ lệ đọc từ các lượt điểm danh đã ghi.
 */
class AttendanceRateTest extends TestCase
{
    use OfferingFixtures, RefreshDatabase;

    public function test_one_absent_out_of_four_sessions_is_75_percent(): void
    {
        $offering = $this->singleOffering('TKB01');
        $enrollment = $this->enroll($this->student(), $offering);

        foreach (['2026-10-05', '2026-10-12', '2026-10-19'] as $date) {
            $this->attend($enrollment, $date);
        }
        $this->attend($enrollment, '2026-10-26', Attendance::ABSENT);

        $this->assertSame(
            ['sessions' => 4, 'present' => 3, 'rate' => 75.0, 'pending' => 0],
            AttendanceRate::forEnrollment($enrollment),
        );
    }

    public function test_a_student_with_no_session_recorded_has_no_rate(): void
    {
        $enrollment = $this->enroll($this->student(), $this->singleOffering('TKB01'));

        $rate = AttendanceRate::forEnrollment($enrollment);

        $this->assertSame(0, $rate['sessions']);
        $this->assertNull($rate['rate'], 'Chưa có dữ liệu khác 0% — UI phải hiện "Chưa có dữ liệu".');
    }

    public function test_a_session_recorded_for_the_class_but_not_for_this_student_is_pending(): void
    {
        $offering = $this->singleOffering('TKB01');
        $early = $this->enroll($this->student('Vào từ đầu'), $offering);
        $late = $this->enroll($this->student('Vào trễ'), $offering);

        $this->attend($early, '2026-10-05');
        $this->attend($late, '2026-10-05');
        $this->attend($early, '2026-10-12');

        $this->assertSame(0, AttendanceRate::forEnrollment($early)['pending']);
        $this->assertSame(1, AttendanceRate::forEnrollment($late)['pending']);
        $this->assertSame(100.0, AttendanceRate::forEnrollment($late)['rate'], 'Tỷ lệ chỉ tính buổi đã chấm.');
    }

    public function test_a_session_nobody_was_recorded_for_is_left_out(): void
    {
        $offering = $this->singleOffering('TKB01');
        $enrollment = $this->enroll($this->student(), $offering);
        $this->attend($enrollment, '2026-10-05');

        AttendanceSession::create([
            'course_offering_id' => $offering->id,
            'session_date' => '2026-10-12',
        ]);

        $rate = AttendanceRate::forEnrollment($enrollment);

        $this->assertSame(1, $rate['sessions']);
        $this->assertSame(0, $rate['pending'], 'Giảng viên quên điểm danh thì không tính là buổi chưa chấm.');
    }

    public function test_the_student_rate_is_the_sum_over_every_enrollment(): void
    {
        $first = $this->offering($this->subject('KT01'), $this->semester());
        $second = $this->offering($this->subject('KT02'), $first->semester);
        $student = $this->student();

        $this->attend($this->enroll($student, $first), '2026-10-05');
        $this->attend($this->enroll($student, $second), '2026-10-05', Attendance::ABSENT);

        $this->assertSame(50.0, AttendanceRate::forStudent($student));
        $this->assertNull(AttendanceRate::forStudent($this->student('Chưa đi học')));
    }

    public function test_the_warning_threshold_is_eighty_percent_from_config(): void
    {
        $this->assertSame(80.0, AttendanceRate::warningThreshold());
        $this->assertTrue(AttendanceRate::isBelowThreshold(79.9));
        $this->assertFalse(AttendanceRate::isBelowThreshold(80.0));
        $this->assertFalse(AttendanceRate::isBelowThreshold(null), 'Chưa có dữ liệu thì chưa cảnh báo.');
    }
}
