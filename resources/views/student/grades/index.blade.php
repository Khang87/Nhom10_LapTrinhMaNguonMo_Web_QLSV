@extends('layouts.spark')

@section('title', 'Kết quả học tập theo Chương trình khung - HUIT')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Kết quả học tập theo Chương trình khung</h1>
            <p class="text-secondary small mb-0">Theo dõi tiến độ tích lũy 8 học kỳ đào tạo và tra cứu bảng điểm chi tiết theo chuyên ngành</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('student.curriculum.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-diagram-3"></i>
                <span>Xem Khung CTĐT</span>
            </a>
            <a href="{{ route('student.subjects.index') }}" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-journal-plus"></i>
                <span>Đăng ký học phần</span>
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

    @if (session('error'))
        <div class="alert alert-light border border-danger-subtle shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
            <div class="text-dark small">{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($student)
        <!-- THẺ THÔNG TIN SINH VIÊN & CHỈ SỐ TIẾN ĐỘ KHUNG CHƯƠNG TRÌNH (SPARK UI) -->
        <div class="card border shadow-sm rounded-3 bg-white mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="row align-items-center g-4">
                    <!-- Bên trái: Avatar & Thông tin sinh viên -->
                    <div class="col-lg-5 col-md-12 border-end-lg">
                        <div class="d-flex align-items-start gap-3">
                            <div class="bg-light text-dark border rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 flex-shrink-0" 
                                 style="width: 58px; height: 58px;">
                                {{ mb_substr($student->full_name, 0, 1) }}
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <h2 class="h5 fw-bold text-dark mb-0">{{ $student->full_name }}</h2>
                                    <span class="badge bg-light text-dark border font-monospace">{{ $student->student_code }}</span>
                                </div>
                                <div class="small text-secondary mb-1">
                                    <i class="bi bi-mortarboard me-1"></i>Chuyên ngành: 
                                    <strong class="text-dark">{{ $student->specialization?->name ?? ($student->specialization_name ?: 'Chưa phân ngành') }}</strong>
                                </div>
                                <div class="small text-secondary d-flex flex-wrap align-items-center gap-3">
                                    <span><i class="bi bi-people me-1"></i>Lớp: <strong class="text-dark">{{ $student->class_code ?? 'Chưa gán' }}</strong></span>
                                    <span>•</span>
                                    <span><i class="bi bi-building me-1"></i>Khoa: {{ $student->faculty_name ?? 'Công nghệ thông tin' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bên phải: 4 Thẻ KPI Tiến độ & Học lực -->
                    <div class="col-lg-7 col-md-12">
                        <div class="row g-2 text-center">
                            <!-- KPI 1: GPA Hệ 4 -->
                            <div class="col-6 col-sm-3">
                                <div class="p-2.5 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                                    <span class="text-secondary small">GPA Tích lũy (4.0)</span>
                                    <div class="fs-4 fw-bold text-dark mt-0.5">
                                        {{ number_format($cumulativeGpa, 2) }}
                                    </div>
                                    <span class="badge bg-light text-dark border small mx-auto mt-1">
                                        {{ $academicRank }}
                                    </span>
                                </div>
                            </div>

                            <!-- KPI 2: Điểm TB Hệ 10 -->
                            <div class="col-6 col-sm-3">
                                <div class="p-2.5 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                                    <span class="text-secondary small">Điểm TB (10.0)</span>
                                    <div class="fs-4 fw-bold text-dark mt-0.5">
                                        {{ number_format($cumulativeAvg10, 2) }}
                                    </div>
                                    <span class="text-secondary small mt-1">Thang điểm 10</span>
                                </div>
                            </div>

                            <!-- KPI 3: Tín chỉ tích lũy / Khung -->
                            <div class="col-6 col-sm-3">
                                <div class="p-2.5 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                                    <span class="text-secondary small">Tín chỉ Tích lũy</span>
                                    <div class="fs-4 fw-bold text-dark mt-0.5">
                                        {{ $totalEarnedCredits }} <span class="fs-6 fw-normal text-secondary">/ {{ $totalFrameworkCredits }}</span>
                                    </div>
                                    <div class="progress mt-1.5" style="height: 4px;">
                                        <div class="progress-bar bg-dark" role="progressbar" style="width: {{ $completionRate }}%"></div>
                                    </div>
                                    <span class="text-secondary small mt-1" style="font-size: 0.75rem;">Đạt {{ $completionRate }}% Khung</span>
                                </div>
                            </div>

                            <!-- KPI 4: Môn Đạt / Môn Nợ -->
                            <div class="col-6 col-sm-3">
                                <div class="p-2.5 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                                    <span class="text-secondary small">Tình trạng Môn</span>
                                    <div class="d-flex align-items-center justify-content-center gap-1.5 mt-1">
                                        <span class="badge bg-light text-success border border-success-subtle small px-2 py-1">
                                            ✓ {{ $totalPassedCourses }} Đạt
                                        </span>
                                    </div>
                                    <div class="mt-1">
                                        @if ($totalFailedCourses > 0)
                                            <span class="badge bg-light text-danger border border-danger-subtle small px-2 py-0.5">
                                                ✗ {{ $totalFailedCourses }} Rớt
                                            </span>
                                        @else
                                            <span class="text-secondary small" style="font-size: 0.75rem;">Không nợ môn</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BẢNG ĐIỂM CHI TIẾT THEO 8 HỌC KỲ KHUNG CHƯƠNG TRÌNH -->
        <div class="card border shadow-sm rounded-3 bg-white overflow-hidden">
            <!-- Header Card & 8 Pills Kỳ học -->
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div>
                        <strong class="text-dark fs-6">Chương trình khung & Bảng điểm theo từng Học kỳ</strong>
                        <div class="text-secondary small mt-0.5">
                            Khung chương trình chuyên ngành: <strong class="text-dark">{{ $student->specialization?->name ?? $student->specialization_name }}</strong>
                        </div>
                    </div>
                    <span class="badge bg-light text-dark border font-monospace">
                        Tổng khung: {{ $totalFrameworkCourses }} học phần ({{ $totalFrameworkCredits }} TC)
                    </span>
                </div>

                <!-- 8 Tabs Học kỳ (Pills phẳng chuẩn Spark UI) -->
                <style>
                    #studentGradeTabs .nav-link {
                        transition: all 0.15s ease-in-out;
                    }
                    #studentGradeTabs .nav-link.active {
                        background-color: #212529 !important;
                        border-color: #212529 !important;
                        color: #ffffff !important;
                    }
                    #studentGradeTabs .nav-link:not(.active):hover {
                        background-color: #e9ecef !important;
                    }
                </style>
                <ul class="nav nav-pills d-flex flex-wrap gap-2 mb-0" id="studentGradeTabs" role="tablist">
                    @for ($sem = 1; $sem <= 8; $sem++)
                        @php
                            $stat = $semesterStats[$sem] ?? [];
                            $semGpa = $stat['gpa'] ?? null;
                            $isActive = ($activeSemester === $sem);
                            $semCoursesCount = $stat['total_courses'] ?? 0;
                        @endphp
                        <li class="nav-item flex-grow-1" role="presentation">
                            <button class="nav-link w-100 py-2 px-3 text-center rounded-3 border {{ $isActive ? 'active bg-dark text-white border-dark' : 'bg-light text-dark' }}" 
                                    id="grade-tab-{{ $sem }}" data-bs-toggle="pill" data-bs-target="#grade-pane-{{ $sem }}" 
                                    type="button" role="tab" aria-selected="{{ $isActive ? 'true' : 'false' }}">
                                <div class="fw-bold">Học kỳ {{ $sem }}</div>
                                <div class="small mt-0.5 {{ $isActive ? 'text-white-50' : 'text-secondary' }}" style="font-size: 0.75rem;">
                                    @if ($semGpa !== null)
                                        GPA: <strong class="{{ $isActive ? 'text-white' : 'text-dark' }}">{{ number_format($semGpa, 2) }}</strong>
                                    @else
                                        <span>{{ $semCoursesCount }} môn</span>
                                    @endif
                                </div>
                            </button>
                        </li>
                    @endfor
                </ul>
            </div>

            <!-- Nội dung Bảng điểm từng Kỳ học -->
            <div class="card-body p-0">
                <div class="tab-content" id="studentGradeContent">
                    @for ($sem = 1; $sem <= 8; $sem++)
                        @php
                            $semList = $curriculumBySemester[$sem] ?? collect();
                            $stat = $semesterStats[$sem] ?? [];
                            $semGpa = $stat['gpa'] ?? null;
                            $isActive = ($activeSemester === $sem);
                        @endphp
                        <div class="tab-pane fade {{ $isActive ? 'show active' : '' }}" id="grade-pane-{{ $sem }}" role="tabpanel">
                            <!-- Thanh tóm tắt nhanh kỳ đang chọn -->
                            <div class="p-3 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge bg-dark text-white rounded-circle px-2 py-1" style="font-size: 0.75rem;">
                                        {{ $sem }}
                                    </span>
                                    <strong class="text-dark">Bảng điểm Học kỳ {{ $sem }}</strong>
                                    <span class="badge bg-light text-dark border ms-1">
                                        {{ $stat['earned_credits'] ?? 0 }} / {{ $stat['total_credits'] ?? 0 }} Tín chỉ tích lũy
                                    </span>
                                    @if ($semGpa !== null)
                                        <span class="badge bg-light text-dark border font-monospace ms-1">
                                            GPA: <strong>{{ number_format($semGpa, 2) }}</strong> / 4.0
                                        </span>
                                        <span class="badge bg-light text-dark border font-monospace">
                                            TB: <strong>{{ number_format($stat['avg10'] ?? 0, 2) }}</strong> / 10
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex align-items-center gap-1.5 small">
                                    <span class="badge bg-light text-success border border-success-subtle px-2 py-1">
                                        ✓ Đạt: {{ $stat['passed_count'] ?? 0 }}
                                    </span>
                                    @if (($stat['failed_count'] ?? 0) > 0)
                                        <span class="badge bg-light text-danger border border-danger-subtle px-2 py-1">
                                            ✗ Rớt: {{ $stat['failed_count'] ?? 0 }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Bảng dữ liệu môn học -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 text-center">
                                    <thead class="table-light text-secondary small">
                                        <tr>
                                            <th class="ps-3 text-center" style="width: 50px;">#</th>
                                            <th style="width: 110px;" class="text-start">Mã HP</th>
                                            <th class="text-start">Tên Học phần</th>
                                            <th style="width: 70px;">TC</th>
                                            <th style="width: 100px;">Loại</th>
                                            <th style="width: 90px;" title="Điểm quá trình (20%)">QT (20%)</th>
                                            <th style="width: 90px;" title="Điểm giữa kỳ (20%)">GK (20%)</th>
                                            <th style="width: 90px;" title="Điểm cuối kỳ (60%)">CK (60%)</th>
                                            <th class="bg-light" style="width: 90px;">Tổng (10)</th>
                                            <th style="width: 80px;">Thang 4</th>
                                            <th style="width: 90px;" class="text-center">Chữ</th>
                                            <th class="text-center" style="width: 100px;">Trạng thái</th>
                                            <th class="text-center pe-3" style="width: 110px;">Phúc khảo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($semList as $index => $row)
                                            @php
                                                $course = $row['course'];
                                                $item = $row['item'];
                                                $enr = $row['enrollment'];
                                                $hasScore = ($enr && $enr->total_score_4 !== null);
                                            @endphp
                                            <tr>
                                                <td class="ps-3 text-center text-muted small">{{ $index + 1 }}</td>
                                                <td class="text-start">
                                                    <span class="badge bg-light text-dark border font-monospace">
                                                        {{ $course?->course_code }}
                                                    </span>
                                                </td>
                                                <td class="text-start">
                                                    <div class="fw-semibold text-dark">{{ $course?->course_name }}</div>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap text-secondary small">
                                                        <span>{{ $course?->credits }} tín chỉ</span>
                                                        @if ($row['lecturer'] || $row['section']?->lecturer)
                                                            <span>•</span>
                                                            <span><i class="bi bi-person-badge text-secondary me-0.5"></i>GV: <strong class="text-dark">{{ $row['lecturer']?->full_name ?? $row['section']?->lecturer?->full_name }}</strong></span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">{{ $course?->credits }}</span>
                                                </td>
                                                <td>
                                                    @if ($item?->is_mandatory)
                                                        <span class="badge bg-light text-dark border small">Bắt buộc</span>
                                                    @else
                                                        <span class="badge bg-light text-secondary border small">Tự chọn</span>
                                                    @endif
                                                </td>

                                                <!-- Điểm QT (nếu chưa có thì để trống) -->
                                                <td class="small">
                                                    {{ $enr && $enr->process_score !== null ? number_format($enr->process_score, 1) : '' }}
                                                </td>

                                                <!-- Điểm GK (nếu chưa có thì để trống) -->
                                                <td class="small">
                                                    {{ $enr && $enr->midterm_score !== null ? number_format($enr->midterm_score, 1) : '' }}
                                                </td>

                                                <!-- Điểm CK (nếu chưa có thì để trống) -->
                                                <td class="small">
                                                    {{ $enr && $enr->final_score !== null ? number_format($enr->final_score, 1) : '' }}
                                                </td>

                                                <!-- Điểm Tổng kết 10 -->
                                                <td class="bg-light fw-bold text-dark">
                                                    {{ $enr && $enr->total_score_10 !== null ? number_format($enr->total_score_10, 1) : '' }}
                                                </td>

                                                <!-- Thang 4 -->
                                                <td class="fw-bold text-dark small">
                                                    {{ $enr && $enr->total_score_4 !== null ? number_format($enr->total_score_4, 1) : '' }}
                                                </td>

                                                <!-- Điểm chữ ABCDF -->
                                                <td class="text-center">
                                                    @if ($enr && $enr->letter_grade)
                                                        <span class="badge {{ $enr->letter_grade === 'F' ? 'bg-light text-danger border border-danger-subtle' : 'bg-light text-dark border font-monospace' }} px-2 py-1">
                                                            {{ $enr->letter_grade }}
                                                        </span>
                                                    @endif
                                                </td>

                                                <!-- Trạng thái (Đạt / Chưa đạt / để trống nếu chưa có điểm) -->
                                                <td class="text-center">
                                                    @if ($hasScore)
                                                        @if ($enr->isPassed())
                                                            <span class="badge bg-light text-success border border-success-subtle px-2 py-1">
                                                                <i class="bi bi-check-lg me-0.5"></i>Đạt
                                                            </span>
                                                        @else
                                                            <span class="badge bg-light text-danger border border-danger-subtle px-2 py-1">
                                                                <i class="bi bi-x-lg me-0.5"></i>Rớt
                                                            </span>
                                                        @endif
                                                    @endif
                                                </td>

                                                <!-- Nút Phúc khảo -->
                                                <td class="text-center pe-3">
                                                    @if ($hasScore && $enr)
                                                        <button type="button" class="btn btn-sm btn-outline-dark py-0.5 px-2" style="font-size: 0.75rem;"
                                                                data-bs-toggle="modal" data-bs-target="#appealModal-{{ $enr->id }}">
                                                            <i class="bi bi-chat-left-dots me-0.5"></i>Phúc khảo
                                                        </button>

                                                        <!-- Modal Phúc khảo -->
                                                        <div class="modal fade" id="appealModal-{{ $enr->id }}" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content border-0 shadow">
                                                                    <form method="POST" action="{{ route('student.grades.appeal') }}">
                                                                        @csrf
                                                                        <input type="hidden" name="enrollment_id" value="{{ $enr->id }}">
                                                                        <div class="modal-header border-bottom">
                                                                            <h5 class="modal-title fw-bold text-dark fs-6">
                                                                                <i class="bi bi-chat-left-dots text-primary me-1"></i>Đơn đề nghị phúc khảo điểm
                                                                            </h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body p-4 text-start">
                                                                            <div class="p-3 rounded-3 bg-light border mb-3">
                                                                                <div class="small text-secondary">Học phần:</div>
                                                                                <div class="fw-bold text-dark">{{ $course?->course_name }} ({{ $course?->course_code }})</div>
                                                                                <div class="small text-secondary mt-1">
                                                                                    Điểm hiện tại: QT: <strong>{{ number_format($enr->process_score, 1) }}</strong> 
                                                                                    • GK: <strong>{{ number_format($enr->midterm_score, 1) }}</strong> 
                                                                                    • CK: <strong>{{ number_format($enr->final_score, 1) }}</strong>
                                                                                    • Tổng kết: <strong class="text-primary">{{ number_format($enr->total_score_10, 1) }}</strong>
                                                                                </div>
                                                                            </div>

                                                                            <div class="mb-3">
                                                                                <label class="form-label small fw-bold text-dark">Thành phần điểm muốn phúc khảo:</label>
                                                                                <select name="component" class="form-select form-select-sm" required>
                                                                                    <option value="FINAL">Điểm thi kết thúc học phần (60%)</option>
                                                                                    <option value="MIDTERM">Điểm thi giữa kỳ (20%)</option>
                                                                                    <option value="PROCESS">Điểm quá trình / chuyên cần (20%)</option>
                                                                                    <option value="TOTAL">Điểm tổng kết môn</option>
                                                                                </select>
                                                                            </div>

                                                                            <div class="mb-3">
                                                                                <label class="form-label small fw-bold text-dark">Điểm mong đợi (thang điểm 10):</label>
                                                                                <input type="number" step="0.1" min="0" max="10" name="desired_score" class="form-control form-control-sm" placeholder="Ví dụ: 8.0">
                                                                            </div>

                                                                            <div class="mb-3">
                                                                                <label class="form-label small fw-bold text-dark">Lý do phúc khảo chi tiết:</label>
                                                                                <textarea name="reason" class="form-control form-control-sm" rows="3" required placeholder="Nêu rõ lý do, câu hỏi thắc mắc trong bài thi hoặc bài tập..."></textarea>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer border-top bg-light">
                                                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                                                                            <button type="submit" class="btn btn-sm btn-dark px-3">Gửi đơn phúc khảo</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted small">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="12" class="text-center py-4 text-secondary small">
                                                    Chưa có môn học trong Học kỳ {{ $sem }} cho chuyên ngành này.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <!-- LỊCH SỬ PHÚC KHẢO ĐIỂM CỦA SINH VIÊN -->
        @if ($myAppeals->isNotEmpty())
            <div class="card border shadow-sm rounded-3 bg-white overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <strong class="text-dark fs-6">Lịch sử phúc khảo điểm & Phản hồi từ Giảng viên</strong>
                        <div class="text-secondary small mt-0.5">Theo dõi tiến độ giải quyết thắc mắc điểm thi</div>
                    </div>
                    <span class="badge bg-light text-dark border font-monospace">
                        {{ $myAppeals->count() }} đơn
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary small">
                                <tr>
                                    <th class="ps-3 text-center" style="width: 50px;">#</th>
                                    <th>Môn học</th>
                                    <th class="text-center" style="width: 140px;">Thành phần</th>
                                    <th class="text-center" style="width: 100px;">Điểm gốc</th>
                                    <th class="text-center" style="width: 100px;">Điểm mới</th>
                                    <th class="text-center" style="width: 150px;">Trạng thái</th>
                                    <th class="pe-3">Phản hồi của Giảng viên</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($myAppeals as $aIdx => $app)
                                    @php
                                        $badge = $app->getStatusBadge();
                                    @endphp
                                    <tr>
                                        <td class="ps-3 text-center text-muted small">{{ $aIdx + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $app->section?->course?->course_name }}</div>
                                            <div class="text-secondary small font-monospace">{{ $app->section?->section_code }} • Gửi ngày {{ $app->created_at->format('d/m/Y') }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border small">{{ $app->getComponentName() }}</span>
                                        </td>
                                        <td class="text-center fw-bold text-dark">
                                            {{ number_format($app->current_score, 1) }}
                                        </td>
                                        <td class="text-center fw-bold text-success">
                                            {{ $app->new_score !== null ? number_format($app->new_score, 1) : '-' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $badge['bg'] }} text-white px-2 py-1">
                                                {{ $badge['label'] }}
                                            </span>
                                        </td>
                                        <td class="pe-3 small">
                                            @if ($app->lecturer_feedback)
                                                <div class="text-dark">{{ $app->lecturer_feedback }}</div>
                                            @else
                                                <span class="text-muted">Đang chờ Giảng viên kiểm tra bài thi...</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="alert alert-light border text-center py-5">
            <i class="bi bi-person-x fs-1 text-secondary mb-3 d-block"></i>
            <h5 class="fw-bold text-dark">Chưa liên kết hồ sơ sinh viên</h5>
            <p class="text-secondary small mb-0">Tài khoản này chưa được gắn với mã sinh viên cụ thể trong hệ thống.</p>
        </div>
    @endif
</div>
@endsection
