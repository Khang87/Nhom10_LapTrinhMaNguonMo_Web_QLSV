<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * PDF nộp trường. Dompdf chỉ render đúng tiếng Việt có dấu khi font nhúng phủ bảng Unicode,
 * nên dùng DejaVu Sans (font mặc định của dompdf) và khổ ngang cho bảng phổ điểm nhiều cột.
 */
final class PdfExporter implements ReportExporter
{
    /** Từ cột thứ bảy thì bảng dọc bắt đầu tràn lề, mà phổ điểm lại có tám chín cột. */
    private const LANDSCAPE_FROM = 7;

    public function export(string $filename, array $report): Response
    {
        $pdf = Pdf::loadView('reports.pdf', $report)
            ->setPaper(count($report['header']) >= self::LANDSCAPE_FROM ? 'a4-landscape' : 'a4');

        return $pdf->download($filename);
    }
}
