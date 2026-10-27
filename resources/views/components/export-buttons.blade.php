@props(['route', 'query' => []])

{{-- Ba nút tải cùng một bảng số theo ba định dạng; màn hình chỉ lo route, không lo file dựng thế nào. --}}
<a class="btn btn-custom btn-custom-outline-primary" href="{{ route($route, array_merge($query, ['format' => 'xlsx'])) }}">
    <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Xuất Excel
</a>
<a class="btn btn-custom btn-custom-outline-primary" href="{{ route($route, array_merge($query, ['format' => 'pdf'])) }}">
    <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Xuất PDF
</a>
<a class="btn btn-custom btn-custom-light" href="{{ route($route, array_merge($query, ['format' => 'csv'])) }}">
    <i class="bi bi-download me-1" aria-hidden="true"></i>Tải CSV
</a>
