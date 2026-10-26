<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentClass extends Model
{
    protected $table = 'classes';

    protected $fillable = ['class_code', 'class_name', 'room', 'intake_year', 'department_id', 'major_id'];

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

    public function label(): string
    {
        return "{$this->class_code} - {$this->class_name}";
    }
}
