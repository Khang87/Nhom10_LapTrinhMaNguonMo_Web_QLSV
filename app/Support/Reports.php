<?php

namespace App\Support;

use App\Models\AttendanceSession;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Bốn báo cáo của nhóm yêu cầu 8. Mọi con số tính lại từ bảng nguồn (users, enrollments,
 * grades, attendances) qua đúng các helper mà màn hình đã dùng — GradingScale cho điểm,
 * GradeBook cho GPA, AttendanceRate cho chuyên cần — nên trang thống kê, file Excel và file
 * PDF nộp trường cùng ra một số.
 *
 * Mỗi báo cáo trả về ['title', 'header', 'rows']: một bảng phẳng để một trang Blade,
 * một exporter và một template PDF tiêu thụ được mà không cần biết báo cáo nào.
 */
final class Reports
{
    public const TYPES = [
        'census' => 'Thống kê sinh viên',
        'scores' => 'Phổ điểm',
        'attendance' => 'Chuyên cần',
        'ranking' => 'Xếp hạng GPA',
    ];

    /**
     * @return array{title: string, header: list<string>, rows: list<list<mixed>>}|null
     */
    public static function show(string $type, ReportFilter $filter): ?array
    {
        return match ($type) {
            'census' => self::census($filter),
            'scores' => self::scores($filter),
            'attendance' => self::attendance($filter),
            'ranking' => self::ranking($filter),
            default => null,
        };
    }

    /**
     * Phân bố sinh viên theo khóa, giới tính, tình trạng học tập, lớp và khoa.
     *
     * @return array{title: string, header: list<string>, rows: list<list<mixed>>}
     */
    public static function census(ReportFilter $filter): array
    {
        $students = $filter->students()
            ->with('studentClass.department', 'studentClass.major')
            ->get();
        $total = $students->count();

        $rows = [];
        foreach (self::groupings() as [$label, $resolver]) {
            $counts = $students
                ->map($resolver)
                ->filter(fn (?string $value) => $value !== null && $value !== '')
                ->countBy()
                ->sortKeys();

            foreach ($counts as $value => $count) {
                $rows[] = [$label, $value, $count, self::share($count, $total)];
            }
        }

        return [
            'title' => 'Phân bố sinh viên theo bộ lọc',
            'header' => ['Nhóm', 'Giá trị', 'Số sinh viên', 'Tỷ lệ (%)'],
            'rows' => $rows,
        ];
    }

    /**
     * Phổ điểm theo khoảng của GradingScale::buckets(), gom theo lớp học phần. Tổng các cột
     * khoảng của một hàng đúng bằng sĩ số có điểm công bố của hàng đó — đó là điều kiểm
     * nghiệp vụ mà trang thống kê phải chứng minh được.
     *
     * @return array{title: string, header: list<string>, rows: list<list<mixed>>}
     */
    public static function scores(ReportFilter $filter): array
    {
        $buckets = GradingScale::buckets();
        $published = self::publishedGrades($filter);

        $rows = $published
            ->groupBy(fn (Grade $grade) => $grade->course_offering_id)
            ->map(function (Collection $grades) use ($buckets) {
                $scores = $grades->map(fn (Grade $grade) => $grade->subjectScore())->filter();
                $offering = $grades->first()->offering;

                return array_merge(
                    [$offering?->label() ?? '—', $grades->count()],
                    array_map(fn (array $bucket) => self::inBucket($scores, $bucket), $buckets),
                    [round((float) $scores->avg(), 2)],
                );
            })
            ->sortBy(fn (array $row) => $row[0])
            ->values()
            ->all();

        return [
            'title' => 'Phổ điểm theo lớp học phần',
            'header' => array_merge(
                ['Lớp học phần', 'Số sinh viên có điểm'],
                array_column($buckets, 'label'),
                ['Điểm TB'],
            ),
            'rows' => $rows,
        ];
    }

