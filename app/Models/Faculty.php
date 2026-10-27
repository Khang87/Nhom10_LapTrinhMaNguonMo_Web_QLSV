<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Giảng viên giữ chức vụ Trưởng khoa
     */
    public function dean(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'dean_id');
    }

    /**
     * Các chuyên ngành trực thuộc khoa
     */
    public function specializations(): HasMany
    {
        return $this->hasMany(Specialization::class);
    }

    /**
     * Các giảng viên trực thuộc khoa
     */
    public function lecturers(): HasMany
    {
        return $this->hasMany(Lecturer::class);
    }
}
