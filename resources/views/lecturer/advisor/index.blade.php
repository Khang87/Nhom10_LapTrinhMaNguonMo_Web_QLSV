@extends('layouts.spark')

@section('title', 'Quản lý Lớp Cố Vấn Học Tập - HUIT')

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
                <span class="badge bg-light text-primary border border-primary-subtle">Cố vấn học tập (CVHT)</span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Quản lý Lớp Cố Vấn Học Tập</h1>
            <p class="text-secondary small mb-0">
                Theo dõi sức khỏe học tập, cảnh báo học vụ và đôn đốc học phí sinh viên các lớp sinh hoạt do Thầy/Cô chủ nhiệm.
            </p>
        </div>
    </div>

    <!-- Danh sách các lớp sinh hoạt phụ trách -->
    <div class="row g-4">
        @forelse ($classesStats as $item)
            @php
                $cls = $item->class;
            @endphp
            <div class="col-md-6 col-lg-4">
                <div class="card border shadow-sm rounded-3 bg-white h-100 d-flex flex-column transition-all hover-shadow">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <span class="badge bg-dark text-white font-monospace fs-6 px-2.5 py-1">
                            {{ $cls->class_code }}
                        </span>
                        <span class="badge bg-light text-secondary border">
                            Khóa {{ $cls->cohort_year ?? '2023' }}
                        </span>
                    </div>
                    <div class="card-body p-3 flex-grow-1">
                        <h5 class="fw-bold text-dark mb-1">{{ $cls->class_name ?? 'Lớp ' . $cls->class_code }}</h5>
                        <div class="small text-secondary mb-3">
                            <i class="bi bi-mortarboard me-1"></i>Chuyên ngành: <strong>{{ $cls->specialization?->name ?? 'Công nghệ thông tin' }}</strong>
                            <br>
                            <i class="bi bi-building me-1"></i>Khoa: {{ $cls->specialization?->faculty?->faculty_name ?? 'Công nghệ thông tin' }}
                        </div>

                        <!-- 4 Chỉ số của lớp -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="p-2.5 rounded-3 bg-light border text-center">
                                    <span class="text-secondary small" style="font-size: 0.75rem;">Sĩ số sinh viên</span>
                                    <div class="fs-5 fw-bold text-dark mt-0.5">{{ $item->total_students }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2.5 rounded-3 bg-light border text-center">
                                    <span class="text-secondary small" style="font-size: 0.75rem;">GPA bình quân</span>
                                    <div class="fs-5 fw-bold text-dark mt-0.5">{{ number_format($item->avg_gpa, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2.5 rounded-3 bg-light border text-center">
                                    <span class="text-secondary small" style="font-size: 0.75rem;">Cảnh báo học vụ</span>
                                    <div class="fs-5 fw-bold {{ $item->warning_count > 0 ? 'text-danger' : 'text-success' }} mt-0.5">
                                        {{ $item->warning_count }} SV
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2.5 rounded-3 bg-light border text-center">
                                    <span class="text-secondary small" style="font-size: 0.75rem;">Nợ học phí</span>
                                    <div class="fs-5 fw-bold {{ $item->unpaid_count > 0 ? 'text-warning' : 'text-success' }} mt-0.5">
                                        {{ $item->unpaid_count }} SV
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top p-3">
                        <a href="{{ route('lecturer.advisor.class', $cls) }}" class="btn btn-dark btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1.5 py-2">
                            <i class="bi bi-people-fill"></i>
                            <span class="fw-semibold">Vào quản lý hồ sơ lớp</span>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border rounded-3 bg-white p-5 text-center">
                    <i class="bi bi-person-x fs-1 text-secondary opacity-50 mb-2"></i>
                    <h5 class="fw-bold text-dark mb-1">Chưa được phân công lớp Cố vấn học tập</h5>
                    <p class="text-secondary small mb-0">
                        Thầy/Cô hiện chưa được phân công làm Cố vấn học tập cho lớp sinh hoạt nào. Vui lòng liên hệ Phòng Đào tạo hoặc Quản trị viên để được phân công.
                    </p>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
