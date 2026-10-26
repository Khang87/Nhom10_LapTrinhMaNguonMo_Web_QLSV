<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'term',
        'slug',
        'starts_at',
        'ends_at',
        'registration_starts_at',
        'registration_ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'registration_starts_at' => 'datetime',
            'registration_ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Semester $semester) {
            $semester->slug ??= self::buildSlug($semester->academic_year, (int) $semester->term);

            // Kỳ đầu tiên của hệ thống mặc nhiên là kỳ đang chạy, tránh trạng thái "không kỳ nào".
            $semester->is_active = $semester->is_active || ! static::where('is_active', true)->exists();
        });
    }

    public static function buildSlug(string $academicYear, int $term): string
    {
        return 'HK'.$term.'-'.$academicYear;
    }

    /**
     * Buộc đúng một kỳ đang hoạt động — quy tắc đặt ở service, không phải constraint.
     */
    public static function activate(int $id): void
    {
        DB::transaction(function () use ($id) {
            static::whereKeyNot($id)->update(['is_active' => false]);
            static::whereKey($id)->update(['is_active' => true]);
        });
    }

    public static function current(): ?self
    {
        return static::where('is_active', true)->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function isRegistrationOpen(): bool
    {
        return $this->registration_starts_at !== null
            && $this->registration_ends_at !== null
            && now()->between($this->registration_starts_at, $this->registration_ends_at);
    }

    protected function label(): Attribute
    {
        return Attribute::get(
            fn () => 'Học kỳ '.$this->term.' năm học '.$this->academic_year,
        );
    }
}
