@extends('layouts.spark')

@section('title', 'Quản lý Môn học theo Chương trình khung')

@section('content')
<div class="container-fluid px-0">
    <!-- Header: Tối giản, không nút thêm ở đầu trang -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Quản lý Môn học theo Chương trình khung</h1>
            <p class="text-secondary small mb-0">Quản lý danh sách các học phần theo từng chuyên ngành đào tạo và phân bổ chi tiết theo 8 học kỳ</p>
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

    <!-- Thanh chọn Chuyên ngành đào tạo (Thiết kế phẳng, tối giản) -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.subjects.index', [], false) }}" class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <input type="hidden" name="semester" value="{{ $activeTab }}">
                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 750px;">
                    <label class="form-label small fw-medium text-dark mb-0 text-nowrap">
                        <i class="bi bi-mortarboard me-1"></i>Chuyên ngành:
                    </label>
                    <select name="specialization_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach ($specializations as $spec)
                            <option value="{{ $spec->id }}" {{ $activeSpec && $activeSpec->id == $spec->id ? 'selected' : '' }}>
                                [{{ $spec->code }}] {{ $spec->name }} ({{ $spec->faculty?->name ?? ($spec->faculty_name ?: 'Khoa quản lý') }})
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-dark d-inline-flex align-items-center gap-1.5 px-3 text-nowrap">
                        <i class="bi bi-funnel"></i>
                        <span>Lọc</span>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.specializations.index', [], false) }}" class="btn btn-sm btn-light border text-dark" title="Quản lý Chuyên ngành">
                        <i class="bi bi-mortarboard me-1"></i> Quản lý Chuyên ngành
                    </a>
                    <a href="{{ route('admin.faculties.index', [], false) }}" class="btn btn-sm btn-light border text-dark" title="Quản lý Khoa">
                        <i class="bi bi-building me-1"></i> Quản lý Khoa
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if ($activeSpec)
        @php
            $allSpecItems = collect($curriculumBySemester)->flatten(1);
            $totalSpecCredits = $allSpecItems->sum(fn($i) => $i->course?->credits ?? 0);
        @endphp

        <!-- Khung Chuyên ngành được chọn -->
        <div class="card border shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border font-monospace px-2 py-1 fs-6">
                        {{ $activeSpec->code }}
                    </span>
                    <div>
                        <h2 class="h5 fw-bold text-dark mb-0">{{ $activeSpec->name }}</h2>
                        <div class="text-secondary small">
                            Khoa trực thuộc: {{ $activeSpec->faculty?->name ?? ($activeSpec->faculty_name ?: 'Chưa phân khoa') }}
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border px-3 py-2">
                        Tổng cộng: {{ $allSpecItems->count() }} học phần
                    </span>
                    <span class="badge bg-light text-dark border px-3 py-2">
                        Tổng tín chỉ: {{ $totalSpecCredits }} TC
                    </span>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- 8 Nút chọn Kỳ (Tối giản, đơn sắc) -->
                <ul class="nav nav-pills nav-fill mb-4 p-1 bg-light rounded-3 border gap-1" id="semesterTabs" role="tablist">
                    @for ($sem = 1; $sem <= 8; $sem++)
                        @php
                            $semItems = $curriculumBySemester[$sem] ?? collect();
                            $isCurrentActive = ($activeTab === $sem);
                        @endphp
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 fw-medium {{ $isCurrentActive ? 'active bg-dark text-white' : 'text-dark bg-transparent' }}" 
                                    id="tab-sem-{{ $sem }}" data-bs-toggle="pill" data-bs-target="#pane-sem-{{ $sem }}" 
                                    type="button" role="tab" aria-selected="{{ $isCurrentActive ? 'true' : 'false' }}">
                                <span>Kỳ {{ $sem }}</span>
                                <span class="badge {{ $isCurrentActive ? 'bg-light text-dark' : 'bg-light text-secondary border' }} ms-1">
                                    {{ $semItems->count() }}
                                </span>
                            </button>
                        </li>
                    @endfor
                </ul>

                <!-- Nội dung từng kỳ (Khung lớn gồm 2 khung nhỏ: Bắt buộc & Tự chọn) -->
                <div class="tab-content" id="semesterTabContent">
                    @for ($sem = 1; $sem <= 8; $sem++)
                        @php
                            $semItems = $curriculumBySemester[$sem] ?? collect();
                            $mandatoryItems = $semItems->where('is_mandatory', true)->values();
                            $electiveItems = $semItems->where('is_mandatory', false)->values();

                            $mandatoryCredits = $mandatoryItems->sum(fn($i) => $i->course?->credits ?? 0);
                            $electiveCredits = $electiveItems->sum(fn($i) => $i->course?->credits ?? 0);
                            $totalSemCredits = $mandatoryCredits + $electiveCredits;

                            $isCurrentActive = ($activeTab === $sem);
                        @endphp
                        <div class="tab-pane fade {{ $isCurrentActive ? 'show active' : '' }}" id="pane-sem-{{ $sem }}" role="tabpanel">
                            
                            <!-- Header tóm tắt Kỳ (Đã bỏ dòng note tự động xếp theo yêu cầu) -->
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 bg-light rounded-3 mb-4 border">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-calendar3 text-dark"></i>
                                    <h3 class="h6 fw-bold text-dark mb-0">Học kỳ {{ $sem }}</h3>
                                </div>
                                <div class="text-secondary small">
                                    Số lượng: <strong class="text-dark">{{ $semItems->count() }} môn</strong> | 
                                    Tổng số: <strong class="text-dark">{{ $totalSemCredits }} tín chỉ</strong>
                                </div>
                            </div>

                            <!-- 2 KHUNG NHỎ: BẮT BUỘC & TỰ CHỌN -->
                            <div class="d-flex flex-column gap-4">

                                <!-- KHUNG NHỎ 1: HỌC PHẦN BẮT BUỘC -->
                                <div class="card border rounded-3 bg-white">
                                    <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <strong class="text-dark">Học phần Bắt buộc</strong>
                                            <span class="badge bg-light text-dark border">
                                                {{ $mandatoryItems->count() }} môn ({{ $mandatoryCredits }} TC)
                                            </span>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-dark d-inline-flex align-items-center gap-1.5"
                                                    data-bs-toggle="modal" data-bs-target="#addSubjectModal"
                                                    data-semester="{{ $sem }}" data-mandatory="1">
                                                <i class="bi bi-plus-lg"></i>
                                                <span>Thêm học phần bắt buộc</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light text-secondary small">
                                                <tr>
                                                    <th class="ps-3 text-center" style="width: 50px;">#</th>
                                                    <th style="width: 130px;">Mã học phần</th>
                                                    <th>Tên học phần</th>
                                                    <th class="text-center" style="width: 120px;">Số tín chỉ</th>
                                                    <th class="text-center" style="width: 130px;">Phân loại</th>
                                                    <th class="text-center pe-3" style="width: 140px;">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($mandatoryItems as $index => $item)
                                                    <tr>
                                                        <td class="ps-3 text-center text-muted small">{{ $index + 1 }}</td>
                                                        <td>
                                                            <span class="badge bg-light text-dark border font-monospace">
                                                                {{ $item->course?->course_code }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="fw-semibold text-dark">{{ $item->course?->course_name }}</div>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                                {{ $item->course?->credits ?? 3 }} TC
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-light text-dark border">
                                                                Bắt buộc
                                                            </span>
                                                        </td>
                                                        <td class="text-center pe-3">
                                                            <div class="d-inline-flex gap-1">
                                                                <button type="button" class="btn btn-sm btn-light border text-dark" 
                                                                        data-bs-toggle="modal" data-bs-target="#editModal-{{ $item->id }}"
                                                                        title="Sửa học phần">
                                                                    <i class="bi bi-pencil pe-none"></i>
                                                                    <span class="pe-none">Sửa</span>
                                                                </button>

                                                                <!-- Modal Sửa Học Phần #{{ $item->id }} -->
                                                                <div class="modal fade text-start" id="editModal-{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                                                    <div class="modal-dialog modal-dialog-centered">
                                                                        <div class="modal-content border shadow-sm rounded-3">
                                                                            <form method="POST" action="{{ route('admin.subjects.update', $item->id, false) }}">
                                                                                @csrf
                                                                                @method('PUT')
                                                                                <input type="hidden" name="curriculum_id" value="{{ $item->id }}">
                                                                                <div class="modal-header border-bottom py-3">
                                                                                    <h5 class="modal-title fw-bold text-dark fs-6">Sửa thông tin học phần trong khung</h5>
                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                </div>
                                                                                <div class="modal-body p-4">
                                                                                    <div class="alert alert-light border mb-3 p-2.5">
                                                                                        <span class="text-secondary small d-block mb-1">Môn học đang chọn:</span>
                                                                                        <strong class="text-dark fs-6">{{ $item->course?->course_code }} - {{ $item->course?->course_name }}</strong>
                                                                                    </div>
                                                                                    <div class="row g-3">
                                                                                        <div class="col-md-6">
                                                                                            <label class="form-label small fw-semibold text-secondary">Chuyển sang Kỳ</label>
                                                                                            <select class="form-select form-select-sm" name="semester_order" required>
                                                                                                @for ($i = 1; $i <= 8; $i++)
                                                                                                    <option value="{{ $i }}" {{ $item->semester_order == $i ? 'selected' : '' }}>Học kỳ {{ $i }}</option>
                                                                                                @endfor
                                                                                            </select>
                                                                                        </div>
                                                                                        <div class="col-md-6">
                                                                                            <label class="form-label small fw-semibold text-secondary">Phân loại</label>
                                                                                            <select class="form-select form-select-sm" name="is_mandatory" required>
                                                                                                <option value="1" {{ $item->is_mandatory ? 'selected' : '' }}>Bắt buộc</option>
                                                                                                <option value="0" {{ !$item->is_mandatory ? 'selected' : '' }}>Tự chọn</option>
                                                                                            </select>
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
                                                                <form method="POST" action="{{ route('admin.subjects.destroy', $item->id, false) }}" class="d-inline"
                                                                      onsubmit="return confirm('Bạn có chắc chắn muốn xóa học phần này khỏi chương trình khung?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-light border text-dark" title="Xóa học phần">
                                                                        <i class="bi bi-trash"></i>
                                                                        <span>Xóa</span>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center py-4 text-muted small">
                                                            Chưa có học phần bắt buộc nào trong Học kỳ {{ $sem }}.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- KHUNG NHỎ 2: HỌC PHẦN TỰ CHỌN -->
                                <div class="card border rounded-3 bg-white">
                                    <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <strong class="text-dark">Học phần Tự chọn</strong>
                                            <span class="badge bg-light text-dark border">
                                                {{ $electiveItems->count() }} môn ({{ $electiveCredits }} TC)
                                            </span>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-dark d-inline-flex align-items-center gap-1.5"
                                                    data-bs-toggle="modal" data-bs-target="#addSubjectModal"
                                                    data-semester="{{ $sem }}" data-mandatory="0">
                                                <i class="bi bi-plus-lg"></i>
                                                <span>Thêm học phần tự chọn</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light text-secondary small">
                                                <tr>
                                                    <th class="ps-3 text-center" style="width: 50px;">#</th>
                                                    <th style="width: 130px;">Mã học phần</th>
                                                    <th>Tên học phần</th>
                                                    <th class="text-center" style="width: 120px;">Số tín chỉ</th>
                                                    <th class="text-center" style="width: 130px;">Phân loại</th>
                                                    <th class="text-center pe-3" style="width: 140px;">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($electiveItems as $index => $item)
                                                    <tr>
                                                        <td class="ps-3 text-center text-muted small">{{ $index + 1 }}</td>
                                                        <td>
                                                            <span class="badge bg-light text-dark border font-monospace">
                                                                {{ $item->course?->course_code }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="fw-semibold text-dark">{{ $item->course?->course_name }}</div>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                                {{ $item->course?->credits ?? 3 }} TC
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-light text-dark border">
                                                                Tự chọn
                                                            </span>
                                                        </td>
                                                        <td class="text-center pe-3">
                                                            <div class="d-inline-flex gap-1">
                                                                <button type="button" class="btn btn-sm btn-light border text-dark" 
                                                                        data-bs-toggle="modal" data-bs-target="#editModal-{{ $item->id }}"
                                                                        title="Sửa học phần">
                                                                    <i class="bi bi-pencil pe-none"></i>
                                                                    <span class="pe-none">Sửa</span>
                                                                </button>

                                                                <!-- Modal Sửa Học Phần #{{ $item->id }} -->
                                                                <div class="modal fade text-start" id="editModal-{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                                                    <div class="modal-dialog modal-dialog-centered">
                                                                        <div class="modal-content border shadow-sm rounded-3">
                                                                            <form method="POST" action="{{ route('admin.subjects.update', $item->id, false) }}">
                                                                                @csrf
                                                                                @method('PUT')
                                                                                <input type="hidden" name="curriculum_id" value="{{ $item->id }}">
                                                                                <div class="modal-header border-bottom py-3">
                                                                                    <h5 class="modal-title fw-bold text-dark fs-6">Sửa thông tin học phần trong khung</h5>
                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                </div>
                                                                                <div class="modal-body p-4">
                                                                                    <div class="alert alert-light border mb-3 p-2.5">
                                                                                        <span class="text-secondary small d-block mb-1">Môn học đang chọn:</span>
                                                                                        <strong class="text-dark fs-6">{{ $item->course?->course_code }} - {{ $item->course?->course_name }}</strong>
                                                                                    </div>
                                                                                    <div class="row g-3">
                                                                                        <div class="col-md-6">
                                                                                            <label class="form-label small fw-semibold text-secondary">Chuyển sang Kỳ</label>
                                                                                            <select class="form-select form-select-sm" name="semester_order" required>
                                                                                                @for ($i = 1; $i <= 8; $i++)
                                                                                                    <option value="{{ $i }}" {{ $item->semester_order == $i ? 'selected' : '' }}>Học kỳ {{ $i }}</option>
                                                                                                @endfor
                                                                                            </select>
                                                                                        </div>
                                                                                        <div class="col-md-6">
                                                                                            <label class="form-label small fw-semibold text-secondary">Phân loại</label>
                                                                                            <select class="form-select form-select-sm" name="is_mandatory" required>
                                                                                                <option value="1" {{ $item->is_mandatory ? 'selected' : '' }}>Bắt buộc</option>
                                                                                                <option value="0" {{ !$item->is_mandatory ? 'selected' : '' }}>Tự chọn</option>
                                                                                            </select>
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
                                                                <form method="POST" action="{{ route('admin.subjects.destroy', $item->id, false) }}" class="d-inline"
                                                                      onsubmit="return confirm('Bạn có chắc chắn muốn xóa học phần này khỏi chương trình khung?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-light border text-dark" title="Xóa học phần">
                                                                        <i class="bi bi-trash"></i>
                                                                        <span>Xóa</span>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center py-4 text-muted small">
                                                            Chưa có học phần tự chọn nào trong Học kỳ {{ $sem }}.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @else
        <div class="card border shadow-sm rounded-3 bg-white p-5 text-center text-muted">
            <i class="bi bi-mortarboard fs-1 text-secondary opacity-50 mb-2"></i>
            <div class="fw-semibold fs-5 text-dark mb-1">Chưa có chuyên ngành nào trong hệ thống</div>
            <p class="small mb-3">Vui lòng tạo chuyên ngành đào tạo trước để quản lý môn học theo chương trình khung.</p>
            <div>
                <a href="{{ route('admin.specializations.create', [], false) }}" class="btn btn-dark btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Thêm chuyên ngành mới
                </a>
            </div>
        </div>
    @endif
</div>

<!-- Modal Thêm Học Phần Vào Kỳ (Mở bằng data-bs-toggle="modal") -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border shadow-sm rounded-3">
            <form method="POST" action="{{ route('admin.subjects.store', [], false) }}">
                @csrf
                <input type="hidden" name="specialization_id" value="{{ $activeSpec?->id }}">

                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark fs-6" id="addSubjectModalTitle">
                        Thêm Học phần vào Kỳ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Lựa chọn 1: Chọn môn có sẵn -->
                    <div class="mb-3">
                        <label for="select_course_id" class="form-label small fw-semibold text-secondary">
                            Chọn môn học có sẵn trong hệ thống:
                        </label>
                        <select class="form-select form-select-sm" id="select_course_id" name="course_id">
                            <option value="">-- Chọn môn học có sẵn --</option>
                            @foreach ($allCourses as $c)
                                <option value="{{ $c->id }}">
                                    [{{ $c->course_code }}] {{ $c->course_name }} ({{ $c->credits }} tín chỉ)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1">
                        <span class="px-3 text-secondary small fw-medium">HOẶC NHẬP HỌC PHẦN MỚI</span>
                        <hr class="flex-grow-1">
                    </div>

                    <!-- Lựa chọn 2: Nhập thông tin môn mới -->
                    <div class="row g-3 p-3 bg-light rounded-3 border mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-medium text-secondary">Mã môn mới</label>
                            <input type="text" class="form-control form-control-sm" name="new_course_code" placeholder="VD: CNPM105">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium text-secondary">Tên môn học mới</label>
                            <input type="text" class="form-control form-control-sm" name="new_course_name" placeholder="VD: Lập trình Flutter & Dart">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-medium text-secondary">Số tín chỉ</label>
                            <input type="number" class="form-control form-control-sm" name="new_credits" value="3" min="1" max="15">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="semester_order_select" class="form-label small fw-semibold text-secondary">
                                Xếp vào Học kỳ <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm" id="semester_order_select" name="semester_order" required>
                                @for ($i = 1; $i <= 8; $i++)
                                    <option value="{{ $i }}" {{ $activeTab === $i ? 'selected' : '' }}>Học kỳ {{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="is_mandatory_select" class="form-label small fw-semibold text-secondary">
                                Phân loại học phần <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm" id="is_mandatory_select" name="is_mandatory" required>
                                <option value="1">Bắt buộc</option>
                                <option value="0">Tự chọn</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5 bg-light">
                    <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-sm btn-dark px-3">
                        Lưu vào chương trình khung
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Bắt sự kiện mở Modal Thêm học phần qua Bootstrap 5 native event
    const addModalEl = document.getElementById('addSubjectModal');
    if (addModalEl) {
        addModalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget?.closest('[data-semester]') || event.relatedTarget;
            if (button) {
                const sem = button.getAttribute('data-semester') || '1';
                const mandatory = button.getAttribute('data-mandatory') || '1';

                const semSelect = document.getElementById('semester_order_select');
                const mandSelect = document.getElementById('is_mandatory_select');
                const titleEl = document.getElementById('addSubjectModalTitle');

                if (semSelect) semSelect.value = sem;
                if (mandSelect) mandSelect.value = mandatory;
                if (titleEl) {
                    const typeLabel = (mandatory === '1') ? 'Bắt buộc' : 'Tự chọn';
                    titleEl.textContent = 'Thêm học phần ' + typeLabel + ' vào Học kỳ ' + sem;
                }
            }
        });
    // Đồng bộ khi bấm pill tab sang hidden input semester và update URL
    const tabButtons = document.querySelectorAll('#semesterTabs button[data-bs-toggle="pill"]');
    const semHiddenInput = document.querySelector('input[name="semester"]');
    tabButtons.forEach(btn => {
        btn.addEventListener('shown.bs.tab', function (e) {
            const semNum = e.target.id.replace('tab-sem-', '');
            if (semHiddenInput) {
                semHiddenInput.value = semNum;
            }
            const url = new URL(window.location);
            url.searchParams.set('semester', semNum);
            window.history.replaceState({}, '', url);
        });
    });
});
</script>
@endsection
