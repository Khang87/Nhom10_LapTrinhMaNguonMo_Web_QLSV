@extends('layouts.spark')

@section('title', 'Quản lý Giảng viên')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Quản lý Giảng viên</h1>
            <p class="text-secondary small mb-0">Hồ sơ giảng viên, học vị, phân công theo khoa và chuyên ngành đào tạo</p>
        </div>
        <div>
            <a href="{{ route('admin.lecturers.create') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-2">
                <i class="bi bi-plus-lg"></i>
                <span>Thêm giảng viên mới</span>
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
                        <i class="bi bi-person-video3 fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Tổng số giảng viên</div>
                        <div class="fs-4 fw-bold text-dark">{{ $totalLecturers }} <span class="fs-6 fw-normal text-muted">người</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border shadow-sm rounded-3 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-light text-primary d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                        <i class="bi bi-award fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Tiến sĩ / Học hàm cao</div>
                        <div class="fs-4 fw-bold text-dark">{{ $totalPhd }} <span class="fs-6 fw-normal text-muted">tiến sĩ</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border shadow-sm rounded-3 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-light text-primary d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                        <i class="bi bi-building fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Khoa phụ trách</div>
                        <div class="fs-4 fw-bold text-dark">{{ $faculties->count() }} <span class="fs-6 fw-normal text-muted">khoa</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.lecturers.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Khoa phụ trách</label>
                    <select name="faculty_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Tất cả Khoa --</option>
                        @foreach ($faculties as $fac)
                            <option value="{{ $fac->id }}" {{ request('faculty_id') == $fac->id ? 'selected' : '' }}>
                                [{{ $fac->code }}] {{ $fac->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Chuyên ngành</label>
                    <select name="specialization_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Tất cả Chuyên ngành --</option>
                        @foreach ($specializations as $spec)
                            <option value="{{ $spec->id }}" {{ request('specialization_id') == $spec->id ? 'selected' : '' }}>
                                [{{ $spec->code }}] {{ $spec->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary mb-1">Từ khóa tìm kiếm</label>
                    <input type="text" name="keyword" class="form-control form-select-sm" 
                           value="{{ request('keyword') }}" placeholder="Mã GV, họ tên, email, số điện thoại...">
                </div>
                <div class="col-12 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-dark w-100">
                        <i class="bi bi-funnel"></i> Lọc
                    </button>
                    @if (request()->hasAny(['faculty_id', 'specialization_id', 'keyword']))
                        <a href="{{ route('admin.lecturers.index') }}" class="btn btn-sm btn-light border" title="Xóa lọc">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="fw-bold text-dark">
                Danh sách Giảng viên ({{ $lecturers->total() }})
            </div>
            <div class="small text-muted">
                Hiển thị trang {{ $lecturers->currentPage() }} / {{ $lecturers->lastPage() }}
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="ps-3 text-center" style="width: 50px;">#</th>
                        <th style="width: 110px;">Mã GV</th>
                        <th>Họ và tên</th>
                        <th style="width: 120px;">Học vị</th>
                        <th>Khoa phụ trách</th>
                        <th>Chuyên ngành</th>
                        <th style="min-width: 150px;">Lớp danh nghĩa (CVHT)</th>
                        <th>Liên hệ</th>
                        <th class="text-center pe-3" style="width: 140px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lecturers as $index => $lec)
                        <tr>
                            <td class="ps-3 text-center text-muted small">
                                {{ $lecturers->firstItem() + $index }}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">
                                    {{ $lec->lecturer_code }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">
                                    {{ $lec->full_name }}
                                    @if ($lec->headedFaculty)
                                        <span class="badge bg-light text-dark border ms-1 small">
                                            <i class="bi bi-star-fill text-warning me-1"></i>Trưởng khoa {{ $lec->headedFaculty->code }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="small">{{ $lec->degree }}</span>
                            </td>
                            <td>
                                <span class="text-secondary small">{{ $lec->faculty?->name ?: $lec->faculty_name }}</span>
                            </td>
                            <td>
                                <span class="text-secondary small">{{ $lec->specialization?->name ?: '—' }}</span>
                            </td>
                            <td>
                                @if ($lec->homeroomClasses->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($lec->homeroomClasses as $cls)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" title="{{ $cls->class_name }}">
                                                <i class="bi bi-people-fill me-1"></i>{{ $cls->class_code }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small fst-italic">Chưa phân công</span>
                                @endif
                            </td>
                            <td>
                                <div class="small text-secondary">
                                    @if($lec->email)
                                        <div><i class="bi bi-envelope"></i> {{ $lec->email }}</div>
                                    @endif
                                    @if($lec->phone)
                                        <div><i class="bi bi-telephone"></i> {{ $lec->phone }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.lecturers.edit', $lec) }}" 
                                       class="btn btn-sm btn-light border" 
                                       title="Sửa thông tin giảng viên">
                                        <i class="bi bi-pencil"></i>
                                        <span>Sửa</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.lecturers.destroy', $lec) }}" class="d-inline"
                                          onsubmit="return handleDeleteLecturer(event, '{{ addslashes($lec->full_name) }}', {{ $lec->headedFaculty ? 1 : 0 }});">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-sm btn-light border" 
                                                title="Xóa giảng viên">
                                            <i class="bi bi-trash"></i>
                                            <span>Xóa</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="py-3">
                                    <i class="bi bi-people fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    Không tìm thấy giảng viên nào phù hợp với bộ lọc.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($lecturers->hasPages())
            <div class="card-footer bg-white py-2.5 border-top d-flex justify-content-center">
                {{ $lecturers->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    function handleDeleteLecturer(event, name, isDean) {
        if (isDean) {
            alert('Không thể xóa giảng viên [' + name + '] vì đang giữ chức vụ Trưởng khoa.\n\nĐể bảo đảm an toàn dữ liệu, vui lòng chỉ định Trưởng khoa khác trước khi xóa giảng viên này!');
            return false;
        }
        return confirm('Bạn có chắc chắn muốn xóa giảng viên [' + name + '] không?');
    }
</script>
@endsection
