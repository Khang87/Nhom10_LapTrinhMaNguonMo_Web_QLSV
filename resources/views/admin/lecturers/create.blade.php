@extends('layouts.spark')

@section('title', 'Thêm Giảng viên mới')

@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Thêm Giảng viên mới</h1>
            <p class="text-secondary small mb-0">Tạo hồ sơ giảng viên, phân công khoa và chuyên ngành phụ trách</p>
        </div>
        <a href="{{ $selectedFacultyId ? route('admin.faculties.show', $selectedFacultyId) : route('admin.lecturers.index') }}" 
           class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-arrow-left"></i>
            <span>Quay lại</span>
        </a>
    </div>

    <!-- Form Card -->
    <div class="card border shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.lecturers.store') }}">
                @csrf

                @if ($selectedFacultyId)
                    <input type="hidden" name="from_faculty" value="{{ $selectedFacultyId }}">
                @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="lecturer_code" class="form-label small fw-semibold">
                            Mã giảng viên
                        </label>
                        <input type="text" class="form-control font-monospace text-uppercase @error('lecturer_code') is-invalid @enderror" 
                               id="lecturer_code" name="lecturer_code" value="{{ old('lecturer_code', $nextCode) }}" 
                               placeholder="VD: GV026" maxlength="20">
                        @error('lecturer_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text small">Để trống để hệ thống tự cấp mã.</div>
                    </div>

                    <div class="col-md-8">
                        <label for="full_name" class="form-label small fw-semibold">
                            Họ và tên giảng viên <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('full_name') is-invalid @enderror" 
                               id="full_name" name="full_name" value="{{ old('full_name') }}" 
                               placeholder="VD: Nguyễn Văn A" required autofocus maxlength="100">
                        @error('full_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="degree" class="form-label small fw-semibold">
                            Học vị / Học hàm <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('degree') is-invalid @enderror" id="degree" name="degree" required>
                            <option value="Thạc sĩ" {{ old('degree') == 'Thạc sĩ' ? 'selected' : '' }}>Thạc sĩ (ThS)</option>
                            <option value="Tiến sĩ" {{ old('degree') == 'Tiến sĩ' ? 'selected' : '' }}>Tiến sĩ (TS)</option>
                            <option value="PGS.TS" {{ old('degree') == 'PGS.TS' ? 'selected' : '' }}>PGS.TS</option>
                            <option value="GS.TS" {{ old('degree') == 'GS.TS' ? 'selected' : '' }}>GS.TS</option>
                            <option value="Kỹ sư" {{ old('degree') == 'Kỹ sư' ? 'selected' : '' }}>Kỹ sư</option>
                            <option value="Cử nhân" {{ old('degree') == 'Cử nhân' ? 'selected' : '' }}>Cử nhân</option>
                        </select>
                        @error('degree')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="faculty_id" class="form-label small fw-semibold">
                            Khoa phụ trách <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('faculty_id') is-invalid @enderror" id="faculty_id" name="faculty_id" required>
                            <option value="">-- Chọn Khoa --</option>
                            @foreach ($faculties as $fac)
                                <option value="{{ $fac->id }}" {{ old('faculty_id', $selectedFacultyId) == $fac->id ? 'selected' : '' }}>
                                    [{{ $fac->code }}] {{ $fac->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('faculty_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="specialization_id" class="form-label small fw-semibold">
                            Chuyên ngành phụ trách
                        </label>
                        <select class="form-select @error('specialization_id') is-invalid @enderror" id="specialization_id" name="specialization_id">
                            <option value="">-- Chưa gán chuyên ngành --</option>
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

                    <div class="col-md-6">
                        <label for="email" class="form-label small fw-semibold">
                            Email công vụ (Tài khoản) <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" 
                               placeholder="VD: nguyenvana@huit.edu.vn" required maxlength="100">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text small">Email dùng để đăng nhập vào cổng Giảng viên.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label small fw-semibold">
                            Số điện thoại liên hệ
                        </label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" name="phone" value="{{ old('phone') }}" 
                               placeholder="VD: 0912345678" maxlength="20">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="password" class="form-label small fw-semibold">
                            Mật khẩu khởi tạo
                        </label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" placeholder="Mặc định: 12345678 nếu để trống">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold d-flex align-items-center justify-content-between">
                            <span>Phân công Lớp danh nghĩa (Cố vấn học tập - CVHT)</span>
                            <span class="text-muted fw-normal" style="font-size: 0.8rem;">Có thể chọn nhiều lớp</span>
                        </label>
                        <div class="card bg-light border p-3 rounded-3" style="max-height: 240px; overflow-y: auto;">
                            @if($studentClasses->isEmpty())
                                <p class="text-muted small mb-0">Chưa có lớp sinh hoạt nào trong hệ thống.</p>
                            @else
                                <div class="row g-2">
                                    @foreach($studentClasses as $cls)
                                        <div class="col-md-6">
                                            <div class="form-check p-2 bg-white rounded border border-light-subtle h-100">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" 
                                                       name="homeroom_class_ids[]" value="{{ $cls->id }}" 
                                                       id="class_check_{{ $cls->id }}"
                                                       {{ in_array($cls->id, old('homeroom_class_ids', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label small d-block" for="class_check_{{ $cls->id }}">
                                                    <div class="fw-bold font-monospace text-dark">{{ $cls->class_code }}</div>
                                                    <div class="text-secondary text-truncate" style="font-size: 0.8rem;">{{ $cls->class_name }}</div>
                                                    @if($cls->specialization)
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            <i class="bi bi-tag me-1"></i>{{ $cls->specialization->name }}
                                                        </div>
                                                    @endif
                                                    @if($cls->lecturer)
                                                        <div class="text-warning-emphasis" style="font-size: 0.75rem;">
                                                            <i class="bi bi-info-circle me-1"></i>Đang do: {{ $cls->lecturer->full_name }} phụ trách
                                                        </div>
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="form-text small">Giảng viên được chọn sẽ có quyền quản lý, theo dõi tình hình học tập của lớp sinh hoạt này tại phân hệ <strong>Cố vấn học tập (CVHT)</strong>.</div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ $selectedFacultyId ? route('admin.faculties.show', $selectedFacultyId) : route('admin.lecturers.index') }}" 
                       class="btn btn-sm btn-light border px-3 py-1.5">
                        Hủy
                    </a>
                    <button type="submit" class="btn btn-sm btn-dark px-3 py-1.5">
                        Lưu hồ sơ giảng viên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
