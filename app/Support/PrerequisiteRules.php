<?php

namespace App\Support;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PrerequisiteRules
{
    public const ARROW = ' → ';

    /**
     * Cạnh "subject cần prerequisite" chỉ được thêm khi đồ thị tiên quyết vẫn là DAG,
     * vì một vòng lặp khiến không ai đăng ký được các môn nằm trong vòng đó.
     *
     * @throws RuntimeException khi tự tham chiếu hoặc tạo chu trình
     */
    public static function assertAcyclic(int $subjectId, int $prerequisiteId): void
    {
        if ($subjectId === $prerequisiteId) {
            throw new RuntimeException('Một môn học không thể là môn tiên quyết của chính nó.');
        }

        $cycle = self::findCycle($subjectId, $prerequisiteId);

        if ($cycle !== null) {
            throw new RuntimeException(
                'Xếp tiên quyết sẽ tạo vòng lặp: '
                .implode(self::ARROW, self::codes($cycle))
                .'. Hãy bỏ một môn trong vòng này khỏi danh sách tiên quyết.',
            );
        }
    }

    /**
     * Hợp đồng cho màn hình đăng ký học phần: môn tiên quyết chưa đạt sẽ chặn đăng ký.
     *
     * @param  Collection<Subject>  $passed  những môn sinh viên đã đạt
     * @param  Collection<int, object{subject_score: float|null}>|null  $scores  điểm tổng kết theo môn, truyền vào khi dựng danh sách để không truy vấn lại
     * @return array<int, array{code: string, name: string, required_score: float, actual_score: float|null}>
     */
    public static function blockingPrerequisites(
        User $student,
        Subject $subject,
        Collection $passed,
        float $requiredScore = 5.0,
        ?Collection $scores = null,
    ): array {
        $passedIds = $passed->pluck('id');
        $scores ??= self::scoreMap($student);

        return $subject->prerequisites
            ->reject(fn (Subject $prerequisite) => $passedIds->contains($prerequisite->id))
            ->map(function (Subject $prerequisite) use ($scores, $requiredScore) {
                $grade = $scores->get($prerequisite->id);

                return [
                    'code' => $prerequisite->code,
                    'name' => $prerequisite->name,
                    'required_score' => $requiredScore,
                    'actual_score' => $grade === null ? null : $grade->subject_score,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Điểm tổng kết đã có của sinh viên, khóa theo id môn. Một truy vấn dùng chung cho
     * cả danh sách lẫn chi tiết vì nó không phụ thuộc từng lớp học phần. Học lại nhiều lần
     * thì giữ lần điểm cao nhất.
     *
     * @return Collection<int, object{subject_id: int, subject_score: float|null}>
     */
    public static function scoreMap(User $student): Collection
    {
        return DB::table('grades')
            ->join('course_offerings', 'course_offerings.id', '=', 'grades.course_offering_id')
            ->where('grades.user_id', $student->id)
            ->get([
                'course_offerings.subject_id',
                'grades.attendance_score',
                'grades.midterm_score',
                'grades.final_score',
            ])
            ->map(fn (object $row) => (object) [
                'subject_id' => $row->subject_id,
                'subject_score' => GradingScale::subjectScore(
                    self::float($row->attendance_score),
                    self::float($row->midterm_score),
                    self::float($row->final_score),
                ),
            ])
            ->sortBy('subject_score')
            ->keyBy('subject_id');
    }

    private static function float(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    /**
     * @return array<int, int>|null chuỗi id mở đầu và kết thúc bằng $subjectId
     */
    private static function findCycle(int $subjectId, int $prerequisiteId): ?array
    {
        $edges = [$subjectId => [$prerequisiteId]];

        foreach (DB::table('subject_prerequisites')->get(['subject_id', 'prerequisite_subject_id']) as $row) {
            $edges[$row->subject_id][] = $row->prerequisite_subject_id;
        }

        $seen = [$subjectId => true];
        $stack = [[$subjectId, [$subjectId]]];

        while ($stack) {
            [$node, $path] = array_pop($stack);

            foreach ($edges[$node] ?? [] as $next) {
                if ($next === $subjectId) {
                    return [...$path, $subjectId];
                }
                if (! isset($seen[$next])) {
                    $seen[$next] = true;
                    $stack[] = [$next, [...$path, $next]];
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    private static function codes(array $ids): array
    {
        $codes = Subject::whereIn('id', $ids)->pluck('code', 'id');

        return array_map(fn (int $id) => $codes[$id] ?? "#{$id}", $ids);
    }
}
