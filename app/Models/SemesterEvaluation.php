<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemesterEvaluation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'min_course_score' => 'decimal:2',
        'semester_gpa_10' => 'decimal:2',
        'semester_gpa_4' => 'decimal:2',
        'scholarship_score' => 'decimal:2',
        'is_eligible' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
