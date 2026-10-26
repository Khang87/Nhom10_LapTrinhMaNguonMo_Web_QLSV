<?php

namespace Tests\Unit;

use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Support\EnrollmentPolicy;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * EnrollmentPolicy là hàm thuần: mọi dữ liệu đã nằm sẵn trong $context và trong quan hệ
 * đã load, nên test dựng fixture mảng mà không chạm database.
 */
class EnrollmentPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-02-01 08:00:00');
    }

    public function test_a_clean_request_passes_all_six_branches(): void
    {
        $verdict = EnrollmentPolicy::check($this->student(), $this->offering(), $this->context());

        $this->assertTrue($verdict['ok']);
        $this->assertSame([], $verdict['reasons']);
    }

    public function test_the_gate_blocks_before_the_registration_window_opens(): void
    {
        $this->travelTo('2026-01-10 07:00:00');

        $this->assertReason('GATE_CLOSED', 'Cổng đăng ký đã đóng', $this->check());
    }

    public function test_the_gate_blocks_after_the_registration_window_closes(): void
    {
        $this->travelTo('2026-02-16 00:01:00');

        $this->assertReason('GATE_CLOSED', 'Cổng đăng ký đã đóng', $this->check());
    }

    public function test_a_closed_offering_blocks_even_inside_the_window(): void
    {
        $verdict = $this->check($this->offering(isOpen: false));

        $this->assertSame(['GATE_CLOSED'], array_column($verdict['reasons'], 'code'));
        $this->assertSame('Cổng đăng ký đã đóng', $verdict['reasons'][0]['message']);
    }

    public function test_the_credit_cap_blocks_when_the_term_would_overflow(): void
    {
        $max = config('academic.max_credits_per_term');

        $verdict = $this->check(context: $this->context(['credits_in_semester' => $max]));

        $this->assertSame(['MAX_CREDITS'], array_column($verdict['reasons'], 'code'));
        $this->assertSame('Vượt quá số tín chỉ tối đa trong học kỳ', $verdict['reasons'][0]['message']);
    }

    public function test_the_credit_cap_still_admits_the_last_subject_that_fits(): void
    {
        $max = config('academic.max_credits_per_term');

        $verdict = $this->check(context: $this->context([
            'credits_in_semester' => $max - $this->subject()->credits,
        ]));

        $this->assertTrue($verdict['ok']);
    }

    public function test_a_missing_prerequisite_is_named_by_its_code(): void
    {
        $verdict = $this->check(context: $this->context([
            'blocking_prerequisites' => [
                ['code' => 'WEB101', 'name' => 'Lập trình Web', 'required_score' => 5.0, 'actual_score' => null],
            ],
        ]));

        $this->assertSame('Chưa học môn tiên quyết: WEB101', $this->message($verdict, 'PREREQUISITE_MISSING'));
    }

    public function test_every_missing_prerequisite_is_listed_in_one_reason(): void
    {
        $verdict = $this->check(context: $this->context([
            'blocking_prerequisites' => [
                ['code' => 'WEB101', 'name' => 'Lập trình Web', 'required_score' => 5.0, 'actual_score' => null],
                ['code' => 'DB101', 'name' => 'Cơ sở dữ liệu', 'required_score' => 5.0, 'actual_score' => 4.2],
            ],
        ]));

        $this->assertSame('Chưa học môn tiên quyết: WEB101, DB101', $this->message($verdict, 'PREREQUISITE_MISSING'));
    }

    public function test_a_concurrent_subject_needs_a_passing_grade_or_a_same_term_enrollment(): void
    {
        $db101 = $this->subject(2, 'DB101');

        $verdict = $this->check($this->offering(concurrent: $db101));

        $this->assertSame('Phải đăng ký song hành với môn: DB101', $this->message($verdict, 'CONCURRENT_MISSING'));

        $this->assertTrue($this->check($this->offering(concurrent: $db101), ['passed_subject_ids' => [$db101->id]])['ok']);
        $this->assertTrue($this->check($this->offering(concurrent: $db101), ['active_subject_ids' => [$db101->id]])['ok']);
    }

    public function test_a_full_class_blocks_but_zero_capacity_is_unlimited(): void
    {
        $full = $this->check(context: $this->context(['seats_left' => 0]));

        $this->assertSame('Lớp đã đủ sĩ số', $this->message($full, 'CLASS_FULL'));

        $this->assertTrue($this->check(context: $this->context(['seats_left' => \PHP_INT_MAX]))['ok']);
    }

    public function test_an_active_enrollment_in_the_same_class_blocks(): void
    {
        $verdict = $this->check(context: $this->context(['already_active' => true]));

        $this->assertSame('Bạn đã đăng ký lớp này', $this->message($verdict, 'ALREADY_REGISTERED'));
    }

    public function test_a_dropped_enrollment_can_be_registered_again_inside_the_gate(): void
    {
        $verdict = $this->check(context: $this->context(['already_active' => false]));

        $this->assertTrue($verdict['ok'], 'Từng hủy lớp không được chặn sinh viên đăng ký lại khi cổng còn mở.');
    }

    public function test_several_broken_rules_are_reported_together(): void
    {
        $verdict = $this->check($this->offering(isOpen: false), $this->context([
            'seats_left' => 0,
            'already_active' => true,
        ]));

        $this->assertFalse($verdict['ok']);
        $this->assertSame(
            ['GATE_CLOSED', 'CLASS_FULL', 'ALREADY_REGISTERED'],
            array_column($verdict['reasons'], 'code'),
        );
    }

    public function test_the_admin_path_skips_the_gate_but_no_other_rule(): void
    {
        $this->travelTo('2026-03-01 08:00:00');

        $this->assertTrue(EnrollmentPolicy::check($this->student(), $this->offering(), $this->context(), false)['ok']);

        $verdict = EnrollmentPolicy::check($this->student(), $this->offering(), $this->context(['seats_left' => 0]), false);

        $this->assertSame(['CLASS_FULL'], array_column($verdict['reasons'], 'code'));
    }

    public function test_the_policy_never_touches_the_database(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        EnrollmentPolicy::check($this->student(), $this->offering(), $this->context());

        DB::disableQueryLog();

        $this->assertSame([], DB::getQueryLog(), 'Policy phải là hàm thuần trên dữ liệu đã load.');
    }

    /**
     * @param  array<string, mixed>  $context  overrides của fixture mặc định, hoặc context đã dựng đầy đủ
     */
    private function check(?CourseOffering $offering = null, array $context = []): array
    {
        return EnrollmentPolicy::check(
            $this->student(),
            $offering ?? $this->offering(),
            $this->context($context),
        );
    }

    /**
     * @param  array{ok: bool, reasons: list<array{code: string, message: string}>}  $verdict
     */
    private function message(array $verdict, string $code): string
    {
        $reasons = array_column($verdict['reasons'], 'message', 'code');

        $this->assertArrayHasKey($code, $reasons, "Thiếu lý do {$code} trong kết quả: "
            .implode(', ', array_column($verdict['reasons'], 'code')));

        return $reasons[$code];
    }

    /**
     * @param  array{code: string, message: string}  $expected
     */
    private function assertReason(string $code, string $message, array $verdict): void
    {
        $this->assertFalse($verdict['ok']);
        $this->assertSame($message, $this->message($verdict, $code));
    }

    private function student(): User
    {
        return (new User)->forceFill(['id' => 1, 'name' => 'Sinh viên A', 'role' => 'student']);
    }

    private function subject(int $id = 1, string $code = 'WEB101', int $credits = 3): Subject
    {
        return (new Subject)->forceFill(['id' => $id, 'code' => $code, 'name' => 'Môn '.$code, 'credits' => $credits]);
    }

    private function semester(): Semester
    {
        return (new Semester)->forceFill([
            'id' => 1,
            'academic_year' => '2025-2026',
            'term' => 1,
            'slug' => 'HK1-2025-2026',
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-05-31',
            'registration_starts_at' => '2026-01-15 00:00:00',
            'registration_ends_at' => '2026-02-15 23:59:59',
        ]);
    }

    private function offering(bool $isOpen = true, ?Subject $concurrent = null, ?Subject $subject = null): CourseOffering
    {
        $subject ??= $this->subject();
        $subject->setRelation('prerequisites', collect());
        $subject->setRelation('concurrents', collect($concurrent === null ? [] : [$concurrent]));
        $subject->setRelation('concurrentOf', collect());

        $offering = (new CourseOffering)->forceFill([
            'id' => 1,
            'subject_id' => $subject->id,
            'semester_id' => 1,
            'group_code' => 'TN01',
            'max_students' => 10,
            'is_open' => $isOpen,
        ]);
        $offering->setRelation('subject', $subject);
        $offering->setRelation('semester', $this->semester());

        return $offering;
    }

    /**
     * @return array<string, mixed>
     */
    private function context(array $overrides = []): array
    {
        return $overrides + [
            'credits_in_semester' => 0,
            'passed_subject_ids' => [],
            'active_subject_ids' => [],
            'blocking_prerequisites' => [],
            'seats_left' => 10,
            'already_active' => false,
        ];
    }
}
