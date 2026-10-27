<?php

namespace App\Models;

use App\Support\GradingScale;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    protected $fillable = [
        'user_id',
        'enrollment_id',
        'course_offering_id',
        'attendance_score',
        'midterm_score',
        'final_score',
        'is_published',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_score' => 'decimal:2',
            'midterm_score' => 'decimal:2',
            'final_score' => 'decimal:2',
            'is_published' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Điểm gắn với lớp học phần, nhưng mọi bảng biểu vẫn cần mã/tên môn của lớp đó.
     * eager-load: Grade::with('offering.subject').
     */
    protected function subject(): Attribute
    {
        return Attribute::get(fn () => $this->offering?->subject);
    }

    /**
     * Điểm tổng kết môn: tính từ ba cột thành phần, không lưu trong DB.
     */
    public function subjectScore(): ?float
    {
        return GradingScale::subjectScore(
            $this->numeric($this->attendance_score),
            $this->numeric($this->midterm_score),
            $this->numeric($this->final_score),
        );
    }

    /**
     * @return array{letter: string, four: float}
     */
    public function band(): array
    {
        $score = $this->subjectScore();
        $final = $this->numeric($this->final_score);

        return $score === null
            ? ['letter' => '—', 'four' => 0.0]
            : GradingScale::toLetterAndFour($score, $final);
    }

    public function letter(): string
    {
        return $this->band()['letter'];
    }

    public function fourScore(): float
    {
        return $this->band()['four'];
    }

    public function isPassing(): bool
    {
        return GradingScale::isPassing($this->subjectScore());
    }

    /**
     * Publish cần điểm cuối kỳ của mọi dòng; cột 70% thiếu thì tổng kết chỉ là một nửa sự thật.
     */
    public function isPublishable(): bool
    {
        return $this->numeric($this->final_score) !== null;
    }

    public function credits(): int
    {
        return (int) ($this->subject?->credits ?? 0);
    }

    private function numeric(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
