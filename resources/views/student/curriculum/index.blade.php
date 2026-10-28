@extends('layouts.spark')

@section('title', 'Chương trình khung đào tạo - HUIT')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Chương trình khung đào tạo</h1>
            <p class="text-secondary small mb-0">Danh sách các học phần theo chuyên ngành đào tạo và phân bổ chi tiết theo 8 học kỳ</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('student.grades.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-award"></i>
                <span>Xem Kết quả học tập</span>
            </a>
            <a href="{{ route('student.subjects.index') }}" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-journal-plus"></i>
                <span>Đăng ký môn học</span>
            </a>
        </div>
    </div>

    @if ($specialization)
        <!-- Khung Chuyên ngành đào tạo (Thiết kế phẳng Spark UI giống Admin) -->
        <div class="card border shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border font-monospace px-2.5 py-1.5 fs-6">
                        {{ $specialization->code }}
                    </span>
                    <div>
                        <h2 class="h5 fw-bold text-dark mb-0">{{ $specialization->name }}</h2>
                        <div class="text-secondary small">
                            Khoa trực thuộc: {{ $specialization->faculty?->name ?? ($specialization->faculty_name ?: ($student->faculty_name ?? 'Khoa Công nghệ Thông tin')) }}
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border px-3 py-2">
                        Tổng cộng: {{ $totalFrameworkCourses }} học phần
                    </span>
                    <span class="badge bg-light text-dark border px-3 py-2">
                        Tổng tín chỉ: {{ $totalFrameworkCredits }} TC
                    </span>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- 8 Nút chọn Kỳ (Đơn sắc Spark UI giống Admin) -->
                <ul class="nav nav-pills nav-fill mb-4 p-1 bg-light rounded-3 border gap-1" id="curriculumSemesterTabs" role="tablist">
                    @for ($sem = 1; $sem <= 8; $sem++)
                        @php
                            $semItems = $curriculumBySemester[$sem] ?? collect();
                            $isCurrentActive = ($activeTab === $sem);
                        @endphp
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 fw-medium {{ $isCurrentActive ? 'active bg-dark text-white' : 'text-dark bg-transparent' }}" 
                                    id="tab-curriculum-sem-{{ $sem }}" data-bs-toggle="pill" data-bs-target="#pane-curriculum-sem-{{ $sem }}" 
                                    type="button" role="tab" aria-selected="{{ $isCurrentActive ? 'true' : 'false' }}">
                                <span>Kỳ {{ $sem }}</span>
                            </button>
                        </li>
                    @endfor
                </ul>

                <!-- Nội dung từng kỳ (Gồm 2 khung: Bắt buộc & Tự chọn) -->
                <div class="tab-content" id="curriculumSemesterTabContent">
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
                        <div class="tab-pane fade {{ $isCurrentActive ? 'show active' : '' }}" id="pane-curriculum-sem-{{ $sem }}" role="tabpanel">
                            
                            <!-- Header tóm tắt Kỳ -->
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

                            <div class="row g-4">
                                <!-- KHUNG 1: HỌC PHẦN BẮT BUỘC -->
                                <div class="col-12 col-xl-6">
                                    <div class="card border rounded-3 h-100">
                                        <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-dark text-white rounded-circle px-2 py-1" style="font-size: 0.75rem;">1</span>
                                                <h4 class="h6 fw-bold text-dark mb-0">Học phần Bắt buộc</h4>
                                            </div>
                                            <span class="badge bg-light text-dark border">
                                                {{ $mandatoryItems->count() }} môn ({{ $mandatoryCredits }} TC)
                                            </span>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0">
                                                    <thead class="table-light text-secondary small">
                                                        <tr>
                                                            <th style="width: 45px;" class="ps-3 text-center">#</th>
                                                            <th style="width: 100px;">Mã môn</th>
                                                            <th>Tên môn học</th>
                                                            <th style="width: 70px;" class="text-center">Số TC</th>
                                                            <th style="width: 110px;" class="text-center pe-3">Loại</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($mandatoryItems as $mIdx => $mItem)
                                                            <tr>
                                                                <td class="ps-3 text-center text-muted small">{{ $mIdx + 1 }}</td>
                                                                <td>
                                                                    <span class="badge bg-light text-dark border font-monospace">
                                                                        {{ $mItem->course?->course_code }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="fw-semibold text-dark">{{ $mItem->course?->course_name }}</span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-light text-dark border">{{ $mItem->course?->credits }}</span>
                                                                </td>
                                                                <td class="text-center pe-3">
                                                                    <span class="badge bg-light text-dark border small">Bắt buộc</span>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-center py-4 text-secondary small">
                                                                    Không có học phần bắt buộc trong kỳ này.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- KHUNG 2: HỌC PHẦN TỰ CHỌN -->
                                <div class="col-12 col-xl-6">
                                    <div class="card border rounded-3 h-100">
                                        <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-secondary text-white rounded-circle px-2 py-1" style="font-size: 0.75rem;">2</span>
                                                <h4 class="h6 fw-bold text-dark mb-0">Học phần Tự chọn</h4>
                                            </div>
                                            <span class="badge bg-light text-dark border">
                                                {{ $electiveItems->count() }} môn ({{ $electiveCredits }} TC)
                                            </span>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0">
                                                    <thead class="table-light text-secondary small">
                                                        <tr>
                                                            <th style="width: 45px;" class="ps-3 text-center">#</th>
                                                            <th style="width: 100px;">Mã môn</th>
                                                            <th>Tên môn học</th>
                                                            <th style="width: 70px;" class="text-center">Số TC</th>
                                                            <th style="width: 110px;" class="text-center pe-3">Loại</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($electiveItems as $eIdx => $eItem)
                                                            <tr>
                                                                <td class="ps-3 text-center text-muted small">{{ $eIdx + 1 }}</td>
                                                                <td>
                                                                    <span class="badge bg-light text-dark border font-monospace">
                                                                        {{ $eItem->course?->course_code }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="fw-semibold text-dark">{{ $eItem->course?->course_name }}</span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-light text-dark border">{{ $eItem->course?->credits }}</span>
                                                                </td>
                                                                <td class="text-center pe-3">
                                                                    <span class="badge bg-light text-secondary border small">Tự chọn</span>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-center py-4 text-secondary small">
                                                                    Không có học phần tự chọn trong kỳ này.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-light border text-center py-5">
            <i class="bi bi-mortarboard fs-1 text-secondary mb-3 d-block"></i>
            <h5 class="fw-bold text-dark">Chưa có thông tin chuyên ngành</h5>
            <p class="text-secondary small mb-0">Hồ sơ sinh viên chưa được gán chuyên ngành cụ thể để tải chương trình khung đào tạo.</p>
        </div>
    @endif
</div>
@endsection
