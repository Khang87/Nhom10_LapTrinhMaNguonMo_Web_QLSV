<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cohort extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function classes(): HasMany
    {
        return $this->hasMany(StudentClass::class, 'cohort_year', 'cohort_year');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'cohort_year', 'cohort_year');
    }
}
