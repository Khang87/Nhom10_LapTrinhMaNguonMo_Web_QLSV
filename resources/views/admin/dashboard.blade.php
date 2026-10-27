@extends('layouts.spark')

@section('title', 'Trang quản trị')

@section('content')
    <div class="page-header mb-4">
        <div>
            <h1 class="page-title h3 fw-bold text-dark">Trang quản trị đào tạo</h1>
            <p class="page-subtitle text-secondary">Hệ thống quản lý chuyên ngành, chương trình khung, sinh viên, điểm số và điểm danh</p>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4">
        {{-- 0. Khoa đào tạo --}}
        <div class="col">
            <a class="card h-100 text-decoration-none border-0 shadow-sm rounded-3 hover-card" href="{{ route('admin.faculties.index') }}">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="text-white rounded-3 p-3 fs-3 d-flex align-items-center justify-content-center bg-primary" style="width: 54px; height: 54px;">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <h2 class="h5 card-title fw-bold text-dark mb-1">Khoa phụ trách</h2>
                        <p class="card-text text-muted small mb-0">Quản lý khoa, thông tin trưởng khoa & giảng viên.</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- 1. Chuyên ngành --}}
        <div class="col">
            <a class="card h-100 text-decoration-none border-0 shadow-sm rounded-3 hover-card" href="{{ route('admin.specializations.index') }}">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="bg-primary text-white rounded-3 p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div>
                        <h2 class="h5 card-title fw-bold text-dark mb-1">Chuyên ngành</h2>
                        <p class="card-text text-muted small mb-0">Thêm, xóa, sửa và cấu hình chương trình khung.</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- 2. Môn học & Khung CT --}}
        <div class="col">
            <a class="card h-100 text-decoration-none border-0 shadow-sm rounded-3 hover-card" href="{{ route('admin.subjects.index') }}">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="bg-info text-white rounded-3 p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-book-fill"></i>
                    </div>
                    <div>
                        <h2 class="h5 card-title fw-bold text-dark mb-1">Môn học & Khung CT</h2>
                        <p class="card-text text-muted small mb-0">Khung chương trình 8 kỳ, bắt buộc & tự chọn.</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- 3. Giảng viên --}}
        <div class="col">
            <a class="card h-100 text-decoration-none border-0 shadow-sm rounded-3 hover-card" href="{{ route('admin.lecturers.index') }}">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="bg-secondary text-white rounded-3 p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-person-video3"></i>
                    </div>
                    <div>
                        <h2 class="h5 card-title fw-bold text-dark mb-1">Giảng viên</h2>
                        <p class="card-text text-muted small mb-0">Hồ sơ, học vị, phân công theo khoa & chuyên ngành.</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- 4. Sinh viên --}}
        <div class="col">
            <a class="card h-100 text-decoration-none border-0 shadow-sm rounded-3 hover-card" href="{{ route('admin.students.index') }}">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="bg-success text-white rounded-3 p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <h2 class="h5 card-title fw-bold text-dark mb-1">Sinh viên</h2>
                        <p class="card-text text-muted small mb-0">Auto-ID, auto xếp Kỳ 1, xem chi tiết & reset pass.</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- 4. Quản lý Điểm --}}
        <div class="col">
            <a class="card h-100 text-decoration-none border-0 shadow-sm rounded-3 hover-card" href="{{ route('admin.grades.index') }}">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="bg-warning text-dark rounded-3 p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <div>
                        <h2 class="h5 card-title fw-bold text-dark mb-1">Quản lý Điểm</h2>
                        <p class="card-text text-muted small mb-0">QT, GK, CK, Điểm chữ ABCDF, Đạt/Rớt và tùy chỉnh điểm.</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- 5. Báo cáo Điểm danh --}}
        <div class="col">
            <a class="card h-100 text-decoration-none border-0 shadow-sm rounded-3 hover-card" href="{{ route('admin.attendances.index') }}">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="bg-danger text-white rounded-3 p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>
                    <div>
                        <h2 class="h5 card-title fw-bold text-dark mb-1">Báo cáo Điểm danh</h2>
                        <p class="card-text text-muted small mb-0">Đồng bộ từ Giảng viên, lọc theo ngành, môn, lớp, ngày.</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- 6. Mở Lớp học phần --}}
        <div class="col">
            <a class="card h-100 text-decoration-none border-0 shadow-sm rounded-3 hover-card" href="{{ route('admin.course-sections.index') }}">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="bg-primary text-white rounded-3 p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-diagram-3-fill"></i>
                    </div>
                    <div>
                        <h2 class="h5 card-title fw-bold text-dark mb-1">Mở Lớp học phần</h2>
                        <p class="card-text text-muted small mb-0">Mở lớp theo kỳ, tự động random Giảng viên theo ngành.</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- 7. Học kỳ & Đợt ĐK --}}
        <div class="col">
            <a class="card h-100 text-decoration-none border-0 shadow-sm rounded-3 hover-card" href="{{ route('admin.semesters.index') }}">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="bg-secondary text-white rounded-3 p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-calendar2-range-fill"></i>
                    </div>
                    <div>
                        <h2 class="h5 card-title fw-bold text-dark mb-1">Học kỳ & Đợt ĐK</h2>
                        <p class="card-text text-muted small mb-0">Cấu hình thời gian mở và đóng đợt đăng ký môn.</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- 8. Lớp học danh nghĩa --}}
        <div class="col">
            <a class="card h-100 text-decoration-none border-0 shadow-sm rounded-3 hover-card" href="{{ route('admin.classes.index') }}">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="bg-dark text-white rounded-3 p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <h2 class="h5 card-title fw-bold text-dark mb-1">Lớp danh nghĩa</h2>
                        <p class="card-text text-muted small mb-0">Quản lý các lớp sinh hoạt sinh viên.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
@endsection
