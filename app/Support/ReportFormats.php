<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Một chỗ duy nhất biết định dạng nào có exporter nào, để màn hình thống kê và hai route export
 * cũ của điểm/điểm danh không tự viết lại match ba lần theo ba cách.
 */
final class ReportFormats
{
    public const LABELS = ['csv' => 'CSV', 'xlsx' => 'Excel', 'pdf' => 'PDF'];

    /**
     * Đoạn "đọc format từ query rồi giao file" mà cả ba màn hình xuất bảng đều cần.
     *
     * Định dạng mặc định khác nhau theo màn hình: trang báo cáo lấy Excel làm nút chính, còn
     * route export của điểm và điểm danh đã tồn tại từ trước nên phải tiếp tục trả CSV khi
     * người gọi không hỏi định dạng nào.
     *
     * @param  array{title: string, header: list<string>, rows: list<list<mixed>>}  $report
     */
    public static function download(Request $request, string $basename, array $report, string $default = 'csv'): Response
    {
        $format = (string) $request->query('format', $default);
        $exporter = self::make($format);
        abort_if($exporter === null, 404, 'Định dạng file không tồn tại.');

        return $exporter->export(self::filename($basename, $format), $report);
    }

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
