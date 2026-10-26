<?php

namespace App\Support;

use App\Models\CourseOffering;
use App\Models\Subject;
use App\Models\User;

/**
 * Toàn bộ quy tắc đăng ký học phần (SPEC-enrollment) tập trung ở đây, theo đúng thứ tự
 * sáu nhánh. Hàm thuần: chỉ đọc dữ liệu đã load, không truy vấn — phần truy vấn thuộc
 * EnrollmentContext để test dựng fixture mảng được.
 */
class EnrollmentPolicy
{
    public const PASS_SCORE = 5.0;

    public const GATE_CLOSED = 'Cổng đăng ký đã đóng';

    /**
     * $student là chủ thể của verdict theo SPEC-enrollment; mọi con số trong $context đã được
     * EnrollmentContext gom theo đúng sinh viên này nên policy không cần truy vấn thêm.
     * $gateRequired = false dành cho quản trị viên đăng ký hộ: bỏ nhánh 1, giữ đủ năm nhánh còn lại.
     *
     * @param  array{credits_in_semester: int, passed_subject_ids: list<int>, active_subject_ids: list<int>, blocking_prerequisites: list<array{code: string}>, seats_left: int, already_active: bool}  $context
     * @return array{ok: bool, reasons: list<array{code: string, message: string}>}
     */
    public static function check(User $student, CourseOffering $offering, array $context, bool $gateRequired = true): array
    {
        $reasons = [];

        if ($gateRequired && ! $offering->isRegistrationOpen()) {
            $reasons[] = self::reason('GATE_CLOSED', self::GATE_CLOSED);
        }

        $credits = $context['credits_in_semester'] + (int) $offering->subject->credits;
        if ($credits > (int) config('academic.max_credits_per_term')) {
            $reasons[] = self::reason('MAX_CREDITS', 'Vượt quá số tín chỉ tối đa trong học kỳ');
        }

        $missing = array_column($context['blocking_prerequisites'], 'code');
        if ($missing !== []) {
            $reasons[] = self::reason('PREREQUISITE_MISSING', 'Chưa học môn tiên quyết: '.implode(', ', $missing));
        }

        $concurrent = self::unmetConcurrentCodes($offering, $context);
        if ($concurrent !== []) {
            $reasons[] = self::reason('CONCURRENT_MISSING', 'Phải đăng ký song hành với môn: '.implode(', ', $concurrent));
        }

        if ($context['seats_left'] <= 0) {
            $reasons[] = self::reason('CLASS_FULL', 'Lớp đã đủ sĩ số');
        }

        if ($context['already_active']) {
            $reasons[] = self::reason('ALREADY_REGISTERED', 'Bạn đã đăng ký lớp này');
        }

        return ['ok' => $reasons === [], 'reasons' => $reasons];
    }

    /**
     * One-line verdict for flash messages and HTTP aborts.
     *
     * @param  array{ok: bool, reasons: list<array{code: string, message: string}>}  $verdict
     */
    public static function reasonText(array $verdict): string
    {
        return implode(' · ', array_column($verdict['reasons'], 'message'));
    }

    /**
     * Hủy đăng ký chỉ hợp lệ khi cổng còn mở và lớp chưa có dữ liệu điểm danh/điểm.
     * Điểm và điểm danh không bao giờ bị xoá kèm enrollment, nên từ chối là cách duy nhất
     * giữ bảng tập trung đúng.
     *
     * @param  array{gate_open: bool, has_record: bool}  $context
     * @return array{ok: bool, reasons: list<array{code: string, message: string}>}
     */
    public static function checkDrop(array $context): array
    {
        $reasons = [];

        if (! $context['gate_open']) {
            $reasons[] = self::reason('GATE_CLOSED', self::GATE_CLOSED);
        }

        if ($context['has_record']) {
            $reasons[] = self::reason('DATA_EXISTS', 'Đã có dữ liệu điểm danh/điểm, liên hệ giảng viên');
        }

        return ['ok' => $reasons === [], 'reasons' => $reasons];
    }

    /**
     * Môn song hành chỉ hợp lệ khi sinh viên đã đạt điểm, hoặc đang học chính nó cùng kỳ.
     *
     * @param  array{passed_subject_ids: list<int>, active_subject_ids: list<int>}  $context
     * @return list<string>
     */
    private static function unmetConcurrentCodes(CourseOffering $offering, array $context): array
    {
        return $offering->subject->concurrent_subjects
            ->reject(fn (Subject $concurrent) => in_array($concurrent->id, $context['passed_subject_ids'], true)
                || in_array($concurrent->id, $context['active_subject_ids'], true))
            ->pluck('code')
            ->values()
            ->all();
    }

    /**
     * @return array{code: string, message: string}
     */
    private static function reason(string $code, string $message): array
    {
        return ['code' => $code, 'message' => $message];
    }
}
