<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleChangeRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'original_date' => 'date',
        'target_date' => 'date',
    ];

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'schedule_id');
    }

    public function getTypeName(): string
    {
        return match ($this->request_type) {
            'CANCEL_SESSION' => 'Báo nghỉ buổi học',
            'MAKEUP_SESSION' => 'Báo lịch dạy bù',
            'ONLINE_SESSION' => 'Chuyển sang học Online',
            default => 'Thay đổi lịch',
        };
    }

    public function getStatusBadge(): array
    {
        return match ($this->status) {
            'APPROVED' => ['bg' => 'success', 'label' => 'Đã phê duyệt'],
            'REJECTED' => ['bg' => 'danger', 'label' => 'Bị từ chối'],
            default => ['bg' => 'warning', 'label' => 'Chờ Admin duyệt'],
        };
    }
}
