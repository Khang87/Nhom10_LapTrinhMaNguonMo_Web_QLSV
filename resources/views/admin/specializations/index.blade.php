@extends('layouts.spark')

@section('title', 'Quản lý Chuyên ngành')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Quản lý Chuyên ngành</h1>
            <p class="text-secondary small mb-0">Danh sách các chuyên ngành đào tạo trực thuộc từng khoa quản lý</p>
        </div>
        <div>
            <a href="{{ route('admin.specializations.create', $selectedFaculty ? ['faculty_id' => $selectedFaculty->id] : []) }}" 
               class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-2">
                <i class="bi bi-plus-lg"></i>
                <span>Thêm chuyên ngành</span>
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
                        <i class="bi bi-mortarboard-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Tổng số chuyên ngành</div>
                        <div class="fs-4 fw-bold text-dark">
                            <span id="statSpecializationCount">{{ $specializations->count() }}</span> 
                            <span class="fs-6 fw-normal text-muted">ngành</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border shadow-sm rounded-3 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-light text-primary d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Sinh viên theo học</div>
                        <div class="fs-4 fw-bold text-dark">
                            <span id="statStudentCount">{{ $specializations->sum('students_count') }}</span> 
                            <span class="fs-6 fw-normal text-muted">sinh viên</span>
                        </div>
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
                        <div class="text-secondary small fw-medium">Giảng viên phụ trách</div>
                        <div class="fs-4 fw-bold text-dark">
                            <span id="statLecturerCount">{{ $specializations->sum('lecturers_count') }}</span> 
                            <span class="fs-6 fw-normal text-muted">giảng viên</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                <span>Danh sách chuyên ngành</span>
                <span class="badge bg-light text-dark border">
                    <span id="headerListCount">{{ $specializations->count() }}</span> chuyên ngành
                </span>
                @if ($selectedFaculty)
                    <span class="badge bg-light text-primary border font-monospace">
                        <i class="bi bi-building me-1"></i>{{ $selectedFaculty->code }} - {{ $selectedFaculty->name }}
                    </span>
                @elseif ($selectedFacultyId === 'unassigned')
                    <span class="badge bg-light text-secondary border">
                        <i class="bi bi-dash-circle me-1"></i>Chưa phân khoa
                    </span>
                @endif
            </div>
            <div>
                <!-- Bộ lọc chọn Khoa & Tìm kiếm -->
                <form method="GET" action="{{ route('admin.specializations.index', [], false) }}" class="d-flex flex-wrap flex-md-nowrap align-items-center gap-2" id="facultyFilterForm">
                    <!-- Dropdown chọn khoa -->
                    <div class="input-group input-group-sm" style="width: auto; min-width: 220px;">
                        <span class="input-group-text bg-white text-muted">
                            <i class="bi bi-building"></i>
                        </span>
                        <select name="faculty_id" id="facultyFilterSelect" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">
                                -- Tất cả các Khoa ({{ $totalSpecializationsCount }}) --
                            </option>
                            @foreach ($faculties as $fac)
                                <option value="{{ $fac->id }}" {{ (string)$selectedFacultyId === (string)$fac->id ? 'selected' : '' }}>
                                    [{{ $fac->code }}] {{ $fac->name }} ({{ $fac->specializations_count }})
                                </option>
                            @endforeach
                            @if($unassignedCount > 0)
                                <option value="unassigned" {{ $selectedFacultyId === 'unassigned' ? 'selected' : '' }}>
                                    -- Chưa phân khoa ({{ $unassignedCount }}) --
                                </option>
                            @endif
                        </select>
                    </div>

                    <!-- Ô Tìm kiếm nhanh -->
                    <div class="input-group input-group-sm" style="width: auto; min-width: 180px;">
                        <span class="input-group-text bg-white text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" id="specializationSearchInput" class="form-control" 
                               placeholder="Tìm kiếm mã, tên..." value="{{ $search ?? '' }}" aria-label="Tìm kiếm" autocomplete="off">
                        @if(!empty($search))
                            <a href="{{ route('admin.specializations.index', array_filter(['faculty_id' => $selectedFacultyId]), false) }}" 
                               class="btn btn-outline-secondary" title="Xóa từ khóa tìm kiếm">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 text-nowrap" title="Lọc dữ liệu">
                        <i class="bi bi-funnel"></i>
                        <span>Lọc</span>
                    </button>

                    @if ($selectedFacultyId || !empty($search))
                        <a href="{{ route('admin.specializations.index', [], false) }}" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1 text-nowrap" title="Đặt lại bộ lọc và tìm kiếm">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Đặt lại</span>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="specializationTable">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="ps-3 text-center" style="width: 50px;">#</th>
                        <th style="width: 120px;">Mã ngành</th>
                        <th>Tên chuyên ngành</th>
                        <th>Khoa quản lý</th>
                        <th class="text-center" style="width: 110px;">Sinh viên</th>
                        <th class="text-center" style="width: 110px;">Giảng viên</th>
                        <th style="min-width: 250px;">Mô tả</th>
                        <th class="text-center pe-3" style="width: 150px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($specializations as $index => $spec)
                        <tr class="spec-row" data-faculty-id="{{ $spec->faculty_id }}">
                            <td class="ps-3 text-center text-muted small row-number">
                                {{ $index + 1 }}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">
                                    {{ $spec->code }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $spec->name }}</div>
                            </td>
                            <td>
                                @if($spec->faculty)
                                    <span class="badge bg-light text-primary border">
                                        <i class="bi bi-building me-1"></i>{{ $spec->faculty->name }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border">
                                        <i class="bi bi-dash-circle me-1"></i>Chưa phân khoa
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $spec->students_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $spec->lecturers_count }}</span>
                            </td>
                            <td>
                                <div class="text-secondary small text-truncate" style="max-width: 300px;" title="{{ $spec->description }}">
                                    {{ $spec->description ?: '—' }}
                                </div>
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.specializations.edit', $spec) }}" 
                                       class="btn btn-sm btn-light border" 
                                       title="Sửa chuyên ngành">
                                        <i class="bi bi-pencil"></i>
                                        <span>Sửa</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.specializations.destroy', $spec) }}" class="d-inline"
                                          onsubmit="return handleDeleteSpec(event, '{{ addslashes($spec->name) }}', {{ $spec->students_count }});">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-sm btn-light border" 
                                                title="Xóa chuyên ngành">
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
                                    <i class="bi bi-folder-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    @if ($selectedFaculty)
                                        <div class="fw-medium mb-2">
                                            Khoa <strong>{{ $selectedFaculty->name }}</strong> hiện chưa có chuyên ngành nào trực thuộc.
                                        </div>
                                        <a href="{{ route('admin.specializations.create', ['faculty_id' => $selectedFaculty->id]) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus-lg me-1"></i> Thêm chuyên ngành cho khoa này
                                        </a>
                                    @elseif ($selectedFacultyId === 'unassigned')
                                        <div class="fw-medium">Không có chuyên ngành nào chưa phân khoa.</div>
                                    @else
                                        <div class="fw-medium">Chưa có chuyên ngành nào được tạo trong hệ thống.</div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    <tr id="noSearchResultRow" class="d-none">
                        <td colspan="8" class="text-center py-4 text-muted">
                            Không tìm thấy chuyên ngành nào phù hợp với từ khóa tìm kiếm.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2.5 border-top text-muted small d-flex justify-content-between align-items-center">
            <span>Hiển thị: <strong>{{ $specializations->count() }}</strong> chuyên ngành @if($selectedFaculty) (thuộc khoa {{ $selectedFaculty->name }}) @endif</span>
            <span class="text-secondary fst-italic">Quản lý môn học theo kỳ tại mục <strong>Môn học & Khung CT</strong></span>
        </div>
    </div>
