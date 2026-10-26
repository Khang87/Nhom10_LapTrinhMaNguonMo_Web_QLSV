<?php

namespace App\Support;

use App\Models\User;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class StudentExport
{
    /**
     * Hàng tiêu đề cũng là thứ tự cột mà StudentImport đọc lại.
     */
    public const HEADERS = ['Mã SV', 'Họ tên', 'Email', 'Ngày sinh', 'Giới tính', 'Quê quán', 'SĐT', 'Lớp', 'Tình trạng'];

    /**
     * @var list<list<string>>
     */
    public const TEMPLATE_ROWS = [
        ['20240001', 'Nguyễn Văn An', 'an@example.com', '01/01/2006', 'FEMALE', 'Hà Nội', '0912345678', 'CTK44A', 'STUDYING'],
        ['20240002', 'Trần Thị Bình', 'binh@example.com', '02/02/2006', 'MALE', 'Đà Nẵng', '0987654321', '', 'Đang học'],
    ];

    /**
     * @param  iterable<User>  $students
     */
    public static function spreadsheet(iterable $students): Spreadsheet
    {
        $spreadsheet = self::spreadsheetWithHeaders();

        $row = 2;
        foreach ($students as $student) {
            self::writeRow($spreadsheet->getActiveSheet(), $row, [
                (string) $student->person_code,
                $student->name,
                $student->email,
                self::text($student->dob?->format('d/m/Y')),
                self::text($student->gender === null ? null : PersonLabels::gender($student->gender)),
                self::text($student->hometown),
                self::text($student->phone),
                self::text($student->studentClass?->class_code),
                self::text($student->academic_status === null ? null : PersonLabels::status($student->academic_status)),
            ]);
            $row++;
        }

        self::decorate($spreadsheet->getActiveSheet(), $row - 1);

        return $spreadsheet;
    }

    public static function templateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = self::spreadsheetWithHeaders();
        $sheet = $spreadsheet->getActiveSheet();

        foreach (self::TEMPLATE_ROWS as $index => $values) {
            self::writeRow($sheet, $index + 2, $values);
        }

        self::decorate($sheet, count(self::TEMPLATE_ROWS) + 1);

        return $spreadsheet;
    }

    private static function spreadsheetWithHeaders(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh sach sinh vien');

        self::writeRow($sheet, 1, self::HEADERS);
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');

        return $spreadsheet;
    }

    private static function decorate(Worksheet $sheet, int $lastRow): void
    {
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        if ($lastRow > 2) {
            $sheet->getStyle("A2:I{$lastRow}")->getNumberFormat()->setFormatCode('@');
        }
    }

    /**
     * @param  list<string>  $values
     */
    private static function writeRow(Worksheet $sheet, int $row, array $values): void
    {
        foreach ($values as $index => $value) {
            // Ghi kiểu chuỗi để Excel không nuốt số 0 đầu SĐT hay biến mã sinh viên thành số.
            $sheet->setCellValueExplicit(chr(65 + $index).$row, $value, DataType::TYPE_STRING);
        }
    }

    private static function text(?string $value): string
    {
        return $value ?? '';
    }
}