    /**
     * Chuyên cần theo lớp học phần: số buổi đã điểm, trung bình tỷ lệ của từng sinh viên
     * (không phải tổng số buổi) và số em dưới ngưỡng cảnh báo học vụ.
     *
     * @return array{title: string, header: list<string>, rows: list<list<mixed>>}
     */
    public static function attendance(ReportFilter $filter): array
    {
        $threshold = AttendanceRate::warningThreshold();
        $rows = [];

        $enrollments = $filter->enrollments()->with('offering.subject')->get();

        foreach ($enrollments->groupBy('course_offering_id') as $offeringId => $group) {
            $rates = collect(AttendanceRate::forEnrollments($group))->pluck('rate')->filter();
            $offering = $group->first()->offering;

            $rows[] = [
                $offering?->label() ?? '—',
                $group->count(),
                AttendanceSession::where('course_offering_id', $offeringId)->whereHas('records')->count(),
                $rates->isEmpty() ? null : round((float) $rates->avg(), 1),
                $rates->filter(fn (float $rate) => AttendanceRate::isBelowThreshold($rate))->count(),
            ];
        }

        // Trung bình các tỷ lệ cá nhân, không phải tổng số buổi: một lớp điểm danh 10 buổi mà
        // đi đủ vẫn phải bằng một lớp điểm danh 2 buổi đi đủ.
        return [
            'title' => 'Chuyên cần theo lớp học phần (ngưỡng '.self::number($threshold).'%)',
            'header' => ['Lớp học phần', 'Sĩ số', 'Số buổi đã điểm', 'TB chuyên cần (%)', 'Dưới ngưỡng'],
            'rows' => collect($rows)->sortBy(fn (array $row) => $row[3] ?? 100.0)->values()->all(),
        ];
    }

    /**
     * Bảng xếp hạng GPA hệ 4 — cột "toàn khoá" hay "lớp mình phụ trách" dùng cùng một hàm,
     * chỉ khác bộ lọc.
     *
     * @return array{title: string, header: list<string>, rows: list<list<mixed>>}
     */
    public static function ranking(ReportFilter $filter, int $limit = 20): array
    {
        $rows = self::publishedGrades($filter)
            ->groupBy('user_id')
            ->map(function (Collection $grades) {
                $summary = GradeBook::summary($grades);

                return [
                    $grades->first()->user?->person_code ?? '—',
                    $grades->first()->user?->name ?? '—',
                    $summary['gpa'],
                    $summary['average_ten'],
                    $summary['credits_accumulated'],
                    $summary['classification'],
                ];
            })
            ->filter(fn (array $row) => $row[2] !== null)
            ->sortByDesc(fn (array $row) => $row[2])
            ->take($limit)
            ->values()
            ->map(fn (array $row, int $i) => array_merge([$i + 1], $row))
            ->all();

        return [
            'title' => 'Xếp hạng GPA (top '.$limit.')',
            'header' => ['#', 'Mã SV', 'Họ tên', 'GPA hệ 4', 'TB hệ 10', 'TC tích lũy', 'Xếp loại'],
            'rows' => $rows,
        ];
    }

    /**
     * Một truy vấn điểm đã công bố, eager-load đúng những gì các hàm tính điểm cần: môn để lấy
     * tín chỉ, người học để xếp hạng. Không có bảng "điểm đã tổng hợp" trung gian.
     *
     * @return Collection<int, Grade>
     */
    private static function publishedGrades(ReportFilter $filter): Collection
    {
        return $filter->grades()
            ->where('is_published', true)
            ->whereNotNull('final_score')
            ->with(['offering.subject', 'user'])
            ->get()
            ->filter(fn (Grade $grade) => $grade->subjectScore() !== null);
    }

    /**
     * @param  Collection<int, float>  $scores
     * @param  array{floor: float, ceiling: float}  $bucket
     */
    private static function inBucket(Collection $scores, array $bucket): int
    {
        return $scores->filter(fn (float $score) => $score >= $bucket['floor'] && $score < $bucket['ceiling'])->count();
    }

    private static function share(int $count, int $total): ?float
    {
        return $total === 0 ? null : round($count / $total * 100, 1);
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }

    /**
     * Các chiều phân bố của bảng thống kê sinh viên. Mỗi chiều là một hàm trả nhãn hiển thị,
     * thêm chiều mới là thêm một dòng vào danh sách này chứ không thêm một vòng lặp mới.
     *
     * @return list<array{0: string, 1: callable(User): ?string}>
     */
    private static function groupings(): array
    {
        return [
            ['Khóa', fn (User $s) => $s->studentClass?->intake_year === null ? null : (string) $s->studentClass->intake_year],
            ['Giới tính', fn (User $s) => PersonLabels::gender($s->gender)],
            ['Tình trạng học tập', fn (User $s) => PersonLabels::status($s->academic_status)],
            ['Lớp', fn (User $s) => $s->studentClass?->class_code],
            ['Khoa', fn (User $s) => $s->studentClass?->department?->name],
            ['Chuyên ngành', fn (User $s) => $s->studentClass?->major?->name],
        ];
    }
}