</div>

<script>
    function handleDeleteSpec(event, name, studentCount) {
        if (studentCount > 0) {
            alert('Không thể xóa chuyên ngành [' + name + '] vì hiện đang có ' + studentCount + ' sinh viên đang theo học.\n\nĐể bảo đảm an toàn dữ liệu, vui lòng chuyển hoặc xóa sinh viên trước khi xóa chuyên ngành này!');
            return false;
        }
        return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn chuyên ngành [' + name + '] không?');
    }

    // Live search client-side
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('specializationSearchInput');
        const rows = document.querySelectorAll('.spec-row');
        const noResultRow = document.getElementById('noSearchResultRow');
        const headerCountBadge = document.getElementById('headerListCount');

        if (searchInput && rows.length > 0) {
            function performFilter() {
                const keyword = searchInput.value.trim().toLowerCase();
                let visibleCount = 0;

                rows.forEach(function (row) {
                    const text = row.textContent.toLowerCase();
                    const rowNumberCell = row.querySelector('.row-number');

                    if (text.includes(keyword)) {
                        row.style.display = '';
                        visibleCount++;
                        if (rowNumberCell) {
                            rowNumberCell.textContent = visibleCount;
                        }
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (noResultRow) {
                    noResultRow.classList.toggle('d-none', visibleCount !== 0);
                }

                if (headerCountBadge) {
                    headerCountBadge.textContent = visibleCount;
                }
            }

            searchInput.addEventListener('input', performFilter);

            // Nhấn phím Escape để xóa trắng tìm kiếm
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    performFilter();
                }
            });
        }
    });
</script>
@endsection
