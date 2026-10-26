<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    protected $fillable = ['user_id', 'course_offering_id', 'final_score'];

    protected function casts(): array
    {
        return ['final_score' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    /**
     * Điểm gắn với lớp học phần, nhưng mọi bảng biểu vẫn cần mã/tên môn của lớp đó.
     * eager-load: Grade::with('offering.subject').
     */
    protected function subject(): Attribute
    {
        return Attribute::get(fn () => $this->offering?->subject);
    }
}
