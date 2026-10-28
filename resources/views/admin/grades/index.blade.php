@extends('layouts.spark')

@section('title', 'Quản lý Điểm Sinh Viên theo Khung Chương trình')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Quản lý Điểm Sinh viên theo Khung Chương trình</h1>
            <p class="text-secondary small mb-0">Theo dõi tiến độ tích lũy 8 học kỳ, tra cứu bảng điểm chi tiết và cập nhật điểm học phần</p>
        </div>
        @if ($student)
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.students.show', $student) }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
                    <i class="bi bi-person-badge"></i>
                    <span>Hồ sơ sinh viên</span>
                </a>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check2-circle text-dark"></i>
            <div class="text-dark small">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- THANH ĐIỀU HƯỚNG & TÌM KIẾM SINH VIÊN (ĐÃ BỎ 2 DROPDOWN LỌC TRÙNG LẶP) -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <!-- Breadcrumb phân cấp trực quan: Khoa -> Khóa -> Chuyên ngành -> Lớp -> Sinh viên -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 align-items-center small">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.grades.index') }}" class="text-decoration-none fw-semibold {{ !$selectedFacultyId && !$keyword ? 'text-dark' : 'text-secondary' }}">
                                <i class="bi bi-building me-1"></i>Tất cả Khoa
                            </a>
                        </li>
                        @if ($selectedFaculty)
                            <li class="breadcrumb-item {{ !$selectedCohort ? 'active fw-bold text-dark' : '' }}">
                                @if ($selectedCohort)
                                    <a href="{{ route('admin.grades.index', ['faculty_id' => $selectedFaculty->id]) }}" class="text-decoration-none text-secondary">
                                        {{ $selectedFaculty->name }}
                                    </a>
                                @else
                                    {{ $selectedFaculty->name }}
                                @endif
                            </li>
                        @endif
                        @if ($selectedCohort)
                            <li class="breadcrumb-item {{ !$selectedSpecialization ? 'active fw-bold text-dark' : '' }}">
                                @if ($selectedSpecialization)
                                    <a href="{{ route('admin.grades.index', ['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort]) }}" class="text-decoration-none text-secondary">
                                        Khóa {{ $selectedCohort }}
                                    </a>
                                @else
                                    Khóa {{ $selectedCohort }}
                                @endif
                            </li>
                        @endif
                        @if ($selectedSpecialization)
                            <li class="breadcrumb-item {{ !$selectedClassCode && !$student ? 'active fw-bold text-dark' : '' }}">
                                @if ($selectedClassCode || $student)
                                    <a href="{{ route('admin.grades.index', array_filter(['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort, 'specialization_id' => $selectedSpecialization->id])) }}" class="text-decoration-none text-secondary">
                                        Ngành {{ $selectedSpecialization->name }}
                                    </a>
                                @else
                                    Ngành {{ $selectedSpecialization->name }}
                                @endif
                            </li>
                        @endif
                        @if ($selectedClassCode)
                            <li class="breadcrumb-item {{ !$student ? 'active fw-bold text-dark' : '' }}">
                                @if ($student)
                                    <a href="{{ route('admin.grades.index', array_filter(['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort, 'specialization_id' => $selectedSpecId, 'class_code' => $selectedClassCode])) }}" class="text-decoration-none text-secondary">
                                        Lớp {{ $selectedClassCode }}
                                    </a>
                                @else
                                    Lớp {{ $selectedClassCode }}
                                @endif
                            </li>
                        @endif
                        @if ($student)
                            <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">
                                {{ $student->full_name }} ({{ $student->student_code }})
                            </li>
                        @endif
                        @if ($keyword)
                            <li class="breadcrumb-item active text-dark" aria-current="page">
                                Tìm kiếm: "{{ $keyword }}"
                            </li>
                        @endif
                    </ol>
                </nav>

                <!-- Ô tìm kiếm MSSV / Họ tên & Nút đặt lại -->
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <form method="GET" action="{{ route('admin.grades.index') }}" class="d-flex gap-1" id="searchKeywordForm">
                        @if ($selectedFacultyId)
                            <input type="hidden" name="faculty_id" value="{{ $selectedFacultyId }}">
                        @endif
                        @if ($selectedCohort)
                            <input type="hidden" name="cohort" value="{{ $selectedCohort }}">
                        @endif
                        @if ($selectedSpecId)
                            <input type="hidden" name="specialization_id" value="{{ $selectedSpecId }}">
                        @endif
                        @if ($selectedClassCode)
                            <input type="hidden" name="class_code" value="{{ $selectedClassCode }}">
                        @endif
                        <div class="input-group input-group-sm" style="min-width: 280px;">
                            <input type="text" name="keyword" id="keywordInput" class="form-control py-1.5 no-auto-submit" 
                                   value="{{ $keyword }}" placeholder="Tìm theo MSSV hoặc họ tên...">
                            @if ($keyword)
                                <a href="{{ route('admin.grades.index', array_filter(['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort, 'specialization_id' => $selectedSpecId, 'class_code' => $selectedClassCode])) }}" 
                                   class="btn btn-outline-secondary d-flex align-items-center" title="Xóa tìm kiếm">
                                    <i class="bi bi-x"></i>
                                </a>
                            @endif
                            <button type="submit" class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1" title="Tìm kiếm">
                                <i class="bi bi-search"></i>
                                <span>Tìm</span>
                            </button>
                        </div>
                    </form>

                    @if ($selectedFacultyId || $selectedCohort || $selectedSpecId || $selectedClassCode || $keyword || $student)
                        <a href="{{ route('admin.grades.index') }}" class="btn btn-outline-secondary btn-sm py-1.5 px-3 d-inline-flex align-items-center gap-1 shadow-sm" title="Đặt lại về ban đầu">
                            <i class="bi bi-arrow-clockwise"></i>
                            <span class="d-none d-sm-inline">Đặt lại</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($student)
        <!-- THANH ĐIỀU HƯỚNG & CHỌN NHANH SINH VIÊN TRONG LỚP -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 bg-white border rounded-3 p-3 mb-4 shadow-sm">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('admin.grades.index', array_filter(['faculty_id' => $selectedFacultyId, 'specialization_id' => $selectedSpecId, 'cohort' => $selectedCohort, 'class_code' => $selectedClassCode, 'keyword' => $keyword])) }}" 
                   class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 shadow-sm">
                    <i class="bi bi-arrow-left"></i>
                    <span>Xem danh sách sinh viên lớp {{ $student->class_code }}</span>
                </a>
                <span class="text-secondary small d-none d-md-inline">|</span>
                <span class="small text-secondary d-none d-md-inline">
                    Đang xem bảng điểm: <strong class="text-dark">{{ $student->full_name }}</strong> ({{ $student->student_code }})
                </span>
            </div>

            <!-- Dropdown chuyển nhanh giữa các sinh viên trong lớp -->
            @if ($allStudents->count() > 1)
                <div class="d-flex align-items-center gap-2">
                    <label class="small text-secondary fw-semibold text-nowrap mb-0">Đổi sinh viên:</label>
                    <select class="form-select form-select-sm py-1.5 fw-medium" style="min-width: 260px;" onchange="window.location.href=this.value">
                        @foreach ($allStudents as $stu)
                            <option value="{{ route('admin.grades.index', array_filter(['faculty_id' => $selectedFacultyId, 'specialization_id' => $selectedSpecId, 'cohort' => $selectedCohort, 'class_code' => $selectedClassCode, 'keyword' => $keyword, 'student_id' => $stu->id, 'semester' => $activeSemester])) }}"
                                {{ $student->id === $stu->id ? 'selected' : '' }}>
                                [{{ $stu->student_code }}] {{ $stu->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <!-- THẺ THÔNG TIN SINH VIÊN & CHỈ SỐ TIẾN ĐỘ KHUNG CHƯƠNG TRÌNH (SPARK MINIMALIST) -->
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
                                    <strong class="text-dark">{{ $student->specialization?->name ?? $student->specialization_name }}</strong>
                                </div>
                                <div class="small text-secondary d-flex flex-wrap align-items-center gap-3">
                                    <span><i class="bi bi-people me-1"></i>Lớp: <strong class="text-dark">{{ $student->class_code ?? 'Chưa gán' }}</strong></span>
                                    <span>•</span>
                                    <span><i class="bi bi-building me-1"></i>Khoa: {{ $student->faculty_name }}</span>
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
            <!-- Header Card & Thanh 8 Pills Kỳ học -->
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div>
                        <strong class="text-dark fs-6">Chương trình khung & Bảng điểm theo từng Học kỳ</strong>
                        <div class="text-secondary small mt-0.5">
                            Theo dõi kết quả học tập của <strong>{{ $student->full_name }}</strong> qua 8 học kỳ đào tạo chính khóa
                        </div>
                    </div>
                    <span class="badge bg-light text-dark border font-monospace">
                        Tổng khung: {{ $totalFrameworkCourses }} học phần ({{ $totalFrameworkCredits }} TC)
                    </span>
                </div>

                <!-- 8 Tabs Học kỳ (Pills dạng phẳng Spark) -->
                <style>
                    #gradeSemesterTabs .nav-link {
                        transition: all 0.15s ease-in-out;
                    }
                    #gradeSemesterTabs .nav-link.active {
                        background-color: #212529 !important;
                        border-color: #212529 !important;
                        color: #ffffff !important;
                    }
                    #gradeSemesterTabs .nav-link:not(.active):hover {
                        background-color: #e9ecef !important;
                    }
                </style>
                <ul class="nav nav-pills d-flex flex-wrap gap-2 mb-0" id="gradeSemesterTabs" role="tablist">
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
                <div class="tab-content" id="gradeSemesterContent">
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
                                        {{ $stat['earned_credits'] ?? 0 }} / {{ $stat['total_credits'] ?? 0 }} Tín chỉ
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
                                    <span class="badge bg-light text-secondary border px-2 py-1">
                                        Chưa tích lũy: {{ $stat['pending_count'] ?? 0 }}
                                    </span>
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
                                            <th class="text-center pe-3" style="width: 120px;">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($semList as $index => $row)
                                            @php
                                                $course = $row['course'];
                                                $item = $row['item'];
                                                $enr = $row['enrollment'];
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
                                                        <span>•</span>
                                                        <span><i class="bi bi-person-badge text-secondary me-0.5"></i>GV: <strong class="text-dark">{{ $row['lecturer']?->full_name ?? ($row['enrollment']?->section?->lecturer?->full_name ?? 'Chưa phân công') }}</strong></span>
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

                                                <!-- Điểm QT -->
                                                <td class="small">
                                                    {{ $enr && $enr->process_score !== null ? number_format($enr->process_score, 1) : '-' }}
                                                </td>

                                                <!-- Điểm GK -->
                                                <td class="small">
                                                    {{ $enr && $enr->midterm_score !== null ? number_format($enr->midterm_score, 1) : '-' }}
                                                </td>

                                                <!-- Điểm CK -->
                                                <td class="small">
                                                    {{ $enr && $enr->final_score !== null ? number_format($enr->final_score, 1) : '-' }}
                                                </td>

                                                <!-- Điểm Tổng kết 10 (Highlight xám nhẹ) -->
                                                <td class="bg-light fw-bold text-dark">
                                                    {{ $enr && $enr->total_score_10 !== null ? number_format($enr->total_score_10, 1) : '-' }}
                                                </td>

                                                <!-- Thang 4 -->
                                                <td class="fw-bold text-dark small">
                                                    {{ $enr && $enr->total_score_4 !== null ? number_format($enr->total_score_4, 1) : '-' }}
                                                </td>

                                                <!-- Điểm chữ ABCDF hoặc Chưa học -->
                                                <td class="text-center">
                                                    @if ($enr && $enr->letter_grade)
                                                        <span class="badge {{ $enr->letter_grade === 'F' ? 'bg-light text-danger border border-danger-subtle' : 'bg-light text-dark border font-monospace' }} px-2 py-1">
                                                            {{ $enr->letter_grade }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-secondary border small">Chưa học</span>
                                                    @endif
                                                </td>

                                                <!-- Thao tác Tùy chỉnh điểm (Admin) -->
                                                <td class="text-center pe-3">
                                                    @php
                                                        $cId = $course?->id ?? ($item?->course_id ?? '');
                                                        $cName = $course?->course_name ?? ($item?->course?->course_name ?? 'Môn học');
                                                        $cCode = $course?->course_code ?? ($item?->course?->course_code ?? '');
                                                        $cCredits = $course?->credits ?? ($item?->course?->credits ?? 3);
                                                        $qtVal = $enr && $enr->process_score !== null ? (float) $enr->process_score : '';
                                                        $gkVal = $enr && $enr->midterm_score !== null ? (float) $enr->midterm_score : '';
                                                        $ckVal = $enr && $enr->final_score !== null ? (float) $enr->final_score : '';
                                                        $lecName = $row['lecturer']?->full_name ?? ($row['enrollment']?->section?->lecturer?->full_name ?? 'Chưa phân công');
                                                    @endphp
                                                    <button type="button" 
                                                            class="btn btn-sm btn-light border text-dark d-inline-flex align-items-center gap-1 px-2.5 py-1"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#scoreModal_{{ $sem }}_{{ $cId }}"
                                                            title="Admin tùy chỉnh / cập nhật lại điểm học phần">
                                                        <i class="bi bi-pencil-square"></i>
                                                        <span>Tùy chỉnh</span>
                                                    </button>

                                                    <!-- Modal Nhập / Tùy Chỉnh Điểm Riêng Cho Môn Học Này (Đã điền sẵn điểm hiện có trên danh sách) -->
                                                    <div class="modal fade" id="scoreModal_{{ $sem }}_{{ $cId }}" tabindex="-1" aria-labelledby="modalLabel_{{ $sem }}_{{ $cId }}" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content border shadow-sm rounded-3">
                                                                <form method="POST" action="{{ route('admin.grades.updateScore') }}">
                                                                    @csrf
                                                                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                                    <input type="hidden" name="course_id" value="{{ $cId }}">
                                                                    <input type="hidden" name="course_code" value="{{ $cCode }}">
                                                                    <input type="hidden" name="semester_order" value="{{ $sem }}">
                                                                    <input type="hidden" name="faculty_id" value="{{ $selectedFacultyId }}">
                                                                    <input type="hidden" name="specialization_id" value="{{ $selectedSpecId }}">
                                                                    <input type="hidden" name="cohort" value="{{ $selectedCohort }}">
                                                                    <input type="hidden" name="class_code" value="{{ $selectedClassCode }}">

                                                                    <div class="modal-header border-bottom py-3">
                                                                        <h5 class="modal-title fw-bold text-dark fs-6 d-flex align-items-center gap-2" id="modalLabel_{{ $sem }}_{{ $cId }}">
                                                                            <i class="bi bi-pencil-square"></i>
                                                                            <span>Cập nhật & Tùy chỉnh Điểm Học Phần</span>
                                                                        </h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    
                                                                    <div class="modal-body p-4 text-start">
                                                                        <!-- Thông tin môn học -->
                                                                        <div class="p-3 bg-light border rounded-3 mb-3">
                                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                                <span class="badge bg-light text-dark border font-monospace">{{ $cCode }}</span>
                                                                                <span class="badge bg-light text-secondary border small">{{ $cCredits }} Tín chỉ</span>
                                                                            </div>
                                                                            <div class="fw-bold text-dark fs-6">{{ $cName }}</div>
                                                                            <div class="text-secondary small mt-0.5">Học kỳ {{ $sem }} (Chương trình khung)</div>
                                                                        </div>

                                                                        <!-- 3 Cột điểm thành phần: Điền sẵn điểm hiện có (nếu môn này đã có điểm) -->
                                                                        <div class="row g-3">
                                                                            <div class="col-4">
                                                                                <label class="form-label small fw-semibold text-secondary">
                                                                                    Quá trình (20%)
                                                                                </label>
                                                                                <input type="number" step="0.1" min="0" max="10" 
                                                                                       class="form-control form-control-sm text-center fw-bold fs-6 score-input-qt" 
                                                                                       name="process_score" 
                                                                                       value="{{ $qtVal !== '' ? $qtVal : '' }}"
                                                                                       placeholder="0.0 - 10.0">
                                                                            </div>

                                                                            <div class="col-4">
                                                                                <label class="form-label small fw-semibold text-secondary">
                                                                                    Giữa kỳ (20%)
                                                                                </label>
                                                                                <input type="number" step="0.1" min="0" max="10" 
                                                                                       class="form-control form-control-sm text-center fw-bold fs-6 score-input-gk" 
                                                                                       name="midterm_score" 
                                                                                       value="{{ $gkVal !== '' ? $gkVal : '' }}"
                                                                                       placeholder="0.0 - 10.0">
                                                                            </div>

                                                                            <div class="col-4">
                                                                                <label class="form-label small fw-semibold text-secondary">
                                                                                    Cuối kỳ (60%)
                                                                                </label>
                                                                                <input type="number" step="0.1" min="0" max="10" 
                                                                                       class="form-control form-control-sm text-center fw-bold fs-6 score-input-ck" 
                                                                                       name="final_score" 
                                                                                       value="{{ $ckVal !== '' ? $ckVal : '' }}"
                                                                                       placeholder="0.0 - 10.0">
                                                                            </div>
                                                                        </div>

                                                                        <!-- Hộp Xem trước Điểm tổng kết -->
                                                                        @php
                                                                            $prev10Val = ($enr && $enr->total_score_10 !== null) ? number_format($enr->total_score_10, 1) : '-';
                                                                            $prev4Val = ($enr && $enr->total_score_4 !== null) ? number_format($enr->total_score_4, 1) : '-';
                                                                            $prevLetterVal = ($enr && $enr->letter_grade) ? $enr->letter_grade : '-';
                                                                        @endphp
                                                                        <div class="p-3 bg-light rounded-3 border mt-3">
                                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                                <span class="small fw-semibold text-secondary">Xem trước kết quả tự động:</span>
                                                                                <span class="badge bg-dark text-white font-monospace preview-grade-letter">{{ $prevLetterVal }}</span>
                                                                            </div>
                                                                            <div class="d-flex align-items-center justify-content-between text-dark">
                                                                                <span class="small">Tổng kết thang 10: <strong class="preview-total-10">{{ $prev10Val }}</strong></span>
                                                                                <span class="small">Thang 4: <strong class="preview-total-4">{{ $prev4Val }}</strong></span>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="modal-footer border-top py-2.5 bg-light">
                                                                        <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy bỏ</button>
                                                                        <button type="submit" class="btn btn-sm btn-dark px-3">
                                                                            <i class="bi bi-check-lg me-1"></i>Lưu tùy chỉnh điểm
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="12" class="text-center py-5 text-muted small">
                                                    <i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                                    Chưa có môn học nào được thiết lập trong khung Học kỳ {{ $sem }} cho chuyên ngành này.
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
    @else
        <!-- KHI CHƯA CHỌN SINH VIÊN: QUY TRÌNH LỌC TUẦN TỰ 2 BƯỚC (KHÓA -> LỚP -> SINH VIÊN) -->

        {{-- TRƯỜNG HỢP 1: ĐÃ CHỌN LỚP (HOẶC TÌM KIẾM KEYWORD) -> HIỂN THỊ DANH SÁCH SINH VIÊN CỦA LỚP ĐÓ --}}
        @if ($selectedClassCode || $keyword !== '')
            @if ($allStudents->isNotEmpty())
                <div class="card border shadow-sm rounded-3 bg-white mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-dark text-white rounded-pill px-2.5 py-1 small">Danh sách sinh viên</span>
                                <strong class="text-dark fs-6">
                                    @if ($selectedClassCode)
                                        Lớp {{ $selectedClassCode }} 
                                        @if ($selectedCohort)
                                            <span class="fw-normal text-muted">(Khóa {{ $selectedCohort }})</span>
                                        @endif
                                    @else
                                        Kết quả tìm kiếm cho từ khóa "{{ $keyword }}"
                                    @endif
                                </strong>
                            </div>
                           
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            @if ($selectedClassCode)
                                <a href="{{ route('admin.grades.index', array_filter(['faculty_id' => $selectedFacultyId, 'specialization_id' => $selectedSpecId, 'cohort' => $selectedCohort])) }}" 
                                   class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm"
                                   title="Quay lại danh sách các lớp thuộc Khóa {{ $selectedCohort }}">
                                    <i class="bi bi-arrow-left"></i>
                                    <span>Đổi lớp khác (Khóa {{ $selectedCohort }})</span>
                                </a>
                            @endif
                            <span class="badge bg-light text-dark border font-monospace">
                                {{ $allStudents->count() }} sinh viên
                            </span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary small">
                                <tr>
                                    <th class="ps-3 text-center" style="width: 50px;">#</th>
                                    <th style="width: 140px;">Mã SV (MSSV)</th>
                                    <th>Họ và tên</th>
                                    <th style="width: 110px;">Khóa</th>
                                    <th style="width: 130px;">Lớp danh nghĩa</th>
                                    <th>Chuyên ngành</th>
                                    <th style="width: 130px;" class="text-center">Trạng thái</th>
                                    <th class="text-end pe-3" style="width: 160px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($allStudents as $index => $stu)
                                    <tr>
                                        <td class="ps-3 text-center text-muted small">{{ $index + 1 }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace fw-bold">
                                                {{ $stu->student_code }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-light text-dark border rounded-circle d-flex align-items-center justify-content-center fw-semibold small flex-shrink-0"
                                                     style="width: 32px; height: 32px;">
                                                    {{ mb_substr($stu->full_name, 0, 1) }}
                                                </div>
                                                <div class="fw-semibold text-dark">{{ $stu->full_name }}</div>
                                            </div>
                                        </td>
                                        <td class="small text-secondary">
                                            Khóa {{ $stu->cohort_year ?? 2023 }}
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border small">{{ $stu->class_code ?? 'Lớp' }}</span>
                                        </td>
                                        <td class="small text-secondary">
                                            {{ $stu->specialization?->name ?? $stu->specialization_name }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-success border border-success-subtle small">
                                                {{ $stu->study_status ?? 'Đang học' }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('admin.grades.index', array_filter(['faculty_id' => $selectedFacultyId, 'specialization_id' => $selectedSpecId, 'cohort' => $selectedCohort, 'class_code' => $selectedClassCode ?: $stu->class_code, 'keyword' => $keyword, 'student_id' => $stu->id, 'semester' => $activeSemester])) }}" 
                                               class="btn btn-sm btn-dark d-inline-flex align-items-center gap-1.5 px-3 py-1.5 shadow-sm">
                                                <i class="bi bi-file-earmark-spreadsheet"></i>
                                                <span>Xem bảng điểm</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="card border shadow-sm rounded-3 bg-white p-5 text-center mb-4">
                    <i class="bi bi-person-x fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">Không tìm thấy sinh viên nào</h5>
                    <p class="text-secondary small mb-3">
                        @if ($selectedClassCode)
                            Lớp <strong>{{ $selectedClassCode }}</strong> (Khóa {{ $selectedCohort }}) hiện chưa có sinh viên nào hoặc không khớp với từ khóa tìm kiếm.
                        @else
                            Không tìm thấy sinh viên nào phù hợp với từ khóa <strong>"{{ $keyword }}"</strong>.
                        @endif
                    </p>
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        @if ($selectedCohort && $selectedSpecId)
                            <a href="{{ route('admin.grades.index', array_filter(['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort, 'specialization_id' => $selectedSpecId])) }}" class="btn btn-outline-dark btn-sm shadow-sm">
                                <i class="bi bi-arrow-left me-1"></i>Chọn lớp khác thuộc Ngành {{ $selectedSpecialization->name ?? '' }} (Khóa {{ $selectedCohort }})
                            </a>
                        @elseif ($selectedCohort)
                            <a href="{{ route('admin.grades.index', array_filter(['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort])) }}" class="btn btn-outline-dark btn-sm shadow-sm">
                                <i class="bi bi-arrow-left me-1"></i>Chọn chuyên ngành khác (Khóa {{ $selectedCohort }})
                            </a>
                        @endif
                        <a href="{{ route('admin.grades.index') }}" class="btn btn-dark btn-sm shadow-sm">
                            <i class="bi bi-arrow-clockwise me-1"></i>Làm mới bộ lọc
                        </a>
                    </div>
                </div>
            @endif

        {{-- LEVEL 4: KHI ĐÃ CHỌN KHOA, KHÓA & CHUYÊN NGÀNH -> HIỂN THỊ DANH SÁCH LỚP DANH NGHĨA --}}
        @elseif ($selectedCohort && $selectedSpecId)
            <div class="card border shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('admin.grades.index', array_filter(['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort])) }}" 
                           class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm" 
                           title="Quay lại danh sách các Chuyên ngành">
                            <i class="bi bi-arrow-left"></i>
                            <span>Quay lại các Chuyên ngành</span>
                        </a>
                        <span class="text-secondary small d-none d-md-inline">|</span>
                        <strong class="text-dark fs-6">
                            Khóa {{ $selectedCohort }}
                            @php
                                $y = (int) $selectedCohort;
                                $k = $y >= 2010 ? ($y - 2009) : null;
                            @endphp
                            {{ $k ? "- Niên khóa K{$k}" : '' }}
                        </strong>
                        <span class="badge bg-light text-dark border font-monospace">
                            Ngành: {{ $selectedSpecialization->name ?? '' }}
                        </span>
                        <span class="badge bg-dark text-white font-monospace small">
                            Khoa: {{ $selectedFaculty->name ?? '' }}
                        </span>
                        <span class="badge bg-light text-dark border font-monospace">
                            {{ $classesSummary->count() }} Lớp danh nghĩa
                        </span>
                    </div>

                    <a href="{{ route('admin.classes.index', array_filter(['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort, 'specialization_id' => $selectedSpecId])) }}" 
                       class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1 shadow-sm"
                       title="Chuyển sang Quản lý lớp học để thêm/sửa lớp">
                        <i class="bi bi-gear"></i>
                        <span>Quản lý lớp học</span>
                    </a>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xxl-4">
                        @forelse ($classesSummary as $cls)
                            <div class="col">
                                <div class="card border rounded-3 h-100 bg-white shadow-sm hover-shadow transition-all d-flex flex-column justify-content-between" style="border-color: #e5e7eb !important;">
                                    <div class="card-body p-3.5 pb-2">
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                            <span class="badge bg-dark text-white font-monospace fs-6 px-2.5 py-1 rounded-2">
                                                {{ $cls->class_code }}
                                            </span>
                                            <span class="badge bg-light text-secondary border small font-monospace">
                                                <i class="bi bi-people me-1"></i>{{ $cls->students_count }} SV
                                            </span>
                                        </div>

                                        <h6 class="fw-bold text-dark mb-2 text-truncate" title="{{ $cls->class_name }}">
                                            {{ $cls->class_name }}
                                        </h6>

                                        <div class="d-flex flex-column gap-1 mb-2.5">
                                            <div class="small text-secondary text-truncate" title="{{ $cls->specialization?->name ?? 'Công nghệ Thông tin' }}">
                                                <i class="bi bi-journal-code text-muted me-1.5"></i>
                                                <span class="text-dark">{{ $cls->specialization?->name ?? 'Công nghệ Thông tin' }}</span>
                                            </div>
                                            <div class="small text-secondary">
                                                <i class="bi bi-geo-alt text-muted me-1.5"></i>
                                                <span>Phòng: <strong class="text-dark">{{ $cls->room ?: 'Chưa cập nhật' }}</strong></span>
                                            </div>
                                        </div>

                                        <!-- Giảng viên phụ trách / CVHT -->
                                        <div class="p-2.5 bg-light rounded-3 border border-light-subtle mb-1">
                                            <div class="small fw-bold text-secondary text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                                Cố vấn học tập (CVHT)
                                            </div>
                                            @if ($cls->lecturer)
                                                <div class="fw-semibold text-dark small text-truncate">
                                                    <i class="bi bi-person-check-fill text-success me-1"></i>
                                                    {{ $cls->lecturer->full_name }}
                                                    <span class="text-muted fw-normal" style="font-size: 0.78rem;">({{ $cls->lecturer->degree }})</span>
                                                </div>
                                                <div class="small text-muted text-truncate" style="font-size: 0.78rem;">
                                                    <i class="bi bi-envelope me-1"></i>{{ $cls->lecturer->email }}
                                                </div>
                                            @else
                                                <div class="text-secondary small" style="font-size: 0.8rem;">
                                                    <i class="bi bi-dash-circle me-1"></i>Chưa phân công
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="card-footer bg-white border-top p-2.5 pt-2">
                                        <a href="{{ route('admin.grades.index', array_filter(['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort, 'specialization_id' => $selectedSpecId, 'class_code' => $cls->class_code])) }}" 
                                           class="btn btn-sm btn-dark w-100 d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm py-1.5">
                                            <i class="bi bi-people"></i>
                                            <span>Xem sinh viên & Bảng điểm</span>
                                            <i class="bi bi-arrow-right ms-auto"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5 text-muted small">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Chưa có lớp danh nghĩa nào trong Khóa {{ $selectedCohort }} thuộc chuyên ngành {{ $selectedSpecialization->name ?? '' }}.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        {{-- LEVEL 3: KHI ĐÃ CHỌN KHOA VÀ KHÓA -> HIỆN DANH SÁCH CÁC CHUYÊN NGÀNH --}}
        @elseif ($selectedFacultyId && $selectedCohort)
            <div class="card border shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('admin.grades.index', array_filter(['faculty_id' => $selectedFacultyId])) }}" 
                           class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm" 
                           title="Quay lại danh sách các Khóa tuyển sinh">
                            <i class="bi bi-arrow-left"></i>
                            <span>Quay lại các Khóa</span>
                        </a>
                        <span class="text-secondary small d-none d-md-inline">|</span>
                        <strong class="text-dark fs-6">
                            Khóa {{ $selectedCohort }}
                            @php
                                $y = (int) $selectedCohort;
                                $k = $y >= 2010 ? ($y - 2009) : null;
                            @endphp
                            {{ $k ? "- Niên khóa K{$k}" : '' }}
                        </strong>
                        <span class="badge bg-dark text-white font-monospace small">
                            Khoa: {{ $selectedFaculty->name ?? '' }}
                        </span>
                        <span class="badge bg-light text-dark border font-monospace">
                            {{ $cohortSpecsSummary->count() }} Chuyên ngành
                        </span>
                    </div>
                </div>

                <div class="card-body p-3 p-md-4">
                    <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xxl-4">
                        @forelse ($cohortSpecsSummary as $specItem)
                            <div class="col">
                                <div class="card border rounded-3 h-100 bg-white shadow-sm hover-shadow transition-all d-flex flex-column justify-content-between" style="border-color: #e5e7eb !important;">
                                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                                        <div>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="badge bg-dark text-white px-2.5 py-1 font-monospace">
                                                    {{ $specItem->code }}
                                                </span>
                                                <span class="badge bg-light text-secondary border small">
                                                    Chuyên ngành
                                                </span>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-3 mt-1" title="{{ $specItem->name }}">
                                                {{ $specItem->name }}
                                            </h5>

                                            <div class="row g-2 text-center my-3">
                                                <div class="col-6">
                                                    <div class="p-2.5 bg-light rounded-2 border">
                                                        <div class="fs-4 fw-bold text-dark">{{ $specItem->classes_count }}</div>
                                                        <div class="small text-secondary" style="font-size: 0.78rem;">Lớp danh nghĩa</div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="p-2.5 bg-light rounded-2 border">
                                                        <div class="fs-4 fw-bold text-dark">{{ $specItem->students_count }}</div>
                                                        <div class="small text-secondary" style="font-size: 0.78rem;">Sinh viên</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <a href="{{ route('admin.grades.index', ['faculty_id' => $selectedFacultyId, 'cohort' => $selectedCohort, 'specialization_id' => $specItem->id]) }}" 
                                           class="btn btn-dark btn-sm w-100 mt-2 d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm py-2">
                                            <span>Xem các lớp ngành này</span>
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5 text-muted small">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Khoa này hiện chưa có chuyên ngành đào tạo nào.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        {{-- LEVEL 2: KHI ĐÃ CHỌN KHOA -> HIỆN DANH SÁCH CÁC KHÓA (COHORTS) THUỘC KHOA ĐÓ --}}
        @elseif ($selectedFacultyId)
            <div class="card border shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('admin.grades.index') }}" 
                           class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm" 
                           title="Quay lại danh sách tất cả các Khoa">
                            <i class="bi bi-arrow-left"></i>
                            <span>Quay lại các Khoa</span>
                        </a>
                        <span class="text-secondary small d-none d-md-inline">|</span>
                        <strong class="text-dark fs-6">
                            {{ $selectedFaculty->name }}
                        </strong>
                        <span class="badge bg-light text-dark border font-monospace">
                            [{{ $selectedFaculty->code }}]
                        </span>
                        <span class="badge bg-dark text-white font-monospace">
                            {{ count($facultyCohortsSummary) }} Khóa tuyển sinh
                        </span>
                    </div>
                </div>

                <div class="card-body p-3 p-md-4">
                    <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xxl-4">
                        @forelse ($facultyCohortsSummary as $cohortItem)
                            <div class="col">
                                <div class="card border rounded-3 h-100 bg-white shadow-sm hover-shadow transition-all d-flex flex-column justify-content-between" style="border-color: #e5e7eb !important;">
                                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                                        <div>
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <span class="badge bg-dark text-white px-3 py-1.5 fs-6">
                                                    Khóa {{ $cohortItem['cohort_year'] }}
                                                </span>
                                                <span class="badge bg-light text-secondary border small">
                                                    {{ $cohortItem['k_label'] }}
                                                </span>
                                            </div>

                                            <div class="row g-2 text-center my-3">
                                                <div class="col-4">
                                                    <div class="p-2 bg-light rounded-2 border">
                                                        <div class="fs-5 fw-bold text-dark">{{ $cohortItem['specializations_count'] }}</div>
                                                        <div class="small text-secondary" style="font-size: 0.72rem;">Chuyên ngành</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="p-2 bg-light rounded-2 border">
                                                        <div class="fs-5 fw-bold text-dark">{{ $cohortItem['classes_count'] }}</div>
                                                        <div class="small text-secondary" style="font-size: 0.72rem;">Lớp học</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="p-2 bg-light rounded-2 border">
                                                        <div class="fs-5 fw-bold text-dark">{{ $cohortItem['students_count'] }}</div>
                                                        <div class="small text-secondary" style="font-size: 0.72rem;">Sinh viên</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <a href="{{ route('admin.grades.index', ['faculty_id' => $selectedFacultyId, 'cohort' => $cohortItem['cohort_year']]) }}" 
                                           class="btn btn-dark btn-sm w-100 mt-2 d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm py-2">
                                            <span>Xem Chuyên ngành Khóa {{ $cohortItem['cohort_year'] }}</span>
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5 text-muted small">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Khoa này hiện chưa có dữ liệu khóa tuyển sinh nào.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        {{-- LEVEL 1: GỐC -> HIỂN THỊ DANH SÁCH TẤT CẢ KHOA ĐÀO TẠO --}}
        @else
            <div class="card border shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 mb-0">
                        <strong class="text-dark fs-6">Danh sách Khoa đào tạo</strong>
                        <span class="badge bg-light text-dark border font-monospace">
                            {{ count($facultiesSummary) }} Khoa
                        </span>
                    </div>
                </div>

                <div class="card-body p-3 p-md-4">
                    <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xxl-4">
                        @forelse ($facultiesSummary as $facItem)
                            <div class="col">
                                <div class="card border rounded-3 h-100 bg-white shadow-sm hover-shadow transition-all d-flex flex-column justify-content-between" style="border-color: #e5e7eb !important;">
                                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                                        <div>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="badge bg-dark text-white px-2.5 py-1 font-monospace">
                                                    {{ $facItem['code'] }}
                                                </span>
                                                <span class="badge bg-light text-secondary border small">
                                                    Khoa đào tạo
                                                </span>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-3 mt-1" title="{{ $facItem['name'] }}">
                                                {{ $facItem['name'] }}
                                            </h5>

                                            <div class="row g-2 text-center my-3">
                                                <div class="col-4">
                                                    <div class="p-2 bg-light rounded-2 border">
                                                        <div class="fs-5 fw-bold text-dark">{{ $facItem['specializations_count'] }}</div>
                                                        <div class="small text-secondary" style="font-size: 0.72rem;">Chuyên ngành</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="p-2 bg-light rounded-2 border">
                                                        <div class="fs-5 fw-bold text-dark">{{ $facItem['classes_count'] }}</div>
                                                        <div class="small text-secondary" style="font-size: 0.72rem;">Lớp học</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="p-2 bg-light rounded-2 border">
                                                        <div class="fs-5 fw-bold text-dark">{{ $facItem['students_count'] }}</div>
                                                        <div class="small text-secondary" style="font-size: 0.72rem;">Sinh viên</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <a href="{{ route('admin.grades.index', ['faculty_id' => $facItem['id']]) }}" 
                                           class="btn btn-dark btn-sm w-100 mt-2 d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm py-2">
                                            <span>Vào xem Khoa này</span>
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5 text-muted small">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Chưa có khoa đào tạo nào được thiết lập.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

<script>
// Tự động tính toán xem trước điểm thang 10, thang 4 và điểm chữ khi người dùng nhập điểm trong modal
document.addEventListener("input", function (e) {
    if (e.target && (e.target.classList.contains("score-input-qt") || e.target.classList.contains("score-input-gk") || e.target.classList.contains("score-input-ck"))) {
        const modal = e.target.closest(".modal");
        if (!modal) return;
        const qtEl = modal.querySelector(".score-input-qt");
        const gkEl = modal.querySelector(".score-input-gk");
        const ckEl = modal.querySelector(".score-input-ck");
        const prev10 = modal.querySelector(".preview-total-10");
        const prev4 = modal.querySelector(".preview-total-4");
        const prevLetter = modal.querySelector(".preview-grade-letter");
        if (!qtEl || !gkEl || !ckEl || !prev10 || !prev4 || !prevLetter) return;

        const qt = parseFloat(qtEl.value);
        const gk = parseFloat(gkEl.value);
        const ck = parseFloat(ckEl.value);

        if (isNaN(qt) || isNaN(gk) || isNaN(ck)) {
            prev10.innerText = "-";
            prev4.innerText = "-";
            prevLetter.innerText = "-";
            return;
        }

        if (qt < 0 || qt > 10 || gk < 0 || gk > 10 || ck < 0 || ck > 10) {
            prev10.innerText = "Lỗi";
            prev4.innerText = "Lỗi";
            prevLetter.innerText = "!";
            return;
        }

        const total10 = Math.round((qt * 0.2 + gk * 0.2 + ck * 0.6) * 10) / 10;
        let total4 = 0.0;
        let letter = "F";

        if (total10 >= 8.5) {
            total4 = 4.0; letter = "A";
        } else if (total10 >= 7.0) {
            total4 = 3.0; letter = "B";
        } else if (total10 >= 5.5) {
            total4 = 2.0; letter = "C";
        } else if (total10 >= 4.0) {
            total4 = 1.0; letter = "D";
        } else {
            total4 = 0.0; letter = "F";
        }

        prev10.innerText = total10.toFixed(1);
        prev4.innerText = total4.toFixed(1);
        prevLetter.innerText = letter;
    }
});
</script>
@endsection
