<?php

namespace App\Models;

use App\Support\Tuition;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Thông báo học phí của một sinh viên trong một học kỳ: số tín chỉ đã đăng ký tại thời điểm
 * phát hành, số tiền phải nộp và trạng thái thu.
 */
class TuitionInvoice extends Model
{
    protected $fillable = [
        'user_id',
        'semester_id',
        'credits',
        'amount',
        'content',
        'paid_at',
        'collected_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    /**
     * Ảnh QR của VietQR, hoặc null khi nhà trường chưa cấu hình tài khoản nhận.
     */
    protected function qrUrl(): Attribute
    {
        return Attribute::get(fn () => Tuition::qrUrl($this));
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function amountLabel(): string
    {
        return number_format((float) $this->amount, 0, ',', '.').' đ';
    }
}
