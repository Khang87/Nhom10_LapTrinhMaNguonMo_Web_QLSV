<?php

namespace Tests\Unit;

use App\Support\EnrollmentPolicy;
use App\Support\GradingScale;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Mọi con số của nhóm yêu cầu 6 nằm trong GradingScale; test này chốt từng biên của bảng
 * quy đổi để không ai vô tình đổi thang điểm khi viết màn hình.
 */
class GradingScaleTest extends TestCase
{
    public function test_the_three_components_are_weighted_ten_twenty_seventy(): void
    {
        $this->assertSame(
            ['attendance' => 0.10, 'midterm' => 0.20, 'final' => 0.70],
            GradingScale::WEIGHTS,
            'Trọng số đã phê duyệt là 10/20/70 và không cấu hình được.'
        );
        $this->assertSame(1.0, array_sum(GradingScale::WEIGHTS));
        $this->assertSame(6.4, GradingScale::subjectScore(8, 7, 6));
    }

    public function test_a_missing_component_counts_as_zero_but_an_empty_row_has_no_score(): void
    {
        $this->assertNull(GradingScale::subjectScore(null, null, null), 'Chưa nhập gì thì chưa có điểm tổng kết.');
        $this->assertSame(4.9, GradingScale::subjectScore(null, null, 7));
        $this->assertSame(0.8, GradingScale::subjectScore(8, null, null));
    }

    public function test_the_subject_score_is_rounded_to_two_decimals(): void
    {
        $this->assertSame(7.0, GradingScale::subjectScore(null, null, 9.995));
        $this->assertSame(10.0, GradingScale::subjectScore(10, 10, 9.995));
        $this->assertSame(8.49, GradingScale::subjectScore(5, 5, 9.99));
    }

    public function test_the_letter_and_four_point_bands_follow_the_approved_table(): void
    {
        $band = fn (float $score) => GradingScale::toLetterAndFour($score);

        $this->assertSame(['letter' => 'A', 'four' => 4.0], $band(8.5));
        $this->assertSame(['letter' => 'A', 'four' => 4.0], $band(10.0));
        $this->assertSame(['letter' => 'B', 'four' => 3.5], $band(8.4999));
        $this->assertSame(['letter' => 'B', 'four' => 3.5], $band(7.0));
        $this->assertSame(['letter' => 'C', 'four' => 3.0], $band(6.9));
        $this->assertSame(['letter' => 'C', 'four' => 3.0], $band(6.0));
        $this->assertSame(['letter' => 'D', 'four' => 2.5], $band(5.9));
        $this->assertSame(['letter' => 'D', 'four' => 2.5], $band(5.0));
        $this->assertSame(['letter' => 'F', 'four' => 0.0], $band(4.99));
        $this->assertSame(['letter' => 'F', 'four' => 0.0], $band(0.0));
    }

    public function test_a_final_exam_below_the_floor_drops_the_subject_to_f(): void
    {
        $total = GradingScale::subjectScore(10, 10, 3.9);

        $this->assertSame(5.73, $total, 'Tổng kết hệ 10 vẫn là điểm D nếu chỉ nhìn mỗi tổng.');
        $this->assertSame(['letter' => 'F', 'four' => 0.0], GradingScale::toLetterAndFour($total, 3.9));
        $this->assertSame('D', GradingScale::toLetterAndFour($total, 4.5)['letter'], 'Ngưỡng liệt đã duyệt là 4.0, không phải 5.0.');
        $this->assertSame('A', GradingScale::toLetterAndFour(9.0, 8.0)['letter'], 'Chỉ điểm liệt mới hạ được xếp loại.');
    }

    public function test_the_gpa_is_credit_weighted_on_the_four_point_scale(): void
    {
        $gpa = GradingScale::gpa([
            ['four' => 4.0, 'credits' => 3],
            ['four' => 3.0, 'credits' => 2],
        ]);

        $this->assertSame(3.6, $gpa);
        $this->assertNull(GradingScale::gpa([]));
        $this->assertNull(GradingScale::gpa([['four' => null, 'credits' => 3]]));
    }

    public function test_the_ten_point_average_uses_the_same_credit_weights(): void
    {
        $average = GradingScale::averageTen([
            ['score' => 6.4, 'credits' => 3],
            ['score' => 8.0, 'credits' => 2],
        ]);

        $this->assertSame(7.04, $average);
        $this->assertNull(GradingScale::averageTen([]));
    }

