<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Gom những dòng điểm của một sinh viên thành các con số mà màn hình cần: bảng điểm hiện hành,
 * lịch sử học tập, tổng kết toàn khoá và ngữ cảnh xét lên lớp/tốt nghiệp. Phần tính toán thuần
 * (trọng số, quy đổi hệ 4, xếp loại) nằm ở GradingScale; class này chỉ hỏi DB rồi đưa đúng
 * cấu trúc cho GradingScale tiêu thụ.
 */
final class GradeBook
{
    /**
     * Điểm của các lớp đang học. Khớp theo id lớp học phần thay vì enrollment_id để những dòng
     * điểm di cư từ dữ liệu cũ (chưa có enrollment_id) không biến mất khỏi bảng điểm.
     *
     * @return Collection<int, Grade>
     */
    public static function current(User $student): Collection
    {
        return self::query($student)
            ->whereIn('course_offering_id', self::activeOfferingIds($student))
            ->get();
    }

    /**
     * Mọi lớp đã từng học, kể cả lớp đã hủy và môn học lại.
     *
     * @return Collection<int, Grade>
     */
    public static function history(User $student): Collection
    {
        return self::query($student)->get();
    }

    /**
     * Tổng kết toàn khoá, chỉ tính môn đã công bố vì điểm chưa publish chưa phải kết quả chính thức.
     *
     * @param  Collection<int, Grade>  $grades
     * @return array{gpa: float|null, average_ten: float|null, credits_accumulated: int, failing_subjects: int, classification: string}
     */
    public static function summary(Collection $grades): array
    {
        $published = $grades->filter(fn (Grade $grade) => $grade->is_published);
        $failing = $published->filter(self::isFailing(...));
        $rows = $published->map(fn (Grade $grade) => [
            'score' => $grade->subjectScore(),
            'four' => $grade->fourScore(),
            'credits' => $grade->credits(),
        ]);
        $averageTen = GradingScale::averageTen($rows);

        return [
            'gpa' => GradingScale::gpa($rows),
            'average_ten' => $averageTen,
            'credits_accumulated' => (int) $published->reject(self::isFailing(...))->sum(fn (Grade $grade) => $grade->credits()),
            'failing_subjects' => $failing->count(),
            'classification' => $averageTen === null ? '—' : GradingScale::classification($averageTen, $failing->isNotEmpty()),
        ];
    }

    /**
     * Ngữ cảnh cho nút "Xét duyệt": tổng kết + tỷ lệ chuyên cần + tín chỉ khung chương trình
     * của chuyên ngành, kèm gợi ý và lý do từ GradingScale::progression().
     *
     * @return array<string, mixed>
     */
    public static function review(User $student): array
    {
        $context = self::summary(self::history($student));
        $context['attendance_rate'] = AttendanceRate::forStudent($student);
        $context['credits_required'] = self::requiredCredits($student);
        $context['suggestion'] = GradingScale::progression($context);

        return $context;
    }

    /**
     * Tổng tín chỉ khung chương trình của chuyên ngành mà lớp hành chính của sinh viên thuộc về.
     * Trả null khi chưa có khung (chưa xếp lớp, chưa gán chuyên ngành hoặc khung trống) để
     * progression() không kết luận "đủ tín chỉ tốt nghiệp" dựa trên một con số 0 vô nghĩa.
     */
    public static function requiredCredits(User $student): ?int
    {
        $majorId = $student->studentClass?->major_id;

        if ($majorId === null) {
            return null;
        }

        $credits = (int) DB::table('study_plans')
            ->join('subjects', 'subjects.id', '=', 'study_plans.subject_id')
            ->where('study_plans.major_id', $majorId)
            ->sum('subjects.credits');

        return $credits === 0 ? null : $credits;
    }

    /**
     * Một dòng bị tính là môn F theo chữ trên bảng điểm; đây là cách đếm dùng chung cho GPA,
     * xếp loại và lý do học vụ.
     */
    public static function isFailing(Grade $grade): bool
    {
        return $grade->letter() === 'F';
    }

    /**
     * @return Builder<Grade>
     */
    private static function query(User $student): Builder
    {
        return Grade::where('user_id', $student->id)
            ->with(['offering.subject', 'offering.semester', 'enrollment'])
            ->orderBy('updated_at');
    }

    /**
     * @return Collection<int, int>
     */
    private static function activeOfferingIds(User $student): Collection
    {
        return Enrollment::where('user_id', $student->id)
            ->where('status', 'ACTIVE')
            ->pluck('course_offering_id');
    }
}
