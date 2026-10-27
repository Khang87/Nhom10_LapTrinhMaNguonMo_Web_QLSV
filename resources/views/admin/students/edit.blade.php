@extends('layouts.spark')

@section('title', 'Chỉnh sửa sinh viên: ' . $student->full_name)

@section('content')
<div class="container-fluid px-0" style="max-width: 900px;">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Chỉnh sửa hồ sơ sinh viên</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.students.show', $student) }}" class="btn btn-sm btn-light border text-dark d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-eye"></i> Xem chi tiết
            </a>
            <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-light border text-dark d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-arrow-left"></i> DS Sinh viên
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-light border alert-dismissible fade show mb-4" role="alert">
            <div class="fw-semibold text-dark mb-1 small">
                <i class="bi bi-exclamation-triangle me-1"></i> Có lỗi khi cập nhật:
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
            <form method="POST" action="{{ route('admin.students.update', $student) }}">
                @csrf
                @method('PUT')

                <div class="border-bottom pb-2 mb-3">
                    <strong class="text-dark small text-uppercase">1. Tài khoản đăng nhập & Chuyên ngành đào tạo</strong>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="student_code" class="form-label small fw-semibold text-secondary">
                            Mã số sinh viên  <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm font-monospace @error('student_code') is-invalid @enderror" 
                               id="student_code" name="student_code" value="{{ old('student_code', $student->student_code) }}" required>
                        <div class="form-text small">.</div>
                        @error('student_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label small fw-semibold text-secondary">
                            Email của sinh viên  <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control form-control-sm @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email', $student->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="specialization_id" class="form-label small fw-semibold text-secondary">
                            Chuyên ngành đào tạo <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm" id="specialization_id" name="specialization_id" required>
                            @foreach ($specializations as $spec)
                                <option value="{{ $spec->id }}" {{ old('specialization_id', $student->specialization_id) == $spec->id ? 'selected' : '' }}>
                                    [{{ $spec->code }}] {{ $spec->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="class_code" class="form-label small fw-semibold text-secondary">Lớp danh nghĩa</label>
                        <select class="form-select form-select-sm" id="class_code" name="class_code">
                            <option value="">-- Chưa xếp lớp danh nghĩa --</option>
                            @foreach ($classes->groupBy('cohort_year') as $cohortYear => $classList)
                                <optgroup label="Khóa tuyển sinh {{ $cohortYear ?: 'Chung' }}">
                                    @foreach ($classList as $c)
                                        @php
                                            $isSelected = (old('class_code') == $c->id)
                                                || (old('class_code') == $c->class_code && old('cohort_year', $student->cohort_year) == $c->cohort_year)
                                                || (!old('class_code') && $student->class_code == $c->class_code && $student->cohort_year == $c->cohort_year);
                                        @endphp
                                        <option value="{{ $c->id }}" {{ $isSelected ? 'selected' : '' }}>
                                            {{ $c->class_code }} - {{ $c->class_name }} (Khóa {{ $c->cohort_year }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="study_status" class="form-label small fw-semibold text-secondary">
                            Trạng thái học tập <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm" id="study_status" name="study_status" required>
                            <option value="Đang học" {{ old('study_status', $student->study_status) === 'Đang học' ? 'selected' : '' }}>Đang học</option>
                            <option value="Bảo lưu" {{ old('study_status', $student->study_status) === 'Bảo lưu' ? 'selected' : '' }}>Bảo lưu</option>
                            <option value="Đã tốt nghiệp" {{ old('study_status', $student->study_status) === 'Đã tốt nghiệp' ? 'selected' : '' }}>Đã tốt nghiệp</option>
                            <option value="Buộc thôi học" {{ old('study_status', $student->study_status) === 'Buộc thôi học' ? 'selected' : '' }}>Buộc thôi học</option>
                        </select>
                    </div>
                </div>

                <div class="border-bottom pb-2 mb-3">
                    <strong class="text-dark small text-uppercase">2. Thông tin cá nhân & Liên hệ</strong>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="full_name" class="form-label small fw-semibold text-secondary">
                            Họ và tên <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm @error('full_name') is-invalid @enderror" 
                               id="full_name" name="full_name" value="{{ old('full_name', $student->full_name) }}" required>
                        @error('full_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="gender" class="form-label small fw-semibold text-secondary">
                            Giới tính <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm" id="gender" name="gender" required>
                            <option value="Nam" {{ old('gender', $student->gender) === 'Nam' ? 'selected' : '' }}>Nam</option>
                            <option value="Nữ" {{ old('gender', $student->gender) === 'Nữ' ? 'selected' : '' }}>Nữ</option>
                            <option value="Khác" {{ old('gender', $student->gender) === 'Khác' ? 'selected' : '' }}>Khác</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="dob" class="form-label small fw-semibold text-secondary">Ngày sinh</label>
                        <input type="date" class="form-control form-control-sm" id="dob" name="dob" 
                               value="{{ old('dob', $student->dob?->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label small fw-semibold text-secondary">Số điện thoại</label>
                        <input type="text" class="form-control form-control-sm" id="phone" name="phone" 
                               value="{{ old('phone', $student->phone) }}" placeholder="VD: 0912345678">
                    </div>

                    <div class="col-md-6">
                        <label for="cccd_number" class="form-label small fw-semibold text-secondary">Số CCCD / Định danh</label>
                        <input type="text" class="form-control form-control-sm" id="cccd_number" name="cccd_number" 
                               value="{{ old('cccd_number', $student->cccd_number) }}">
                    </div>

                    <div class="col-12">
                        <label for="contact_address" class="form-label small fw-semibold text-secondary">Địa chỉ liên hệ</label>
                        <input type="text" class="form-control form-control-sm" id="contact_address" name="contact_address" 
                               value="{{ old('contact_address', $student->contact_address) }}">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-light border text-dark px-3">Hủy</a>
                    <button type="submit" class="btn btn-sm btn-dark px-4">
                        Lưu cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
