<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeAppeal extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'current_score' => 'float',
        'desired_score' => 'float',
        'new_score' => 'float',
        'admin_approved' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function getComponentName(): string
    {
        return match ($this->component) {
            'PROCESS' => 'Điểm quá trình (20%)',
            'MIDTERM' => 'Điểm giữa kỳ (20%)',
            'FINAL' => 'Điểm thi kết thúc HP (60%)',
            'TOTAL' => 'Điểm tổng kết',
            default => 'Thành phần điểm',
        };
    }

    public function getStatusBadge(): array
    {
        return match ($this->status) {
            'RESOLVED' => ['bg' => 'success', 'label' => 'Đã giải quyết'],
            'REJECTED' => ['bg' => 'danger', 'label' => 'Từ chối phúc khảo'],
            'REVIEWED' => ['bg' => 'info', 'label' => 'GV đã phản hồi (Chờ duyệt)'],
            default => ['bg' => 'warning', 'label' => 'Đang chờ xử lý'],
        };
    }
}
