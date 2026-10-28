@extends('layouts.spark')

@section('title', 'Phê Duyệt Bảng Điểm Học Phần - Admin')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.grades.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 py-1 px-2.5">
                    <i class="bi bi-arrow-left"></i>
                    <span>Quản lý điểm</span>
                </a>
                <span class="badge bg-light text-dark border font-monospace">Phòng Đào tạo</span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Phê Duyệt Bảng Điểm Học Phần</h1>
            <p class="text-secondary small mb-0">
                Kiểm duyệt, phê duyệt công bố điểm chính thức cho sinh viên hoặc mở khóa cho Giảng viên cập nhật lại.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.grades.appeals') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-chat-left-dots"></i>
                <span>Xem đơn phúc khảo điểm</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-light border shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <div class="text-dark small">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- 3 THẺ KPI TRẠNG THÁI NỘP ĐIỂM -->
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Chờ Phòng Đào tạo duyệt</span>
                <div class="fs-4 fw-bold text-primary mt-1">
                    {{ $submittedCount }} <span class="fs-6 fw-normal text-secondary">lớp HP</span>
                </div>
                <span class="text-primary small" style="font-size: 0.75rem;">Giảng viên đã nộp bảng điểm</span>
            </div>
        </div>
        <div class="col-4">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Đã phê duyệt chính thức</span>
                <div class="fs-4 fw-bold text-success mt-1">
                    {{ $approvedCount }} <span class="fs-6 fw-normal text-secondary">lớp HP</span>
                </div>
                <span class="text-success small" style="font-size: 0.75rem;">Đã công bố cho sinh viên</span>
            </div>
        </div>
        <div class="col-4">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Đang nhập nháp / Chưa nộp</span>
                <div class="fs-4 fw-bold text-secondary mt-1">
                    {{ $draftCount }} <span class="fs-6 fw-normal text-secondary">lớp HP</span>
                </div>
                <span class="text-secondary small" style="font-size: 0.75rem;">Giảng viên đang chấm điểm</span>
            </div>
        </div>
    </div>

    <!-- BỘ LỌC HỌC KỲ VÀ TRẠNG THÁI -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('admin.grades.approvals') }}" class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div style="min-width: 260px;">
                        <label class="form-label small text-secondary fw-semibold mb-1">Chọn Học kỳ:</label>
                        <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach ($semesters as $sem)
                                <option value="{{ $sem->semester_code }}" {{ $semesterCode === $sem->semester_code ? 'selected' : '' }}>
                                    {{ $sem->semester_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label small text-secondary fw-semibold mb-1">Lọc trạng thái:</label>
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <a href="{{ route('admin.grades.approvals', ['semester' => $semesterCode, 'status' => 'all']) }}" 
                               class="btn btn-sm {{ $status === 'all' ? 'btn-dark' : 'btn-outline-secondary' }} py-1 px-2.5">
                                Tất cả ({{ $sections->count() }})
                            </a>
                            <a href="{{ route('admin.grades.approvals', ['semester' => $semesterCode, 'status' => 'SUBMITTED']) }}" 
                               class="btn btn-sm {{ $status === 'SUBMITTED' ? 'btn-primary' : 'btn-outline-primary' }} py-1 px-2.5">
                                Chờ duyệt ({{ $submittedCount }})
                            </a>
                            <a href="{{ route('admin.grades.approvals', ['semester' => $semesterCode, 'status' => 'APPROVED']) }}" 
                               class="btn btn-sm {{ $status === 'APPROVED' ? 'btn-success' : 'btn-outline-success' }} py-1 px-2.5">
                                Đã duyệt ({{ $approvedCount }})
                            </a>
                            <a href="{{ route('admin.grades.approvals', ['semester' => $semesterCode, 'status' => 'DRAFT']) }}" 
                               class="btn btn-sm {{ $status === 'DRAFT' ? 'btn-secondary' : 'btn-outline-secondary' }} py-1 px-2.5">
                                Đang nháp ({{ $draftCount }})
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH LỚP HỌC PHẦN -->
    <div class="card border shadow-sm rounded-3 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <strong class="text-dark fs-6">Danh sách lớp học phần — {{ $semesterCode }} ({{ $sections->count() }} lớp)</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-3 text-center" style="width: 50px;">#</th>
                            <th style="width: 140px;">Mã LHP</th>
                            <th>Môn học & Tín chỉ</th>
                            <th>Giảng viên phụ trách</th>
                            <th class="text-center" style="width: 90px;">Sĩ số</th>
                            <th class="text-center" style="width: 150px;">Trạng thái bảng điểm</th>
                            <th style="width: 160px;">Thời gian nộp</th>
                            <th class="text-center pe-3" style="width: 220px;">Thao tác phê duyệt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sections as $idx => $sec)
                            @php
                                $isSubmitted = ($sec->grade_status === 'SUBMITTED');
                                $isApproved = ($sec->grade_status === 'APPROVED');
                            @endphp
                            <tr class="{{ $isSubmitted ? 'table-primary-subtle' : '' }}">
                                <td class="ps-3 text-center text-muted small">{{ $idx + 1 }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $sec->section_code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $sec->course?->course_name }}</div>
                                    <div class="text-secondary small font-monospace">{{ $sec->course?->course_code }} • {{ $sec->course?->credits }} TC</div>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $sec->lecturer?->full_name ?? 'Chưa phân công' }}</div>
                                    <div class="text-secondary small">{{ $sec->lecturer?->email }}</div>
                                </td>
                                <td class="text-center fw-bold text-dark">
                                    {{ $sec->enrollments_count }}
                                </td>
                                <td class="text-center">
                                    @if ($isApproved)
                                        <span class="badge bg-light text-success border border-success-subtle px-2 py-1">
                                            ✓ Đã phê duyệt
                                        </span>
                                    @elseif ($isSubmitted)
                                        <span class="badge bg-primary text-white px-2 py-1 shadow-sm">
                                            <i class="bi bi-clock-history me-1"></i>Chờ duyệt
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border px-2 py-1">
                                            Đang nhập nháp
                                        </span>
                                    @endif
                                </td>
                                <td class="small text-secondary">
                                    @if ($sec->grade_submitted_at)
                                        <div><i class="bi bi-calendar-check me-1"></i>{{ $sec->grade_submitted_at->format('d/m/Y H:i') }}</div>
                                    @else
                                        <span class="text-muted">Chưa nộp</span>
                                    @endif
                                </td>
                                <td class="text-center pe-3">
                                    <div class="d-inline-flex align-items-center gap-1.5">
                                        <!-- Xem bảng điểm -->
                                        <a href="{{ route('admin.grades.section', $sec) }}" class="btn btn-sm btn-outline-dark py-1 px-2" title="Xem bảng điểm chi tiết">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if ($isSubmitted)
                                            <!-- Duyệt bảng điểm -->
                                            <form method="POST" action="{{ route('admin.grades.approve', $sec) }}" class="d-inline"
                                                  onsubmit="return confirm('Xác nhận phê duyệt bảng điểm môn {{ $sec->course?->course_name }}? Điểm sẽ được công bố chính thức cho sinh viên.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success py-1 px-2.5 d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    <span>Duyệt</span>
                                                </button>
                                            </form>

                                            <!-- Mở khóa / Trả về -->
                                            <button type="button" class="btn btn-sm btn-outline-warning text-dark py-1 px-2.5" 
                                                    data-bs-toggle="modal" data-bs-target="#unlockModal-{{ $sec->id }}">
                                                <i class="bi bi-unlock"></i>
                                                <span>Mở khóa</span>
                                            </button>
                                        @elseif ($isApproved)
                                            <!-- Đã duyệt nhưng muốn mở khóa lại nếu có sai sót -->
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" 
                                                    data-bs-toggle="modal" data-bs-target="#unlockModal-{{ $sec->id }}" title="Mở khóa lại bảng điểm">
                                                <i class="bi bi-unlock"></i>
                                                <span>Mở khóa lại</span>
                                            </button>
                                        @else
                                            <span class="text-muted small">Chờ GV nộp</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal Mở khóa -->
                            <div class="modal fade" id="unlockModal-{{ $sec->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <form method="POST" action="{{ route('admin.grades.unlock', $sec) }}">
                                            @csrf
                                            <div class="modal-header border-bottom">
                                                <h5 class="modal-title fw-bold text-dark">
                                                    <i class="bi bi-unlock text-warning me-1"></i>Mở khóa bảng điểm
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4 text-start">
                                                <p class="small text-secondary mb-3">
                                                    Mở khóa bảng điểm lớp <strong>{{ $sec->section_code }} — {{ $sec->course?->course_name }}</strong> để Giảng viên <strong>{{ $sec->lecturer?->full_name }}</strong> có thể chỉnh sửa lại điểm số.
                                                </p>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-dark">Lý do mở khóa cho Giảng viên:</label>
                                                    <textarea name="reason" class="form-control form-control-sm" rows="3" required placeholder="Ví dụ: Giảng viên xin cập nhật lại điểm bài tập lớn cho sinh viên..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top bg-light">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" class="btn btn-sm btn-warning text-dark px-3">
                                                    Xác nhận mở khóa
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-secondary small">
                                    Không có lớp học phần nào phù hợp trong học kỳ này.
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
