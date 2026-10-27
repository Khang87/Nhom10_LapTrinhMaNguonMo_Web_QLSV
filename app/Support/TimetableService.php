<?php

namespace App\Support;

use App\Models\LectureSlot;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Nguồn dữ liệu của thời khóa biểu (SPEC-attendance-schedule 7.5): mọi ô đều sinh ra từ
 * `lecture_slots`, không có bảng lịch riêng nên không thể lệch với lịch lên lớp.
 */
class TimetableService
{
    /**
     * Lịch học của một sinh viên: chỉ slot của lớp có đăng ký ACTIVE, lớp đã DROPPED biến mất.
     *
     * @return Collection<int, LectureSlot>
     */
    public static function forStudent(User $student, ?Semester $semester = null): Collection
    {
        return static::slots()
            ->whereHas('offering.enrollments', fn (Builder $q) => $q->where('user_id', $student->id)->active())
            ->when($semester, fn (Builder $q) => static::inSemester($q, $semester))
            ->get();
    }

    /**
     * Lịch dạy của một giảng viên: mọi slot của lớp do người đó phụ trách.
     *
     * @return Collection<int, LectureSlot>
     */
    public static function forTeacher(User $teacher, ?Semester $semester = null): Collection
    {
        return static::slots()
            ->whereRelation('offering', 'teacher_id', $teacher->id)
            ->when($semester, fn (Builder $q) => static::inSemester($q, $semester))
            ->get();
    }

    /**
     * Mọi slot của một học kỳ, dùng khi cần so lịch của nhiều lớp mà không hỏi DB từng lớp.
     *
     * @return Collection<int, LectureSlot>
     */
    public static function forSemester(?Semester $semester): Collection
    {
        return $semester === null
            ? static::slots()->get()
            : static::inSemester(static::slots(), $semester)->get();
    }

    /**
     * @return Collection<int, LectureSlot>
     */
    public static function forOffering(int $offeringId): Collection
    {
        return static::slots()->where('course_offering_id', $offeringId)->get();
    }

    /**
     * Danh sách học kỳ cho bộ lọc của trang lịch.
     *
     * @return Collection<int, Semester>
     */
    public static function semesters(): Collection
    {
        return Semester::orderByDesc('academic_year')->orderByDesc('term')->get();
    }

    /**
     * Học kỳ mà trang lịch đang xem: `?semester_id=`, mặc định là kỳ đang chạy (spec 7.5).
     */
    public static function semesterFor(Request $request): ?Semester
    {
        $semesters = static::semesters();
        $selected = $request->query('semester_id');

        if (filled($selected)) {
            return $semesters->firstWhere('id', (int) $selected);
        }

        return $semesters->firstWhere('is_active', true) ?? $semesters->first();
    }

    /**
     * Lưới Thứ 2–Thứ 7: mọi ngày đều có mặt kể cả ngày nghỉ, để trang chỉ việc lặp một vòng.
     *
     * @param  Collection<int, LectureSlot>  $slots
     * @return array<int, Collection<int, LectureSlot>>
     */
    public static function grid(Collection $slots): array
    {
        $byDay = $slots->groupBy(fn (LectureSlot $slot) => (int) $slot->week_day);

        return collect(array_keys(LectureSlot::WEEK_DAYS))
            ->mapWithKeys(fn (int $day) => [$day => $byDay->get($day, collect())])
            ->all();
    }

    /**
     * Hai slot chồng giờ — spec yêu cầu cảnh báo, không chặn đăng ký.
     *
     * @param  Collection<int, LectureSlot>  $slots
     * @return array<int, array{first: LectureSlot, second: LectureSlot}>
     */
    public static function conflicts(Collection $slots): array
    {
        $slots = $slots->values();
        $found = [];

        for ($i = 0; $i < $slots->count(); $i++) {
            for ($j = $i + 1; $j < $slots->count(); $j++) {
                if (static::overlaps($slots[$i], $slots[$j])) {
                    $found[] = ['first' => $slots[$i], 'second' => $slots[$j]];
                }
            }
        }

        return $found;
    }

    /**
     * @param  Collection<int, LectureSlot>  $mine
     * @param  Collection<int, LectureSlot>  $candidates
     * @return Collection<int, LectureSlot> những slot ứng viên trùng giờ một slot đã có
     */
    public static function overlapping(Collection $mine, Collection $candidates): Collection
    {
        return $candidates->filter(
            fn (LectureSlot $candidate) => $mine->contains(
                fn (LectureSlot $slot) => static::overlaps($slot, $candidate)
            )
        )->values();
    }

    /**
     * Cột `time` trả về chuỗi 'HH:MM:SS' nên so sánh theo thứ tự từ điển đúng bằng so sánh giờ.
     */
    private static function overlaps(LectureSlot $first, LectureSlot $second): bool
    {
        return (int) $first->week_day === (int) $second->week_day
            && $first->start_time < $second->end_time
            && $second->start_time < $first->end_time;
    }

    private static function slots(): Builder
    {
        return LectureSlot::query()
            ->with(['offering.subject', 'offering.semester', 'offering.teacher'])
            ->orderBy('week_day')
            ->orderBy('start_time');
    }

    private static function inSemester(Builder $query, Semester $semester): Builder
    {
        return $query->whereRelation('offering', 'semester_id', $semester->id);
    }
}
