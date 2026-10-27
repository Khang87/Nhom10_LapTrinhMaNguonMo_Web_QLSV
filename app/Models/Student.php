<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'dob' => 'date',
        'enrollment_date' => 'date',
        'cccd_issue_date' => 'date',
        'youth_union_date' => 'date',
        'party_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function semesterEvaluations(): HasMany
    {
        return $this->hasMany(SemesterEvaluation::class);
    }

    public function tuitionInvoices(): HasMany
    {
        return $this->hasMany(TuitionInvoice::class);
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(SectionAttendance::class);
    }

    public function studentClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class, 'class_code', 'class_code');
    }
}
