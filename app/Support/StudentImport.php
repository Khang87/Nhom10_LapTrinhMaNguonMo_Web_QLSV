<?php

namespace App\Support;

use App\Models\StudentClass;
use App\Models\User;
use DateTime;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class StudentImport
{
    public const MAX_ROWS = 500;

    /**
     * Đọc sheet đầu tiên: hàng 1 là tiêu đề, các hàng sau là dữ liệu.
     *
     * @return array{header: list<string>, rows: list<array{row: int, data: list<string>}>}
     */
    public static function read(UploadedFile $file): array
    {
        $grid = IOFactory::load($file->getRealPath())
            ->getSheet(0)
            ->toArray(null, true, false, false);

        $header = array_values(array_map(self::normalize(...), array_shift($grid) ?? []));
        $rows = [];

        foreach ($grid as $index => $row) {
            $data = array_values(array_map(self::normalize(...), $row));
            if (implode('', $data) === '') {
                continue;
            }
            // Hàng đầu tiên của sheet là tiêu đề nên dữ liệu bắt đầu từ dòng 2.
            $rows[] = ['row' => $index + 2, 'data' => $data];
        }

        return ['header' => $header, 'rows' => $rows];
    }

    /**
     * @param  list<string>  $header
     */
    public static function headerMatches(array $header): bool
    {
        $expected = array_map(self::normalize(...), StudentExport::HEADERS);
        $actual = array_slice(array_pad($header, count($expected), ''), 0, count($expected));

        return $actual === $expected;
    }

    /**
     * @param  list<array{row: int, data: list<string>}>  $rows
     */
    public static function persist(array $rows): ImportResult
    {
        $result = new ImportResult;
        $classIds = StudentClass::pluck('id', 'class_code');

        DB::transaction(function () use ($rows, $result, $classIds) {
            foreach ($rows as $entry) {
                static::persistRow($entry['row'], $entry['data'], $result, $classIds);
            }
        });

        return $result;
    }

    /**
     * @param  list<string>  $data
     * @param  Collection<string, int>  $classIds
     */
    private static function persistRow(int $row, array $data, ImportResult $result, $classIds): void
    {
        [$code, $name, $email, $dobRaw, $genderRaw, $hometown, $phoneRaw, $classCode, $statusRaw] = array_pad($data, 9, '');

        if ($name === '' || $email === '') {
            $result->skip($row, 'Thiếu họ tên hoặc email.');

            return;
        }

        if ($code === '') {
            $result->skip($row, 'Thiếu mã sinh viên.');

            return;
        }

        $dob = $dobRaw === '' ? null : self::date($dobRaw);
        if ($dobRaw !== '' && $dob === null) {
            $result->skip($row, "Ngày sinh \"{$dobRaw}\" không hợp lệ.");

            return;
        }

        $gender = $genderRaw === '' ? null : PersonLabels::resolve(PersonLabels::GENDERS, $genderRaw);
        if ($genderRaw !== '' && $gender === null) {
            $result->skip($row, "Giới tính \"{$genderRaw}\" không hợp lệ.");

            return;
        }

        $status = $statusRaw === '' ? null : PersonLabels::resolve(PersonLabels::ACADEMIC_STATUSES, $statusRaw);
        if ($statusRaw !== '' && $status === null) {
            $result->skip($row, "Tình trạng \"{$statusRaw}\" không hợp lệ.");

            return;
        }

        $classId = null;
        if ($classCode !== '') {
            $classId = $classIds->get($classCode);
            if ($classId === null) {
                $result->skip($row, "Lớp {$classCode} không tồn tại.");

                return;
            }
        }

        $owner = User::where('student_code', $code)->first();
        $emailOwner = User::where('email', $email)->first();

        if ($emailOwner && $emailOwner->id !== $owner?->id) {
            $ownerCode = $emailOwner->student_code ?? 'chưa có mã';
            $result->skip($row, "Email {$email} đã thuộc mã sinh viên khác ({$ownerCode}).");

            return;
        }

        if ($owner && $owner->role !== 'student') {
            $result->skip($row, "Mã {$code} đã thuộc tài khoản không phải sinh viên.");

            return;
        }

        $attributes = array_filter([
            'name' => $name,
            'email' => $email,
            'dob' => $dob,
            'gender' => $gender,
            'hometown' => $hometown,
            'phone' => self::phone($phoneRaw),
            'class_id' => $classId,
        ], fn ($value) => $value !== null && $value !== '');

        if ($owner) {
            // Cập nhật hồ sơ theo mã sinh viên, không đụng mật khẩu đang dùng.
            if ($status !== null) {
                $attributes['academic_status'] = $status;
            }
            $owner->update($attributes);
            $result->updated++;

            return;
        }

        User::create([
            ...$attributes,
            'role' => 'student',
            'student_code' => $code,
            'academic_status' => $status ?? 'STUDYING',
            'password' => Hash::make(PersonLabels::IMPORT_DEFAULT_PASSWORD),
        ]);
        $result->created++;
    }

    /**
     * @return ($value is null ? '' : string)
     */
    private static function normalize(mixed $value): string
    {
        return is_string($value) ? trim($value) : (string) ($value ?? '');
    }

    private static function date(string $raw): ?string
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            $parsed = DateTime::createFromFormat($format, $raw);
            if ($parsed && $parsed->format($format) === $raw) {
                return $parsed->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Excel thường nuốt số 0 đầu của số điện thoại; khôi phục lại theo chuẩn Việt Nam.
     */
    private static function phone(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (str_starts_with($digits, '84')) {
            $digits = substr($digits, 2);
        }

        return $digits === '' || str_starts_with($digits, '0') ? $digits : '0'.$digits;
    }
}
