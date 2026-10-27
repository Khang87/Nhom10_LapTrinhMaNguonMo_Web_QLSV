<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Một chỗ duy nhất biết định dạng nào có exporter nào, để màn hình thống kê và hai route export
 * cũ của điểm/điểm danh không tự viết lại match ba lần theo ba cách.
 */
final class ReportFormats
{
    public const LABELS = ['csv' => 'CSV', 'xlsx' => 'Excel', 'pdf' => 'PDF'];

    public static function make(string $format): ?ReportExporter
    {
        return match ($format) {
            'csv' => new CsvExporter,
            'xlsx' => new XlsxExporter,
            'pdf' => new PdfExporter,
            default => null,
        };
    }

    /**
     * Tên file mang dấu tiếng Việt dễ vỡ trên một số trình duyệt, nên slug không dấu.
     */
    public static function filename(string $title, string $format): string
    {
        $slug = Str::of($title)->ascii()->slug('-')->value();

        return ($slug === '' ? 'bao-cao' : $slug).'.'.($format === 'xlsx' ? 'xlsx' : $format);
    }
}
