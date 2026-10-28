@extends('layouts.spark')

@section('title', 'Hồ Sơ Lớp Cố Vấn ' . $class->class_code . ' - HUIT')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('lecturer.advisor.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 py-1 px-2.5">
                    <i class="bi bi-arrow-left"></i>
                    <span>Danh sách lớp</span>
                </a>
                <span class="badge bg-dark text-white font-monospace">{{ $class->class_code }}</span>
                <span class="badge bg-light text-secondary border">Khóa {{ $class->cohort_year ?? '2023' }}</span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">
                Lớp {{ $class->class_code }} — {{ $class->specialization?->name ?? 'Công nghệ phần mềm' }}
            </h1>
            <p class="text-secondary small mb-0">
                Khoa: <strong>{{ $class->specialization?->faculty?->faculty_name ?? 'Công nghệ thông tin' }}</strong>
                • Cố vấn học tập: <strong>{{ $class->lecturer?->full_name ?? auth()->user()->name }}</strong>
                • Học kỳ theo dõi: <strong>{{ $currentSemester->semester_name ?? 'HK1 2026-2027' }}</strong>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button onclick="window.print()" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-printer"></i>
                <span>In báo cáo lớp</span>
            </button>
        </div>
    </div>

    <!-- 4 THẺ KPI TỔNG HỢP LỚP CỐ VẤN -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Sĩ số sinh viên</span>
                <div class="fs-4 fw-bold text-dark mt-1">
                    {{ $totalStudents }} <span class="fs-6 fw-normal text-secondary">sinh viên</span>
                </div>
                <span class="text-secondary small" style="font-size: 0.75rem;">Đang sinh hoạt chính khóa</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">GPA bình quân lớp</span>
                <div class="fs-4 fw-bold text-dark mt-1">
                    {{ $avgClassGpaFormatted }} <span class="fs-6 fw-normal text-secondary">/ 4.0</span>
                </div>
                <span class="text-secondary small" style="font-size: 0.75rem;">Thang điểm 4</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Cảnh báo học vụ</span>
                <div class="fs-4 fw-bold {{ $totalWarnings > 0 ? 'text-danger' : 'text-success' }} mt-1">
                    {{ $totalWarnings }} <span class="fs-6 fw-normal text-secondary">sinh viên</span>
                </div>
                <span class="text-secondary small" style="font-size: 0.75rem;">GPA tích lũy < 2.0</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Chưa hoàn thành học phí</span>
                <div class="fs-4 fw-bold {{ $totalDebts > 0 ? 'text-warning' : 'text-success' }} mt-1">
                    {{ $totalDebts }} <span class="fs-6 fw-normal text-secondary">sinh viên</span>
                </div>
                <span class="text-secondary small" style="font-size: 0.75rem;">Cần đôn đốc thanh toán</span>
            </div>
        </div>
    </div>

    <!-- BỘ LỌC VÀ TÌM KIẾM -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('lecturer.advisor.class', $class) }}" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-secondary border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}" class="form-control border-start-0 py-2" placeholder="Tìm kiếm theo MSSV, Họ tên, SĐT, Email...">
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                        <span class="small text-secondary fw-semibold me-1">Lọc:</span>
                        <a href="{{ route('lecturer.advisor.class', ['class' => $class->id, 'filter' => 'all', 'search' => $search]) }}" 
                           class="btn btn-sm {{ $filterStatus === 'all' ? 'btn-dark' : 'btn-outline-secondary' }} py-1 px-2.5">
                            Tất cả ({{ $totalStudents }})
                        </a>
                        <a href="{{ route('lecturer.advisor.class', ['class' => $class->id, 'filter' => 'warning', 'search' => $search]) }}" 
                           class="btn btn-sm {{ $filterStatus === 'warning' ? 'btn-danger' : 'btn-outline-danger' }} py-1 px-2.5">
                            Cảnh báo học vụ ({{ $totalWarnings }})
                        </a>
                        <a href="{{ route('lecturer.advisor.class', ['class' => $class->id, 'filter' => 'debt', 'search' => $search]) }}" 
                           class="btn btn-sm {{ $filterStatus === 'debt' ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' }} py-1 px-2.5">
                            Nợ học phí ({{ $totalDebts }})
                        </a>
                        <a href="{{ route('lecturer.advisor.class', ['class' => $class->id, 'filter' => 'good', 'search' => $search]) }}" 
                           class="btn btn-sm {{ $filterStatus === 'good' ? 'btn-primary' : 'btn-outline-primary' }} py-1 px-2.5">
                            Giỏi / Xuất sắc
                        </a>
                    </div>
                </div>

                <div class="col-md-2 text-md-end">
                    <button type="submit" class="btn btn-dark btn-sm py-2 px-3">
                        <i class="bi bi-filter me-1"></i>Áp dụng
                    </button>
                    @if ($search || $filterStatus !== 'all')
                        <a href="{{ route('lecturer.advisor.class', $class) }}" class="btn btn-link btn-sm text-secondary text-decoration-none">
                            Xóa lọc
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH SINH VIÊN -->
    <div class="card border shadow-sm rounded-3 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <strong class="text-dark fs-6">Danh sách sinh viên lớp {{ $class->class_code }} (Hiển thị {{ $studentsData->count() }} sinh viên)</strong>
            <span class="badge bg-light text-dark border font-monospace">
                Sĩ số: {{ $totalStudents }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-3 text-center" style="width: 50px;">#</th>
                            <th style="width: 130px;">MSSV</th>
                            <th>Họ và tên</th>
                            <th class="text-center" style="width: 100px;">Ngày sinh</th>
                            <th class="text-center" style="width: 80px;">Giới tính</th>
                            <th class="text-center" style="width: 90px;">Tín chỉ</th>
                            <th class="text-center" style="width: 100px;">GPA (4.0)</th>
                            <th class="text-center" style="width: 180px;">Tình trạng học vụ</th>
                            <th class="text-center" style="width: 140px;">Học phí kỳ này</th>
                            <th class="pe-3" style="width: 160px;">Liên hệ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($studentsData as $idx => $item)
                            @php
                                $stu = $item->student;
                            @endphp
                            <tr class="{{ $item->academic_status === 'DANGER' ? 'table-danger-subtle' : ($item->academic_status === 'WARNING' ? 'table-warning-subtle' : '') }}">
                                <td class="ps-3 text-center text-muted small">{{ $idx + 1 }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $stu->student_code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $stu->full_name }}</div>
                                    <div class="text-secondary small font-monospace">{{ $stu->email }}</div>
                                </td>
                                <td class="text-center small text-secondary">
                                    {{ $stu->dob ? $stu->dob->format('d/m/Y') : '-' }}
                                </td>
                                <td class="text-center small">
                                    {{ $stu->gender ?? '-' }}
                                </td>
                                <td class="text-center fw-bold text-dark">
                                    {{ $item->earned_credits }} TC
                                </td>
                                <td class="text-center">
                                    <span class="fs-6 fw-bold {{ $item->gpa_4 < 2.0 && $item->gpa_4 > 0 ? 'text-danger' : 'text-dark' }}">
                                        {{ number_format($item->gpa_4, 2) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $item->academic_badge_class }} px-2 py-1">
                                        {{ $item->academic_status_label }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if ($item->has_debt)
                                        <span class="badge bg-light text-danger border border-danger-subtle px-2 py-1">
                                            Nợ {{ number_format($item->debt_amount, 0, ',', '.') }}đ
                                        </span>
                                    @else
                                        <span class="badge bg-light text-success border border-success-subtle px-2 py-1">
                                            ✓ Đã hoàn thành
                                        </span>
                                    @endif
                                </td>
                                <td class="pe-3 small text-secondary">
                                    <div><i class="bi bi-telephone me-1"></i>{{ $stu->phone ?: 'Chưa cập nhật' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-secondary small">
                                    Không tìm thấy sinh viên nào phù hợp với điều kiện tìm kiếm.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
