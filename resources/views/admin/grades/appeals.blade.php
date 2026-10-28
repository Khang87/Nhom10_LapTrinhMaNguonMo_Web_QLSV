@extends('layouts.spark')

@section('title', 'Phê Duyệt Đơn Phúc Khảo Điểm - Admin')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.grades.approvals') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 py-1 px-2.5">
                    <i class="bi bi-arrow-left"></i>
                    <span>Duyệt bảng điểm</span>
                </a>
                <span class="badge bg-light text-dark border font-monospace">Phòng Khảo thí & ĐBCL</span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Phê Duyệt Đơn Phúc Khảo Điểm</h1>
            <p class="text-secondary small mb-0">
                Xem xét kết luận của Giảng viên và phê duyệt cập nhật điểm thi chính thức vào cơ sở dữ liệu.
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-light border shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <div class="text-dark small">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- BẢNG DANH SÁCH ĐƠN PHÚC KHẢO -->
    <div class="card border shadow-sm rounded-3 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <strong class="text-dark fs-6">Danh sách đơn phúc khảo toàn trường ({{ $appeals->count() }} đơn)</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-3 text-center" style="width: 50px;">#</th>
                            <th style="width: 130px;">MSSV</th>
                            <th>Sinh viên</th>
                            <th>Lớp học phần & Giảng viên</th>
                            <th class="text-center" style="width: 140px;">Thành phần</th>
                            <th class="text-center" style="width: 90px;">Điểm cũ</th>
                            <th class="text-center" style="width: 110px;">Điểm GV đề xuất</th>
                            <th class="text-center" style="width: 140px;">Trạng thái</th>
                            <th class="text-center pe-3" style="width: 150px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appeals as $idx => $app)
                            @php
                                $badge = $app->getStatusBadge();
                                $isReviewed = ($app->status === 'REVIEWED');
                            @endphp
                            <tr class="{{ $isReviewed ? 'table-warning-subtle' : '' }}">
                                <td class="ps-3 text-center text-muted small">{{ $idx + 1 }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $app->student?->student_code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $app->student?->full_name }}</div>
                                    <div class="text-secondary small">{{ $app->student?->class_code }}</div>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $app->section?->course?->course_name }}</div>
                                    <div class="text-secondary small">GV: {{ $app->section?->lecturer?->full_name }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        {{ $app->getComponentName() }}
                                    </span>
                                </td>
                                <td class="text-center fw-bold text-dark fs-6">
                                    {{ number_format($app->current_score, 1) }}
                                </td>
                                <td class="text-center">
                                    @if ($app->new_score !== null)
                                        <span class="fs-6 fw-bold text-success">{{ number_format($app->new_score, 1) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $badge['bg'] }} text-white px-2 py-1">
                                        {{ $badge['label'] }}
                                    </span>
                                </td>
                                <td class="text-center pe-3">
                                    @if ($isReviewed)
                                        <form method="POST" action="{{ route('admin.grades.approveAppeal', $app) }}" class="d-inline"
                                              onsubmit="return confirm('Xác nhận phê duyệt điểm mới {{ number_format($app->new_score, 1) }} cho sinh viên {{ $app->student?->full_name }}? Điểm số trong bảng điểm sẽ được cập nhật.');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success py-1 px-2.5 d-inline-flex align-items-center gap-1 shadow-sm">
                                                <i class="bi bi-check-circle-fill"></i>
                                                <span>Duyệt điểm mới</span>
                                            </button>
                                        </form>
                                    @elseif ($app->status === 'RESOLVED')
                                        <span class="badge bg-light text-success border border-success-subtle">
                                            ✓ Đã cập nhật điểm
                                        </span>
                                    @else
                                        <span class="text-secondary small">Chờ GV phản hồi</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-secondary small">
                                    Chưa có đơn phúc khảo điểm nào trong hệ thống.
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
