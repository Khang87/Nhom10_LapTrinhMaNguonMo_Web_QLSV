@extends('layouts.spark')

@section('title', 'Chỉnh sửa Giảng viên: ' . $lecturer->full_name)

@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Chỉnh sửa Giảng viên</h1>
            <p class="text-secondary small mb-0">Cập nhật hồ sơ giảng viên <strong>{{ $lecturer->full_name }}</strong> ({{ $lecturer->lecturer_code }})</p>
        </div>
        <a href="{{ route('admin.lecturers.index') }}" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-arrow-left"></i>
            <span>Quay lại</span>
        </a>
    </div>

    <!-- Form Card -->
    <div class="card border shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.lecturers.update', $lecturer) }}">
                @csrf
                @method('PUT')

                @if ($lecturer->headedFaculty)
                    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2" role="alert">
                        <i class="bi bi-star-fill text-warning"></i>
                        <div class="small">
                            <strong>Lưu ý:</strong> Giảng viên này đang giữ chức vụ <strong>Trưởng khoa {{ $lecturer->headedFaculty->name }}</strong>. 
                            Nếu bạn đổi sang khoa khác, chức danh Trưởng khoa tại {{ $lecturer->headedFaculty->name }} sẽ được tự động giải phóng.
                        </div>
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="lecturer_code" class="form-label small fw-semibold">
                            Mã giảng viên <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control font-monospace text-uppercase @error('lecturer_code') is-invalid @enderror" 
                               id="lecturer_code" name="lecturer_code" value="{{ old('lecturer_code', $lecturer->lecturer_code) }}" required maxlength="20">
                        @error('lecturer_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8">
                        <label for="full_name" class="form-label small fw-semibold">
                            Họ và tên giảng viên <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('full_name') is-invalid @enderror" 
                               id="full_name" name="full_name" value="{{ old('full_name', $lecturer->full_name) }}" required maxlength="100">
                        @error('full_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="degree" class="form-label small fw-semibold">
                            Học vị / Học hàm <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('degree') is-invalid @enderror" id="degree" name="degree" required>
                            @php $deg = old('degree', $lecturer->degree); @endphp
                            <option value="Thạc sĩ" {{ $deg == 'Thạc sĩ' ? 'selected' : '' }}>Thạc sĩ (ThS)</option>
                            <option value="Tiến sĩ" {{ $deg == 'Tiến sĩ' ? 'selected' : '' }}>Tiến sĩ (TS)</option>
                            <option value="PGS.TS" {{ $deg == 'PGS.TS' ? 'selected' : '' }}>PGS.TS</option>
                            <option value="GS.TS" {{ $deg == 'GS.TS' ? 'selected' : '' }}>GS.TS</option>
                            <option value="Kỹ sư" {{ $deg == 'Kỹ sư' ? 'selected' : '' }}>Kỹ sư</option>
                            <option value="Cử nhân" {{ $deg == 'Cử nhân' ? 'selected' : '' }}>Cử nhân</option>
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
                                <option value="{{ $fac->id }}" {{ old('faculty_id', $lecturer->faculty_id) == $fac->id ? 'selected' : '' }}>
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
                                <option value="{{ $spec->id }}" {{ old('specialization_id', $lecturer->specialization_id) == $spec->id ? 'selected' : '' }}>
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
                               id="email" name="email" value="{{ old('email', $lecturer->email) }}" required maxlength="100">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label small fw-semibold">
                            Số điện thoại liên hệ
                        </label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" name="phone" value="{{ old('phone', $lecturer->phone) }}" maxlength="20">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="password" class="form-label small fw-semibold">
                            Đổi mật khẩu mới
                        </label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" placeholder="Để trống nếu muốn giữ nguyên mật khẩu cũ">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @php
                        $currentClassIds = old('homeroom_class_ids', $lecturer->homeroomClasses->pluck('id')->toArray());
                    @endphp
                    <div class="col-12">
                        <label class="form-label small fw-semibold d-flex align-items-center justify-content-between">
                            <span>Phân công Lớp danh nghĩa (Cố vấn học tập - CVHT)</span>
                            <span class="text-muted fw-normal" style="font-size: 0.8rem;">Có thể chọn nhiều lớp</span>
                        </label>
                        <div class="card bg-light border p-3 rounded-3" style="max-height: 260px; overflow-y: auto;">
                            @if($studentClasses->isEmpty())
                                <p class="text-muted small mb-0">Chưa có lớp sinh hoạt nào trong hệ thống.</p>
                            @else
                                <div class="row g-2">
                                    @foreach($studentClasses as $cls)
                                        @php
                                            $isAssignedToCurrent = in_array($cls->id, $currentClassIds);
                                            $isAssignedToOther = $cls->lecturer_id && $cls->lecturer_id != $lecturer->id;
                                        @endphp
                                        <div class="col-md-6">
                                            <div class="form-check p-2 bg-white rounded border {{ $isAssignedToCurrent ? 'border-primary bg-primary-subtle' : 'border-light-subtle' }} h-100">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" 
                                                       name="homeroom_class_ids[]" value="{{ $cls->id }}" 
                                                       id="class_check_{{ $cls->id }}"
                                                       {{ $isAssignedToCurrent ? 'checked' : '' }}>
                                                <label class="form-check-label small d-block" for="class_check_{{ $cls->id }}">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <span class="fw-bold font-monospace text-dark">{{ $cls->class_code }}</span>
                                                        @if($isAssignedToCurrent)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">
                                                                <i class="bi bi-check2"></i> Đang phụ trách
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-secondary text-truncate" style="font-size: 0.8rem;">{{ $cls->class_name }}</div>
                                                    @if($cls->specialization)
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            <i class="bi bi-tag me-1"></i>{{ $cls->specialization->name }}
                                                        </div>
                                                    @endif
                                                    @if($isAssignedToOther)
                                                        <div class="text-warning-emphasis" style="font-size: 0.75rem;">
                                                            <i class="bi bi-info-circle me-1"></i>Đang do: {{ $cls->lecturer?->full_name }} phụ trách
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
                    <a href="{{ route('admin.lecturers.index') }}" class="btn btn-sm btn-light border px-3 py-1.5">
                        Hủy
                    </a>
                    <button type="submit" class="btn btn-sm btn-dark px-3 py-1.5">
                        Cập nhật hồ sơ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
