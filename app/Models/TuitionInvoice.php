<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TuitionInvoice extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getDebtAmountAttribute(): float
    {
        return max(0, (float)$this->total_amount - (float)$this->paid_amount);
    }

    public function getVietQrUrlAttribute(): string
    {
        $amount = (int)$this->debt_amount;
        $addInfo = rawurlencode($this->payment_code);
        $accountName = rawurlencode('TRUONG DH CONG THUONG TPHCM');
        
        return "https://img.vietqr.io/image/MB-0942367010-compact2.png?amount={$amount}&addInfo={$addInfo}&accountName={$accountName}";
    }
}
