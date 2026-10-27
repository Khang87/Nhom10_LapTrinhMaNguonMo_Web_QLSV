<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\User;

/**
 * Tỷ lệ chuyên cần tính từ các buổi đã điểm danh (SPEC-attendance-schedule).
 *
 * Mẫu số là số buổi sinh viên đó đã được chấm, không phải số buổi dự kiến của học kỳ:
 * giảng viên chưa điểm danh thì chưa tính, để không phạt sinh viên vì thiếu dữ liệu.
 */
class AttendanceRate
{
    /**
     * @return array{sessions: int, present: int, rate: float|null, pending: int}
     */
    public static function forEnrollment(Enrollment $enrollment): array
    {
        $sessions = $enrollment->attendances()->count();
        $present = $enrollment->attendances()->where('status', Attendance::PRESENT)->count();
        $held = $enrollment->offering?->attendanceSessions()->whereHas('records')->count() ?? 0;

        return [
            'sessions' => $sessions,
            'present' => $present,
            'rate' => $sessions === 0 ? null : round($present / $sessions * 100, 1),
            'pending' => max(0, $held - $sessions),
        ];
    }

    /**
     * Tỷ lệ gộp của mọi đăng ký đã có dữ liệu điểm danh — dùng cho trang tổng kết học tập.
     */
    public static function forStudent(User $student): ?float
    {
        $sessions = $student->attendances()->count();

        if ($sessions === 0) {
            return null;
        }

        $present = $student->attendances()->where('attendances.status', Attendance::PRESENT)->count();

        return round($present / $sessions * 100, 1);
    }

    public static function warningThreshold(): float
    {
        return (float) config('academic.min_attendance_rate');
    }

    public static function isBelowThreshold(?float $rate): bool
    {
        return $rate !== null && $rate < self::warningThreshold();
    }
}
