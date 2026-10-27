@extends('layouts.spark')

@section('title', 'Chi tiết Khoa: ' . $faculty->name)

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb" class="mb-1">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.faculties.index') }}" class="text-decoration-none text-secondary">Quản lý Khoa</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $faculty->code }}</li>
                </ol>
            </nav>
            <h1 class="h4 fw-bold text-dark mb-1">{{ $faculty->name }}</h1>
            <p class="text-secondary small mb-0">Mã khoa: <span class="font-monospace fw-semibold">{{ $faculty->code }}</span></p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.faculties.index') }}" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-arrow-left"></i>
                <span>Quay lại</span>
            </a>
            <a href="{{ route('admin.faculties.edit', $faculty) }}" class="btn btn-sm btn-dark d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-pencil"></i>
                <span>Chỉnh sửa khoa</span>
            </a>
        </div>
    </div>

    <!-- Thông tin Trưởng khoa & Thông tin Khoa -->
    <div class="row g-3 mb-4">
        <!-- Khối Thông tin Trưởng khoa -->
        <div class="col-12 col-lg-5">
            <div class="card border shadow-sm rounded-3 h-100 bg-white">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge text-primary"></i>
                        <span>Thông tin Trưởng khoa</span>
                    </div>
                    @if ($faculty->dean)
                        <span class="badge bg-light text-dark border">Đang đương nhiệm</span>
                    @endif
                </div>
                <div class="card-body p-3">
                    @if ($faculty->dean)
                        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-primary fw-bold fs-4" style="width: 56px; height: 56px;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0 fs-6">
                                    {{ $faculty->dean->degree }} {{ $faculty->dean->full_name }}
                                </h6>
                                <div class="text-muted small">
                                    Mã giảng viên: <span class="font-monospace fw-semibold">{{ $faculty->dean->lecturer_code }}</span>
                                </div>
                            </div>
                        </div>

                        <dl class="row small mb-0">
                            <dt class="col-4 text-secondary">Học vị:</dt>
                            <dd class="col-8 text-dark fw-semibold">{{ $faculty->dean->degree }}</dd>

                            <dt class="col-4 text-secondary">Email:</dt>
                            <dd class="col-8 text-dark">
                                @if($faculty->dean->email)
                                    <a href="mailto:{{ $faculty->dean->email }}" class="text-decoration-none">{{ $faculty->dean->email }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </dd>

                            <dt class="col-4 text-secondary">Điện thoại:</dt>
                            <dd class="col-8 text-dark">{{ $faculty->dean->phone ?: '—' }}</dd>

                            <dt class="col-4 text-secondary">Chuyên ngành:</dt>
                            <dd class="col-8 text-dark">
                                {{ $faculty->dean->specialization?->name ?: 'Khoa ' . $faculty->name }}
                            </dd>
                        </dl>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-person-x fs-2 d-block mb-2 opacity-50"></i>
                            <div class="small fw-semibold mb-2">Chưa bổ nhiệm Trưởng khoa</div>
                            <p class="small text-muted mb-3">Vui lòng cập nhật thông tin khoa để chỉ định giảng viên phụ trách khoa.</p>
                            <a href="{{ route('admin.faculties.edit', $faculty) }}" class="btn btn-sm btn-light border">
                                <i class="bi bi-plus-lg me-1"></i> Bổ nhiệm Trưởng khoa
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Khối Thông tin Văn phòng & Liên hệ Khoa -->
        <div class="col-12 col-lg-7">
            <div class="card border shadow-sm rounded-3 h-100 bg-white">
                <div class="card-header bg-white py-3 border-bottom fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-building text-primary"></i>
                    <span>Thông tin liên hệ & Văn phòng khoa</span>
                </div>
                <div class="card-body p-3">
                    <dl class="row small mb-3">
                        <dt class="col-sm-3 text-secondary">Tên chính thức:</dt>
                        <dd class="col-sm-9 fw-semibold text-dark">{{ $faculty->name }}</dd>

                        <dt class="col-sm-3 text-secondary">Mã định danh:</dt>
                        <dd class="col-sm-9 font-monospace">{{ $faculty->code }}</dd>

                        <dt class="col-sm-3 text-secondary">Văn phòng khoa:</dt>
                        <dd class="col-sm-9 text-dark">{{ $faculty->office_location ?: 'Chưa cập nhật' }}</dd>

                        <dt class="col-sm-3 text-secondary">Email khoa:</dt>
                        <dd class="col-sm-9">
                            @if($faculty->email)
                                <a href="mailto:{{ $faculty->email }}" class="text-decoration-none">{{ $faculty->email }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3 text-secondary">Số điện thoại:</dt>
                        <dd class="col-sm-9 text-dark">{{ $faculty->phone ?: 'Chưa cập nhật' }}</dd>
                    </dl>

                    <div class="pt-2 border-top">
                        <div class="text-secondary small fw-semibold mb-1">Mô tả / Định hướng đào tạo:</div>
                        <p class="small text-muted mb-0">
                            {{ $faculty->description ?: 'Chưa có mô tả chi tiết cho khoa này.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách Chuyên ngành trực thuộc -->
    <div class="card border shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-diagram-3"></i>
                <span>Chuyên ngành trực thuộc ({{ $faculty->specializations->count() }})</span>
            </div>
            <a href="{{ route('admin.specializations.create') }}" class="btn btn-sm btn-light border">
                <i class="bi bi-plus-lg me-1"></i> Thêm chuyên ngành
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="ps-3 text-center" style="width: 50px;">#</th>
                        <th style="width: 130px;">Mã ngành</th>
                        <th>Tên chuyên ngành</th>
                        <th class="text-center" style="width: 120px;">Sinh viên</th>
                        <th class="text-center" style="width: 120px;">Giảng viên</th>
                        <th class="text-center" style="width: 140px;">Học phần khung</th>
                        <th class="text-center pe-3" style="width: 120px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($faculty->specializations as $idx => $spec)
                        <tr>
                            <td class="ps-3 text-center text-muted small">{{ $idx + 1 }}</td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">{{ $spec->code }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $spec->name }}</div>
                                @if($spec->description)
                                    <div class="text-muted small text-truncate" style="max-width: 320px;">{{ $spec->description }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $spec->students_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $spec->lecturers_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="small">{{ $spec->curriculum_items_count }} môn</span>
                            </td>
                            <td class="text-center pe-3">
                                <a href="{{ route('admin.specializations.edit', $spec) }}" class="btn btn-sm btn-light border" title="Sửa chuyên ngành">
                                    <i class="bi bi-pencil"></i> Sửa
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted small">
                                Chưa có chuyên ngành nào thuộc khoa này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Danh sách Giảng viên thuộc khoa -->
    <div class="card border shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-people"></i>
                <span>Danh sách Giảng viên thuộc khoa ({{ $faculty->lecturers->count() }})</span>
            </div>
            <a href="{{ route('admin.lecturers.create', ['faculty_id' => $faculty->id]) }}" class="btn btn-sm btn-light border">
                <i class="bi bi-plus-lg me-1"></i> Thêm giảng viên vào khoa
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="ps-3 text-center" style="width: 50px;">#</th>
                        <th style="width: 110px;">Mã GV</th>
                        <th>Họ và tên</th>
                        <th style="width: 110px;">Học vị</th>
                        <th>Email</th>
                        <th style="width: 120px;">Điện thoại</th>
                        <th>Chuyên ngành</th>
                        <th class="text-center" style="width: 110px;">Vai trò</th>
                        <th class="text-center pe-3" style="width: 90px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($faculty->lecturers as $idx => $lec)
                        <tr>
                            <td class="ps-3 text-center text-muted small">{{ $idx + 1 }}</td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">{{ $lec->lecturer_code }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $lec->full_name }}</div>
                            </td>
                            <td>
                                <span class="small">{{ $lec->degree }}</span>
                            </td>
                            <td>
                                <span class="small text-secondary">{{ $lec->email ?: '—' }}</span>
                            </td>
                            <td>
                                <span class="small">{{ $lec->phone ?: '—' }}</span>
                            </td>
                            <td>
                                <span class="small text-secondary">{{ $lec->specialization?->name ?: '—' }}</span>
                            </td>
                            <td class="text-center">
                                @if ($faculty->dean_id == $lec->id)
                                    <span class="badge bg-light text-dark border fw-bold">
                                        <i class="bi bi-star-fill text-warning me-1"></i>Trưởng khoa
                                    </span>
                                @else
                                    <span class="text-muted small">Giảng viên</span>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                <a href="{{ route('admin.lecturers.edit', $lec) }}" class="btn btn-sm btn-light border" title="Sửa thông tin giảng viên">
                                    <i class="bi bi-pencil"></i> Sửa
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted small">
                                Chưa có giảng viên nào thuộc khoa này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
