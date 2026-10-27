<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_start_date' => 'datetime',
        'registration_end_date' => 'datetime',
        'is_registration_open' => 'boolean',
    ];

    /**
     * Check if registration window is currently open and active.
     */
    public function isRegistrationActive(): bool
    {
        if (!$this->is_registration_open) {
            return false;
        }

        $now = Carbon::now();
        return $now->between($this->registration_start_date, $this->registration_end_date);
    }

    /**
     * Get registration status enum (OPEN, UPCOMING, EXPIRED, CLOSED).
     */
    public function getRegistrationStatusAttribute(): string
    {
        if (!$this->is_registration_open) {
            return 'CLOSED';
        }

        $now = Carbon::now();
        if ($now->lt($this->registration_start_date)) {
            return 'UPCOMING';
        }

        if ($now->gt($this->registration_end_date)) {
            return 'EXPIRED';
        }

        return 'OPEN';
    }

    /**
     * Human readable registration status in Vietnamese.
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->registration_status) {
            'OPEN' => 'Đang mở đăng ký',
            'UPCOMING' => 'Chưa tới hạn mở',
            'EXPIRED' => 'Đã hết hạn đăng ký',
            default => 'Đang tạm đóng',
        };
    }

    /**
     * Calculate remaining time for registration.
     */
    public function getRemainingTimeAttribute(): string
    {
        if ($this->registration_status !== 'OPEN') {
            return '';
        }

        $diff = Carbon::now()->diff($this->registration_end_date);
        if ($diff->days > 0) {
            return "Còn {$diff->days} ngày {$diff->h} giờ";
        }
        return "Còn {$diff->h} giờ {$diff->i} phút";
    }

    public function courseSections(): HasMany
    {
        return $this->hasMany(CourseSection::class, 'semester_code', 'semester_code');
    }
}
