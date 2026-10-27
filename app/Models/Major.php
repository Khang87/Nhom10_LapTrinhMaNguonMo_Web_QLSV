<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Major extends Model
{
    protected $fillable = ['department_id', 'code', 'name'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlan::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(StudentClass::class, 'major_id');
    }

    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, StudentClass::class, 'major_id', 'class_id');
    }
}
