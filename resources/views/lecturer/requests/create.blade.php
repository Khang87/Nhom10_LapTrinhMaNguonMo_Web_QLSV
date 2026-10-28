@extends('layouts.spark')

@section('title', 'Gửi Yêu Cầu Thay Đổi Lịch Giảng Dạy - Giảng Viên')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('lecturer.requests.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 py-1 px-2.5">
                    <i class="bi bi-arrow-left"></i>
                    <span>Danh sách yêu cầu</span>
                </a>
                <span class="badge bg-light text-primary border border-primary-subtle">Kế hoạch đào tạo</span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Gửi Yêu Cầu Thay Đổi Lịch Giảng Dạy</h1>
            <p class="text-secondary small mb-0">
                Thầy/Cô vui lòng điền đầy đủ thông tin để Phòng Đào tạo kiểm tra phòng học và phê duyệt.
            </p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-light border border-danger-subtle shadow-sm rounded-3 p-3 mb-4 text-danger small">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border shadow-sm rounded-3 bg-white p-3 p-md-4">
                <form method="POST" action="{{ route('lecturer.requests.store') }}">
                    @csrf

                    <!-- 1. Loại yêu cầu -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">1. Chọn loại yêu cầu:</label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="border rounded-3 p-3 text-center w-100 d-block cursor-pointer bg-light request-option" style="cursor: pointer;">
                                    <input type="radio" name="request_type" value="CANCEL_SESSION" checked class="form-check-input mb-1"
                                           onchange="toggleFields('CANCEL_SESSION')">
                                    <div class="fw-bold text-danger small">Báo nghỉ ca học</div>
                                    <span class="text-secondary" style="font-size: 0.7rem;">Tạm ngưng buổi giảng</span>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="border rounded-3 p-3 text-center w-100 d-block cursor-pointer bg-light request-option" style="cursor: pointer;">
                                    <input type="radio" name="request_type" value="MAKEUP_SESSION" class="form-check-input mb-1"
                                           onchange="toggleFields('MAKEUP_SESSION')">
                                    <div class="fw-bold text-success small">Báo lịch dạy bù</div>
                                    <span class="text-secondary" style="font-size: 0.7rem;">Tổ chức buổi học bù</span>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="border rounded-3 p-3 text-center w-100 d-block cursor-pointer bg-light request-option" style="cursor: pointer;">
                                    <input type="radio" name="request_type" value="ONLINE_SESSION" class="form-check-input mb-1"
                                           onchange="toggleFields('ONLINE_SESSION')">
                                    <div class="fw-bold text-primary small">Học trực tuyến (Online)</div>
                                    <span class="text-secondary" style="font-size: 0.7rem;">Google Meet / Zoom</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Lớp học phần -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">2. Chọn Lớp học phần:</label>
                        <select name="section_id" class="form-select form-select-sm py-2" required>
                            @foreach ($sections as $sec)
                                <option value="{{ $sec->id }}" {{ $selectedSectionId == $sec->id ? 'selected' : '' }}>
                                    [{{ $sec->section_code }}] {{ $sec->course?->course_name }} ({{ $sec->semester_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Ngày buổi học gốc -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">3. Ngày diễn ra ca học bị thay đổi:</label>
                        <input type="date" name="original_date" value="{{ $selectedDate }}" class="form-control form-control-sm py-2" required>
                        <div class="form-text small text-secondary">Hệ thống sẽ đối chiếu với thời khóa biểu tuần của ngày này.</div>
                    </div>

                    <!-- 4. Trường thông tin cho Dạy bù -->
                    <div id="makeupFields" style="display: none;" class="p-3 bg-light border rounded-3 mb-3">
                        <div class="fw-bold small text-dark mb-2">Thông tin buổi dạy bù:</div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Ngày dạy bù dự kiến:</label>
                                <input type="date" name="target_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Ca dạy bù:</label>
                                <select name="shift" class="form-select form-select-sm">
                                    <option value="MORNING">Ca Sáng (Tiết 1 - 6)</option>
                                    <option value="AFTERNOON" selected>Ca Chiều (Tiết 7 - 12)</option>
                                    <option value="EVENING">Ca Tối (Tiết 13 - 16)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label small text-secondary">Phòng học dạy bù:</label>
                            <input type="text" name="room_name" class="form-control form-control-sm" placeholder="Ví dụ: B202 - Cơ sở 140 Lê Trọng Tấn">
                        </div>
                    </div>

                    <!-- 5. Trường thông tin cho Học Online -->
                    <div id="onlineFields" style="display: none;" class="p-3 bg-light border rounded-3 mb-3">
                        <div class="fw-bold small text-dark mb-2">Đường link lớp học trực tuyến:</div>
                        <label class="form-label small text-secondary">Link phòng họp (Google Meet / Zoom):</label>
                        <input type="url" name="meeting_link" class="form-control form-control-sm" placeholder="https://meet.google.com/xyz-abcd-efg">
                    </div>

                    <!-- 6. Lý do -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">4. Lý do thay đổi / Ghi chú gửi PĐT & Sinh viên:</label>
                        <textarea name="reason" class="form-control form-control-sm" rows="3" required placeholder="Ví dụ: Giảng viên tham dự Hội nghị Khoa học Công nghệ toàn quốc hoặc Lớp học bù theo kế hoạch..."></textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 pt-2 border-top">
                        <a href="{{ route('lecturer.requests.index') }}" class="btn btn-sm btn-outline-secondary px-3">Hủy bỏ</a>
                        <button type="submit" class="btn btn-sm btn-dark px-4 py-2">
                            <i class="bi bi-send me-1"></i>Gửi yêu cầu lên Phòng Đào tạo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFields(type) {
    const makeup = document.getElementById('makeupFields');
    const online = document.getElementById('onlineFields');

    if (type === 'MAKEUP_SESSION') {
        makeup.style.display = 'block';
        online.style.display = 'none';
    } else if (type === 'ONLINE_SESSION') {
        makeup.style.display = 'none';
        online.style.display = 'block';
    } else {
        makeup.style.display = 'none';
        online.style.display = 'none';
    }
}
</script>
@endsection
