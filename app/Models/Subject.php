<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = ['code', 'name', 'credits', 'major_id', 'description', 'total_sessions'];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_subject')->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlan::class);
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_prerequisites', 'subject_id', 'prerequisite_subject_id');
    }

    public function dependentSubjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_prerequisites', 'prerequisite_subject_id', 'subject_id');
    }

    public function concurrents(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_concurrents', 'subject_id', 'concurrent_subject_id');
    }

    public function concurrentOf(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_concurrents', 'concurrent_subject_id', 'subject_id');
    }

    protected function concurrentSubjects(): Attribute
    {
        return Attribute::get(
            fn () => $this->concurrents->merge($this->concurrentOf)->unique('id')->values(),
        );
    }
}
