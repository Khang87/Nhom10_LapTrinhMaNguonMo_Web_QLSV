@extends('layouts.spark')

@section('title', 'Yêu Cầu Báo Nghỉ & Dạy Bù - Giảng Viên')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('lecturer.dashboard') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 py-1 px-2.5">
                    <i class="bi bi-arrow-left"></i>
                    <span>Trang chủ</span>
                </a>
                <span class="badge bg-light text-primary border border-primary-subtle">Kế hoạch đào tạo</span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Yêu Cầu Báo Nghỉ & Dạy Bù</h1>
            <p class="text-secondary small mb-0">
                Gửi yêu cầu báo nghỉ ca dạy, đề xuất lịch dạy bù hoặc chuyển sang hình thức giảng dạy trực tuyến (Online) gửi Phòng Đào tạo duyệt.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('lecturer.requests.create') }}" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-2 shadow-sm">
                <i class="bi bi-plus-lg"></i>
                <span class="fw-semibold">Gửi yêu cầu mới</span>
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

    <!-- BẢNG DANH SÁCH YÊU CẦU -->
    <div class="card border shadow-sm rounded-3 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <strong class="text-dark fs-6">Lịch sử yêu cầu thay đổi lịch dạy ({{ $requests->count() }} yêu cầu)</strong>
            <span class="badge bg-light text-secondary border">Tất cả các kỳ</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-3 text-center" style="width: 50px;">#</th>
                            <th style="width: 170px;">Loại yêu cầu</th>
                            <th>Lớp học phần</th>
                            <th style="width: 140px;">Ngày ca học gốc</th>
                            <th style="width: 160px;">Ngày dạy bù / Online</th>
                            <th>Lý do & Ghi chú</th>
                            <th class="text-center" style="width: 140px;">Trạng thái</th>
                            <th class="pe-3" style="width: 150px;">Ghi chú Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $idx => $req)
                            @php
                                $badge = $req->getStatusBadge();
                            @endphp
                            <tr>
                                <td class="ps-3 text-center text-muted small">{{ $idx + 1 }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-bold">
                                        {{ $req->getTypeName() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $req->section?->course?->course_name }}</div>
                                    <div class="text-secondary small font-monospace">{{ $req->section?->section_code }}</div>
                                </td>
                                <td class="small">
                                    {{ $req->original_date ? $req->original_date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="small">
                                    @if ($req->request_type === 'MAKEUP_SESSION')
                                        <div class="fw-bold text-dark">{{ $req->target_date ? $req->target_date->format('d/m/Y') : '-' }}</div>
                                        <div class="text-secondary small">Phòng: {{ $req->room_name }}</div>
                                    @elseif ($req->request_type === 'ONLINE_SESSION')
                                        <div class="text-primary fw-medium"><i class="bi bi-camera-video me-1"></i>Học trực tuyến</div>
                                        <a href="{{ $req->meeting_link }}" target="_blank" class="small text-truncate d-inline-block" style="max-width: 150px;">
                                            {{ $req->meeting_link ?: 'Đã gắn link' }}
                                        </a>
                                    @else
                                        <span class="text-danger small"><i class="bi bi-x-circle me-1"></i>Nghỉ ca học</span>
                                    @endif
                                </td>
                                <td class="small">
                                    <div class="text-dark">{{ $req->reason }}</div>
                                    <div class="text-secondary small" style="font-size: 0.75rem;">Gửi: {{ $req->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $badge['bg'] }} text-white px-2 py-1">
                                        {{ $badge['label'] }}
                                    </span>
                                </td>
                                <td class="pe-3 small text-secondary">
                                    {{ $req->admin_note ?: ($req->status === 'PENDING' ? 'Đang chờ xét duyệt' : '-') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-secondary small">
                                    Thầy/Cô chưa gửi yêu cầu thay đổi lịch giảng dạy nào.
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
