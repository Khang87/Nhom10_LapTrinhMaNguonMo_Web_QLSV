<?php

namespace App\Support;

/**
 * Toàn bộ con số của nhóm yêu cầu 6 (trọng số, bảng quy đổi hệ 4, GPA, xếp loại, xét lên lớp)
 * gom về một file. Điểm tổng kết KHÔNG lưu trong DB mà tính lại từ ba cột thành phần, để đổi
 * trọng số không phải backfill. Class này là hàm thuần: không query, không session.
 */
final class GradingScale
{
    /**
     * Trọng số mẫu đã duyệt, cộng lại đúng 1.0. Muốn tách "bài tập" thành cột riêng thì chỉ
     * cần thêm một trọng số + một cột điểm, các hàm bên dưới tự thích ứng.
     */
    public const WEIGHTS = [
        'attendance' => 0.10,
        'midterm' => 0.20,
        'final' => 0.70,
    ];

    public const COMPONENT_LABELS = [
        'attendance' => 'Chuyên cần',
        'midterm' => 'Giữa kỳ',
        'final' => 'Cuối kỳ',
    ];

    public const PASS_SCORE = 5.0;

    /** Điểm liệt: cuối kỳ dưới ngưỡng này là F, bất kể tổng kết hệ 10 bao nhiêu. */
    public const FAIL_FLOOR = 4.0;

    /** GPA hệ 4 dưới ngưỡng này là cảnh báo học vụ. */
    public const AT_RISK_GPA = 2.0;

    /**
     * Cận dưới mỗi bảng (hệ 10) kèm chữ và điểm hệ 4; phần còn lại là F.
     */
    private const BANDS = [
        [8.5, 'A', 4.0],
        [7.0, 'B', 3.5],
        [6.0, 'C', 3.0],
        [5.0, 'D', 2.5],
    ];

    /**
     * Ba cột nhập điểm kèm nhãn tiếng Việt và trọng số %, để lưới nhập điểm của giảng viên,
     * biểu mẫu admin và bảng điểm của sinh viên không tự viết ba cách gọi khác nhau.
     *
     * @return list<array{key: string, label: string, weight: int}>
     */
    public static function components(): array
    {
        return array_map(
            fn (string $key) => [
                'key' => $key.'_score',
                'label' => self::COMPONENT_LABELS[$key],
                'weight' => (int) round(self::WEIGHTS[$key] * 100),
            ],
            array_keys(self::WEIGHTS),
        );
    }

    /**
     * Điểm tổng kết môn = Σ(thành phần × trọng số), làm tròn 2 chữ số. Thành phần chưa nhập
     * tính là 0; chưa nhập cột nào thì trả null để màn hình in "—" thay vì 0.
     */
    public static function subjectScore(?float $attendance, ?float $midterm, ?float $final): ?float
    {
        if ($attendance === null && $midterm === null && $final === null) {
            return null;
        }

        $total = ($attendance ?? 0.0) * self::WEIGHTS['attendance']
            + ($midterm ?? 0.0) * self::WEIGHTS['midterm']
            + ($final ?? 0.0) * self::WEIGHTS['final'];

        return round($total, 2);
    }

    /**
     * @return array{letter: string, four: float}
     */
    public static function toLetterAndFour(float $subjectScore, ?float $final = null): array
    {
        if ($final !== null && $final < self::FAIL_FLOOR) {
            return ['letter' => 'F', 'four' => 0.0];
        }

        foreach (self::BANDS as [$floor, $letter, $four]) {
            if ($subjectScore >= $floor) {
                return ['letter' => $letter, 'four' => $four];
            }
        }

        return ['letter' => 'F', 'four' => 0.0];
    }

    /**
     * GPA hệ 4 = Σ(hệ 4 × tín chỉ) / Σ(tín chỉ), làm tròn 2 chữ số.
     *
     * @param  iterable<array{four: float|null, credits: int|null}>  $rows
     */
    public static function gpa(iterable $rows): ?float
    {
        return self::weighted($rows, 'four');
    }

    /**
     * Trung bình hệ 10 toàn khoá, dùng đúng trọng số tín chỉ như GPA.
     *
     * @param  iterable<array{score: float|null, credits: int|null}>  $rows
     */
    public static function averageTen(iterable $rows): ?float
    {
        return self::weighted($rows, 'score');
    }

