<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Một buổi học của lớp học phần — đơn vị mà giảng viên điểm danh.
 */
class AttendanceSession extends Model
{
    protected $fillable = ['course_offering_id', 'lecture_slot_id', 'session_date', 'note', 'recorded_by'];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(LectureSlot::class, 'lecture_slot_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(Attendance::class, 'attendance_session_id');
    }

    public function label(): string
    {
        return 'Buổi '.$this->session_date->format('d/m/Y');
    }
}
