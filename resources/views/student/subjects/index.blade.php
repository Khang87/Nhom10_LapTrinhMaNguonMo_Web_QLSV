@extends('layouts.spark')

@section('title', 'Đăng ký môn học - HUIT')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Đăng ký môn học trực tuyến</h1>
            <p class="text-secondary small mb-0">
                Sinh viên: <strong class="text-dark">{{ $student->full_name ?? auth()->user()->name }}</strong> (MSSV: {{ $student->student_code ?? auth()->user()->username }})
                • Lớp: <strong class="text-dark">{{ $student->class_code ?? '14DHTH04' }}</strong>
                • Chuyên ngành: <strong class="text-dark">{{ $student->specialization?->name ?? ($student->specialization_name ?: 'Công nghệ phần mềm') }}</strong>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('student.schedule.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5" title="Xem thời khóa biểu">
                <i class="bi bi-calendar-week"></i>
                <span>Thời khóa biểu</span>
            </a>
            <a href="{{ route('student.tuition.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5" title="Tra cứu công nợ & thanh toán học phí">
                <i class="bi bi-receipt"></i>
                <span>Công nợ & Học phí</span>
            </a>
        </div>
    </div>

    {{-- Alert Messages In-page --}}
    @if (session('success'))
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check2-circle text-dark"></i>
            <div class="text-dark small">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-circle text-dark"></i>
            <div class="text-dark small">{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- BỘ CHỌN HỌC KỲ VÀ TRẠNG THÁI ĐỢT ĐĂNG KÝ (THIẾT KẾ GIỐNG PHÂN HỆ ADMIN) --}}
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('student.subjects.index') }}" id="semesterForm" class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex flex-wrap align-items-center gap-3 flex-grow-1" style="max-width: 650px;">
                    <label for="semesterSelect" class="form-label mb-0 small text-dark fw-bold text-nowrap d-flex align-items-center gap-1.5">
                        <span class="badge bg-dark text-white rounded-circle px-2 py-1" style="font-size: 0.75rem;">1</span>
                        <span>Đợt Học kỳ áp dụng:</span>
                    </label>
                    <div class="input-group input-group-sm flex-grow-1" style="max-width: 420px;">
                        <select id="semesterSelect" name="semester" class="form-select py-2" onchange="this.form.submit()">
                            @foreach ($availableSemesters as $sem)
                                <option value="{{ $sem->semester_code }}" {{ $selectedSemesterCode === $sem->semester_code ? 'selected' : '' }}>
                                    {{ $sem->semester_name }} {{ $sem->isRegistrationActive() ? '(Đang mở ĐK)' : '(Đã đóng)' }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-dark d-inline-flex align-items-center gap-1.5 px-3 py-2 text-nowrap fw-medium shadow-none" id="btnFilterSemester" title="Nhấn để tải danh sách môn học theo học kỳ">
                            <i class="bi bi-funnel-fill"></i>
                            <span>Lọc</span>
                        </button>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    @if ($isRegistrationActive)
                        <span class="badge bg-light text-success border border-success-subtle px-3 py-2">
                            <i class="bi bi-check-circle-fill me-1"></i>Đang mở đăng ký (Còn {{ $selectedSemester->remaining_time ?? 'thời hạn' }})
                        </span>
                    @else
                        <span class="badge bg-light text-danger border border-danger-subtle px-3 py-2">
                            <i class="bi bi-lock-fill me-1"></i>Đã đóng đợt đăng ký
                        </span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- 3 THẺ KPI TỔNG QUAN HỌC KỲ --}}
    @php
        $totalCredits = $enrollments->sum(fn($e) => $e->section->course->credits ?? 0);
        $totalTuition = $enrollments->sum(fn($e) => ($e->section->course->credits ?? 0) * ($e->section->course->tuition_fee_per_credit ?? 550000));
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="p-3 rounded-3 bg-light border h-100">
                <div class="text-secondary small fw-medium">Lớp học phần đã đăng ký ({{ $selectedSemester->semester_name ?? '' }})</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ $enrollments->count() }} <span class="fs-6 fw-normal text-secondary">lớp</span></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-3 rounded-3 bg-light border h-100">
                <div class="text-secondary small fw-medium">Tổng số tín chỉ đăng ký kỳ này</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ $totalCredits }} <span class="fs-6 fw-normal text-secondary">tín chỉ</span></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-3 rounded-3 bg-light border h-100">
                <div class="text-secondary small fw-medium">Học phí tạm tính kỳ này</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($totalTuition, 0, ',', '.') }} <span class="fs-6 fw-normal text-secondary">VNĐ</span></div>
            </div>
        </div>
    </div>

    {{-- KHỐI 1: DANH SÁCH MÔN HỌC ĐÃ ĐĂNG KÝ CỦA KỲ ĐƯỢC CHỌN --}}
    <div class="card border shadow-sm rounded-3 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark text-white rounded-circle px-2 py-1" style="font-size: 0.75rem;">✓</span>
                <strong class="text-dark fs-6">
                    Học phần đã đăng ký — {{ $selectedSemester->semester_name ?? $selectedSemesterCode }}
                </strong>
            </div>
            <span class="badge bg-light text-dark border">
                Tổng cộng: {{ $enrollments->count() }} học phần ({{ $totalCredits }} TC)
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-3 text-center" style="width: 50px;">#</th>
                            <th style="width: 130px;">Mã LHP</th>
                            <th>Tên môn học</th>
                            <th class="text-center" style="width: 70px;">Số TC</th>
                            <th style="width: 170px;">Lịch học tuần</th>
                            <th style="width: 140px;">Phòng học</th>
                            <th style="width: 180px;">Giảng viên</th>
                            <th class="text-center" style="width: 120px;">Loại đăng ký</th>
                            <th class="text-end" style="width: 130px;">Học phí</th>
                            <th class="text-center pe-3" style="width: 110px;">Thao tác</th>
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
                                <td class="ps-3 text-center text-muted small">{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $sec->section_code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $sec->course->course_name }}</div>
                                    <div class="d-flex flex-wrap align-items-center gap-1.5 mt-0.5">
                                        <span class="text-secondary small font-monospace">{{ $sec->course->course_code }}</span>
                                        @if (isset($curriculumOrderMap[$sec->course_id]))
                                            <span class="badge bg-light text-primary border border-primary-subtle" style="font-size: 10px;">
                                                Kỳ {{ $curriculumOrderMap[$sec->course_id] }} khung
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $sec->course->credits }}</span>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">{{ $dayStr }}</div>
                                    <div class="text-secondary small">Tiết {{ $sec->start_period }} - {{ $sec->end_period }}</div>
                                    <div class="text-secondary small mt-0.5" style="font-size: 11px;">
                                        <i class="bi bi-calendar-range me-0.5"></i>{{ $sec->getDateRangeText() }}
                                    </div>
                                </td>
                                <td>
                                    <span class="small text-secondary">{{ $sec->room_name }}</span>
                                </td>
                                <td>
                                    <span class="small fw-medium text-dark">{{ $sec->lecturer->full_name ?? 'Chưa phân công' }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($enrollment->registration_type === 'RETAKE')
                                        <span class="badge bg-light text-danger border border-danger-subtle small px-2 py-1">
                                            Học lại
                                        </span>
                                    @elseif ($enrollment->registration_type === 'IMPROVEMENT')
                                        <span class="badge bg-light text-warning border border-warning-subtle small px-2 py-1">
                                            Học cải thiện
                                        </span>
                                    @else
                                        <span class="badge bg-light text-dark border small px-2 py-1">
                                            Học mới
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold text-dark small">
                                    {{ number_format($fee, 0, ',', '.') }} đ
                                </td>
                                <td class="text-center pe-3">
                                    @if ($isRegistrationActive)
                                        <form method="POST" action="{{ route('student.subjects.unregister', $enrollment->id, false) }}"
                                              onsubmit="return confirm('Bạn có chắc chắn muốn hủy đăng ký môn [{{ $sec->course->course_name }}]?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="semester" value="{{ $selectedSemesterCode }}">
                                            <button class="btn btn-sm btn-outline-danger py-1 px-2.5 d-inline-flex align-items-center gap-1" type="submit" title="Hủy học phần này">
                                                <i class="bi bi-trash3"></i>
                                                <span>Hủy</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-light text-secondary border small" title="Đã khóa khi hết hạn đợt đăng ký">
                                            <i class="bi bi-lock me-1"></i>Đã khóa
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-secondary small">
                                    <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                    Bạn chưa đăng ký lớp học phần nào trong học kỳ này.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- KHỐI 2: ĐĂNG KÝ HỌC PHẦN MỞ (GỒM 3 TABS: HỌC MỚI, HỌC LẠI, HỌC CẢI THIỆN) --}}
    <div class="card border shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <strong class="text-dark fs-6">
                        Đăng ký học phần theo Chuyên ngành — {{ $selectedSemester->semester_name ?? $selectedSemesterCode }}
                    </strong>
                    <div class="text-secondary small mt-0.5">
                        Chọn ca học và giảng viên phù hợp cho các môn trong chương trình khung hoặc môn học lại, cải thiện
                    </div>
                </div>
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-light border"><i class="bi bi-search text-secondary"></i></span>
                    <input type="text" id="subjectFilter" class="form-control" placeholder="Lọc nhanh môn học..." onkeyup="filterSectionsTable()">
                </div>
            </div>

            {{-- 3 TABS ĐĂNG KÝ THEO PHONG CÁCH ADMIN SPARK UI --}}
            <ul class="nav nav-pills nav-fill bg-light p-1 rounded-3 border gap-1" id="regTypeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-2 fw-medium bg-dark text-white" id="tab-first-time" data-bs-toggle="pill" data-bs-target="#content-first-time" type="button" role="tab">
                        <i class="bi bi-book me-1"></i> Học mới 
                        <span class="badge bg-light text-dark ms-1">{{ $firstTimeCourses->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-2 fw-medium text-dark bg-transparent" id="tab-retake" data-bs-toggle="pill" data-bs-target="#content-retake" type="button" role="tab">
                        <i class="bi bi-arrow-repeat me-1"></i> Học lại 
                        <span class="badge bg-light text-dark border ms-1">{{ $retakeCourses->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-2 fw-medium text-dark bg-transparent" id="tab-improvement" data-bs-toggle="pill" data-bs-target="#content-improvement" type="button" role="tab">
                        <i class="bi bi-arrow-up-circle me-1"></i> Học cải thiện 
                        <span class="badge bg-light text-dark border ms-1">{{ $improvementCourses->count() }}</span>
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
                    <div class="alert alert-light border mx-3 mt-3 mb-0 py-2 small d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-dark"></i>
                        <div class="text-dark">
                            <strong>Diện Học lại:</strong> Danh sách hiển thị các môn bạn <strong>đã từng bị rớt (Điểm &lt; 4.0 hoặc Điểm F)</strong> ở các học kỳ trước để bạn đăng ký học lại nhằm hoàn thành tiến độ đào tạo.
                        </div>
                    </div>
                    @include('student.subjects.partials.section_table', [
                        'courses' => $retakeCourses,
                        'sections' => $retakeSections,
                        'regType' => 'RETAKE',
                        'regTypeLabel' => 'Học lại',
                        'emptyMessage' => 'Tuyệt vời! Bạn không có môn học nào bị rớt cần phải học lại.',
                        'isRegistrationActive' => $isRegistrationActive,
                    ])
                </div>

                {{-- TAB 3: HỌC CẢI THIỆN --}}
                <div class="tab-pane fade" id="content-improvement" role="tabpanel">
                    <div class="alert alert-light border mx-3 mt-3 mb-0 py-2 small d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-dark"></i>
                        <div class="text-dark">
                            <strong>Diện Học cải thiện:</strong> Danh sách hiển thị các môn bạn <strong>đã học xong và đạt điểm</strong> ở các học kỳ trước, bạn có thể đăng ký học lại để nâng cao điểm GPA tích lũy.
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
function filterSectionsTable() {
    const input = document.getElementById('subjectFilter');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('.subject-row');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// Xử lý chuyển đổi active class đơn sắc cho 3 tab
document.querySelectorAll('#regTypeTabs button').forEach(button => {
    button.addEventListener('shown.bs.tab', event => {
        document.querySelectorAll('#regTypeTabs button').forEach(btn => {
            btn.classList.remove('bg-dark', 'text-white');
            btn.classList.add('text-dark', 'bg-transparent');
        });
        event.target.classList.remove('text-dark', 'bg-transparent');
        event.target.classList.add('bg-dark', 'text-white');
    });
});
</script>
@endsection
