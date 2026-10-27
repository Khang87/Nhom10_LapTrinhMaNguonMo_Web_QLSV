<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\PersonLabels;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'teacher', 'student'];

    public const ROLE_LABELS = [
        'admin' => 'Quản trị viên',
        'teacher' => 'Giảng viên',
        'student' => 'Sinh viên',
    ];

    private const DASHBOARD_ROUTES = [
        'admin' => 'admin.dashboard',
        'teacher' => 'teacher.dashboard',
        'student' => 'student.dashboard',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'class_id',
        'avatar',
        'student_code',
        'teacher_code',
        'degree',
        'department_id',
        'dob',
        'gender',
        'hometown',
        'enrolled_on',
        'education_level',
        'training_type',
        'admission_region',
        'priority_category',
        'ethnicity',
        'religion',
        'nationality',
        'birth_place',
        'permanent_residence',
        'contact_address',
        'id_card_number',
        'id_card_issued_at',
        'id_card_issued_place',
        'phone',
        'academic_status',
        'academic_status_updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dob' => 'date',
            'enrolled_on' => 'date',
            'id_card_issued_at' => 'date',
            'academic_status_updated_at' => 'datetime',
        ];
    }

    protected function genderLabel(): Attribute
    {
        return Attribute::get(fn () => PersonLabels::gender($this->gender));
    }

    protected function academicStatusLabel(): Attribute
    {
        return Attribute::get(fn () => PersonLabels::status($this->academic_status));
    }

    /**
     * Mã định dạng dùng chung cho bảng và phiếu in: mã SV với sinh viên, mã GV với giảng viên.
     */
    protected function personCode(): Attribute
    {
        return Attribute::get(fn () => match ($this->role) {
            'student' => $this->student_code,
            'teacher' => $this->teacher_code,
            default => null,
        });
    }

    public function studentClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class, 'class_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function avatarUrl(): string
    {
        return $this->avatar
            ? (Storage::disk('public')->url($this->avatar) ?: asset('images/HUIT_logo.png'))
            : asset('images/HUIT_logo.png');
    }

    public function dashboardRoute(): string
    {
        return self::DASHBOARD_ROUTES[$this->role];
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }

    public function offerings(): BelongsToMany
    {
        return $this->belongsToMany(CourseOffering::class, 'enrollments')
            ->withPivot(['id', 'status', 'dropped_at'])
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Lớp học phần do giảng viên này phụ trách — giảng viên chỉ được nhìn thấy danh sách này.
     */
    public function assignedOfferings(): HasMany
    {
        return $this->hasMany(CourseOffering::class, 'teacher_id');
    }

    /**
     * Những môn sinh viên đang học theo lớp hành chính mới (enrollment ACTIVE).
     *
     * @return Collection<Subject>
     */
    public function enrolledSubjects(): Collection
    {
        $offerings = $this->relationLoaded('offerings')
            ? $this->getRelation('offerings')
            : $this->offerings()->wherePivot('status', 'ACTIVE')->get();

        return $offerings
            ->filter(fn (CourseOffering $offering) => $offering->pivot->status === 'ACTIVE')
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->sortBy('code')
            ->values();
    }

    /**
     * Các lượt điểm danh của sinh viên, đi qua bảng đăng ký (SPEC-attendance-schedule:
     * attendances được khóa theo enrollment, không còn theo môn).
     */
    public function attendances(): HasManyThrough
    {
        return $this->hasManyThrough(Attendance::class, Enrollment::class, 'user_id', 'enrollment_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }
}
