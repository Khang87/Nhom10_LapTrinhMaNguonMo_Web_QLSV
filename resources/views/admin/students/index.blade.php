@extends('layouts.spark')

@section('title', 'Quản lý Sinh viên')

@section('content')
<div class="container-fluid px-0">
    <!-- Header: Tối giản, đồng bộ với các phân hệ khác -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Quản lý Sinh viên</h1>
            <p class="text-secondary small mb-0">Quản lý hồ sơ sinh viên, tài khoản MSSV đăng nhập và email liên hệ</p>
        </div>
        <div>
            <a href="{{ route('admin.students.create') }}" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-2">
                <i class="bi bi-person-plus"></i>
                <span>Thêm sinh viên mới</span>
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

    @if (session('error'))
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-exclamation-circle text-dark"></i>
            <div class="text-dark small">{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Bộ lọc & Tìm kiếm -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.students.index') }}" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-secondary"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0" 
                               placeholder="Tìm theo MSSV (Tài khoản), Họ tên, Email liên hệ, SĐT..." value="{{ $search }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="specialization_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Tất cả Chuyên ngành --</option>
                        @foreach ($specializations as $spec)
                            <option value="{{ $spec->id }}" {{ $specId == $spec->id ? 'selected' : '' }}>
                                [{{ $spec->code }}] {{ $spec->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark btn-sm flex-grow-1">Tìm kiếm</button>
                    @if ($search || $specId)
                        <a href="{{ route('admin.students.index') }}" class="btn btn-light btn-sm border text-dark" title="Đặt lại bộ lọc">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Bảng danh sách sinh viên -->
    <div class="card border shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <strong class="text-dark">Danh sách sinh viên ({{ $students->total() }})</strong>
            <span class="text-secondary small">Tài khoản đăng nhập là MSSV, email dùng để liên hệ</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="ps-3 text-center" style="width: 50px;">#</th>
                        <th style="width: 150px;">MSSV (Tài khoản)</th>
                        <th>Họ và tên</th>
                        <th>Chuyên ngành</th>
                        <th style="width: 110px;">Lớp</th>
                        <th class="text-center" style="width: 80px;">Giới tính</th>
                        <th>Email liên hệ</th>
                        <th class="text-center" style="width: 120px;">Trạng thái</th>
                        <th class="text-center pe-3" style="width: 180px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $index => $stu)
                        <tr>
                            <td class="ps-3 text-center text-muted small">
                                {{ $students->firstItem() + $index }}
                            </td>
                            <td>
                                <a href="{{ route('admin.students.show', $stu) }}" class="badge bg-light text-dark border font-monospace text-decoration-none">
                                    {{ $stu->student_code }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('admin.students.show', $stu) }}" class="fw-semibold text-dark text-decoration-none d-block">
                                    {{ $stu->full_name }}
                                </a>
                                <span class="text-secondary small">{{ $stu->phone ?? 'Chưa có SĐT' }}</span>
                            </td>
                            <td>
                                <span class="text-dark small">
                                    {{ $stu->specialization?->name ?? $stu->specialization_name ?? 'Chưa gán' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $stu->class_code }}</span>
                            </td>
                            <td class="text-center">
                                <span class="small text-secondary">{{ $stu->gender }}</span>
                            </td>
                            <td>
                                <span class="small text-dark font-monospace">{{ $stu->email }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">
                                    {{ $stu->study_status }}
                                </span>
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.students.show', $stu) }}" class="btn btn-sm btn-light border text-dark" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-light border text-dark" 
                                            onclick="openResetModal({{ $stu->id }}, '{{ addslashes($stu->full_name) }}', '{{ $stu->student_code }}')" 
                                            title="Reset mật khẩu">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <a href="{{ route('admin.students.edit', $stu) }}" class="btn btn-sm btn-light border text-dark" title="Chỉnh sửa">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.students.destroy', $stu) }}" class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa sinh viên [{{ $stu->full_name }}] không?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-dark" title="Xóa sinh viên">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted small">
                                <i class="bi bi-people fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Không tìm thấy sinh viên nào phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($students->hasPages())
            <div class="card-footer bg-white py-2.5 border-top">
                {{ $students->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Reset Mật Khẩu (Thiết kế phẳng, tối giản) -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border shadow-sm rounded-3">
            <form id="resetPasswordForm" method="POST" action="">
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
                        <strong class="text-dark fs-6" id="resetStudentName"></strong> 
                        <span class="badge bg-light text-dark border font-monospace ms-1" id="resetStudentCode"></span>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label small fw-semibold text-secondary">Mật khẩu mới</label>
                        <input type="text" class="form-control form-control-sm font-monospace" id="new_password" name="new_password" value="12345678" required>
                        <div class="form-text small">Mặc định: <code>12345678</code>. Bạn có thể thay đổi trước khi xác nhận.</div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5 bg-light">
                    <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-dark px-3">
                        Xác nhận đặt lại
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openResetModal(studentId, studentName, studentCode) {
    document.getElementById('resetStudentName').innerText = studentName;
    document.getElementById('resetStudentCode').innerText = studentCode;
    document.getElementById('new_password').value = '12345678';
    document.getElementById('resetPasswordForm').action = '/admin/students/' + studentId + '/reset-password';
    const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
}
</script>
@endsection
