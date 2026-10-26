<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentClass extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'class_code',
        'class_name',
        'intake_year',
        'department_id',
        'major_id',
        'adviser_id',
        'semester_id',
    ];

    protected function casts(): array
    {
        return ['intake_year' => 'integer'];
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'class_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function label(): string
    {
        return "{$this->class_code} - {$this->class_name}";
    }

    /**
     * GVCN hiển thị bằng tên thật kèm mã giảng viên, không dùng id.
     */
    protected function adviserLabel(): Attribute
    {
        return Attribute::get(function () {
            if ($this->adviser === null) {
                return 'Chưa phân công';
            }

            return $this->adviser->teacher_code
                ? "{$this->adviser->name} ({$this->adviser->teacher_code})"
                : $this->adviser->name;
        });
    }
}
