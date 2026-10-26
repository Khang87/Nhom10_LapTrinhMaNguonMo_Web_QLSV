<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['code', 'name'];

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(StudentClass::class, 'department_id');
    }
}
