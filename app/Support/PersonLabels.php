<?php

namespace App\Support;

final class PersonLabels
{
    public const GENDERS = [
        'MALE' => 'Nam',
        'FEMALE' => 'Nữ',
        'OTHER' => 'Khác',
    ];

    public const ACADEMIC_STATUSES = [
        'STUDYING' => 'Đang học',
        'AT_RISK' => 'Cảnh báo học vụ',
        'SUSPENDED' => 'Tạm dừng học',
        'GRADUATED' => 'Tốt nghiệp',
        'DROPPED' => 'Nghỉ học',
    ];

    public const IMPORT_DEFAULT_PASSWORD = '12345678';

    public static function gender(?string $code): string
    {
        return self::label(self::GENDERS, $code);
    }

    public static function status(?string $code): string
    {
        return self::label(self::ACADEMIC_STATUSES, $code);
    }

    /**
     * Nhãn kèm mã cho danh sách chọn, ví dụ "Đang học (STUDYING)".
     */
    public static function option(string $code, array $labels): string
    {
        return ($labels[$code] ?? $code).' ('.$code.')';
    }

    /**
     * @return list<string>
     */
    public static function genderCodes(): array
    {
        return array_keys(self::GENDERS);
    }

    /**
     * @return list<string>
     */
    public static function statusCodes(): array
    {
        return array_keys(self::ACADEMIC_STATUSES);
    }

    /**
     * Đọc giá trị từ Excel: chấp nhận cả mã UPPER_SNAKE lẫn nhãn tiếng Việt.
     *
     * @param  array<string, string>  $labels
     */
    public static function resolve(array $labels, ?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $upper = mb_strtoupper($value);
        if (array_key_exists($upper, $labels)) {
            return $upper;
        }

        $matched = array_search(mb_strtolower($value), array_map('mb_strtolower', $labels), true);

        return $matched === false ? null : $matched;
    }

    /**
     * @param  array<string, string>  $labels
     */
    private static function label(array $labels, ?string $code): string
    {
        return $labels[$code] ?? 'Chưa cập nhật';
    }
}
