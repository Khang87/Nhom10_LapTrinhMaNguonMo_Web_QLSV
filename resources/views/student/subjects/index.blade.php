@extends('layouts.spark')

@section('title', 'Đăng ký học phần - Cổng Thông tin HUIT')

@section('content')
<div class="registration-container pb-4">
    {{-- Header Banner & Bộ chọn Học kỳ --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                         style="width: 52px; height: 52px; background-color: #e8f4fd; color: #1da1f2;">
                        <i class="bi bi-journal-plus fs-3"></i>
                    </div>
                    <div>
                        <h1 class="h4 mb-1 fw-bold" style="color: #0f2942;">Đăng ký học phần trực tuyến</h1>
                        <p class="text-secondary small mb-0">
                            Sinh viên: <strong class="text-dark">{{ $student->full_name ?? auth()->user()->name }}</strong> (MSSV: {{ $student->student_code ?? auth()->user()->username }}) 
                            &bull; Ngành: <strong class="text-dark">{{ $student->major_name ?? 'Công nghệ thông tin' }}</strong> 
                            &bull; Chuyên ngành: <span class="badge bg-primary">{{ $student->specialization_name ?? 'Công nghệ phần mềm' }}</span>
                        </p>
                    </div>
                </div>

                {{-- BỘ CHỌN HỌC KỲ THEO TIẾN ĐỘ TỪ NĂM 1 ĐẾN HIỆN TẠI --}}
                <div class="d-flex align-items-center gap-2">
                    <form method="GET" action="{{ route('student.subjects.index', [], false) }}" id="semesterForm" class="d-flex align-items-center gap-2">
                        <label for="semesterSelect" class="form-label mb-0 small text-secondary fw-semibold text-nowrap">
                            <i class="bi bi-calendar3 me-1"></i>Học kỳ:
                        </label>
                        <select id="semesterSelect" name="semester" class="form-select form-select-sm fw-bold border-primary" onchange="onSemesterChange(this.value)" style="min-width: 230px;">
                            @foreach ($availableSemesters as $sem)
                                <option value="{{ $sem->semester_code }}" {{ $selectedSemesterCode === $sem->semester_code ? 'selected' : '' }}>
                                    {{ $sem->semester_name }} {{ $sem->order_index == 4 ? '(Hiện tại)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm px-2 text-nowrap" style="background-color: #1da1f2; border-color: #1da1f2;" title="Tải dữ liệu học kỳ">
                            <i class="bi bi-arrow-clockwise me-1"></i>Xem
                        </button>
                    </form>
                    <a href="{{ route('student.schedule.index', [], false) }}" class="btn btn-outline-primary btn-sm" title="Xem thời khóa biểu">
                        <i class="bi bi-calendar-week"></i> TKB
                    </a>
                    <a href="{{ route('student.tuition.index', [], false) }}" class="btn btn-outline-success btn-sm" title="Tra cứu học phí và thanh toán QR">
                        <i class="bi bi-qr-code"></i> Học phí
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- FLOATING TOAST POPUP (Tự động biến mất sau 3 giây khi Đăng ký hoặc Hủy thành công) --}}
    @if (session('success') || session('error'))
        <div id="huitToast" class="toast-popup-container" style="position: fixed; top: 24px; right: 24px; z-index: 99999; min-width: 320px; max-width: 460px; pointer-events: auto;">
            <div class="card border-0 shadow-lg {{ session('success') ? 'border-start border-4 border-success' : 'border-start border-4 border-danger' }}" style="border-radius: 12px; overflow: hidden; animation: slideInRight 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
                <div class="card-body p-3 d-flex align-items-start gap-3 bg-white">
                    <div class="fs-3 {{ session('success') ? 'text-success' : 'text-danger' }} lh-1 mt-1">
                        <i class="bi {{ session('success') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold fs-6 {{ session('success') ? 'text-success' : 'text-danger' }} mb-1">
                            {{ session('success') ? 'Thành công!' : 'Thông báo lỗi' }}
                        </div>
                        <div class="text-dark small lh-sm">
                            {{ session('success') ?? session('error') }}
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-sm" onclick="dismissHuitToast()" aria-label="Close"></button>
                </div>
                <div class="toast-progress-bar" style="height: 3px; background: {{ session('success') ? '#198754' : '#dc3545' }}; animation: shrinkWidth 3s linear forwards;"></div>
            </div>
        </div>

        <style>
            @keyframes slideInRight {
                from { transform: translateX(110%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes fadeOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(110%); opacity: 0; }
            }
            @keyframes shrinkWidth {
                from { width: 100%; }
                to { width: 0%; }
            }
            .toast-fading {
                animation: fadeOutRight 0.35s ease-in forwards !important;
            }
        </style>

        <script>
            function dismissHuitToast() {
                const toast = document.getElementById('huitToast');
                if (toast) {
                    toast.classList.add('toast-fading');
                    setTimeout(() => toast.remove(), 350);
                }
            }
            setTimeout(dismissHuitToast, 3000);
        </script>
    @endif

    {{-- Alert Messages In-page --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-4 me-2 text-success"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-2 text-danger"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- BANNER TRẠNG THÁI ĐỢT ĐĂNG KÝ HỌC PHẦN CỦA KỲ ĐƯỢC CHỌN --}}
    @if ($isRegistrationActive)
        <div class="alert alert-success border-0 shadow-sm p-3 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center">
                <div class="spinner-grow spinner-grow-sm text-success me-2" role="status"></div>
                <div>
                    <h6 class="mb-0 fw-bold text-success">
                        <i class="bi bi-check-circle-fill me-1"></i>ĐỢT ĐĂNG KÝ HỌC PHẦN ĐANG MỞ (Trước 2 tháng so với ngày bắt đầu học kỳ)
                    </h6>
                    <small class="text-secondary">
                        Áp dụng cho: <strong>{{ $selectedSemester->semester_name }}</strong> &bull; Hạn chót: <strong>{{ $selectedSemester->registration_end_date->format('H:i - d/m/Y') }}</strong>
                    </small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success fs-6 px-3 py-2">
                    <i class="bi bi-stopwatch me-1"></i>{{ $selectedSemester->remaining_time }}
                </span>
            </div>
        </div>
    @else
        <div class="alert alert-warning border-0 shadow-sm p-3 mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-lock-fill fs-3 text-warning"></i>
            <div>
                <h6 class="mb-0 fw-bold text-dark">
                    ĐỢT ĐĂNG KÝ HỌC PHẦN CHO HỌC KỲ NÀY HIỆN ĐANG ĐÓNG
                </h6>
                <small class="text-muted">
                    Thời gian đăng ký môn học cho <strong>{{ $selectedSemester->semester_name ?? $selectedSemesterCode }}</strong> đã kết thúc (hoặc chưa tới hạn mở). Bạn chỉ có thể xem danh sách lớp học phần đã đăng ký, hệ thống khóa thao tác Đăng ký mới và Hủy môn.
                </small>
            </div>
        </div>
    @endif

    {{-- Thống kê tổng quan nhanh --}}
    @php
        $totalCredits = $enrollments->sum(fn($e) => $e->section->course->credits ?? 0);
        $totalTuition = $enrollments->sum(fn($e) => ($e->section->course->credits ?? 0) * ($e->section->course->tuition_fee_per_credit ?? 550000));
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-primary bg-white">
                <div class="text-secondary small fw-medium">Số học phần đã đăng ký ({{ $selectedSemester->semester_name ?? '' }})</div>
                <div class="h3 fw-bold mb-0 text-primary">{{ $enrollments->count() }} <span class="fs-6 fw-normal text-muted">lớp</span></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-info bg-white">
                <div class="text-secondary small fw-medium">Tổng số tín chỉ đăng ký kỳ này</div>
                <div class="h3 fw-bold mb-0 text-info">{{ $totalCredits }} <span class="fs-6 fw-normal text-muted">tín chỉ</span></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-success bg-white">
                <div class="text-secondary small fw-medium">Học phí tạm tính</div>
                <div class="h3 fw-bold mb-0 text-success">{{ number_format($totalTuition, 0, ',', '.') }} <span class="fs-6 fw-normal text-muted">VNĐ</span></div>
            </div>
        </div>
    </div>

    {{-- PHẦN 1: DANH SÁCH MÔN HỌC ĐÃ ĐĂNG KÝ CỦA KỲ ĐƯỢC CHỌN --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <i class="bi bi-bookmark-check-fill text-success fs-5 me-2"></i>
                <h2 class="h5 mb-0 fw-bold" style="color: #0f2942;">
                    Danh sách học phần đã đăng ký - {{ $selectedSemester->semester_name ?? $selectedSemesterCode }}
                </h2>
            </div>
            <span class="badge bg-light text-dark border">
                Tổng cộng: {{ $enrollments->count() }} học phần
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-secondary small">
                            <th class="text-center" style="width: 50px;">STT</th>
                            <th>Mã LHP</th>
                            <th>Tên môn học</th>
                            <th class="text-center">Số TC</th>
                            <th>Lịch học tuần</th>
                            <th>Phòng học</th>
                            <th>Giảng viên</th>
                            <th class="text-center">Loại đăng ký</th>
                            <th class="text-end">Học phí</th>
                            <th class="text-center" style="width: 120px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enrollments as $index => $enrollment)
                            @php
                                $sec = $enrollment->section;
                                $dayStr = match ((int)$sec->day_of_week) {
                                    2 => 'Thứ 2',
                                    3 => 'Thứ 3',
                                    4 => 'Thứ 4',
                                    5 => 'Thứ 5',
                                    6 => 'Thứ 6',
                                    7 => 'Thứ 7',
                                    default => 'Chủ nhật',
                                };
                                $fee = ($sec->course->credits ?? 0) * ($sec->course->tuition_fee_per_credit ?? 550000);
                            @endphp
                            <tr>
                                <td class="text-center text-muted fw-bold">{{ $index + 1 }}</td>
                                <td class="fw-semibold text-primary font-monospace">{{ $sec->section_code }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $sec->course->course_name }}</div>
                                    <span class="text-muted small font-monospace">{{ $sec->course->course_code }}</span>
                                </td>
                                <td class="text-center fw-bold">{{ $sec->course->credits }}</td>
                                <td>
                                    <span class="badge bg-light text-primary border">
                                        <i class="bi bi-calendar-event me-1"></i>{{ $dayStr }}
                                    </span>
                                    <span class="small text-muted ms-1">Tiết {{ $sec->start_period }} - {{ $sec->end_period }}</span>
                                </td>
                                <td>
                                    <span class="small text-secondary">{{ $sec->room_name }}</span>
                                </td>
                                <td>
                                    <span class="small fw-medium">{{ $sec->lecturer->full_name ?? 'Chưa phân công' }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($enrollment->registration_type === 'RETAKE')
                                        <span class="badge bg-danger">
                                            <i class="bi bi-arrow-repeat me-1"></i>Học lại
                                        </span>
                                    @elseif ($enrollment->registration_type === 'IMPROVEMENT')
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-arrow-up-circle me-1"></i>Học cải thiện
                                        </span>
                                    @else
                                        <span class="badge bg-primary">
                                            <i class="bi bi-check2-circle me-1"></i>Học mới
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold text-dark">
                                    {{ number_format($fee, 0, ',', '.') }} đ
                                </td>
                                <td class="text-center">
                                    @if ($isRegistrationActive)
                                        <form method="POST" action="{{ route('student.subjects.unregister', $enrollment->id, false) }}"
                                              onsubmit="return confirm('Bạn có chắc chắn muốn hủy đăng ký môn [{{ $sec->course->course_name }}]?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="semester" value="{{ $selectedSemesterCode }}">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" title="Hủy học phần này">
                                                <i class="bi bi-trash3 me-1"></i>Hủy
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary" title="Đã khóa khi hết hạn đợt đăng ký">
                                            <i class="bi bi-lock me-1"></i>Đã khóa
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    Bạn chưa đăng ký lớp học phần nào trong học kỳ này.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- PHẦN 2: LỚP HỌC PHẦN TRONG HỌC KỲ ĐƯỢC CHỌN (GỒM 3 TAB: HỌC MỚI, HỌC LẠI, HỌC CẢI THIỆN) --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1 fw-bold" style="color: #0f2942;">
                        <i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Đăng ký học phần theo Chuyên ngành: {{ $student->specialization_name ?? 'Công nghệ phần mềm' }} - {{ $selectedSemester->semester_name ?? $selectedSemesterCode }}
                    </h2>
                    <small class="text-muted">Tất cả lớp học phần theo chương trình khung & các lớp mở thêm đều được gộp chung tại đây.</small>
                </div>
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="subjectFilter" class="form-control border-start-0" placeholder="Lọc nhanh môn học..." onkeyup="filterSectionsTable()">
                </div>
            </div>

            {{-- BỘ CHỌN 3 LOẠI ĐĂNG KÝ ĐƯỢC ĐƯA LÊN TRÊN ĐẦU (NAV TABS: HỌC MỚI, HỌC LẠI, HỌC CẢI THIỆN) --}}
            <ul class="nav nav-pills nav-fill bg-light p-1 rounded border" id="regTypeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold py-2" id="tab-first-time" data-bs-toggle="pill" data-bs-target="#content-first-time" type="button" role="tab">
                        <i class="bi bi-star-fill text-primary me-1"></i> Học mới 
                        <span class="badge bg-primary ms-1">{{ $firstTimeCourses->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-2" id="tab-retake" data-bs-toggle="pill" data-bs-target="#content-retake" type="button" role="tab">
                        <i class="bi bi-arrow-repeat text-danger me-1"></i> Học lại 
                        <span class="badge bg-danger ms-1">{{ $retakeCourses->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-2" id="tab-improvement" data-bs-toggle="pill" data-bs-target="#content-improvement" type="button" role="tab">
                        <i class="bi bi-arrow-up-circle-fill text-warning me-1"></i> Học cải thiện 
                        <span class="badge bg-warning text-dark ms-1">{{ $improvementCourses->count() }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="regTypeTabContent">
                {{-- TAB 1: HỌC MỚI --}}
                <div class="tab-pane fade show active" id="content-first-time" role="tabpanel">
                    @include('student.subjects.partials.section_table', [
                        'courses' => $firstTimeCourses,
                        'sections' => $firstTimeSections,
                        'regType' => 'FIRST_TIME',
                        'regTypeLabel' => 'Học mới',
                        'emptyMessage' => 'Không còn môn học mới nào cần đăng ký trong kỳ này.',
                        'isRegistrationActive' => $isRegistrationActive,
                    ])
                </div>

                {{-- TAB 2: HỌC LẠI --}}
                <div class="tab-pane fade" id="content-retake" role="tabpanel">
                    <div class="alert alert-danger mx-3 mt-3 mb-0 py-2 small d-flex align-items-center">
                        <i class="bi bi-info-circle-fill fs-5 me-2 text-danger"></i>
                        <div>
                            <strong>Diện Học lại:</strong> Danh sách hiển thị các môn bạn <strong>đã từng bị rớt (Điểm &lt; 5.0 hoặc Điểm F)</strong> ở các học kỳ trước, kèm tối đa 3 option thời gian để bạn lựa chọn lịch học phù hợp.
                        </div>
                    </div>
                    @include('student.subjects.partials.section_table', [
                        'courses' => $retakeCourses,
                        'sections' => $retakeSections,
                        'regType' => 'RETAKE',
                        'regTypeLabel' => 'Học lại',
                        'emptyMessage' => 'Chúc mừng! Bạn không có môn học nào bị rớt cần phải học lại.',
                        'isRegistrationActive' => $isRegistrationActive,
                    ])
                </div>

                {{-- TAB 3: HỌC CẢI THIỆN --}}
                <div class="tab-pane fade" id="content-improvement" role="tabpanel">
                    <div class="alert alert-warning mx-3 mt-3 mb-0 py-2 small d-flex align-items-center">
                        <i class="bi bi-info-circle-fill fs-5 me-2 text-warning"></i>
                        <div>
                            <strong>Diện Học cải thiện:</strong> Danh sách hiển thị các môn bạn <strong>đã học xong và đạt điểm</strong> ở các học kỳ trước, bạn có thể đăng ký học lại để cải thiện nâng cao điểm GPA tích lũy.
                        </div>
                    </div>
                    @include('student.subjects.partials.section_table', [
                        'courses' => $improvementCourses,
                        'sections' => $improvementSections,
                        'regType' => 'IMPROVEMENT',
                        'regTypeLabel' => 'Học cải thiện',
                        'emptyMessage' => 'Hiện không có lớp học phần mở cho các môn bạn đã tích lũy điểm.',
                        'isRegistrationActive' => $isRegistrationActive,
                    ])
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function onSemesterChange(semesterCode) {
    if (!semesterCode) return;
    const url = new URL(window.location.href);
    url.searchParams.set('semester', semesterCode);
    window.location.href = url.pathname + '?' + url.searchParams.toString();
}

function filterSectionsTable() {
    const input = document.getElementById('subjectFilter');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('.subject-row');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}
</script>
@endsection
