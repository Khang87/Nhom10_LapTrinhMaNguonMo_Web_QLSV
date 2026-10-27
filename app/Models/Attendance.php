<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Một dòng điểm danh: (buổi học, đăng ký) -> có mặt hay vắng. Trạng thái chỉ có hai giá trị
 * theo quyết duyệt 2026-10-25 của SPEC-attendance-schedule — không "đi muộn", không "có phép".
 */
class Attendance extends Model
{
    public const PRESENT = 'PRESENT';

    public const ABSENT = 'ABSENT';

    public const STATUSES = [self::PRESENT, self::ABSENT];

    public const STATUS_LABELS = [
        self::PRESENT => 'Có mặt',
        self::ABSENT => 'Vắng',
    ];

    protected $fillable = ['attendance_session_id', 'course_offering_id', 'enrollment_id', 'status'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function isPresent(): bool
    {
        return $this->status === self::PRESENT;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
