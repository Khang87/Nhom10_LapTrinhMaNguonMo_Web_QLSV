@extends('layouts.spark')

@section('title', 'Thêm sinh viên mới')

@section('content')
<div class="container-fluid px-0" style="max-width: 900px;">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Thêm Sinh viên mới</h1>
        </div>
        <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-light border text-dark d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-arrow-left"></i>
            <span>DS Sinh viên</span>
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-light border alert-dismissible fade show mb-4" role="alert">
            <div class="fw-semibold text-dark mb-1 small">
                <i class="bi bi-exclamation-triangle me-1"></i> Có lỗi khi nhập dữ liệu:
            </div>
            <ul class="mb-0 small text-danger ps-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border shadow-sm rounded-3 bg-white">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.students.store') }}">
                @csrf

        
                <div class="border-bottom pb-2 mb-3">
                    <strong class="text-dark small text-uppercase">1. Tài khoản đăng nhập & Chuyên ngành đào tạo</strong>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="student_code" class="form-label small fw-semibold text-secondary">
                            Mã số sinh viên  <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm font-monospace @error('student_code') is-invalid @enderror" 
                               id="student_code" name="student_code" value="{{ old('student_code', $autoStudentCode) }}" required>
                        <div class="form-text small">Sinh viên dùng MSSV này để đăng nhập vào hệ thống.</div>
                        @error('student_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label small fw-semibold text-secondary">
                            Email của sinh viên  <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control form-control-sm @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" placeholder="VD: sinhvien@gmail.com" required>
                        <div class="form-text small">Email cá nhân để Admin / Nhà trường liên hệ với sinh viên.</div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="password" class="form-label small fw-semibold text-secondary">Mật khẩu khởi tạo</label>
                        <input type="text" class="form-control form-control-sm font-monospace" 
                               id="password" name="password" value="{{ old('password', '12345678') }}" placeholder="Mặc định: 12345678">
                        <div class="form-text small">Mặc định: <code>12345678</code></div>
                    </div>

                    <div class="col-md-4">
                        <label for="specialization_id" class="form-label small fw-semibold text-secondary">
                            Chuyên ngành đào tạo <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm @error('specialization_id') is-invalid @enderror" 
                                id="specialization_id" name="specialization_id" required>
                            <option value="">-- Chọn chuyên ngành --</option>
                            @foreach ($specializations as $spec)
                                <option value="{{ $spec->id }}" {{ old('specialization_id') == $spec->id ? 'selected' : '' }}>
                                    [{{ $spec->code }}] {{ $spec->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('specialization_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="class_code" class="form-label small fw-semibold text-secondary">Lớp danh nghĩa</label>
                        <select class="form-select form-select-sm @error('class_code') is-invalid @enderror" id="class_code" name="class_code">
                            <option value="">-- Chưa xếp lớp danh nghĩa --</option>
                            @foreach ($classes->groupBy('cohort_year') as $cohortYear => $classList)
                                <optgroup label="Khóa tuyển sinh {{ $cohortYear ?: 'Chung' }}">
                                    @foreach ($classList as $c)
                                        <option value="{{ $c->id }}" {{ old('class_code') == $c->id || old('class_code') == $c->class_code ? 'selected' : '' }}>
                                            {{ $c->class_code }} - {{ $c->class_name }} (Khóa {{ $c->cohort_year }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="border-bottom pb-2 mb-3">
                    <strong class="text-dark small text-uppercase">2. Thông tin cá nhân sinh viên</strong>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="full_name" class="form-label small fw-semibold text-secondary">
                            Họ và tên sinh viên <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm @error('full_name') is-invalid @enderror" 
                               id="full_name" name="full_name" value="{{ old('full_name') }}" placeholder="VD: Nguyễn Văn An" required>
                        @error('full_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="gender" class="form-label small fw-semibold text-secondary">
                            Giới tính <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                            <option value="Nam" {{ old('gender') === 'Nam' ? 'selected' : '' }}>Nam</option>
                            <option value="Nữ" {{ old('gender') === 'Nữ' ? 'selected' : '' }}>Nữ</option>
                            <option value="Khác" {{ old('gender') === 'Khác' ? 'selected' : '' }}>Khác</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="dob" class="form-label small fw-semibold text-secondary">Ngày sinh</label>
                        <input type="date" class="form-control form-control-sm @error('dob') is-invalid @enderror" 
                               id="dob" name="dob" value="{{ old('dob', '2005-01-15') }}">
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label small fw-semibold text-secondary">Số điện thoại liên hệ</label>
                        <input type="text" class="form-control form-control-sm @error('phone') is-invalid @enderror" 
                               id="phone" name="phone" value="{{ old('phone') }}" placeholder="VD: 0912345678">
                    </div>

                    <div class="col-md-6">
                        <label for="cccd_number" class="form-label small fw-semibold text-secondary">Số CCCD / Định danh</label>
                        <input type="text" class="form-control form-control-sm @error('cccd_number') is-invalid @enderror" 
                               id="cccd_number" name="cccd_number" value="{{ old('cccd_number') }}" placeholder="VD: 079204001234">
                    </div>

                    <div class="col-12">
                        <label for="contact_address" class="form-label small fw-semibold text-secondary">Địa chỉ liên hệ</label>
                        <input type="text" class="form-control form-control-sm @error('contact_address') is-invalid @enderror" 
                               id="contact_address" name="contact_address" value="{{ old('contact_address') }}" 
                               placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-light border text-dark px-3">Hủy bỏ</a>
                    <button type="submit" class="btn btn-sm btn-dark px-4">
                        Tạo sinh viên & Xếp Kỳ 1
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
