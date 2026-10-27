@extends('layouts.spark')

@section('title', 'Chi tiết sinh viên: ' . $student->full_name)

@section('content')
<div class="container-fluid px-0">
    <!-- Header: Tối giản, đồng bộ -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 bg-light text-dark border d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 52px; height: 52px;">
                {{ mb_substr($student->full_name, 0, 1) }}
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h4 fw-bold text-dark mb-0">{{ $student->full_name }}</h1>
                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">{{ $student->student_code }}</span>
                    <span class="badge bg-light text-dark border">{{ $student->study_status }}</span>
                </div>
                <p class="text-secondary small mb-0">
                    <span class="me-2"><i class="bi bi-mortarboard me-1"></i>{{ $student->specialization?->name ?? $student->specialization_name }}</span>
                    <span class="me-2">|</span>
                    <span class="me-2"><i class="bi bi-building me-1"></i>Lớp: {{ $student->class_code }}</span>
                    <span class="me-2">|</span>
                    <span><i class="bi bi-envelope me-1"></i>Email liên hệ: <strong class="text-dark">{{ $student->email }}</strong></span>
                </p>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-light border text-dark d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#resetModal">
                <i class="bi bi-key"></i>
                <span>Đặt lại mật khẩu</span>
            </button>
            <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-dark d-inline-flex align-items-center gap-1.5 px-3">
                <i class="bi bi-pencil"></i>
                <span>Sửa thông tin</span>
            </a>
            <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-light border text-dark d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-arrow-left"></i>
                <span>Quay lại</span>
            </a>
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

    <div class="row g-4">
        <!-- Cột 1: Thông tin học vấn & Thông tin cá nhân -->
        <div class="col-lg-6">
            <div class="card border shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <strong class="text-dark">Hồ sơ đào tạo & Học vấn</strong>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless table-sm mb-0 small">
                        <tbody>
                            <tr>
                                <td class="text-secondary" style="width: 180px;">MSSV (Tài khoản đăng nhập):</td>
                                <td><span class="font-monospace fw-bold text-dark">{{ $student->student_code }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Chuyên ngành đào tạo:</td>
                                <td><strong class="text-dark">{{ $student->specialization?->name ?? $student->specialization_name }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Khoa quản lý:</td>
                                <td class="text-dark">{{ $student->faculty_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Ngành đào tạo:</td>
                                <td class="text-dark">{{ $student->major_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Lớp danh nghĩa:</td>
                                <td><span class="badge bg-light text-dark border">{{ $student->class_code }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Bậc đào tạo:</td>
                                <td class="text-dark">{{ $student->education_level }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Loại hình đào tạo:</td>
                                <td class="text-dark">{{ $student->training_type }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Trạng thái học tập:</td>
                                <td><span class="badge bg-light text-dark border">{{ $student->study_status }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Khóa học:</td>
                                <td class="text-dark">Khóa {{ $student->cohort_year }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Ngày nhập học:</td>
                                <td class="text-dark">{{ $student->enrollment_date?->format('d/m/Y') ?? 'Chưa cập nhật' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <strong class="text-dark">Thông tin cá nhân & Liên hệ</strong>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless table-sm mb-0 small">
                        <tbody>
                            <tr>
                                <td class="text-secondary" style="width: 180px;">Họ và tên:</td>
                                <td><strong class="text-dark">{{ $student->full_name }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Giới tính:</td>
                                <td class="text-dark">{{ $student->gender }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Ngày sinh:</td>
                                <td class="text-dark">{{ $student->dob?->format('d/m/Y') ?? 'Chưa cập nhật' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Số CCCD / Định danh:</td>
                                <td><span class="font-monospace text-dark">{{ $student->cccd_number ?? 'Chưa cập nhật' }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Email liên hệ:</td>
                                <td><a href="mailto:{{ $student->email }}" class="text-dark font-monospace text-decoration-none">{{ $student->email }}</a></td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Số điện thoại:</td>
                                <td class="text-dark">{{ $student->phone ?? 'Chưa cập nhật' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Dân tộc / Tôn giáo:</td>
                                <td class="text-dark">{{ $student->ethnicity ?? 'Kinh' }} / {{ $student->religion ?? 'Không' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Quốc tịch:</td>
                                <td class="text-dark">{{ $student->nationality ?? 'Việt Nam' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Địa chỉ liên hệ:</td>
                                <td class="text-dark">{{ $student->contact_address ?? 'Chưa cập nhật' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cột 2: Danh sách các học phần ghi danh & Điểm số -->
        <div class="col-lg-6">
            <div class="card border shadow-sm rounded-3 bg-white h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <strong class="text-dark">Các học phần đã ghi danh ({{ $student->enrollments->count() }})</strong>
                    <a href="{{ route('admin.grades.index', ['student_id' => $student->id]) }}" class="btn btn-sm btn-light border text-dark">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Xem bảng điểm
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light text-secondary small">
                                <tr>
                                    <th class="ps-3" style="width: 90px;">Mã HP</th>
                                    <th>Tên học phần</th>
                                    <th>Giảng viên</th>
                                    <th class="text-center" style="width: 60px;">ĐTK</th>
                                    <th class="text-center" style="width: 60px;">Chữ</th>
                                    <th class="text-center pe-3" style="width: 75px;">Kết quả</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($student->enrollments as $enr)
                                    @php
                                        $course = $enr->section?->course;
                                    @endphp
                                    <tr>
                                        <td class="ps-3 font-monospace">
                                            <span class="badge bg-light text-dark border">{{ $course?->course_code }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $course?->course_name }}</div>
                                            <div class="text-muted small">Lớp: {{ $enr->section?->section_code }} ({{ $course?->credits }} TC)</div>
                                        </td>
                                        <td>
                                            <span class="small text-secondary">{{ $enr->section?->lecturer?->full_name ?? 'Chưa phân công' }}</span>
                                        </td>
                                        <td class="text-center fw-semibold text-dark">
                                            {{ $enr->total_score_10 !== null ? number_format($enr->total_score_10, 1) : '-' }}
                                        </td>
                                        <td class="text-center">
                                            @if ($enr->letter_grade)
                                                <span class="badge bg-light text-dark border">
                                                    {{ $enr->letter_grade }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center pe-3">
                                            @if ($enr->letter_grade !== null)
                                                @if ($enr->isPassed())
                                                    <span class="badge bg-light text-dark border">
                                                        Đạt
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-dark border">
                                                        Rớt
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge bg-light text-muted border">Đang học</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted small">
                                            Sinh viên chưa có học phần nào được ghi danh.
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

<!-- Modal Reset Mật Khẩu -->
<div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border shadow-sm rounded-3">
            <form method="POST" action="{{ route('admin.students.resetPassword', $student) }}">
                @csrf
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark fs-6">
                        Đặt lại mật khẩu sinh viên
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-3 p-2.5">
                        <span class="text-secondary small d-block mb-1">Sinh viên được chọn:</span>
                        <strong class="text-dark fs-6">{{ $student->full_name }}</strong> 
                        <span class="badge bg-light text-dark border font-monospace ms-1">{{ $student->student_code }}</span>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label small fw-semibold text-secondary">Mật khẩu mới</label>
                        <input type="text" class="form-control form-control-sm font-monospace" id="new_password" name="new_password" value="12345678" required>
                        <div class="form-text small">Mặc định: <code>12345678</code>. Bạn có thể thay đổi trước khi xác nhận.</div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5 bg-light">
                    <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-sm btn-dark px-3">
                        Xác nhận đặt lại
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
