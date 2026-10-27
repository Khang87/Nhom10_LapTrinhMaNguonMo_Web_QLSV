<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'schedule_date' => 'date',
        'start_period' => 'integer',
        'end_period' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (ClassSchedule $schedule) {
            if ($schedule->start_period <= 6) {
                $schedule->shift = 'MORNING';
            } elseif ($schedule->start_period <= 12) {
                $schedule->shift = 'AFTERNOON';
            } else {
                $schedule->shift = 'EVENING';
            }
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('schedule_date', [$startDate, $endDate]);
    }

    public function getCardStyle(): array
    {
        return match ($this->schedule_type) {
            'PRACTICE' => [
                'bg' => '#68c136',
                'border' => '#4b9422',
                'color' => '#0b2239',
                'label' => 'Thực hành',
                'badge' => 'badge-practice',
            ],
            'ONLINE' => [
                'bg' => '#74c0fc',
                'border' => '#228be6',
                'color' => '#08203e',
                'label' => 'Trực tuyến',
                'badge' => 'badge-online',
            ],
            'EXAM' => [
                'bg' => '#fff3a1',
                'border' => '#f59f00',
                'color' => '#4a3500',
                'label' => 'Lịch thi',
                'badge' => 'badge-exam',
            ],
            'SUSPENDED' => [
                'bg' => '#e0533c',
                'border' => '#b02a17',
                'color' => '#ffffff',
                'label' => 'Tạm ngưng',
                'badge' => 'badge-suspended',
            ],
            default => [
                'bg' => '#e8ecef',
                'border' => '#adb5bd',
                'color' => '#0f2942',
                'label' => 'Lý thuyết',
                'badge' => 'badge-theory',
            ],
        };
    }
}
