@extends('layouts.spark')

@section('title', 'Thông tin cá nhân Giảng viên - HUIT')

@section('content')
<div class="container-fluid px-0">
    <!-- Tiêu đề trang -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1 fw-bold text-dark">Hồ sơ Giảng viên</h1>
            <p class="text-secondary small mb-0">Thông tin lý lịch khoa học, đơn vị công tác và phân công quản lý lớp học</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                <i class="bi bi-shield-check me-1"></i> Tài khoản hoạt động
            </span>
        </div>
    </div>

    <!-- Thông báo Alert -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center gap-2 mb-1 fw-bold">
                <i class="bi bi-exclamation-triangle-fill"></i> Có lỗi xảy ra, vui lòng kiểm tra lại:
            </div>
            <ul class="mb-0 ps-4 small">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Thẻ Header tóm tắt hồ sơ giảng viên -->
    <div class="card border shadow-sm mb-4 bg-white rounded-3">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-2 shadow-sm" 
                         style="width: 80px; height: 80px; border-radius: 50%;">
                        {{ mb_substr($lecturer->full_name ?? $user->name, 0, 1) }}
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h2 class="h5 mb-0 fw-bold text-dark">{{ $lecturer->full_name }}</h2>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                            {{ $lecturer->degree }}
                        </span>
                        @if ($lecturer->headedFaculty)
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                <i class="bi bi-star-fill text-warning me-1"></i>Trưởng khoa {{ $lecturer->headedFaculty->code }}
                            </span>
                        @endif
                    </div>
                    <div class="text-secondary small mb-1">
                        Mã GV: <strong class="text-dark font-monospace">{{ $lecturer->lecturer_code }}</strong> 
                        | Khoa: <strong class="text-dark">{{ $lecturer->faculty?->name ?: $lecturer->faculty_name }}</strong>
                        @if ($lecturer->specialization)
                            | Bộ môn/Chuyên ngành: <strong class="text-dark">{{ $lecturer->specialization->name }}</strong>
                        @endif
                    </div>
                    <div class="text-muted small d-flex flex-wrap gap-3">
                        <span><i class="bi bi-envelope text-primary me-1"></i>{{ $lecturer->email }}</span>
                        @if ($lecturer->phone)
                            <span><i class="bi bi-telephone text-primary me-1"></i>{{ $lecturer->phone }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3 Thẻ KPI tổng quan -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border shadow-sm rounded-3 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-light text-primary d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Lớp danh nghĩa (CVHT)</div>
                        <div class="fs-4 fw-bold text-dark">{{ $lecturer->homeroomClasses->count() }} <span class="fs-6 fw-normal text-muted">lớp</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border shadow-sm rounded-3 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-light text-primary d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                        <i class="bi bi-mortarboard fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Sinh viên phụ trách CVHT</div>
                        <div class="fs-4 fw-bold text-dark">{{ $totalHomeroomStudents }} <span class="fs-6 fw-normal text-muted">sinh viên</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border shadow-sm rounded-3 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-light text-primary d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                        <i class="bi bi-journal-bookmark fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Lớp HP giảng dạy (Kỳ này)</div>
                        <div class="fs-4 fw-bold text-dark">{{ $activeSections->count() }} <span class="fs-6 fw-normal text-muted">lớp</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bố cục 2 Cột -->
    <div class="row g-4">
        <!-- Cột 1: Thông tin công tác & Lớp phụ trách -->
        <div class="col-lg-7">
            <!-- Thông tin học thuật & công tác -->
            <div class="card border shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-light border-bottom py-3">
                    <h3 class="h6 mb-0 fw-bold text-primary">
                        <i class="bi bi-briefcase-fill me-2"></i>THÔNG TIN CÔNG TÁC & HỌC VẤN
                    </h3>
                </div>
                <div class="card-body p-4">
                    <table class="table table-sm table-borderless align-middle mb-0">
                        <tbody>
                            <tr>
                                <th class="text-secondary" style="width: 35%;">Mã giảng viên:</th>
                                <td class="fw-bold font-monospace text-dark">{{ $lecturer->lecturer_code }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Họ và tên:</th>
                                <td class="fw-semibold text-dark">{{ $lecturer->full_name }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Học vị / Học hàm:</th>
                                <td><span class="badge bg-light text-dark border">{{ $lecturer->degree }}</span></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Khoa quản lý:</th>
                                <td class="fw-semibold">{{ $lecturer->faculty?->name ?: $lecturer->faculty_name }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Chuyên ngành đào tạo:</th>
                                <td>{{ $lecturer->specialization?->name ?: 'Giảng dạy chung / Chưa gán' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Chức vụ học thuật:</th>
                                <td>
                                    @if ($lecturer->headedFaculty)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                            Trưởng khoa {{ $lecturer->headedFaculty->name }}
                                        </span>
                                    @elseif ($lecturer->homeroomClasses->isNotEmpty())
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            Cố vấn học tập (CVHT) & Giảng viên
                                        </span>
                                    @else
                                        <span class="text-dark">Giảng viên cơ hữu</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Cơ sở giảng dạy:</th>
                                <td>Trường Đại học Công Thương TP. Hồ Chí Minh (HUIT)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Danh sách Lớp danh nghĩa (CVHT) phụ trách -->
            <div class="card border shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-light border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h3 class="h6 mb-0 fw-bold text-primary">
                        <i class="bi bi-people-fill me-2"></i>LỚP DANH NGHĨA PHỤ TRÁCH (CVHT)
                    </h3>
                    @if($lecturer->homeroomClasses->isNotEmpty())
                        <a href="{{ route('lecturer.advisor.index') }}" class="btn btn-sm btn-outline-primary py-1 px-2.5">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Vào phân hệ CVHT
                        </a>
                    @endif
                </div>
                <div class="card-body p-4">
                    @if ($lecturer->homeroomClasses->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-people fs-2 d-block mb-2 text-secondary opacity-50"></i>
                            Hiện tại Quản trị viên chưa phân công lớp danh nghĩa nào cho giảng viên.
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach ($lecturer->homeroomClasses as $homeroomClass)
                                <div class="col-12 col-md-6">
                                    <div class="card border rounded-3 h-100 p-3 bg-light-subtle">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="fw-bold font-monospace text-primary fs-6">{{ $homeroomClass->class_code }}</span>
                                            <span class="badge bg-primary text-white">
                                                {{ $homeroomClass->students_count }} sinh viên
                                            </span>
                                        </div>
                                        <div class="fw-semibold text-dark small mb-1">{{ $homeroomClass->class_name }}</div>
                                        @if ($homeroomClass->specialization)
                                            <div class="text-secondary small mb-3">
                                                <i class="bi bi-diagram-2 me-1"></i>{{ $homeroomClass->specialization->name }}
                                            </div>
                                        @endif
                                        <div class="mt-auto pt-2 border-top">
                                            <a href="{{ route('lecturer.advisor.class', $homeroomClass) }}" class="btn btn-sm btn-light border w-100">
                                                <i class="bi bi-person-lines-fill me-1"></i>Xem danh sách lớp
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Các lớp học phần kỳ này -->
            <div class="card border shadow-sm rounded-3 bg-white">
                <div class="card-header bg-light border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h3 class="h6 mb-0 fw-bold text-primary">
                        <i class="bi bi-journal-text me-2"></i>LỚP HỌC PHẦN GIẢNG DẠY ({{ $currentSemester?->semester_name ?? 'Kỳ hiện tại' }})
                    </h3>
                    <a href="{{ route('lecturer.schedule.index') }}" class="btn btn-sm btn-light border py-1 px-2.5">
                        <i class="bi bi-calendar-week me-1"></i>Thời khóa biểu
                    </a>
                </div>
                <div class="card-body p-0">
                    @if ($activeSections->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                            Không có lớp học phần nào được phân công trong học kỳ này.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-secondary small">
                                    <tr>
                                        <th class="ps-3">Mã lớp HP</th>
                                        <th>Học phần</th>
                                        <th class="text-center">Sĩ số</th>
                                        <th class="text-center pe-3">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($activeSections as $sec)
                                        <tr>
                                            <td class="ps-3 font-monospace fw-semibold small text-primary">
                                                {{ $sec->section_code }}
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark small">{{ $sec->course?->course_name }}</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">{{ $sec->course?->course_code }} ({{ $sec->course?->credits }} TC)</div>
                                            </td>
                                            <td class="text-center small">
                                                <span class="badge bg-light text-dark border">{{ $sec->enrollments_count }} SV</span>
                                            </td>
                                            <td class="text-center pe-3">
                                                <div class="d-inline-flex gap-1">
                                                    <a href="{{ route('lecturer.attendances.take', $sec) }}" class="btn btn-xs btn-light border" title="Điểm danh">
                                                        <i class="bi bi-check2-square"></i>
                                                    </a>
                                                    <a href="{{ route('lecturer.grades.edit', $sec) }}" class="btn btn-xs btn-light border" title="Nhập điểm">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Cột 2: Cập nhật Thông tin & Đổi mật khẩu -->
        <div class="col-lg-5">
            <div class="card border shadow-sm rounded-3 bg-white sticky-top" style="top: 20px;">
                <div class="card-header bg-light border-bottom py-3">
                    <h3 class="h6 mb-0 fw-bold text-primary">
                        <i class="bi bi-shield-lock-fill me-2"></i>CẬP NHẬT THÔNG TIN & BẢO MẬT
                    </h3>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('lecturer.profile.update') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="lecturer_code_ro" class="form-label small fw-semibold text-secondary">
                                Mã giảng viên (Tên đăng nhập)
                            </label>
                            <input type="text" class="form-control bg-light font-monospace" id="lecturer_code_ro" 
                                   value="{{ $lecturer->lecturer_code }}" readonly disabled>
                            <div class="form-text small">Mã định danh do Nhà trường cấp, không thể tự thay đổi.</div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold text-dark">
                                Email công vụ / Liên hệ <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email', $lecturer->email) }}" required maxlength="100">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">Email chính thức để nhận thông báo từ PĐT và Nhà trường.</div>
                        </div>

                        <div class="mb-4">
                            <label for="phone" class="form-label small fw-semibold text-dark">
                                Số điện thoại liên hệ
                            </label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone', $lecturer->phone) }}" placeholder="VD: 0912345678" maxlength="20">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <h4 class="h6 fw-bold text-dark mb-3">
                            <i class="bi bi-key me-1 text-primary"></i> Đổi mật khẩu đăng nhập
                        </h4>

                        <div class="mb-3">
                            <label for="current_password" class="form-label small fw-semibold text-dark">
                                Mật khẩu hiện tại
                            </label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                   id="current_password" name="current_password" placeholder="Nhập mật khẩu hiện tại nếu muốn đổi">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label small fw-semibold text-dark">
                                Mật khẩu mới
                            </label>
                            <input type="password" class="form-control @error('new_password') is-invalid @enderror" 
                                   id="new_password" name="new_password" placeholder="Tối thiểu 6 ký tự">
                            @error('new_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="new_password_confirmation" class="form-label small fw-semibold text-dark">
                                Xác nhận mật khẩu mới
                            </label>
                            <input type="password" class="form-control" 
                                   id="new_password_confirmation" name="new_password_confirmation" placeholder="Nhập lại mật khẩu mới">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-save me-1"></i> Lưu thông tin hồ sơ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
