<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\AttendanceSession;
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
        return self::forEnrollments([$enrollment])[$enrollment->id];
    }

    /**
     * Tỷ lệ chuyên cần của cả danh sách lớp. Gom thành hai truy vấn cho toàn lớp thay vì
     * ba truy vấn cho từng sinh viên — một lớp 60 người thì khác biệt rất rõ.
     *
     * @param  iterable<int, Enrollment>  $enrollments
     * @return array<int, array{sessions: int, present: int, rate: float|null, pending: int}> keyed by enrollment id
     */
    public static function forEnrollments(iterable $enrollments): array
    {
        $enrollments = collect($enrollments);
        $ids = $enrollments->pluck('id');

        if ($ids->isEmpty()) {
            return [];
        }

        $counts = Attendance::whereIn('enrollment_id', $ids)
            ->selectRaw(
                'enrollment_id, COUNT(*) as sessions, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as present',
                [Attendance::PRESENT],
            )
            ->groupBy('enrollment_id')
            ->get()
            ->keyBy('enrollment_id');

        $held = AttendanceSession::whereIn('course_offering_id', $enrollments->pluck('course_offering_id')->unique())
            ->whereHas('records')
            ->selectRaw('course_offering_id, COUNT(*) as held')
            ->groupBy('course_offering_id')
            ->pluck('held', 'course_offering_id');

        return $enrollments->mapWithKeys(fn (Enrollment $enrollment) => [$enrollment->id => self::shape(
            (int) $counts->get($enrollment->id)?->sessions,
            (int) $counts->get($enrollment->id)?->present,
            (int) $held->get($enrollment->course_offering_id, 0),
        )])->all();
    }

    /**
     * @return array{sessions: int, present: int, rate: float|null, pending: int}
     */
    private static function shape(int $sessions, int $present, int $held): array
    {
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
