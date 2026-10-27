<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'process_score' => 'decimal:2',
        'midterm_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'total_score_10' => 'decimal:2',
        'total_score_4' => 'decimal:2',
    ];

    public function calculateGrade(): void
    {
        if ($this->process_score !== null && $this->midterm_score !== null && $this->final_score !== null) {
            $score10 = round(($this->process_score * 0.2) + ($this->midterm_score * 0.2) + ($this->final_score * 0.6), 2);
            $this->total_score_10 = $score10;

            if ($score10 >= 8.5) {
                $this->letter_grade = 'A';
                $this->total_score_4 = 4.0;
            } elseif ($score10 >= 7.0) {
                $this->letter_grade = 'B';
                $this->total_score_4 = 3.0;
            } elseif ($score10 >= 5.5) {
                $this->letter_grade = 'C';
                $this->total_score_4 = 2.0;
            } elseif ($score10 >= 4.0) {
                $this->letter_grade = 'D';
                $this->total_score_4 = 1.0;
            } else {
                $this->letter_grade = 'F';
                $this->total_score_4 = 0.0;
            }
        } else {
            $this->total_score_10 = null;
            $this->total_score_4 = null;
            $this->letter_grade = null;
        }
    }

    public function isPassed(): bool
    {
        return $this->letter_grade !== null && $this->letter_grade !== 'F' && ($this->total_score_10 >= 4.0);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }
}
