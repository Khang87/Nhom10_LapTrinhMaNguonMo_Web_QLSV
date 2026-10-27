@extends('layouts.spark')

@section('title', 'Quản lý Đăng ký & Mở Lớp Học Phần')

@section('content')
<div class="container-fluid px-0">
    <!-- Header: Tối giản, đồng bộ -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Quản lý Đăng ký & Mở Lớp Học Phần</h1>
            <p class="text-secondary small mb-0">Mở các lớp học phần (Options ca học & giảng viên) theo Chương trình khung để sinh viên đăng ký</p>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session('success'))
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-check2-circle text-dark"></i>
            <div class="text-dark small">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-exclamation-circle text-dark"></i>
            <div class="text-dark small">{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Cảnh báo nếu Đợt Học kỳ đã đóng đợt đăng ký & mở lớp -->
    @if ($selectedSemester && !$selectedSemester->is_registration_open)
        <div class="alert alert-light border border-secondary-subtle d-flex align-items-center gap-3 mb-4 py-2.5 px-3 rounded-3 bg-white" role="alert">
            <div class="p-2 bg-light border rounded-circle d-flex align-items-center justify-content-center text-danger">
                <i class="bi bi-lock-fill fs-5"></i>
            </div>
            <div class="small">
                <strong class="text-danger">Đợt học kỳ này đã đóng đăng ký:</strong>
                <span class="text-dark"> [{{ $selectedSemester->semester_name }}] hiện đang ở trạng thái <strong>ĐÃ ĐÓNG</strong>. Bạn chỉ có thể theo dõi danh sách lớp học phần đã mở, không thể mở thêm lớp mới (tự động hoặc thủ công).</span>
            </div>
        </div>
    @endif

    <!-- BỘ LỌC ĐIỀU HÀNH 2 BƯỚC: CHỌN CHUYÊN NGÀNH TRƯỚC -> CHỌN HỌC KỲ SAU -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('admin.course-sections.index') }}" id="filterForm">
                <input type="hidden" name="section_filter_spec" value="{{ $sectionFilterSpec ?? 'all' }}">
                <input type="hidden" name="view_scope" value="{{ $viewScope ?? 'current' }}">
                
                <div class="row g-3 align-items-end">
                    <!-- BƯỚC 1: CHỌN CHUYÊN NGÀNH -->
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label small fw-bold text-dark d-flex align-items-center gap-2 mb-1.5">
                            <span class="badge bg-dark text-white rounded-circle px-2 py-1" style="font-size: 0.75rem;">1</span>
                            <span>Chọn Chuyên ngành:</span>
                        </label>
                        <select name="specialization_id" class="form-select form-select-sm py-2" onchange="this.form.submit()">
                            @foreach ($specializations as $spec)
                                <option value="{{ $spec->id }}" {{ $selectedSpec && $selectedSpec->id == $spec->id ? 'selected' : '' }}>
                                    [{{ $spec->code }}] {{ $spec->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- BƯỚC 2A: CHỌN HỌC KỲ TRONG CHƯƠNG TRÌNH KHUNG (KỲ 1 -> 8) -->
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label small fw-bold text-dark d-flex align-items-center gap-2 mb-1.5">
                            <span class="badge bg-dark text-white rounded-circle px-2 py-1" style="font-size: 0.75rem;">2</span>
                            <span>Chọn Học kỳ (Khung chương trình):</span>
                        </label>
                        <select name="semester_order" class="form-select form-select-sm py-2" onchange="this.form.submit()">
                            @for ($i = 1; $i <= 8; $i++)
                                <option value="{{ $i }}" {{ $selectedSemesterOrder === $i ? 'selected' : '' }}>
                                    Học kỳ {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <!-- BƯỚC 2B: ĐỢT HỌC KỲ ÁP DỤNG MỞ LỚP (NIÊN KHÓA) -->
                    <div class="col-lg-3 col-md-8">
                        <label class="form-label small fw-bold text-dark d-flex align-items-center gap-2 mb-1.5">
                            <span class="badge bg-secondary text-white rounded-circle px-2 py-1" style="font-size: 0.75rem;">Đợt</span>
                            <span>Đợt Học kỳ áp dụng mở lớp:</span>
                        </label>
                        <select name="semester" class="form-select form-select-sm py-2" onchange="this.form.submit()">
                            @foreach ($semesters as $sem)
                                <option value="{{ $sem->semester_code }}" {{ $selectedSemesterCode === $sem->semester_code ? 'selected' : '' }}>
                                    {{ $sem->semester_name }} {{ $sem->is_registration_open ? '(Đang mở ĐK)' : '(Đã đóng)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- NÚT BẤM LỌC -->
                    <div class="col-lg-2 col-md-4">
                        <button type="submit" class="btn btn-dark btn-sm w-100 py-2 d-inline-flex align-items-center justify-content-center gap-1.5">
                            <i class="bi bi-funnel"></i>
                            <span>Lọc</span>
                        </button>
                    </div>
                </div>

                <!-- Chuyển nhanh Học kỳ khung 1 -> 8 (Pills) -->
                <div class="d-flex flex-wrap align-items-center gap-1.5 mt-3 pt-3 border-top">
                    <span class="small fw-semibold text-secondary me-2">Chọn nhanh Học kỳ khung:</span>
                    @for ($k = 1; $k <= 8; $k++)
                        <button type="submit" name="semester_order" value="{{ $k }}" 
                                class="btn btn-sm {{ $selectedSemesterOrder === $k ? 'btn-dark' : 'btn-outline-secondary' }} px-3 py-1 rounded-pill">
                            Học kỳ {{ $k }}
                        </button>
                    @endfor
                </div>
            </form>
        </div>
    </div>

    <!-- KHỐI 1: DANH SÁCH CÁC HỌC PHẦN CỦA CHƯƠNG TRÌNH HỌC (ĐÃ LOAD THEO NGÀNH & KỲ) -->
    @if ($selectedSpec)
        <div class="card border shadow-sm rounded-3 bg-white mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark text-white font-monospace">{{ $selectedSpec->code }}</span>
                    <strong class="text-dark">Chương trình học: {{ $selectedSpec->name }} — Học kỳ {{ $selectedSemesterOrder }}</strong>
                    <span class="badge bg-light text-dark border ms-1">
                        {{ $frameworkCourses->count() }} học phần
                    </span>
                </div>

                <div>
                    <form method="POST" action="{{ route('admin.course-sections.open-by-curriculum') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="semester_code" value="{{ $selectedSemesterCode }}">
                        <input type="hidden" name="specialization_id" value="{{ $selectedSpec->id }}">
                        <input type="hidden" name="semester_order" value="{{ $selectedSemesterOrder }}">
                        @php
                            $isSemesterOpen = $selectedSemester && $selectedSemester->is_registration_open;
                            $canAutoOpen = $isSemesterOpen && $frameworkCourses->isNotEmpty();
                        @endphp
                        <button type="submit" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-1.5"
                                {{ !$canAutoOpen ? 'disabled' : '' }}
                                title="{{ !$isSemesterOpen ? 'Đợt học kỳ này đã đóng đăng ký, không thể mở thêm lớp' : ($frameworkCourses->isEmpty() ? 'Chưa có môn học trong khung' : '') }}">
                            <i class="bi bi-{{ !$isSemesterOpen ? 'lock-fill' : 'magic' }}"></i>
                            <span>{{ !$isSemesterOpen ? 'Đợt học kỳ đã đóng' : 'Mở lớp tự động (theo Khung)' }}</span>
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                @if ($frameworkCourses->isNotEmpty())
                    <div class="row g-2">
                        @foreach ($frameworkCourses as $item)
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                            <span class="badge bg-light text-dark border font-monospace">
                                                {{ $item->course?->course_code }}
                                            </span>
                                            <span class="badge bg-light text-dark border small">
                                                {{ $item->is_mandatory ? 'Bắt buộc' : 'Tự chọn' }}
                                            </span>
                                        </div>
                                        <div class="fw-semibold text-dark small mb-1">
                                            {{ $item->course?->course_name }}
                                        </div>
                                        <div class="text-secondary small">
                                            {{ $item->course?->credits }} tín chỉ
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                                        <span class="small text-secondary">Tình trạng mở:</span>
                                        @if ($item->opened_sections_count > 0)
                                            <span class="badge bg-light text-success border small">
                                                ✓ {{ $item->opened_sections_count }} lớp đã mở
                                            </span>
                                        @else
                                            <span class="badge bg-light text-secondary border small">
                                                Chưa mở lớp
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                        Chưa có học phần nào được thiết lập trong khung Học kỳ {{ $selectedSemesterOrder }} của chuyên ngành [{{ $selectedSpec->name }}].
                    </div>
                @endif
                <div class="text-secondary small mt-3">
                    <i class="bi bi-info-circle me-1"></i> Bấm <strong>"Mở lớp tự động (theo Khung)"</strong> sẽ tự động tạo 2 Option ca học (Thứ/Tiết/Phòng) cho mỗi môn và phân công Giảng viên ngẫu nhiên theo đúng chuyên ngành.
                </div>
            </div>
        </div>
    @endif

    <!-- KHỐI 2: THỂ HIỆN CÁC LỚP HỌC PHẦN ĐÃ ĐƯỢC MỞ Ở KỲ ĐÓ -->
    <div class="card border shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <strong class="text-dark fs-6">
                        @if ($viewScope === 'current')
                            Lớp Học Phần Đã Mở — Học kỳ {{ $selectedSemesterOrder }}
                        @else
                            Tất cả Lớp Học Phần Đã Mở trong Đợt
                        @endif
                    </strong>
                    <span class="badge bg-light text-dark border font-monospace">
                        {{ $selectedSemesterCode }}
                    </span>
                    @if ($selectedSemester && $selectedSemester->is_registration_open)
                        <span class="badge bg-light text-success border border-success-subtle">
                            <i class="bi bi-check-circle me-1"></i>Đang mở ĐK
                        </span>
                    @else
                        <span class="badge bg-light text-danger border border-danger-subtle">
                            <i class="bi bi-lock-fill me-1"></i>Đã đóng
                        </span>
                    @endif
                    <span class="badge bg-dark text-white">
                        {{ $sections->count() }} lớp hiển thị
                    </span>
                </div>
                <div class="text-secondary small mt-1">
                    @if ($viewScope === 'current')
                        Hiển thị các lớp học phần của các môn thuộc <strong>Học kỳ {{ $selectedSemesterOrder }}</strong> ({{ $selectedSpec?->name }}).
                    @else
                        Hiển thị toàn bộ <strong>{{ $totalSectionsInSemester }} lớp</strong> được mở trong đợt [{{ $selectedSemester?->semester_name }}].
                    @endif
                </div>
            </div>

            <!-- Công cụ bộ lọc phạm vi + lọc ngành + Mở lớp thủ công -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Chuyển đổi phạm vi: Chỉ kỳ hiện tại VS Tất cả kỳ -->
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('admin.course-sections.index', array_merge(request()->query(), ['view_scope' => 'current'])) }}" 
                       class="btn {{ $viewScope === 'current' ? 'btn-dark' : 'btn-outline-secondary' }}"
                       title="Chỉ hiển thị các lớp của môn thuộc Học kỳ {{ $selectedSemesterOrder }}">
                        Kỳ {{ $selectedSemesterOrder }} ({{ $totalSectionsInCurrentFramework }})
                    </a>
                    <a href="{{ route('admin.course-sections.index', array_merge(request()->query(), ['view_scope' => 'all'])) }}" 
                       class="btn {{ $viewScope === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}"
                       title="Hiển thị tất cả lớp học phần trong đợt này">
                        Tất cả các kỳ ({{ $totalSectionsInSemester }})
                    </a>
                </div>

                <!-- Form Lọc chuyên ngành ở Khối 2 -->
                <form method="GET" action="{{ route('admin.course-sections.index') }}" class="d-flex align-items-center gap-1.5">
                    <input type="hidden" name="semester" value="{{ $selectedSemesterCode }}">
                    <input type="hidden" name="specialization_id" value="{{ $selectedSpec?->id }}">
                    <input type="hidden" name="semester_order" value="{{ $selectedSemesterOrder }}">
                    <input type="hidden" name="view_scope" value="{{ $viewScope ?? 'current' }}">
                    <select name="section_filter_spec" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ ($sectionFilterSpec ?? 'all') === 'all' ? 'selected' : '' }}>
                            -- Ngành: Tất cả --
                        </option>
                        @foreach ($specializations as $sp)
                            <option value="{{ $sp->name }}" {{ ($sectionFilterSpec ?? '') === $sp->name ? 'selected' : '' }}>
                                {{ $sp->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-dark d-inline-flex align-items-center gap-1 px-2.5">
                        <i class="bi bi-funnel"></i> <span>Lọc</span>
                    </button>
                </form>

                <!-- Nút Mở lớp thủ công (Disabled nếu đợt học kỳ đã đóng) -->
                @if ($selectedSemester && $selectedSemester->is_registration_open)
                    <button type="button" class="btn btn-sm btn-outline-dark d-inline-flex align-items-center gap-1.5" 
                            data-bs-toggle="modal" data-bs-target="#createSectionModal">
                        <i class="bi bi-plus-lg"></i>
                        <span>Mở lớp thủ công</span>
                    </button>
                @else
                    <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5" disabled 
                            title="Đợt học kỳ này đã đóng đăng ký, không thể tạo thêm lớp">
                        <i class="bi bi-lock-fill"></i>
                        <span>Mở lớp (Đã đóng)</span>
                    </button>
                @endif
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="ps-3 text-center" style="width: 50px;">#</th>
                        <th style="width: 140px;">Mã Lớp HP</th>
                        <th>Tên Môn Học</th>
                        <th>Chuyên ngành</th>
                        <th>Giảng viên phụ trách</th>
                        <th class="text-center" style="width: 140px;">Thứ / Tiết</th>
                        <th>Phòng học</th>
                        <th class="text-center" style="width: 120px;">Sĩ số</th>
                        <th class="text-center pe-3" style="width: 140px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sections as $index => $section)
                        @php
                            $isFull = $section->enrollments_count >= $section->max_capacity;
                            $dayText = match ((int)$section->day_of_week) {
                                2 => 'Thứ 2',
                                3 => 'Thứ 3',
                                4 => 'Thứ 4',
                                5 => 'Thứ 5',
                                6 => 'Thứ 6',
                                7 => 'Thứ 7',
                                8 => 'Chủ nhật',
                                default => 'Thứ ' . $section->day_of_week,
                            };
                            $semOrderOfCourse = $curriculumMap[$section->course_id] ?? null;
                        @endphp
                        <tr>
                            <td class="ps-3 text-center text-muted small">{{ $index + 1 }}</td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">{{ $section->section_code }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                    <span class="fw-semibold text-dark">{{ $section->course?->course_name }}</span>
                                    @if ($semOrderOfCourse)
                                        <span class="badge bg-light text-secondary border small" title="Môn thuộc Kỳ {{ $semOrderOfCourse }} theo chương trình khung">
                                            Kỳ {{ $semOrderOfCourse }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-secondary small">{{ $section->course?->course_code }} ({{ $section->course?->credits }} TC)</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border small">{{ $section->specialization_name ?? 'Chung toàn khoa' }}</span>
                            </td>
                            <td>
                                <span class="small text-dark">{{ $section->lecturer?->full_name ?? 'Chưa phân công' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">
                                    {{ $dayText }} (Tiết {{ $section->start_period }}-{{ $section->end_period }})
                                </span>
                            </td>
                            <td>
                                <span class="small text-secondary">{{ $section->room_name }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">
                                    {{ $section->enrollments_count }} / {{ $section->max_capacity }}
                                </span>
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-inline-flex gap-1">
                                    <!-- Nút Sửa lớp học phần (Modal Per-row) -->
                                    <button type="button" class="btn btn-sm btn-light border text-dark" 
                                            data-bs-toggle="modal" data-bs-target="#editSectionModal-{{ $section->id }}"
                                            title="Sửa thông tin lớp / Đổi giảng viên">
                                        <i class="bi bi-pencil"></i>
                                        <span>Sửa</span>
                                    </button>

                                    <!-- Nút Xóa lớp học phần -->
                                    <form method="POST" action="{{ route('admin.course-sections.destroy', $section) }}" class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa lớp học phần [{{ $section->section_code }}]?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-dark" title="Xóa lớp học phần">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>

                                    <!-- Modal Sửa Lớp Học Phần (Per-row) -->
                                    <div class="modal fade text-start" id="editSectionModal-{{ $section->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border shadow-sm rounded-3">
                                                <form method="POST" action="{{ route('admin.course-sections.update', $section) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header border-bottom py-3">
                                                        <h5 class="modal-title fw-bold text-dark fs-6">
                                                             Sửa Lớp: {{ $section->section_code }} ({{ $section->course?->course_name }})
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Giảng viên giảng dạy</label>
                                                            <select class="form-select form-select-sm" name="lecturer_id">
                                                                <option value="">-- Chưa phân công --</option>
                                                                @foreach ($lecturers as $lec)
                                                                    <option value="{{ $lec->id }}" {{ $section->lecturer_id == $lec->id ? 'selected' : '' }}>
                                                                        {{ $lec->full_name }} ({{ $lec->specialization?->name ?? 'Khoa' }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-4">
                                                                <label class="form-label small fw-semibold text-secondary">Thứ trong tuần</label>
                                                                <select class="form-select form-select-sm" name="day_of_week" required>
                                                                    <option value="2" {{ $section->day_of_week == 2 ? 'selected' : '' }}>Thứ 2</option>
                                                                    <option value="3" {{ $section->day_of_week == 3 ? 'selected' : '' }}>Thứ 3</option>
                                                                    <option value="4" {{ $section->day_of_week == 4 ? 'selected' : '' }}>Thứ 4</option>
                                                                    <option value="5" {{ $section->day_of_week == 5 ? 'selected' : '' }}>Thứ 5</option>
                                                                    <option value="6" {{ $section->day_of_week == 6 ? 'selected' : '' }}>Thứ 6</option>
                                                                    <option value="7" {{ $section->day_of_week == 7 ? 'selected' : '' }}>Thứ 7</option>
                                                                    <option value="8" {{ $section->day_of_week == 8 ? 'selected' : '' }}>Chủ nhật</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label small fw-semibold text-secondary">Tiết bắt đầu</label>
                                                                <input type="number" class="form-control form-control-sm" name="start_period" 
                                                                       value="{{ $section->start_period }}" min="1" max="12" required>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label small fw-semibold text-secondary">Tiết kết thúc</label>
                                                                <input type="number" class="form-control form-control-sm" name="end_period" 
                                                                       value="{{ $section->end_period }}" min="1" max="12" required>
                                                            </div>
                                                        </div>

                                                        <div class="row g-3">
                                                            <div class="col-md-7">
                                                                <label class="form-label small fw-semibold text-secondary">Phòng học</label>
                                                                <input type="text" class="form-control form-control-sm" name="room_name" 
                                                                       value="{{ $section->room_name }}" required>
                                                            </div>
                                                            <div class="col-md-5">
                                                                <label class="form-label small fw-semibold text-secondary">Sĩ số tối đa</label>
                                                                <input type="number" class="form-control form-control-sm" name="max_capacity" 
                                                                       value="{{ $section->max_capacity }}" min="10" max="150" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top py-2.5 bg-light">
                                                        <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy</button>
                                                        <button type="submit" class="btn btn-sm btn-dark px-3">Lưu cập nhật</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted small">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                @if ($viewScope === 'current')
                                    Chưa có lớp học phần nào được mở cho các môn thuộc <strong>Học kỳ {{ $selectedSemesterOrder }}</strong> ({{ $selectedSpec?->name }}) trong đợt [{{ $selectedSemesterCode }}].<br>
                                    @if ($selectedSemester && $selectedSemester->is_registration_open)
                                        Hãy bấm nút <strong>"Mở lớp tự động (theo Khung)"</strong> ở Khối 1 bên trên để mở các lớp theo khung.<br>
                                    @else
                                        <span class="text-danger fw-semibold">Đợt học kỳ này đã đóng đăng ký, không thể tạo thêm lớp mới.</span><br>
                                    @endif
                                    @if ($totalSectionsInSemester > 0)
                                        <a href="{{ route('admin.course-sections.index', array_merge(request()->query(), ['view_scope' => 'all'])) }}" class="btn btn-sm btn-outline-dark mt-2">
                                            <i class="bi bi-eye me-1"></i>Xem tất cả lớp đã mở của mọi kỳ trong đợt này ({{ $totalSectionsInSemester }} lớp)
                                        </a>
                                    @endif
                                @else
                                    Chưa có lớp học phần nào được mở trong đợt học kỳ [{{ $selectedSemesterCode }}].
                                @endif
                            </td>
                        </tr>
                    @endforelse
            </table>
        </div>
    </div>
</div>

<!-- Modal Mở Lớp Học Phần Thủ Công (Thiết kế phẳng, tối giản) -->
<div class="modal fade" id="createSectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border shadow-sm rounded-3">
            <form method="POST" action="{{ route('admin.course-sections.store') }}">
                @csrf
                <input type="hidden" name="semester_code" value="{{ $selectedSemesterCode }}">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark fs-6">Mở Lớp Học Phần Thủ Công</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold text-secondary">Chọn Môn học <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="course_id" required>
                                <option value="">-- Chọn môn học --</option>
                                @foreach ($courses as $c)
                                    <option value="{{ $c->id }}">[{{ $c->course_code }}] {{ $c->course_name }} ({{ $c->credits }} TC)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-secondary">Gán Chuyên ngành</label>
                            <select class="form-select form-select-sm" name="specialization_id">
                                <option value="">-- Chung cho toàn trường/khoa --</option>
                                @foreach ($specializations as $sp)
                                    <option value="{{ $sp->id }}" {{ $selectedSpec && $selectedSpec->id == $sp->id ? 'selected' : '' }}>
                                        {{ $sp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Giảng viên giảng dạy</label>
                            <select class="form-select form-select-sm" name="lecturer_id">
                                <option value="">-- Chưa phân công --</option>
                                @foreach ($lecturers as $lec)
                                    <option value="{{ $lec->id }}">{{ $lec->full_name }} ({{ $lec->specialization?->name ?? 'Khoa' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Sĩ số tối đa <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" name="max_capacity" value="60" min="10" max="150" required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Thứ trong tuần <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="day_of_week" required>
                                <option value="2">Thứ 2</option>
                                <option value="3">Thứ 3</option>
                                <option value="4">Thứ 4</option>
                                <option value="5">Thứ 5</option>
                                <option value="6">Thứ 6</option>
                                <option value="7">Thứ 7</option>
                                <option value="8">Chủ nhật</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold text-secondary">Tiết bắt đầu <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" name="start_period" value="1" min="1" max="12" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold text-secondary">Tiết kết thúc <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" name="end_period" value="3" min="1" max="12" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Phòng học <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="room_name" value="B201 - Cơ sở 140 Lê Trọng Tấn" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5 bg-light">
                    <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-sm btn-dark px-3">Tạo Lớp Học Phần</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
