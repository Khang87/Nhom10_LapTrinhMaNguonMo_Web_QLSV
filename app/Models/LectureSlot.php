<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LectureSlot extends Model
{
    /**
     * Thứ trong tuần: 2 = Thứ 2 ... 7 = Thứ 7. Mã 1 để trống vì hệ thống không xếp lịch
     * Chủ nhật (SPEC ghi "ISO, bỏ 1"; xem ghi chú Deviation trong tasks/todo.md).
     */
    public const WEEK_DAYS = [
        2 => 'Thứ 2',
        3 => 'Thứ 3',
        4 => 'Thứ 4',
        5 => 'Thứ 5',
        6 => 'Thứ 6',
        7 => 'Thứ 7',
    ];

    protected $fillable = [
        'course_offering_id',
        'week_day',
        'start_time',
        'end_time',
        'room',
        'starts_on',
        'ends_on',
    ];

    protected function casts(): array
    {
        return [
            'week_day' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    /** Nhãn hiển thị: "Thứ 3 · 07:00–09:00". */
    public function label(): string
    {
        return $this->weekDayLabel().' · '.$this->timeRange();
    }

    public function weekDayLabel(): string
    {
        return self::WEEK_DAYS[$this->week_day] ?? 'Thứ '.$this->week_day;
    }

    public function timeRange(): string
    {
        return $this->timeLabel($this->start_time).'–'.$this->timeLabel($this->end_time);
    }

    private function timeLabel(?string $time): string
    {
        return $time === null ? '--:--' : substr($time, 0, 5);
    }
}
