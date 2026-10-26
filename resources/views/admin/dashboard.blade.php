@extends('layouts.spark')

@section('title', 'Trang quản trị')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Trang quản trị</h1>
            <p class="page-subtitle">Quản lý thông tin đào tạo và kết quả học tập.</p>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <div class="col">
            <a class="card h-100 text-decoration-none" href="{{ route('admin.classes.index') }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-building fs-2 text-success" aria-hidden="true"></i>
                    <div>
                        <h2 class="h5 card-title mb-1">Lớp học</h2>
                        <p class="card-text text-muted mb-0">Quản lý danh sách lớp và phòng học.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a class="card h-100 text-decoration-none" href="{{ route('admin.subjects.index') }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-book fs-2 text-success" aria-hidden="true"></i>
                    <div>
                        <h2 class="h5 card-title mb-1">Môn học</h2>
                        <p class="card-text text-muted mb-0">Cập nhật môn học và số tín chỉ.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a class="card h-100 text-decoration-none" href="{{ route('admin.students.index') }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-people fs-2 text-success" aria-hidden="true"></i>
                    <div>
                        <h2 class="h5 card-title mb-1">Sinh viên</h2>
                        <p class="card-text text-muted mb-0">Quản lý hồ sơ và lớp của sinh viên.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a class="card h-100 text-decoration-none" href="{{ route('admin.grades.index') }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-journal-check fs-2 text-success" aria-hidden="true"></i>
                    <div>
                        <h2 class="h5 card-title mb-1">Điểm</h2>
                        <p class="card-text text-muted mb-0">Nhập và theo dõi kết quả học tập.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a class="card h-100 text-decoration-none" href="{{ route('admin.attendances.index') }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-calendar-check fs-2 text-success" aria-hidden="true"></i>
                    <div>
                        <h2 class="h5 card-title mb-1">Điểm danh</h2>
                        <p class="card-text text-muted mb-0">Ghi nhận và xem tình hình chuyên cần.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a class="card h-100 text-decoration-none" href="{{ route('admin.accounts.index') }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-person-badge fs-2 text-success" aria-hidden="true"></i>
                    <div>
                        <h2 class="h5 card-title mb-1">Tài khoản</h2>
                        <p class="card-text text-muted mb-0">Quản lý tài khoản quản trị, giảng viên, sinh viên.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
@endsection
