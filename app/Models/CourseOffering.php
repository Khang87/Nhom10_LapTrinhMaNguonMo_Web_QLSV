<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseOffering extends Model
{
    protected $fillable = [
        'subject_id',
        'semester_id',
        'teacher_id',
        'group_code',
        'room',
        'max_students',
        'is_open',
    ];

    protected function casts(): array
    {
        return [
            'max_students' => 'integer',
            'is_open' => 'boolean',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'course_offering_id');
    }

    /**
     * Nhãn dùng chung cho select và bảng: môn nào, nhóm nào.
     */
    public function label(): string
    {
        $subject = $this->subject?->label() ?? 'Môn #'.$this->subject_id;
        $semester = $this->semester?->slug;

        return $subject.' · nhóm '.$this->group_code.($semester ? ' ('.$semester.')' : '');
    }

    /**
     * Số sinh viên đang học: không lưu thành cột để tránh lệch với bảng đăng ký.
     */
    public function enrolledCount(): int
    {
        return $this->enrollments()->where('status', 'ACTIVE')->count();
    }

    public function seatsLeft(): int
    {
        if ($this->max_students === 0) {
            return \PHP_INT_MAX;
        }

        return max(0, $this->max_students - $this->enrolledCount());
    }

    /**
     * Hợp đồng duy nhất để cổng đăng ký (SPEC-enrollment) quyết định mở hay đóng.
     */
    public function isRegistrationOpen(): bool
    {
        return $this->is_open && $this->semester?->isRegistrationOpen() === true;
    }
}
