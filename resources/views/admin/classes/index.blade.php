@extends('layouts.spark')

@section('title', 'Quản lý Lớp học Danh nghĩa & Giảng viên phụ trách')

@section('content')
<div class="container-fluid px-0">
    <!-- Header: Đơn giản, gọn gàng (Đã bỏ 2 button Thêm khóa và Thêm lớp theo yêu cầu) -->
    <div class="mb-4">
        <h1 class="h4 fw-bold text-dark mb-1">Quản lý Lớp học Danh nghĩa & Giảng viên phụ trách</h1>
        <p class="text-secondary small mb-0">Quản lý hệ thống lớp danh nghĩa theo khóa tuyển sinh, phân công cố vấn học tập và theo dõi danh sách sinh viên</p>
    </div>

    @if (session('success'))
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check2-circle text-success fs-5"></i>
            <div class="text-dark small fw-medium">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger-subtle border border-danger-subtle text-danger d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div class="small fw-medium">{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (isset($errors) && $errors->any())
        <div class="alert alert-danger-subtle border border-danger-subtle text-danger mb-4 shadow-sm p-3 rounded-3" role="alert">
            <div class="d-flex align-items-center gap-2 mb-1 fw-bold small">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span>Vui lòng kiểm tra lại thông tin:</span>
            </div>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- THANH ĐIỀU HƯỚNG & TÌM KIẾM (SPARK STYLE TƯƠNG TỰ TRANG QUẢN LÝ ĐIỂM) -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <!-- Breadcrumb phân cấp trực quan: Khoa -> Chuyên ngành -> Khóa -> Lớp -> Sinh viên -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 align-items-center small">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.classes.index') }}" class="text-decoration-none fw-semibold {{ !$selectedFacultyId && !$search ? 'text-dark' : 'text-secondary' }}">
                                <i class="bi bi-building me-1"></i>Tất cả Khoa
                            </a>
                        </li>
                        @if ($selectedFaculty)
                            <li class="breadcrumb-item {{ !$selectedCohort ? 'active fw-bold text-dark' : '' }}">
                                @if ($selectedCohort)
                                    <a href="{{ route('admin.classes.index', ['faculty_id' => $selectedFaculty->id]) }}" class="text-decoration-none text-secondary">
                                        {{ $selectedFaculty->name }}
                                    </a>
                                @else
                                    {{ $selectedFaculty->name }}
                                @endif
                            </li>
                        @endif
                        @if ($selectedCohort)
                            <li class="breadcrumb-item {{ !$selectedSpecialization ? 'active fw-bold text-dark' : '' }}">
                                @if ($selectedSpecialization)
                                    <a href="{{ route('admin.classes.index', ['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort]) }}" class="text-decoration-none text-secondary">
                                        Khóa {{ $selectedCohort }}
                                    </a>
                                @else
                                    Khóa {{ $selectedCohort }}
                                @endif
                            </li>
                        @endif
                        @if ($selectedSpecialization)
                            <li class="breadcrumb-item {{ !$selectedClass ? 'active fw-bold text-dark' : '' }}">
                                @if ($selectedClass)
                                    <a href="{{ route('admin.classes.index', ['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort, 'specialization_id' => $selectedSpecialization->id]) }}" class="text-decoration-none text-secondary">
                                        Ngành {{ $selectedSpecialization->name }}
                                    </a>
                                @else
                                    Ngành {{ $selectedSpecialization->name }}
                                @endif
                            </li>
                        @endif
                        @if ($selectedClass)
                            <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">
                                Lớp {{ $selectedClass->class_code }} ({{ $selectedClass->class_name }})
                            </li>
                        @endif
                        @if ($search)
                            <li class="breadcrumb-item active text-dark" aria-current="page">
                                Tìm kiếm: "{{ $search }}"
                            </li>
                        @endif
                    </ol>
                </nav>

                <!-- Ô tìm kiếm & Nút đặt lại -->
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <form method="GET" action="{{ route('admin.classes.index') }}" class="d-flex gap-1">
                        @if ($selectedFacultyId)
                            <input type="hidden" name="faculty_id" value="{{ $selectedFacultyId }}">
                        @endif
                        @if ($selectedCohort)
                            <input type="hidden" name="cohort" value="{{ $selectedCohort }}">
                        @endif
                        @if ($selectedSpecId)
                            <input type="hidden" name="specialization_id" value="{{ $selectedSpecId }}">
                        @endif
                        @if ($selectedClass)
                            <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
                        @endif
                        <div class="input-group input-group-sm" style="min-width: 280px;">
                            <input type="text" name="search" class="form-control py-1.5" 
                                   value="{{ $search }}" placeholder="Tìm mã lớp, tên lớp, GV, MSSV...">
                            @if ($search)
                                <a href="{{ route('admin.classes.index', array_filter(['faculty_id' => $selectedFacultyId, 'specialization_id' => $selectedSpecId, 'cohort' => $selectedCohort, 'class_id' => $selectedClass?->id])) }}" 
                                   class="btn btn-outline-secondary d-flex align-items-center" title="Xóa từ khóa tìm kiếm">
                                    <i class="bi bi-x"></i>
                                </a>
                            @endif
                            <button type="submit" class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1" title="Tìm kiếm">
                                <i class="bi bi-search"></i>
                                <span>Tìm</span>
                            </button>
                        </div>
                    </form>

                    @if ($selectedFacultyId || $selectedSpecId || $selectedCohort || $selectedClass || $search)
                        <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary btn-sm py-1.5 px-3 d-inline-flex align-items-center gap-1 shadow-sm" title="Đặt lại về ban đầu">
                            <i class="bi bi-arrow-clockwise"></i>
                            <span class="d-none d-sm-inline">Đặt lại</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- LEVEL 5: KHI ĐANG XEM DANH SÁCH SINH VIÊN CỦA 1 LỚP CỤ THỂ --}}
    @if ($selectedClass)
        <div class="card border shadow-sm rounded-3 bg-white mb-4">
            <!-- Header tóm tắt lớp danh nghĩa: gọn, thanh lịch -->
            <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('admin.classes.index', array_filter(['faculty_id' => $selectedFacultyId, 'specialization_id' => $selectedSpecId, 'cohort' => $selectedCohort])) }}" 
                       class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm"
                       title="Quay lại danh sách lớp thuộc Khóa {{ $selectedCohort }}">
                        <i class="bi bi-arrow-left"></i>
                        <span>Quay lại các lớp (Khóa {{ $selectedCohort }})</span>
                    </a>
                    <span class="text-secondary small d-none d-md-inline">|</span>
                    <span class="badge bg-dark text-white font-monospace fs-6 px-2.5 py-1">
                        {{ $selectedClass->class_code }}
                    </span>
                    <strong class="text-dark fs-6">{{ $selectedClass->class_name }}</strong>
                    <span class="badge bg-light text-secondary border small">
                        Khóa {{ $selectedClass->cohort_year ?? $selectedCohort }}
                    </span>
                    @if ($selectedClass->room)
                        <span class="badge bg-light text-dark border small">
                            <i class="bi bi-geo-alt me-1"></i>Phòng: {{ $selectedClass->room }}
                        </span>
                    @endif
                    <span class="badge bg-light text-dark border small">
                        <i class="bi bi-journal-code me-1"></i>{{ $selectedClass->specialization?->name ?? 'CNTT' }}
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1 shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#editClassModal_{{ $selectedClass->id }}">
                        <i class="bi bi-pencil-square"></i>
                        <span>Sửa thông tin lớp</span>
                    </button>
                    <button type="button" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="bi bi-person-plus-fill"></i>
                        <span>Thêm sinh viên vào lớp</span>
                    </button>
                </div>
            </div>

            <!-- Dòng tóm tắt Cố vấn học tập & Sĩ số: Tinh gọn trên 1 dòng -->
            <div class="card-body p-3 bg-light border-bottom">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width: 38px; height: 38px;">
                            <i class="bi bi-person-badge fs-5"></i>
                        </div>
                        <div>
                            <div class="small text-secondary fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.3px;">
                                Cố vấn học tập (CVHT)
                            </div>
                            @if ($selectedClass->lecturer)
                                <div class="fw-bold text-dark small">
                                    {{ $selectedClass->lecturer->full_name }}
                                    <span class="badge bg-light text-dark border small fw-normal ms-1">{{ $selectedClass->lecturer->degree }}</span>
                                    <span class="text-secondary fw-normal ms-2 small"><i class="bi bi-envelope me-1"></i>{{ $selectedClass->lecturer->email }}</span>
                                </div>
                            @else
                                <div class="text-danger small fw-semibold">
                                    <i class="bi bi-exclamation-circle me-1"></i>Chưa phân công Giảng viên phụ trách
                                </div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-outline-dark btn-sm py-1 px-2.5 ms-2 shadow-sm"
                                data-bs-toggle="modal" data-bs-target="#assignLecturerModal_{{ $selectedClass->id }}">
                            <i class="bi bi-arrow-repeat"></i> {{ $selectedClass->lecturer ? 'Đổi CVHT' : 'Phân công GV' }}
                        </button>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary small fw-medium">Tổng sĩ số lớp:</span>
                        <span class="badge bg-dark text-white font-monospace fs-6 px-2.5 py-1">
                            {{ $classStudents ? $classStudents->total() : 0 }} sinh viên
                        </span>
                    </div>
                </div>
            </div>

            <!-- BẢNG DANH SÁCH SINH VIÊN TRONG LỚP -->
            @if ($classStudents && $classStudents->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small">
                            <tr>
                                <th class="ps-3 text-center" style="width: 50px;">#</th>
                                <th style="width: 140px;">Mã SV (MSSV)</th>
                                <th>Họ và tên sinh viên</th>
                                <th style="width: 90px;" class="text-center">Giới tính</th>
                                <th style="width: 110px;">Ngày sinh</th>
                                <th>Email liên hệ</th>
                                <th style="width: 120px;">Điện thoại</th>
                                <th style="width: 120px;" class="text-center">Trạng thái</th>
                                <th class="text-end pe-3" style="width: 220px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classStudents as $index => $stu)
                                <tr>
                                    <td class="ps-3 text-center text-muted small">
                                        {{ $classStudents->firstItem() + $index }}
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace fw-bold">
                                            {{ $stu->student_code }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-light text-dark border rounded-circle d-flex align-items-center justify-content-center fw-semibold small flex-shrink-0"
                                                 style="width: 32px; height: 32px;">
                                                {{ mb_substr($stu->full_name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark">{{ $stu->full_name }}</div>
                                                <div class="small text-secondary" style="font-size: 0.78rem;">
                                                    {{ $stu->specialization?->name ?? $stu->specialization_name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center small text-secondary">
                                        {{ $stu->gender }}
                                    </td>
                                    <td class="small text-secondary">
                                        {{ $stu->dob ? \Carbon\Carbon::parse($stu->dob)->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="small text-secondary font-monospace">
                                        {{ $stu->email }}
                                    </td>
                                    <td class="small text-secondary font-monospace">
                                        {{ $stu->phone ?: '—' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-success border border-success-subtle small">
                                            {{ $stu->study_status ?? 'Đang học' }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-inline-flex align-items-center gap-1.5">
                                            <!-- Sửa thông tin SV: Dẫn trực tiếp đến trang Sửa sinh viên đầy đủ của phân hệ Quản lý SV để đồng bộ 100% -->
                                            <a href="{{ route('admin.students.edit', $stu) }}" 
                                               class="btn btn-sm btn-outline-dark px-2.5 py-1 shadow-sm" 
                                               title="Sửa hồ sơ sinh viên đầy đủ (Đồng bộ phân hệ Quản lý sinh viên)">
                                                <i class="bi bi-pencil"></i>
                                                <span class="d-none d-md-inline ms-1">Sửa</span>
                                            </a>

                                            <!-- Chuyển lớp nhanh -->
                                            <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-1 shadow-sm"
                                                    data-bs-toggle="modal" data-bs-target="#transferStudentModal_{{ $stu->id }}"
                                                    title="Chuyển sinh viên sang lớp danh nghĩa khác">
                                                <i class="bi bi-arrow-left-right"></i>
                                            </button>

                                            <!-- Xem bảng điểm -->
                                            <a href="{{ route('admin.grades.index', ['faculty_id' => $selectedFacultyId, 'specialization_id' => $selectedSpecId, 'cohort' => $selectedClass->cohort_year, 'class_code' => $selectedClass->class_code, 'student_id' => $stu->id]) }}" 
                                               class="btn btn-sm btn-outline-secondary px-2 py-1 shadow-sm"
                                               title="Xem bảng điểm học tập theo kỳ">
                                                <i class="bi bi-card-checklist"></i>
                                            </a>

                                            <!-- Rút sinh viên ra khỏi lớp -->
                                            <form method="POST" action="{{ route('admin.classes.students.remove', [$selectedClass, $stu]) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Bạn có chắc chắn muốn rút sinh viên [{{ $stu->full_name }}] ra khỏi lớp {{ $selectedClass->class_code }}? Sinh viên sẽ chuyển sang trạng thái chưa có lớp.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1 shadow-sm" title="Rút khỏi lớp danh nghĩa">
                                                    <i class="bi bi-person-dash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($classStudents->hasPages())
                    <div class="p-3 border-top bg-white d-flex justify-content-end">
                        {{ $classStudents->links() }}
                    </div>
                @endif
            @else
                <div class="p-5 text-center bg-white rounded-bottom">
                    <i class="bi bi-people fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">Chưa có sinh viên nào trong lớp này</h5>
                    <p class="text-secondary small mb-3">
                        Lớp <strong>{{ $selectedClass->class_code }}</strong> (Khóa {{ $selectedCohort }}) hiện chưa có sinh viên nào.
                    </p>
                    <button type="button" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="bi bi-person-plus-fill"></i>
                        <span>Thêm sinh viên vào lớp ngay</span>
                    </button>
                </div>
            @endif
        </div>

    {{-- LEVEL 4: KHI ĐÃ CHỌN KHOA, KHÓA VÀ CHUYÊN NGÀNH -> HIỆN CÁC LỚP DANH NGHĨA --}}
    @elseif ($selectedCohort && $selectedSpecId)
        <div class="card border shadow-sm rounded-3 bg-white mb-4">
            <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('admin.classes.index', array_filter(['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort])) }}" 
                       class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm" 
                       title="Quay lại danh sách các Chuyên ngành">
                        <i class="bi bi-arrow-left"></i>
                        <span>Quay lại các Chuyên ngành</span>
                    </a>
                    <span class="text-secondary small d-none d-md-inline">|</span>
                    <strong class="text-dark fs-6">
                        Khóa {{ $selectedCohort }}
                        @php
                            $y = (int) $selectedCohort;
                            $k = $y >= 2010 ? ($y - 2009) : null;
                        @endphp
                        {{ $k ? "- Niên khóa K{$k}" : '' }}
                    </strong>
                    <span class="badge bg-dark text-white font-monospace small">
                        Ngành: {{ $selectedSpecialization->name ?? '' }}
                    </span>
                    <span class="badge bg-light text-dark border font-monospace">
                        {{ $classes->count() }} Lớp danh nghĩa
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#addClassModal">
                        <i class="bi bi-plus-circle"></i>
                        <span>Thêm lớp danh nghĩa</span>
                    </button>
                </div>
            </div>

            <!-- Lưới thẻ lớp: Tinh gọn, cân đối, chống tràn và dãn quá khổ trên màn hình lớn -->
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xxl-4">
                    @forelse ($classes as $cls)
                        <div class="col">
                            <div class="card border rounded-3 h-100 bg-white shadow-sm hover-shadow transition-all d-flex flex-column justify-content-between" style="border-color: #e5e7eb !important;">
                                <div class="card-body p-3.5 pb-2">
                                    <!-- Header thẻ lớp -->
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <span class="badge bg-dark text-white font-monospace fs-6 px-2.5 py-1 rounded-2">
                                            {{ $cls->class_code }}
                                        </span>
                                        <span class="badge bg-light text-secondary border small font-monospace">
                                            <i class="bi bi-people me-1"></i>{{ $cls->students_count }} SV
                                        </span>
                                    </div>

                                    <!-- Tên lớp -->
                                    <h6 class="fw-bold text-dark mb-2 text-truncate" title="{{ $cls->class_name }}">
                                        {{ $cls->class_name }}
                                    </h6>

                                    <!-- Thông tin chuyên ngành & phòng học -->
                                    <div class="d-flex flex-column gap-1 mb-2.5">
                                        <div class="small text-secondary text-truncate" title="{{ $cls->specialization?->name ?? 'Công nghệ Thông tin' }}">
                                            <i class="bi bi-journal-code text-muted me-1.5"></i>
                                            <span class="text-dark">{{ $cls->specialization?->name ?? 'Công nghệ Thông tin' }}</span>
                                        </div>
                                        <div class="small text-secondary">
                                            <i class="bi bi-geo-alt text-muted me-1.5"></i>
                                            <span>Phòng: <strong class="text-dark">{{ $cls->room ?: 'Chưa cập nhật' }}</strong></span>
                                        </div>
                                    </div>

                                    <!-- Khối Cố vấn học tập (CVHT) gọn gàng -->
                                    <div class="p-2.5 bg-light rounded-3 border border-light-subtle mb-1">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="small fw-bold text-secondary text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                                Cố vấn học tập (CVHT)
                                            </span>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-dark small"
                                                    data-bs-toggle="modal" data-bs-target="#assignLecturerModal_{{ $cls->id }}">
                                                <i class="bi bi-pencil-square"></i> {{ $cls->lecturer ? 'Đổi' : 'Gán GV' }}
                                            </button>
                                        </div>
                                        @if ($cls->lecturer)
                                            <div class="fw-semibold text-dark small text-truncate">
                                                <i class="bi bi-person-check-fill text-success me-1"></i>
                                                {{ $cls->lecturer->full_name }}
                                                <span class="text-muted fw-normal" style="font-size: 0.78rem;">({{ $cls->lecturer->degree }})</span>
                                            </div>
                                            <div class="small text-muted text-truncate" style="font-size: 0.78rem;">
                                                <i class="bi bi-envelope me-1"></i>{{ $cls->lecturer->email }}
                                            </div>
                                        @else
                                            <div class="text-danger small" style="font-size: 0.8rem;">
                                                <i class="bi bi-exclamation-circle me-1"></i>Chưa phân công giảng viên
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Thanh thao tác ở chân thẻ -->
                                <div class="card-footer bg-white border-top p-2.5 pt-2">
                                    <div class="d-flex align-items-center gap-1.5">
                                        <a href="{{ route('admin.classes.index', array_filter(['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort, 'specialization_id' => $selectedSpecId, 'class_id' => $cls->id])) }}" 
                                           class="btn btn-sm btn-dark flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm py-1.5">
                                            <i class="bi bi-people"></i>
                                            <span>Xem sinh viên</span>
                                            <i class="bi bi-arrow-right ms-auto"></i>
                                        </a>

                                        <button type="button" class="btn btn-sm btn-outline-secondary py-1.5 px-2.5 shadow-sm"
                                                data-bs-toggle="modal" data-bs-target="#editClassModal_{{ $cls->id }}" title="Chỉnh sửa thông tin lớp">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <form method="POST" action="{{ route('admin.classes.destroy', $cls) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Bạn có chắc muốn xóa lớp [{{ $cls->class_code }}]? Các sinh viên thuộc lớp này sẽ chuyển về trạng thái chưa xếp lớp.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1.5 px-2.5 shadow-sm" title="Xóa lớp">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted small">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Chưa có lớp danh nghĩa nào trong Khóa {{ $selectedCohort }} thuộc chuyên ngành {{ $selectedSpecialization->name ?? '' }}.
                            <div class="mt-3">
                                <button type="button" class="btn btn-dark btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addClassModal">
                                    <i class="bi bi-plus-circle me-1"></i>Thêm lớp danh nghĩa đầu tiên vào Khóa {{ $selectedCohort }}
                                </button>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    {{-- LEVEL 3: KHI ĐÃ CHỌN KHOA VÀ KHÓA -> HIỆN DANH SÁCH CÁC CHUYÊN NGÀNH --}}
    @elseif ($selectedFacultyId && $selectedCohort)
        <div class="card border shadow-sm rounded-3 bg-white mb-4">
            <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('admin.classes.index', array_filter(['faculty_id' => $selectedFacultyId])) }}" 
                       class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm" 
                       title="Quay lại danh sách các Khóa tuyển sinh">
                        <i class="bi bi-arrow-left"></i>
                        <span>Quay lại các Khóa</span>
                    </a>
                    <span class="text-secondary small d-none d-md-inline">|</span>
                    <strong class="text-dark fs-6">
                        Khóa {{ $selectedCohort }}
                        @php
                            $y = (int) $selectedCohort;
                            $k = $y >= 2010 ? ($y - 2009) : null;
                        @endphp
                        {{ $k ? "- Niên khóa K{$k}" : '' }}
                    </strong>
                    <span class="badge bg-dark text-white font-monospace small">
                        Khoa: {{ $selectedFaculty->name ?? '' }}
                    </span>
                    <span class="badge bg-light text-dark border font-monospace">
                        {{ $cohortSpecsSummary->count() }} Chuyên ngành
                    </span>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xxl-4">
                    @forelse ($cohortSpecsSummary as $specItem)
                        <div class="col">
                            <div class="card border rounded-3 h-100 bg-white shadow-sm hover-shadow transition-all d-flex flex-column justify-content-between" style="border-color: #e5e7eb !important;">
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="badge bg-dark text-white px-2.5 py-1 font-monospace">
                                                {{ $specItem->code }}
                                            </span>
                                            <span class="badge bg-light text-secondary border small">
                                                Chuyên ngành
                                            </span>
                                        </div>

                                        <h5 class="fw-bold text-dark mb-3 mt-1" title="{{ $specItem->name }}">
                                            {{ $specItem->name }}
                                        </h5>

                                        <!-- Thống kê lớp và sinh viên của chuyên ngành trong khóa -->
                                        <div class="row g-2 text-center my-3">
                                            <div class="col-6">
                                                <div class="p-2.5 bg-light rounded-2 border">
                                                    <div class="fs-4 fw-bold text-dark">{{ $specItem->classes_count }}</div>
                                                    <div class="small text-secondary" style="font-size: 0.78rem;">Lớp danh nghĩa</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-2.5 bg-light rounded-2 border">
                                                    <div class="fs-4 fw-bold text-dark">{{ $specItem->students_count }}</div>
                                                    <div class="small text-secondary" style="font-size: 0.78rem;">Sinh viên</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Nút chọn chuyên ngành để ra các lớp thuộc chuyên ngành đó -->
                                    <a href="{{ route('admin.classes.index', ['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort, 'specialization_id' => $specItem->id]) }}" 
                                       class="btn btn-dark btn-sm w-100 mt-2 d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm py-2">
                                        <span>Xem các lớp ngành này</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted small">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Khoa này hiện chưa có chuyên ngành đào tạo nào.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    {{-- LEVEL 2: KHI ĐÃ CHỌN KHOA -> HIỆN DANH SÁCH CÁC KHÓA (COHORTS) THUỘC KHOA ĐÓ --}}
    @elseif ($selectedFacultyId)
        <div class="card border shadow-sm rounded-3 bg-white mb-4">
            <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('admin.classes.index') }}" 
                       class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm" 
                       title="Quay lại danh sách tất cả các Khoa">
                        <i class="bi bi-arrow-left"></i>
                        <span>Quay lại các Khoa</span>
                    </a>
                    <span class="text-secondary small d-none d-md-inline">|</span>
                    <strong class="text-dark fs-6">
                        {{ $selectedFaculty->name }}
                    </strong>
                    <span class="badge bg-light text-dark border font-monospace">
                        [{{ $selectedFaculty->code }}]
                    </span>
                    <span class="badge bg-dark text-white font-monospace">
                        {{ count($facultyCohortsSummary) }} Khóa tuyển sinh
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-dark d-inline-flex align-items-center gap-1 shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#addCohortModal">
                        <i class="bi bi-plus-lg"></i>
                        <span>Thêm Khóa tuyển sinh mới</span>
                    </button>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xxl-4">
                    @forelse ($facultyCohortsSummary as $cohortItem)
                        <div class="col">
                            <div class="card border rounded-3 h-100 bg-white shadow-sm hover-shadow transition-all d-flex flex-column justify-content-between" style="border-color: #e5e7eb !important;">
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    <div>
                                        <!-- Header thẻ Khóa -->
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <span class="badge bg-dark text-white px-3 py-1.5 fs-6">
                                                Khóa {{ $cohortItem['cohort_year'] }}
                                            </span>
                                            <span class="badge bg-light text-secondary border small">
                                                {{ $cohortItem['k_label'] }}
                                            </span>
                                        </div>

                                        <!-- 3 Khối thống kê chỉ số: Chuyên ngành, Lớp danh nghĩa, Sinh viên -->
                                        <div class="row g-2 text-center my-3">
                                            <div class="col-4">
                                                <div class="p-2 bg-light rounded-2 border">
                                                    <div class="fs-5 fw-bold text-dark">{{ $cohortItem['specializations_count'] }}</div>
                                                    <div class="small text-secondary" style="font-size: 0.72rem;">Chuyên ngành</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2 bg-light rounded-2 border">
                                                    <div class="fs-5 fw-bold text-dark">{{ $cohortItem['classes_count'] }}</div>
                                                    <div class="small text-secondary" style="font-size: 0.72rem;">Lớp học</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2 bg-light rounded-2 border">
                                                    <div class="fs-5 fw-bold text-dark">{{ $cohortItem['students_count'] }}</div>
                                                    <div class="small text-secondary" style="font-size: 0.72rem;">Sinh viên</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Nút chọn Khóa để ra các chuyên ngành của khóa đó -->
                                    <a href="{{ route('admin.classes.index', ['faculty_id' => $selectedFacultyId, 'cohort' => $cohortItem['cohort_year']]) }}" 
                                       class="btn btn-dark btn-sm w-100 mt-2 d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm py-2">
                                        <span>Xem Chuyên ngành Khóa {{ $cohortItem['cohort_year'] }}</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted small">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Khoa này hiện chưa có dữ liệu khóa tuyển sinh nào.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    {{-- LEVEL 1: GỐC -> HIỂN THỊ DANH SÁCH TẤT CẢ KHOA ĐÀO TẠO --}}
    @else
        <div class="card border shadow-sm rounded-3 bg-white mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2 mb-0">
                    <strong class="text-dark fs-6">Danh sách Khoa đào tạo</strong>
                    <span class="badge bg-light text-dark border font-monospace">
                        {{ count($facultiesSummary) }} Khoa
                    </span>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xxl-4">
                    @forelse ($facultiesSummary as $facItem)
                        <div class="col">
                            <div class="card border rounded-3 h-100 bg-white shadow-sm hover-shadow transition-all d-flex flex-column justify-content-between" style="border-color: #e5e7eb !important;">
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    <div>
                                        <!-- Header thẻ Khoa -->
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="badge bg-dark text-white px-2.5 py-1 font-monospace">
                                                {{ $facItem['code'] }}
                                            </span>
                                            <span class="badge bg-light text-secondary border small">
                                                Khoa đào tạo
                                            </span>
                                        </div>

                                        <h5 class="fw-bold text-dark mb-3 mt-1" title="{{ $facItem['name'] }}">
                                            {{ $facItem['name'] }}
                                        </h5>

                                        <!-- 3 Khối thống kê chỉ số: Chuyên ngành, Lớp danh nghĩa, Sinh viên -->
                                        <div class="row g-2 text-center my-3">
                                            <div class="col-4">
                                                <div class="p-2 bg-light rounded-2 border">
                                                    <div class="fs-5 fw-bold text-dark">{{ $facItem['specializations_count'] }}</div>
                                                    <div class="small text-secondary" style="font-size: 0.72rem;">Chuyên ngành</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2 bg-light rounded-2 border">
                                                    <div class="fs-5 fw-bold text-dark">{{ $facItem['classes_count'] }}</div>
                                                    <div class="small text-secondary" style="font-size: 0.72rem;">Lớp học</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2 bg-light rounded-2 border">
                                                    <div class="fs-5 fw-bold text-dark">{{ $facItem['students_count'] }}</div>
                                                    <div class="small text-secondary" style="font-size: 0.72rem;">Sinh viên</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Nút chọn Khoa -->
                                    <a href="{{ route('admin.classes.index', ['faculty_id' => $facItem['id']]) }}" 
                                       class="btn btn-dark btn-sm w-100 mt-2 d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm py-2">
                                        <span>Xem các Khóa tuyển sinh</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted small">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Chưa có dữ liệu Khoa nào trong hệ thống.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>

{{-- ==================== CÁC MODAL HỆ THỐNG ==================== --}}

<!-- 1. MODAL THÊM KHÓA TUYỂN SINH -->
<div class="modal fade" id="addCohortModal" tabindex="-1" aria-labelledby="addCohortModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark" id="addCohortModalLabel">
                    <i class="bi bi-calendar-plus me-1.5 text-dark"></i>Thêm Khóa tuyển sinh mới
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.classes.cohorts.store') }}">
                @csrf
                <input type="hidden" name="faculty_id" value="{{ $selectedFacultyId }}">
                <input type="hidden" name="specialization_id" value="{{ $selectedSpecId }}">
                <div class="modal-body p-4">
                    <div class="mb-2">
                        <label for="cohort_year_input" class="form-label small fw-semibold text-secondary">
                            Năm Khóa tuyển sinh <span class="text-danger">*</span>
                        </label>
                        <input type="number" min="2000" max="2099" class="form-control form-control-lg fw-bold" id="cohort_year_input" 
                               name="cohort_year" value="{{ (int)date('Y') + 1 }}" required placeholder="VD: 2027">
                        <div class="form-text small mt-2">
                            Hệ thống chỉ khởi tạo Khóa tuyển sinh mới (ví dụ: 2027 là K18). Sau khi tạo khóa, hệ thống sẽ mở khóa đó để bạn bấm "Thêm lớp danh nghĩa" và gán cho từng chuyên ngành.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5 bg-light">
                    <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-sm btn-dark px-3">
                        <i class="bi bi-check-lg me-1"></i>Tạo Khóa tuyển sinh
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. MODAL THÊM LỚP DANH NGHĨA -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark" id="addClassModalLabel">
                    <i class="bi bi-plus-circle me-1.5 text-dark"></i>Thêm Lớp danh nghĩa mới
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.classes.store') }}">
                @csrf
                <input type="hidden" name="faculty_id" value="{{ $selectedFacultyId }}">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cohort_year_select" class="form-label small fw-semibold text-secondary">
                                Khóa tuyển sinh <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="cohort_year_select" name="cohort_year" required>
                                @foreach ($allCohorts as $ch)
                                    @php
                                        $y = (int) $ch;
                                        $k = $y >= 2010 ? ($y - 2009) : null;
                                    @endphp
                                    <option value="{{ $ch }}" {{ ($selectedCohort == $ch) ? 'selected' : '' }}>
                                        Khóa {{ $ch }} {{ $k ? "(Niên khóa K{$k})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="modal_specialization_id" class="form-label small fw-semibold text-secondary">
                                Chuyên ngành đào tạo <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="modal_specialization_id" name="specialization_id" required>
                                <option value="">-- Chọn chuyên ngành gán cho lớp --</option>
                                @foreach ($specializations as $spec)
                                    <option value="{{ $spec->id }}" {{ ((string)$selectedSpecId === (string)$spec->id) ? 'selected' : '' }}>
                                        [{{ $spec->code }}] {{ $spec->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small" style="font-size: 0.76rem;">Gán lớp danh nghĩa này vào chuyên ngành tương ứng.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="modal_class_code" class="form-label small fw-semibold text-secondary">
                                Mã lớp danh nghĩa <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control font-monospace" id="modal_class_code" 
                                   name="class_code" placeholder="VD: 14DHTH02" required>
                        </div>

                        <div class="col-md-6">
                            <label for="modal_class_name" class="form-label small fw-semibold text-secondary">
                                Tên lớp danh nghĩa <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="modal_class_name" 
                                   name="class_name" placeholder="VD: Đại học Tin học 02" required>
                        </div>

                        <div class="col-md-6">
                            <label for="modal_lecturer_id" class="form-label small fw-semibold text-secondary">
                                Giảng viên phụ trách (Cố vấn học tập)
                            </label>
                            <select class="form-select" id="modal_lecturer_id" name="lecturer_id">
                                <option value="">-- Chưa phân công --</option>
                                @foreach ($lecturers as $lec)
                                    <option value="{{ $lec->id }}">
                                        {{ $lec->full_name }} ({{ $lec->degree }}) - {{ $lec->email }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="modal_room" class="form-label small fw-semibold text-secondary">
                                Phòng sinh hoạt / Phòng học
                            </label>
                            <input type="text" class="form-control" id="modal_room" name="room" placeholder="VD: A101, B202">
                        </div>

                        <div class="col-12">
                            <label for="modal_description" class="form-label small fw-semibold text-secondary">
                                Ghi chú / Mô tả lớp
                            </label>
                            <input type="text" class="form-control" id="modal_description" name="description" placeholder="Ghi chú thêm về lớp (tùy chọn)">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5 bg-light">
                    <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-sm btn-dark px-3">
                        <i class="bi bi-check-lg me-1"></i>Lưu lớp danh nghĩa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. MODAL PHÂN CÔNG GIẢNG VIÊN VÀ SỬA LỚP CHO TỪNG LỚP -->
@foreach ($classes as $cls)
    <!-- Modal Phân công GV -->
    <div class="modal fade" id="assignLecturerModal_{{ $cls->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold text-dark">
                        <i class="bi bi-person-badge me-1.5 text-dark"></i>Phân công Giảng viên phụ trách
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.classes.assignLecturer', $cls) }}">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="p-2.5 bg-light rounded-2 border mb-3 small">
                            Lớp danh nghĩa: <strong class="text-dark">{{ $cls->class_code }} - {{ $cls->class_name }}</strong><br>
                            Khóa tuyển sinh: <strong class="text-dark">Khóa {{ $cls->cohort_year }}</strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">
                                Chọn Giảng viên làm Cố vấn học tập (CVHT)
                            </label>
                            <select class="form-select" name="lecturer_id">
                                <option value="">-- Hủy phân công (Để trống) --</option>
                                @foreach ($lecturers as $lec)
                                    <option value="{{ $lec->id }}" {{ $cls->lecturer_id == $lec->id ? 'selected' : '' }}>
                                        {{ $lec->full_name }} ({{ $lec->degree }}) - {{ $lec->email }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-2.5 bg-light">
                        <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-sm btn-dark px-3">
                            <i class="bi bi-check-lg me-1"></i>Lưu phân công
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Sửa Lớp -->
    <div class="modal fade" id="editClassModal_{{ $cls->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold text-dark">
                        <i class="bi bi-pencil-square me-1.5 text-dark"></i>Chỉnh sửa lớp danh nghĩa [{{ $cls->class_code }}]
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.classes.update', $cls) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">
                                    Khóa tuyển sinh <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="cohort_year" required>
                                    @foreach ($allCohorts as $ch)
                                        <option value="{{ $ch }}" {{ $cls->cohort_year == $ch ? 'selected' : '' }}>
                                            Khóa {{ $ch }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">
                                    Mã lớp danh nghĩa <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control font-monospace" name="class_code" 
                                       value="{{ $cls->class_code }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">
                                    Tên lớp danh nghĩa <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="class_name" 
                                       value="{{ $cls->class_name }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">
                                    Giảng viên phụ trách (CVHT)
                                </label>
                                <select class="form-select" name="lecturer_id">
                                    <option value="">-- Chưa phân công --</option>
                                    @foreach ($lecturers as $lec)
                                        <option value="{{ $lec->id }}" {{ $cls->lecturer_id == $lec->id ? 'selected' : '' }}>
                                            {{ $lec->full_name }} ({{ $lec->degree }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">
                                    Chuyên ngành đào tạo <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="specialization_id" required>
                                    <option value="">-- Chọn chuyên ngành --</option>
                                    @foreach ($specializations as $spec)
                                        <option value="{{ $spec->id }}" {{ $cls->specialization_id == $spec->id ? 'selected' : '' }}>
                                            [{{ $spec->code }}] {{ $spec->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Phòng học</label>
                                <input type="text" class="form-control" name="room" value="{{ $cls->room }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary">Ghi chú</label>
                                <input type="text" class="form-control" name="description" value="{{ $cls->description }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-2.5 bg-light">
                        <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-sm btn-dark px-3">
                            <i class="bi bi-check-lg me-1"></i>Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

{{-- MODALS KHI ĐANG XEM 1 LỚP CỤ THỂ --}}
@if ($selectedClass)
    <!-- 4. MODAL THÊM SINH VIÊN VÀO LỚP (GỒM 2 TAB: GÁN SINH VIÊN CÓ SẴN HOẶC TẠO MỚI) -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold text-dark" id="addStudentModalLabel">
                        <i class="bi bi-person-plus-fill me-1.5 text-dark"></i>Thêm sinh viên vào lớp {{ $selectedClass->class_code }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <!-- Nav tabs -->
                <ul class="nav nav-tabs px-3 pt-2 bg-light border-bottom" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active small fw-semibold text-dark" id="assign-tab" data-bs-toggle="tab" 
                                data-bs-target="#assign-pane" type="button" role="tab" aria-controls="assign-pane" aria-selected="true">
                            <i class="bi bi-arrow-left-right me-1"></i>1. Xếp sinh viên trong khóa vào lớp
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link small fw-semibold text-dark" id="create-tab" data-bs-toggle="tab" 
                                data-bs-target="#create-pane" type="button" role="tab" aria-controls="create-pane" aria-selected="false">
                            <i class="bi bi-person-plus me-1"></i>2. Tạo mới sinh viên trực tiếp vào lớp
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tab 1: Gán sinh viên có sẵn trong cùng khóa -->
                    <div class="tab-pane fade show active p-4" id="assign-pane" role="tabpanel" aria-labelledby="assign-tab">
                        <form method="POST" action="{{ route('admin.classes.students.add', $selectedClass) }}">
                            @csrf
                            <input type="hidden" name="action_type" value="assign">
                            
                            <p class="text-secondary small mb-3">
                                Chọn sinh viên thuộc Khóa {{ $selectedClass->cohort_year }} đang chưa được xếp lớp hoặc đang ở lớp khác để chuyển vào lớp <strong>{{ $selectedClass->class_code }}</strong>.
                            </p>

                            @if ($unassignedStudents->isNotEmpty())
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-secondary">
                                        Chọn sinh viên <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" name="student_id" required size="8" style="font-family: inherit;">
                                        @foreach ($unassignedStudents as $uStu)
                                            <option value="{{ $uStu->id }}" class="py-1">
                                                [{{ $uStu->student_code }}] {{ $uStu->full_name }} — {{ $uStu->class_code ? 'Đang ở lớp ' . $uStu->class_code : 'Chưa có lớp' }} ({{ $uStu->gender }}, {{ $uStu->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                                    <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy bỏ</button>
                                    <button type="submit" class="btn btn-sm btn-dark px-3">
                                        <i class="bi bi-check-lg me-1"></i>Xếp sinh viên vào lớp này
                                    </button>
                                </div>
                            @else
                                <div class="p-4 text-center border rounded-3 bg-light">
                                    <i class="bi bi-check-circle fs-2 text-success d-block mb-2"></i>
                                    <h6 class="fw-bold text-dark">Tất cả sinh viên Khóa {{ $selectedClass->cohort_year }} đều đã được phân lớp!</h6>
                                    <p class="text-secondary small mb-0">Bạn có thể chuyển sang tab <strong>"Tạo mới sinh viên"</strong> để thêm sinh viên mới trực tiếp vào lớp này.</p>
                                </div>
                            @endif
                        </form>
                    </div>

                    <!-- Tab 2: Tạo mới sinh viên -->
                    <div class="tab-pane fade p-4" id="create-pane" role="tabpanel" aria-labelledby="create-tab">
                        <form method="POST" action="{{ route('admin.classes.students.add', $selectedClass) }}">
                            @csrf
                            <input type="hidden" name="action_type" value="create">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">
                                        Mã sinh viên (MSSV)
                                    </label>
                                    <input type="text" class="form-control font-monospace" name="student_code" 
                                           placeholder="Để trống hệ thống sẽ tự sinh theo khóa {{ $selectedClass->cohort_year }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">
                                        Họ và tên sinh viên <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="full_name" placeholder="VD: Nguyễn Văn An" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">
                                        Email liên hệ <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" name="email" placeholder="VD: sinhvien@gmail.com" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold text-secondary">
                                        Giới tính <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" name="gender" required>
                                        <option value="Nam">Nam</option>
                                        <option value="Nữ">Nữ</option>
                                        <option value="Khác">Khác</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold text-secondary">Ngày sinh</label>
                                    <input type="date" class="form-control" name="dob" value="2005-01-15">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">Số điện thoại</label>
                                    <input type="text" class="form-control" name="phone" placeholder="VD: 0912345678">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary">Chuyên ngành</label>
                                    <select class="form-select" name="specialization_id">
                                        @foreach ($specializations as $spec)
                                            <option value="{{ $spec->id }}" {{ $selectedClass->specialization_id == $spec->id ? 'selected' : '' }}>
                                                [{{ $spec->code }}] {{ $spec->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-4">
                                <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy bỏ</button>
                                <button type="submit" class="btn btn-sm btn-dark px-3">
                                    <i class="bi bi-person-check-fill me-1"></i>Tạo & Thêm vào lớp ngay
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. MODAL CHUYỂN LỚP NHANH CHO TỪNG SINH VIÊN -->
    @if ($classStudents)
        @foreach ($classStudents as $stu)
            <div class="modal fade" id="transferStudentModal_{{ $stu->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header border-bottom py-3">
                            <h6 class="modal-title fw-bold text-dark">
                                <i class="bi bi-arrow-left-right me-1.5 text-dark"></i>Chuyển Lớp danh nghĩa cho sinh viên
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="{{ route('admin.classes.students.update', [$selectedClass, $stu]) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="full_name" value="{{ $stu->full_name }}">
                            <input type="hidden" name="email" value="{{ $stu->email }}">
                            <input type="hidden" name="gender" value="{{ $stu->gender }}">
                            <input type="hidden" name="dob" value="{{ $stu->dob }}">
                            <input type="hidden" name="phone" value="{{ $stu->phone }}">
                            <input type="hidden" name="study_status" value="{{ $stu->study_status }}">
                            <input type="hidden" name="specialization_id" value="{{ $stu->specialization_id }}">

                            <div class="modal-body p-4">
                                <div class="p-3 bg-light rounded-3 border mb-3">
                                    <div class="small text-secondary mb-1">Sinh viên:</div>
                                    <div class="fw-bold text-dark fs-6">[{{ $stu->student_code }}] {{ $stu->full_name }}</div>
                                    <div class="small text-secondary mt-1">
                                        Lớp hiện tại: <span class="badge bg-dark font-monospace text-white">{{ $selectedClass->class_code }}</span>
                                        (Khóa {{ $selectedClass->cohort_year }})
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-secondary">
                                        Chọn Lớp danh nghĩa đích cần chuyển đến <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" name="target_class_code" required>
                                        <option value="">-- Chọn lớp danh nghĩa đích --</option>
                                        @foreach ($classes as $otherClass)
                                            @if ($otherClass->class_code !== $selectedClass->class_code)
                                                <option value="{{ $otherClass->class_code }}">
                                                    {{ $otherClass->class_code }} - {{ $otherClass->class_name }} (Khóa {{ $otherClass->cohort_year }})
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <div class="form-text small">Sinh viên sẽ được cập nhật mã lớp và khóa tuyển sinh theo lớp mới.</div>
                                </div>
                            </div>
                            <div class="modal-footer border-top py-2.5 bg-light">
                                <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy bỏ</button>
                                <button type="submit" class="btn btn-sm btn-dark px-3">
                                    <i class="bi bi-check-lg me-1"></i>Xác nhận chuyển lớp
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
@endif

@endsection