    public function test_the_classification_reads_the_average_but_an_f_caps_it(): void
    {
        $this->assertSame('Xuất sắc', GradingScale::classification(9.2));
        $this->assertSame('Giỏi', GradingScale::classification(8.9));
        $this->assertSame('Giỏi', GradingScale::classification(8.0));
        $this->assertSame('Khá', GradingScale::classification(7.9));
        $this->assertSame('Khá', GradingScale::classification(6.5));
        $this->assertSame('Trung bình', GradingScale::classification(6.4));
        $this->assertSame('Yếu', GradingScale::classification(4.9));
        $this->assertSame('Trung bình', GradingScale::classification(9.2, true), 'Có môn F thì không được xếp từ Khá trở lên.');
    }

    public function test_progression_flags_academic_risk_on_gpa_on_two_f_or_on_attendance(): void
    {
        $byGpa = GradingScale::progression(['gpa' => 1.8]);
        $byFailures = GradingScale::progression(['gpa' => 3.0, 'failing_subjects' => 2]);
        $byAttendance = GradingScale::progression(['gpa' => 3.0, 'attendance_rate' => 75.0]);

        $this->assertSame('AT_RISK', $byGpa['status']);
        $this->assertStringContainsString('1.80', $byGpa['reasons'][0]);
        $this->assertSame('AT_RISK', $byFailures['status']);
        $this->assertStringContainsString('2 môn F', $byFailures['reasons'][0]);
        $this->assertSame('AT_RISK', $byAttendance['status']);
        $this->assertStringContainsString('75%', $byAttendance['reasons'][0]);
    }

    public function test_progression_needs_a_single_f_to_block_promotion(): void
    {
        $verdict = GradingScale::progression(['gpa' => 2.6, 'failing_subjects' => 1]);

        $this->assertSame('STUDYING', $verdict['status']);
        $this->assertCount(1, $verdict['reasons']);
        $this->assertStringContainsString('chưa đủ điều kiện lên lớp', $verdict['reasons'][0]);
    }

    public function test_progression_only_suggests_graduation_with_enough_credits_and_no_f(): void
    {
        $ready = GradingScale::progression([
            'gpa' => 3.2,
            'failing_subjects' => 0,
            'credits_accumulated' => 130,
            'credits_required' => 130,
        ]);
        $short = GradingScale::progression([
            'gpa' => 3.2,
            'failing_subjects' => 0,
            'credits_accumulated' => 129,
            'credits_required' => 130,
        ]);

        $this->assertSame('GRADUATED', $ready['status']);
        $this->assertStringContainsString('130/130', $ready['reasons'][0]);
        $this->assertSame('STUDYING', $short['status']);
        $this->assertStringContainsString('129/130', $short['reasons'][0]);
    }

    public function test_risk_outranks_promotion_when_several_rules_fire_at_once(): void
    {
        $verdict = GradingScale::progression([
            'gpa' => 1.5,
            'failing_subjects' => 3,
            'attendance_rate' => 40.0,
            'credits_accumulated' => 200,
            'credits_required' => 130,
        ]);

        $this->assertSame('AT_RISK', $verdict['status']);
        $this->assertCount(3, $verdict['reasons'], 'Mọi nguyên nhân phải được liệt kê, không chỉ nguyên nhân đầu tiên.');
    }

    public function test_the_scale_never_touches_the_database(): void
    {
        DB::enableQueryLog();

        GradingScale::subjectScore(8, 7, 6);
        GradingScale::toLetterAndFour(6.4, 6.0);
        GradingScale::gpa([['four' => 4.0, 'credits' => 3]]);
        GradingScale::averageTen([['score' => 6.4, 'credits' => 3]]);
        GradingScale::classification(7.0);
        GradingScale::progression(['gpa' => 3.0]);

        $this->assertSame([], DB::getQueryLog(), 'GradingScale là hàm thuần để mọi màn hình dùng lại được.');
    }

    public function test_the_pass_threshold_is_shared_with_the_enrollment_policy(): void
    {
        $this->assertSame(5.0, GradingScale::PASS_SCORE);
        $this->assertSame(4.0, GradingScale::FAIL_FLOOR);
        $this->assertSame(2.0, GradingScale::AT_RISK_GPA);
        $this->assertSame(GradingScale::PASS_SCORE, EnrollmentPolicy::PASS_SCORE, 'Một ngưỡng đạt duy nhất cho toàn hệ thống.');
    }
}
