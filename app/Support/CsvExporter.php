<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Response;

/**
 * Giữ nguyên khế ước TSV + BOM UTF-8 của App\Support\Csv: hai test hồi quy viết từ trước khi
 * có Excel/PDF vẫn xanh, và file CSV mở bằng Excel tiếng Việt không bị vỡ dấu.
 */
final class CsvExporter implements ReportExporter
{
    public function export(string $filename, array $report): Response
    {
        return Csv::download($filename, $report['header'], $report['rows']);
    }
}
