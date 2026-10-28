@extends('layouts.spark')

@section('title', 'Phê Duyệt Lịch Giảng Dạy - Admin')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-light text-dark border font-monospace">Phòng Đào tạo / Khảo thí</span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Duyệt Yêu Cầu Thay Đổi Lịch Giảng Dạy</h1>
            <p class="text-secondary small mb-0">
                Xử lý các đề xuất Báo nghỉ, Báo dạy bù hoặc Chuyển sang học Online từ Giảng viên. Hệ thống tự động đồng bộ Thời khóa biểu của Sinh viên khi phê duyệt.
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

    <!-- 3 Thẻ KPI -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Chờ Admin phê duyệt</span>
                <div class="fs-4 fw-bold text-warning mt-1">
                    {{ $pendingCount }} <span class="fs-6 fw-normal text-secondary">yêu cầu</span>
                </div>
                <span class="text-secondary small" style="font-size: 0.75rem;">Cần phản hồi kịp thời trước giờ lên lớp</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Đã phê duyệt</span>
                <div class="fs-4 fw-bold text-success mt-1">
                    {{ $approvedCount }} <span class="fs-6 fw-normal text-secondary">yêu cầu</span>
                </div>
                <span class="text-success small" style="font-size: 0.75rem;">TKB sinh viên đã được cập nhật</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Đã từ chối</span>
                <div class="fs-4 fw-bold text-danger mt-1">
                    {{ $rejectedCount }} <span class="fs-6 fw-normal text-secondary">yêu cầu</span>
                </div>
                <span class="text-danger small" style="font-size: 0.75rem;">Lớp học diễn ra theo lịch chuẩn</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border rounded-3 mb-4 shadow-none">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-secondary small fw-medium">Trạng thái:</span>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.schedule_requests.index', ['status' => 'all']) }}" 
                           class="btn {{ $status === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}">Tất cả ({{ $requests->count() }})</a>
                        <a href="{{ route('admin.schedule_requests.index', ['status' => 'PENDING']) }}" 
                           class="btn {{ $status === 'PENDING' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' }}">Chờ duyệt ({{ $pendingCount }})</a>
                        <a href="{{ route('admin.schedule_requests.index', ['status' => 'APPROVED']) }}" 
                           class="btn {{ $status === 'APPROVED' ? 'btn-success' : 'btn-outline-secondary' }}">Đã duyệt ({{ $approvedCount }})</a>
                        <a href="{{ route('admin.schedule_requests.index', ['status' => 'REJECTED']) }}" 
                           class="btn {{ $status === 'REJECTED' ? 'btn-danger' : 'btn-outline-secondary' }}">Từ chối ({{ $rejectedCount }})</a>
                    </div>
                </div>

                <div class="text-secondary small">
                    Tổng cộng: <strong class="text-dark">{{ $requests->count() }}</strong> yêu cầu
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border rounded-3 shadow-none">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                <thead class="table-light text-secondary border-bottom" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                    <tr>
                        <th class="ps-3 py-3" style="width: 50px;">#</th>
                        <th class="py-3">Giảng viên</th>
                        <th class="py-3">Loại yêu cầu</th>
                        <th class="py-3">Lớp học phần & Môn</th>
                        <th class="py-3">Chi tiết thay đổi</th>
                        <th class="py-3" style="max-width: 200px;">Lý do & Ghi chú</th>
                        <th class="py-3 text-center">Trạng thái</th>
                        <th class="pe-3 py-3 text-end" style="width: 140px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($requests as $index => $req)
                        @php
                            $badge = $req->getStatusBadge();
                        @endphp
                        <tr>
                            <td class="ps-3 text-secondary font-monospace">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $req->lecturer?->full_name }}</div>
                                <div class="text-secondary small font-monospace">{{ $req->lecturer?->lecturer_code }} &bull; {{ $req->lecturer?->email }}</div>
                            </td>
                            <td>
                                @if ($req->request_type === 'CANCEL_SESSION')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                        <i class="bi bi-x-circle me-1"></i> Báo nghỉ buổi học
                                    </span>
                                @elseif ($req->request_type === 'MAKEUP_SESSION')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                        <i class="bi bi-calendar-plus me-1"></i> Báo lịch dạy bù
                                    </span>
                                @elseif ($req->request_type === 'ONLINE_SESSION')
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                        <i class="bi bi-camera-video me-1"></i> Học Online
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $req->section?->section_code }}</div>
                                <div class="text-secondary small text-truncate" style="max-width: 220px;" title="{{ $req->section?->course?->course_name }}">
                                    {{ $req->section?->course?->course_name }}
                                </div>
                            </td>
                            <td>
                                @if ($req->request_type === 'CANCEL_SESSION')
                                    <div class="small">
                                        <span class="text-secondary">Buổi nghỉ:</span> 
                                        <strong class="text-danger">{{ $req->original_date ? $req->original_date->format('d/m/Y') : 'Chưa rõ' }}</strong>
                                    </div>
                                    @if ($req->schedule)
                                        <div class="text-secondary small">
                                            Tiết {{ $req->schedule->start_period }}-{{ $req->schedule->end_period }} &bull; Phòng {{ $req->schedule->room_name }}
                                        </div>
                                    @endif
                                @elseif ($req->request_type === 'MAKEUP_SESSION')
                                    <div class="small">
                                        <span class="text-secondary">Ngày dạy bù:</span> 
                                        <strong class="text-primary">{{ $req->target_date ? $req->target_date->format('d/m/Y') : 'N/A' }}</strong>
                                    </div>
                                    <div class="text-secondary small">
                                        Tiết {{ $req->start_period }}-{{ $req->end_period }} &bull; Phòng: <span class="fw-semibold text-dark">{{ $req->room_name ?? 'Phòng bù' }}</span>
                                    </div>
                                @elseif ($req->request_type === 'ONLINE_SESSION')
                                    <div class="small">
                                        <span class="text-secondary">Ngày học:</span> 
                                        <strong class="text-dark">{{ $req->original_date ? $req->original_date->format('d/m/Y') : 'N/A' }}</strong>
                                    </div>
                                    @if ($req->meeting_link)
                                        <div class="small text-truncate" style="max-width: 200px;">
                                            <a href="{{ $req->meeting_link }}" target="_blank" class="text-decoration-none">
                                                <i class="bi bi-link-45deg"></i> {{ $req->meeting_link }}
                                            </a>
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td style="max-width: 200px;">
                                <div class="small text-dark">{{ $req->reason }}</div>
                                @if ($req->admin_note)
                                    <div class="small text-secondary mt-1 fst-italic">
                                        <span class="fw-semibold">Admin:</span> {{ $req->admin_note }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $badge['bg'] }}-subtle text-{{ $badge['bg'] }} border border-{{ $badge['bg'] }}-subtle px-2 py-1">
                                    {{ $badge['label'] }}
                                </span>
                            </td>
                            <td class="pe-3 text-end">
                                @if ($req->status === 'PENDING')
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#approveModal-{{ $req->id }}">
                                            <i class="bi bi-check-lg"></i> Duyệt
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $req->id }}">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>

                                    <!-- Modal Approve -->
                                    <div class="modal fade" id="approveModal-{{ $req->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content border-0 shadow">
                                                <form action="{{ route('admin.schedule_requests.approve', $req) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header border-bottom py-3">
                                                        <h5 class="modal-title fs-6 fw-bold text-success">
                                                            <i class="bi bi-check-circle me-1"></i> Phê duyệt yêu cầu thay đổi lịch
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body py-3">
                                                        <p class="small text-secondary mb-3">
                                                            Bạn đang duyệt yêu cầu <strong>{{ $req->getTypeName() }}</strong> của Giảng viên <strong>{{ $req->lecturer?->full_name }}</strong> lớp <strong>{{ $req->section?->section_code }}</strong>.
                                                            Thời khóa biểu của Giảng viên và toàn bộ Sinh viên trong lớp sẽ tự động được cập nhật.
                                                        </p>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-medium text-dark">Ghi chú phê duyệt (tùy chọn):</label>
                                                            <input type="text" name="admin_note" class="form-control form-control-sm" value="Đã kiểm tra phòng học & duyệt yêu cầu.">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top py-2">
                                                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Đóng</button>
                                                        <button type="submit" class="btn btn-sm btn-success px-3">Xác nhận Duyệt</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Reject -->
                                    <div class="modal fade" id="rejectModal-{{ $req->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content border-0 shadow">
                                                <form action="{{ route('admin.schedule_requests.reject', $req) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header border-bottom py-3">
                                                        <h5 class="modal-title fs-6 fw-bold text-danger">
                                                            <i class="bi bi-x-circle me-1"></i> Từ chối yêu cầu
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body py-3">
                                                        <p class="small text-secondary mb-3">
                                                            Vui lòng nhập lý do từ chối để Giảng viên nắm rõ thông tin:
                                                        </p>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-medium text-dark">Lý do từ chối:</label>
                                                            <textarea name="admin_note" class="form-control form-control-sm" rows="3" required placeholder="Ví dụ: Phòng học dự kiến bị trùng lịch, hoặc chưa đủ điều kiện chuyển online..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top py-2">
                                                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Hủy</button>
                                                        <button type="submit" class="btn btn-sm btn-danger px-3">Xác nhận Từ chối</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-secondary small fst-italic">Đã hoàn tất</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                                <span>Chưa có yêu cầu thay đổi lịch giảng dạy nào.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
