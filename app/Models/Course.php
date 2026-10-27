<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'tuition_fee_per_credit' => 'decimal:2',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class);
    }

    public function curriculumItems(): HasMany
    {
        return $this->hasMany(CurriculumFramework::class);
    }
}
