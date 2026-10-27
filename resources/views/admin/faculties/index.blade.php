@extends('layouts.spark')

@section('title', 'Quản lý Khoa phụ trách')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Quản lý Khoa phụ trách</h1>
            <p class="text-secondary small mb-0">Quản lý danh mục các khoa, thông tin trưởng khoa và các chuyên ngành trực thuộc</p>
        </div>
        <div>
            <a href="{{ route('admin.faculties.create') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-2">
                <i class="bi bi-plus-lg"></i>
                <span>Thêm khoa mới</span>
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-check-circle"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Bảng Thống kê tổng quan -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border shadow-sm rounded-3 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-light text-primary d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                        <i class="bi bi-building fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Tổng số khoa</div>
                        <div class="fs-4 fw-bold text-dark">{{ $faculties->count() }} <span class="fs-6 fw-normal text-muted">khoa</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border shadow-sm rounded-3 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-light text-primary d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                        <i class="bi bi-diagram-3-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Chuyên ngành đào tạo</div>
                        <div class="fs-4 fw-bold text-dark">{{ $faculties->sum('specializations_count') }} <span class="fs-6 fw-normal text-muted">ngành</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border shadow-sm rounded-3 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-light text-primary d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                        <i class="bi bi-person-video3 fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Giảng viên trực thuộc</div>
                        <div class="fs-4 fw-bold text-dark">{{ $faculties->sum('lecturers_count') }} <span class="fs-6 fw-normal text-muted">giảng viên</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="fw-bold text-dark">
                Danh sách các khoa ({{ $faculties->count() }})
            </div>
            <div style="max-width: 300px; width: 100%;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="facultySearchInput" class="form-control" 
                           placeholder="Tìm kiếm mã, tên khoa..." aria-label="Tìm kiếm">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="facultyTable">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="ps-3 text-center" style="width: 50px;">#</th>
                        <th style="width: 110px;">Mã khoa</th>
                        <th style="min-width: 220px;">Tên khoa</th>
                        <th style="min-width: 220px;">Trưởng khoa</th>
                        <th class="text-center" style="width: 120px;">Chuyên ngành</th>
                        <th class="text-center" style="width: 110px;">Giảng viên</th>
                        <th style="min-width: 200px;">Văn phòng / Liên hệ</th>
                        <th class="text-center pe-3" style="width: 170px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($faculties as $index => $fac)
                        <tr class="faculty-row">
                            <td class="ps-3 text-center text-muted small">
                                {{ $index + 1 }}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">
                                    {{ $fac->code }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $fac->name }}</div>
                            </td>
                            <td>
                                @if ($fac->dean)
                                    <div>
                                        <div class="fw-semibold text-dark">
                                            {{ $fac->dean->degree }} {{ $fac->dean->full_name }}
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-envelope"></i> {{ $fac->dean->email ?: 'Chưa có email' }}
                                        </div>
                                    </div>
                                @else
                                    <span class="badge bg-light text-muted border">Chưa bổ nhiệm</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $fac->specializations_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $fac->lecturers_count }}</span>
                            </td>
                            <td>
                                <div class="small text-secondary">
                                    @if($fac->office_location)
                                        <div><i class="bi bi-geo-alt"></i> {{ $fac->office_location }}</div>
                                    @endif
                                    @if($fac->phone)
                                        <div><i class="bi bi-telephone"></i> {{ $fac->phone }}</div>
                                    @endif
                                    @if(!$fac->office_location && !$fac->phone)
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.faculties.show', $fac) }}" 
                                       class="btn btn-sm btn-light border" 
                                       title="Xem chi tiết khoa">
                                        <i class="bi bi-eye"></i>
                                        <span>Xem</span>
                                    </a>
                                    <a href="{{ route('admin.faculties.edit', $fac) }}" 
                                       class="btn btn-sm btn-light border" 
                                       title="Sửa khoa">
                                        <i class="bi bi-pencil"></i>
                                        <span>Sửa</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.faculties.destroy', $fac) }}" class="d-inline"
                                          onsubmit="return handleDeleteFaculty(event, '{{ addslashes($fac->name) }}', {{ $fac->specializations_count }}, {{ $fac->lecturers_count }});">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-sm btn-light border" 
                                                title="Xóa khoa">
                                            <i class="bi bi-trash"></i>
                                            <span>Xóa</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyTableRow">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="py-3">
                                    <i class="bi bi-building fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    Chưa có khoa nào trong hệ thống.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    <tr id="noSearchResultRow" style="display: none;">
                        <td colspan="8" class="text-center py-4 text-muted">
                            Không tìm thấy khoa nào phù hợp với từ khóa tìm kiếm.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2.5 border-top text-muted small d-flex justify-content-between align-items-center">
            <span>Tổng số: <strong>{{ $faculties->count() }}</strong> khoa</span>
            <span class="text-secondary fst-italic">Cấu trúc phân cấp: <strong>Khoa</strong> ➔ <strong>Chuyên ngành</strong> ➔ <strong>Học phần (Khung CT)</strong></span>
        </div>
    </div>
</div>

<script>
    function handleDeleteFaculty(event, name, specCount, lecCount) {
        if (specCount > 0 || lecCount > 0) {
            let msg = 'Khoa [' + name + '] hiện đang có:\n';
            if (specCount > 0) msg += '• ' + specCount + ' chuyên ngành trực thuộc\n';
            if (lecCount > 0) msg += '• ' + lecCount + ' giảng viên thuộc khoa\n';
            msg += '\nBạn có chắc chắn muốn xóa khoa này không?\n(Toàn bộ chuyên ngành và giảng viên của khoa sẽ được tự động giải phóng về trạng thái chưa phân khoa).';
            return confirm(msg);
        }
        return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn khoa [' + name + '] không?');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('facultySearchInput');
        const rows = document.querySelectorAll('.faculty-row');
        const noResultRow = document.getElementById('noSearchResultRow');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const keyword = this.value.trim().toLowerCase();
                let visibleCount = 0;

                rows.forEach(function (row) {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(keyword)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (noResultRow) {
                    noResultRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
                }
            });
        }
    });
</script>
@endsection
