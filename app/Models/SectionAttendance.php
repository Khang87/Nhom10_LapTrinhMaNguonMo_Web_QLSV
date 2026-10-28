<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'student_id',
        'attendance_date',
        'status',
        'note',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    /**
     * Luôn chuẩn hóa ngày điểm danh về định dạng chuẩn Y-m-d (tránh dính giờ 00:00:00 gây lỗi UNIQUE trong SQLite)
     */
    public function setAttendanceDateAttribute($value): void
    {
        $this->attributes['attendance_date'] = Carbon::parse($value)->format('Y-m-d');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
