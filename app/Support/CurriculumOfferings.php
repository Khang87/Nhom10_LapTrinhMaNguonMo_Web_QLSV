<?php

namespace App\Support;

use App\Models\CourseOffering;
use App\Models\Major;
use App\Models\Semester;
use App\Models\StudyPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mở lớp học phần hàng loạt theo khung chương trình: chọn một học kỳ thật và một học kỳ đào tạo
 * của khung, mọi môn thuộc khung đó chưa có lớp ở học kỳ đang chọn sẽ thành lớp mới trong một lần.
 */
class CurriculumOfferings
{
    /**
     * Dòng xem trước của khung: môn nào đã có lớp trong học kỳ đang chọn, môn nào mở thêm được.
     *
     * @return Collection<int, array{plan: StudyPlan, group_code: string, group_taken: bool, other_groups: list<string>}>
     */
    public static function preview(Major $major, Semester $semester, int $planSemester, string $groupCode): Collection
    {
        $plans = StudyPlan::with('subject')
            ->where('major_id', $major->id)
            ->where('plan_semester', $planSemester)
            ->orderBy('subject_id')
            ->get();

        $existing = CourseOffering::where('semester_id', $semester->id)
            ->whereIn('subject_id', $plans->pluck('subject_id')->all())
            ->get()
            ->groupBy('subject_id');

        return $plans->map(function (StudyPlan $plan) use ($existing, $groupCode) {
            $opened = $existing->get($plan->subject_id, collect());

            return [
                'plan' => $plan,
                'group_code' => $groupCode,
                'group_taken' => $opened->contains(fn (CourseOffering $offering) => $offering->group_code === $groupCode),
                'other_groups' => $opened->pluck('group_code')->sort()->values()->all(),
            ];
        })->values();
    }

    /**
     * Tổng tín chỉ của các dòng: admin đối chiếu con số này với tổng tín chỉ khung. Số này chỉ
     * tính môn của khung, không nhân theo số lớp.
     *
     * @param  Collection<int, array{plan: StudyPlan}>  $rows
     */
    public static function credits(Collection $rows): int
    {
        return (int) $rows->sum(fn (array $row) => (int) ($row['plan']->subject?->credits ?? 0));
    }

    /**
     * Tạo lớp cho những môn được chọn và chưa bị lấy mã nhóm. Trả về số lớp thực mở để controller
     * báo đúng, kể cả khi admin chọn cả những môn đã có lớp.
     *
     * @param  list<int>  $subjectIds
     * @param  array{group_code: string, max_students: int, is_open: bool}  $attributes
     */
    public static function open(Major $major, Semester $semester, int $planSemester, array $subjectIds, array $attributes): int
    {
        $rows = static::preview($major, $semester, $planSemester, $attributes['group_code'])
            ->filter(fn (array $row) => in_array((int) $row['plan']->subject_id, $subjectIds, true)
                && ! $row['group_taken']);

        DB::transaction(function () use ($rows, $semester, $attributes) {
            foreach ($rows as $row) {
                CourseOffering::create($attributes + [
                    'subject_id' => $row['plan']->subject_id,
                    'semester_id' => $semester->id,
                ]);
            }
        });

        return $rows->count();
    }
}
