<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Department extends Model
{
    protected $fillable = ['code', 'name', 'phone', 'email', 'office', 'description'];

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(StudentClass::class, 'department_id');
    }

    /**
     * Giảng viên thuộc khoa — dùng cho cột đếm trên danh sách khoa.
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(User::class, 'department_id')->where('role', 'teacher');
    }

    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, StudentClass::class, 'department_id', 'class_id');
    }
}
