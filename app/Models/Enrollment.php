<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    public const STATUSES = ['ACTIVE', 'DROPPED'];

    protected $fillable = ['course_offering_id', 'user_id', 'enrolled_by', 'status', 'dropped_at'];

    protected function casts(): array
    {
        return ['dropped_at' => 'datetime'];
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Người thực hiện đăng ký: chính sinh viên, hoặc quản trị viên đăng ký hộ.
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Ai đã thực hiện đăng ký này — sinh viên tự làm hay một tài khoản khác làm hộ.
     */
    public function registeredByLabel(): string
    {
        if ($this->enrolled_by === null) {
            return 'Chưa ghi nhận';
        }

        return (int) $this->enrolled_by === (int) $this->user_id
            ? 'Sinh viên tự đăng ký'
            : ($this->registeredBy?->name ?? 'Tài khoản đã xóa').' đăng ký hộ';
    }
}