    /**
     * Xếp loại học lực theo trung bình hệ 10. Bảng duyệt yêu cầu "không có môn F" cho ba bậc
     * cao, nên một môn F kéo xếp loại về Trung bình kể cả khi điểm rất cao.
     */
    public static function classification(float $averageTen, bool $hasFailingSubject = false): string
    {
        if (! $hasFailingSubject) {
            if ($averageTen >= 9.0) {
                return 'Xuất sắc';
            }
            if ($averageTen >= 8.0) {
                return 'Giỏi';
            }
            if ($averageTen >= 6.5) {
                return 'Khá';
            }
        } elseif ($averageTen >= 6.5) {
            return 'Trung bình';
        }

        return $averageTen >= 5.0 ? 'Trung bình' : 'Yếu';
    }

    /**
     * Gợi ý xét lên lớp / tốt nghiệp. Phần mềm chỉ đề xuất kèm lý do; admin xác nhận bằng nút
     * "Xét duyệt" vì quyết định thật thuộc hội đồng đào tạo.
     *
     * @param  array{gpa?: float|null, failing_subjects?: int, attendance_rate?: float|null, credits_accumulated?: int, credits_required?: int|null}  $context
     * @return array{status: string, reasons: list<string>}
     */
    public static function progression(array $context): array
    {
        $gpa = isset($context['gpa']) ? (float) $context['gpa'] : null;
        $failing = (int) ($context['failing_subjects'] ?? 0);
        $attendanceRate = isset($context['attendance_rate']) ? (float) $context['attendance_rate'] : null;
        $minAttendance = (float) config('academic.min_attendance_rate');

        $reasons = [];

        if ($gpa !== null && $gpa < self::AT_RISK_GPA) {
            $reasons[] = 'GPA hệ 4 '.self::score($gpa).' dưới ngưỡng cảnh báo học vụ '.self::score(self::AT_RISK_GPA).'.';
        }
        if ($failing >= 2) {
            $reasons[] = "Có {$failing} môn F chưa học lại, vượt ngưỡng cảnh báo học vụ.";
        }
        if ($attendanceRate !== null && $attendanceRate < $minAttendance) {
            $reasons[] = 'Tỷ lệ chuyên cần '.self::percent($attendanceRate).'% dưới ngưỡng '.self::percent($minAttendance).'%.';
        }

        if ($reasons !== []) {
            return ['status' => 'AT_RISK', 'reasons' => $reasons];
        }

        $accumulated = (int) ($context['credits_accumulated'] ?? 0);
        $required = isset($context['credits_required']) ? (int) $context['credits_required'] : null;

        if ($failing === 1) {
            return ['status' => 'STUDYING', 'reasons' => ['Có 1 môn F chưa học lại, chưa đủ điều kiện lên lớp.']];
        }

        if ($required !== null && $accumulated >= $required) {
            return ['status' => 'GRADUATED', 'reasons' => [
                "Đã tích luỹ {$accumulated}/{$required} tín chỉ của khung chương trình, không còn môn F — đủ điều kiện tốt nghiệp (hội đồng xét quyết định).",
            ]];
        }

        if ($required !== null) {
            $missing = $required - $accumulated;

            return ['status' => 'STUDYING', 'reasons' => [
                "Đã tích luỹ {$accumulated}/{$required} tín chỉ, còn thiếu {$missing} tín chỉ so với khung chương trình.",
            ]];
        }

        return ['status' => 'STUDYING', 'reasons' => ['Không có môn F, đủ điều kiện lên lớp năm học tiếp.']];
    }

    public static function isPassing(?float $subjectScore): bool
    {
        return $subjectScore !== null && $subjectScore >= self::PASS_SCORE;
    }

    /**
     * @return string dạng "C (3.00)" cho cột xếp loại môn.
     */
    public static function bandLabel(float $subjectScore, ?float $final = null): string
    {
        $band = self::toLetterAndFour($subjectScore, $final);

        return $band['letter'].' ('.self::score($band['four']).')';
    }

    /**
     * @param  iterable<array<string, float|int|null>>  $rows
     */
    private static function weighted(iterable $rows, string $key): ?float
    {
        $weightedSum = 0.0;
        $credits = 0;

        foreach ($rows as $row) {
            $value = $row[$key] ?? null;
            $weight = (int) ($row['credits'] ?? 0);

            if ($value === null || $weight <= 0) {
                continue;
            }

            $weightedSum += (float) $value * $weight;
            $credits += $weight;
        }

        return $credits === 0 ? null : round($weightedSum / $credits, 2);
    }

    private static function score(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    private static function percent(float $value): string
    {
        return $value === floor($value) ? number_format($value, 0, '.', ',') : number_format($value, 1, '.', ',');
    }
}
