<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentClass extends Model
{
    protected $table = 'classes';

    protected $fillable = ['class_code', 'class_name', 'room'];

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'class_id');
    }
}
