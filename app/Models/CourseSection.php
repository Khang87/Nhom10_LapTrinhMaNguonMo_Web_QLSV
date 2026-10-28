<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSection extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'exam_date' => 'datetime',
        'grade_submitted_at' => 'datetime',
        'grade_approved_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'section_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'section_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(SectionAttendance::class, 'section_id');
    }

    public function gradeAppeals(): HasMany
    {
        return $this->hasMany(GradeAppeal::class, 'section_id');
    }

    public function scheduleChangeRequests(): HasMany
    {
        return $this->hasMany(ScheduleChangeRequest::class, 'section_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_code', 'semester_code');
    }

    /**
     * Tính tổng số tuần học của học phần
     */
    public function getTotalWeeks(): int
    {
        $credits = $this->course?->credits ?? 3;
        $periodsPerSession = max(1, ($this->end_period - $this->start_period + 1));
        $weeks = (int) ceil(($credits * 15) / $periodsPerSession);
        return max(10, min(15, $weeks));
    }

    /**
     * Tính ngày bắt đầu học phần (buổi học đầu tiên của học phần)
     */
    public function getStartDate(): \Carbon\Carbon
    {
        $sem = $this->semester ?? Semester::where('semester_code', $this->semester_code)->first();
        $semStart = $sem && $sem->start_date ? \Carbon\Carbon::parse($sem->start_date) : \Carbon\Carbon::parse('2026-10-28');

        $targetDay = (int) $this->day_of_week;
        $targetIso = ($targetDay >= 2 && $targetDay <= 7) ? ($targetDay - 1) : 7; // ISO: 1 = Thứ 2 ... 7 = Chủ nhật

        $date = $semStart->copy()->startOfDay();
        while ($date->dayOfWeekIso !== $targetIso) {
            $date->addDay();
        }

        return $date;
    }

    /**
     * Tính ngày kết thúc học phần (buổi học cuối cùng của học phần)
     */
    public function getEndDate(): \Carbon\Carbon
    {
        $startDate = $this->getStartDate();
        $totalWeeks = $this->getTotalWeeks();
        $endDate = $startDate->copy()->addWeeks($totalWeeks - 1)->endOfDay();

        $sem = $this->semester ?? Semester::where('semester_code', $this->semester_code)->first();
        if ($sem && $sem->end_date) {
            $semEnd = \Carbon\Carbon::parse($sem->end_date)->endOfDay();
            if ($endDate->gt($semEnd)) {
                $endDate = $semEnd;
            }
        }

        return $endDate;
    }

    /**
     * Chuỗi văn bản hiển thị khoảng thời gian học phần
     */
    public function getDateRangeText(): string
    {
        return $this->getStartDate()->format('d/m/Y') . ' - ' . $this->getEndDate()->format('d/m/Y') . ' (' . $this->getTotalWeeks() . ' tuần)';
    }

    public function isGradeLocked(): bool
    {
        return in_array($this->grade_status, ['SUBMITTED', 'APPROVED']);
    }

    public function isGradeApproved(): bool
    {
        return $this->grade_status === 'APPROVED';
    }
}
