<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyPlan extends Model
{
    public const KINDS = ['REQUIRED', 'ELECTIVE'];

    public const KIND_LABELS = [
        'REQUIRED' => 'Bắt buộc',
        'ELECTIVE' => 'Tự chọn',
    ];

    protected $fillable = ['major_id', 'subject_id', 'plan_semester', 'kind'];

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function kindLabel(): string
    {
        return self::KIND_LABELS[$this->kind] ?? $this->kind;
    }
}
