@extends('layouts.spark')

@section('title', 'Phúc Khảo & Thắc Mắc Điểm - Giảng Viên')

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
                <span class="badge bg-light text-primary border border-primary-subtle">Học vụ & Khảo thí</span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Tiếp Nhận & Xử Lý Phúc Khảo Điểm</h1>
            <p class="text-secondary small mb-0">
                Xem và giải trình các khiếu nại, thắc mắc về điểm quá trình, giữa kỳ và thi kết thúc môn của sinh viên các lớp Thầy/Cô giảng dạy.
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

    <!-- 3 THẺ KPI THỐNG KÊ PHÚC KHẢO -->
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Chờ Giảng viên phản hồi</span>
                <div class="fs-4 fw-bold {{ $pendingCount > 0 ? 'text-danger' : 'text-dark' }} mt-1">
                    {{ $pendingCount }} <span class="fs-6 fw-normal text-secondary">yêu cầu</span>
                </div>
                <span class="text-secondary small" style="font-size: 0.75rem;">Cần kiểm tra bài thi</span>
            </div>
        </div>
        <div class="col-4">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Đã phản hồi (Chờ PĐT duyệt)</span>
                <div class="fs-4 fw-bold text-primary mt-1">
                    {{ $reviewedCount }} <span class="fs-6 fw-normal text-secondary">yêu cầu</span>
                </div>
                <span class="text-secondary small" style="font-size: 0.75rem;">Đã gửi đề xuất điểm mới</span>
            </div>
        </div>
        <div class="col-4">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Đã giải quyết xong</span>
                <div class="fs-4 fw-bold text-success mt-1">
                    {{ $resolvedCount }} <span class="fs-6 fw-normal text-secondary">yêu cầu</span>
                </div>
                <span class="text-secondary small" style="font-size: 0.75rem;">Điểm đã cập nhật chính thức</span>
            </div>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH ĐƠN PHÚC KHẢO -->
    <div class="card border shadow-sm rounded-3 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <strong class="text-dark fs-6">Danh sách thắc mắc điểm ({{ $appeals->count() }} yêu cầu)</strong>
            <div class="d-flex align-items-center gap-1.5">
                <a href="{{ route('lecturer.appeals.index', ['status' => 'all']) }}" class="btn btn-sm {{ $status === 'all' ? 'btn-dark' : 'btn-outline-secondary' }} py-0.5 px-2">Tất cả</a>
                <a href="{{ route('lecturer.appeals.index', ['status' => 'PENDING']) }}" class="btn btn-sm {{ $status === 'PENDING' ? 'btn-danger' : 'btn-outline-danger' }} py-0.5 px-2">Chờ xử lý</a>
                <a href="{{ route('lecturer.appeals.index', ['status' => 'RESOLVED']) }}" class="btn btn-sm {{ $status === 'RESOLVED' ? 'btn-success' : 'btn-outline-success' }} py-0.5 px-2">Đã xong</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-3 text-center" style="width: 50px;">#</th>
                            <th style="width: 130px;">MSSV</th>
                            <th>Sinh viên</th>
                            <th>Lớp học phần</th>
                            <th class="text-center" style="width: 140px;">Thành phần điểm</th>
                            <th class="text-center" style="width: 90px;">Điểm hiện tại</th>
                            <th class="text-center" style="width: 90px;">Điểm mong đợi</th>
                            <th class="text-center" style="width: 140px;">Trạng thái</th>
                            <th class="text-center pe-3" style="width: 130px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appeals as $idx => $app)
                            @php
                                $badge = $app->getStatusBadge();
                            @endphp
                            <tr>
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
                                    <div class="text-secondary small font-monospace">{{ $app->section?->section_code }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        {{ $app->getComponentName() }}
                                    </span>
                                </td>
                                <td class="text-center fw-bold text-dark fs-6">
                                    {{ number_format($app->current_score, 1) }}
                                </td>
                                <td class="text-center text-primary fw-bold fs-6">
                                    {{ $app->desired_score !== null ? number_format($app->desired_score, 1) : '-' }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $badge['bg'] }} text-white px-2 py-1">
                                        {{ $badge['label'] }}
                                    </span>
                                </td>
                                <td class="text-center pe-3">
                                    <button type="button" class="btn btn-sm {{ $app->status === 'PENDING' ? 'btn-dark' : 'btn-outline-secondary' }} py-1 px-2.5" 
                                            data-bs-toggle="modal" data-bs-target="#replyModal-{{ $app->id }}">
                                        {{ $app->status === 'PENDING' ? 'Xử lý' : 'Xem phản hồi' }}
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal Phản hồi thắc mắc -->
                            <div class="modal fade" id="replyModal-{{ $app->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <form method="POST" action="{{ route('lecturer.appeals.reply', $app) }}">
                                            @csrf
                                            <div class="modal-header border-bottom">
                                                <h5 class="modal-title fw-bold text-dark">
                                                    <i class="bi bi-chat-left-dots text-primary me-1"></i>Xử lý đơn phúc khảo điểm
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4 text-start">
                                                <div class="p-3 rounded-3 bg-light border mb-3">
                                                    <div class="small text-secondary mb-1">
                                                        Sinh viên: <strong class="text-dark">{{ $app->student?->full_name }}</strong> (MSSV: {{ $app->student?->student_code }})
                                                    </div>
                                                    <div class="small text-secondary mb-1">
                                                        Môn học: <strong class="text-dark">{{ $app->section?->course?->course_name }}</strong>
                                                    </div>
                                                    <div class="small text-secondary mb-1">
                                                        Thành phần: <strong>{{ $app->getComponentName() }}</strong> 
                                                        • Điểm hiện tại: <strong class="text-dark">{{ number_format($app->current_score, 1) }}</strong>
                                                        • Điểm mong muốn: <strong class="text-primary">{{ $app->desired_score !== null ? number_format($app->desired_score, 1) : '-' }}</strong>
                                                    </div>
                                                    <div class="small text-secondary mt-2 pt-2 border-top">
                                                        Lý do sinh viên thắc mắc:
                                                        <div class="text-dark fw-medium mt-0.5">"{{ $app->reason }}"</div>
                                                    </div>
                                                </div>

                                                @if ($app->status === 'PENDING')
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-dark">Quyết định của Giảng viên:</label>
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="decision" id="dec-accept-{{ $app->id }}" value="ACCEPT" checked 
                                                                       onchange="document.getElementById('newScoreGroup-{{ $app->id }}').style.display = 'block';">
                                                                <label class="form-check-label small" for="dec-accept-{{ $app->id }}">
                                                                    Đồng ý điều chỉnh điểm
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="decision" id="dec-reject-{{ $app->id }}" value="REJECT"
                                                                       onchange="document.getElementById('newScoreGroup-{{ $app->id }}').style.display = 'none';">
                                                                <label class="form-check-label small text-danger" for="dec-reject-{{ $app->id }}">
                                                                    Từ chối / Giữ nguyên điểm
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3" id="newScoreGroup-{{ $app->id }}">
                                                        <label class="form-label small fw-bold text-dark">Điểm đề xuất mới (thang điểm 10):</label>
                                                        <input type="number" step="0.1" min="0" max="10" name="new_score" 
                                                               value="{{ $app->desired_score ?? $app->current_score }}" 
                                                               class="form-control form-control-sm" placeholder="Nhập điểm mới...">
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-dark">Lời giải thích / Phản hồi cho Sinh viên & PĐT:</label>
                                                        <textarea name="lecturer_feedback" class="form-control form-control-sm" rows="3" required placeholder="Ví dụ: Đã kiểm tra lại bài thi, đồng ý cộng thêm 0.5 điểm câu 3 do chấm sót ý..."></textarea>
                                                    </div>
                                                @else
                                                    <div class="p-3 border rounded-3 bg-light">
                                                        <div class="small text-secondary">Phản hồi của Giảng viên:</div>
                                                        <div class="fw-semibold text-dark mt-1">{{ $app->lecturer_feedback ?: 'Chưa có phản hồi' }}</div>
                                                        @if ($app->new_score !== null)
                                                            <div class="small text-success mt-2">
                                                                ✓ Điểm đề xuất mới: <strong>{{ number_format($app->new_score, 1) }}</strong>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer border-top bg-light">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                                                @if ($app->status === 'PENDING')
                                                    <button type="submit" class="btn btn-sm btn-dark px-3">Gửi phản hồi</button>
                                                @endif
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-secondary small">
                                    Hiện chưa có sinh viên nào gửi yêu cầu phúc khảo hoặc thắc mắc điểm.
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
