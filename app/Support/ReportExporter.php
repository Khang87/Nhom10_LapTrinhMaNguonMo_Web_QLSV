<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Response;

/**
 * Ba định dạng file nộp cho trường đọc cùng một bảng số liệu mà Reports sinh ra. Controller
 * chỉ biết interface này, nên thêm định dạng mới không đụng tới màn hình thống kê.
 */
interface ReportExporter
{
    /**
     * @param  array{title: string, header: list<string>, rows: list<list<mixed>>}  $report
     */
    public function export(string $filename, array $report): Response;
}
