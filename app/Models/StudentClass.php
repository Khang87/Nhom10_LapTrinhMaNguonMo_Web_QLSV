<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $guarded = [];

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id');
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class, 'specialization_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_code', 'class_code');
    }

    /**
     * Lấy danh sách sinh viên thuộc lớp danh nghĩa này (kết hợp lọc theo cohort_year nếu có)
     */
    public function cohortStudents()
    {
        return Student::where('class_code', $this->class_code)
            ->when($this->cohort_year, fn ($q) => $q->where('cohort_year', $this->cohort_year));
    }

    /**
     * Số lượng sinh viên thực tế trong lớp danh nghĩa
     */
    public function getStudentsCountAttribute(): int
    {
        return $this->cohortStudents()->count();
    }
}
