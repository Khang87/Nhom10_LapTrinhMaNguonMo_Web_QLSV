<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

/**
 * .xlsx thật cho bàn nộp trường: tiêu đề đậm, đóng băng hàng 1, điểm định dạng số 0.00 và mọi
 * mã (mã SV, sĩ số dạng text) ghi kiểu chuỗi để Excel không nuốt số 0 đầu dòng.
 */
final class XlsxExporter implements ReportExporter
{
    public function export(string $filename, array $report): Response
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::sheetTitle($report['title']));

        $this->writeRow($sheet, 1, $report['header']);
        $sheet->getStyle('A1:'.Coordinate::stringFromColumnIndex(count($report['header'])).'1')
            ->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');

        $row = 2;
        foreach ($report['rows'] as $values) {
            $this->writeRow($sheet, $row, $values);
            $row++;
        }

        foreach (range(1, count($report['header'])) as $index) {
            $column = Coordinate::stringFromColumnIndex($index);
            $sheet->getColumnDimension($column)->setAutoSize(true);
            $format = $this->numberFormat($column, $report['rows']);
            if ($format !== null && $row > 2) {
                $sheet->getStyle("{$column}2:{$column}".($row - 1))->getNumberFormat()->setFormatCode($format);
            }
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /**
     * @param  list<mixed>  $values
     */
    private function writeRow(Worksheet $sheet, int $row, array $values): void
    {
        foreach ($values as $index => $value) {
            $cell = Coordinate::stringFromColumnIndex($index + 1).$row;

            if (is_int($value) || is_float($value)) {
                $sheet->setCellValue($cell, $value);

                continue;
            }

            $sheet->setCellValueExplicit($cell, $value === null ? '' : (string) $value, DataType::TYPE_STRING);
        }
    }

    /**
     * Một cột là cột số khi mọi giá trị khác null của nó là số; điểm thì 0.00, còn lại là số nguyên.
     *
     * @param  list<list<mixed>>  $rows
     */
    private function numberFormat(string $column, array $rows): ?string
    {
        $index = Coordinate::columnIndexFromString($column) - 1;
        $values = collect($rows)
            ->map(fn (array $row) => $row[$index] ?? null)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        if ($values === [] || ! collect($values)->every(fn ($value) => is_int($value) || is_float($value))) {
            return null;
        }

        return collect($values)->contains(fn ($value) => is_float($value)) ? '0.00' : '0';
    }

    /**
     * Tên trang tính của Excel tối đa 31 ký tự và cấm một số ký tự, trong khi tiêu đề báo cáo
     * dài hơn và mang dấu %.
     */
    private static function sheetTitle(string $title): string
    {
        $clean = preg_replace('/[\[\]:*?\/\\\\]/', '', $title) ?? $title;

        return mb_substr(trim($clean) === '' ? 'Bao cao' : $clean, 0, 31);
    }
}
