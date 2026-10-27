<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 18mm 12mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; color: #111827; }
        h1 { font-size: 13pt; margin: 0 0 2mm; }
        p.meta { font-size: 8pt; color: #4b5563; margin: 0 0 4mm; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.4pt solid #9ca3af; padding: 1.6mm 1.4mm; text-align: left; }
        th { background: #eef2f7; font-weight: bold; }
        tbody tr:nth-child(even) td { background: #f7f9fc; }
        tfoot td { font-size: 7.5pt; color: #6b7280; border: none; padding-top: 3mm; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">Trường Đại học Sư phạm Kỹ thuật TP.HCM · Xuất lúc {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                @foreach ($header as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ is_float($cell) ? number_format($cell, 2, ',', '.') : $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($header) }}">Không có dữ liệu cho bộ lọc đã chọn.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ count($header) }}">Tổng: {{ count($rows) }} dòng. Số liệu tính trực tiếp từ dữ liệu đào tạo của hệ thống.</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
