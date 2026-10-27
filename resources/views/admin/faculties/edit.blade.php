@extends('layouts.spark')

@section('title', 'Chỉnh sửa Khoa: ' . $faculty->name)

@section('content')
<div class="container-fluid px-0" style="max-width: 780px;">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Chỉnh sửa Khoa</h1>
            <p class="text-secondary small mb-0">Cập nhật thông tin khoa <strong>{{ $faculty->name }}</strong> và phân công trưởng khoa</p>
        </div>
        <a href="{{ route('admin.faculties.index') }}" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-arrow-left"></i>
            <span>Quay lại</span>
        </a>
    </div>

    <!-- Form Card -->
    <div class="card border shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.faculties.update', $faculty) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="code" class="form-label small fw-semibold">
                            Mã khoa <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control font-monospace text-uppercase @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code', $faculty->code) }}" required maxlength="20">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8">
                        <label for="name" class="form-label small fw-semibold">
                            Tên khoa đào tạo <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $faculty->name) }}" required maxlength="150">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="dean_id" class="form-label small fw-semibold">
                            Trưởng khoa phụ trách
                        </label>
                        <select class="form-select @error('dean_id') is-invalid @enderror" id="dean_id" name="dean_id">
                            <option value="">-- Chưa bổ nhiệm / Bỏ trống chức danh --</option>
                            
                            @php
                                $currentFacultyLecs = $lecturers->filter(fn($l) => $l->faculty_id == $faculty->id);
                                $otherFacultyLecs = $lecturers->filter(fn($l) => $l->faculty_id && $l->faculty_id != $faculty->id);
                                $unassignedLecs = $lecturers->filter(fn($l) => !$l->faculty_id);
                            @endphp

                            @if($currentFacultyLecs->isNotEmpty())
                                <optgroup label="── Giảng viên đang thuộc khoa {{ $faculty->name }} ──">
                                    @foreach ($currentFacultyLecs as $lec)
                                        <option value="{{ $lec->id }}" {{ old('dean_id', $faculty->dean_id) == $lec->id ? 'selected' : '' }}>
                                            [{{ $lec->lecturer_code }}] {{ $lec->degree }} {{ $lec->full_name }}
                                            {{ $faculty->dean_id == $lec->id ? ' ★ (Đang giữ chức Trưởng khoa)' : '' }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif

                            @if($otherFacultyLecs->isNotEmpty())
                                <optgroup label="── Giảng viên thuộc khoa khác (Sẽ tự động điều chuyển về khoa này khi chọn) ──">
                                    @foreach ($otherFacultyLecs as $lec)
                                        @php
                                            $isDeanElsewhere = $lec->headedFaculty && $lec->headedFaculty->id != $faculty->id;
                                        @endphp
                                        <option value="{{ $lec->id }}" {{ old('dean_id', $faculty->dean_id) == $lec->id ? 'selected' : '' }}>
                                            [{{ $lec->lecturer_code }}] {{ $lec->degree }} {{ $lec->full_name }} — [{{ $lec->faculty->name ?? 'Khoa khác' }}]
                                            {{ $isDeanElsewhere ? ' (Trưởng khoa ' . $lec->headedFaculty->name . ' - sẽ chuyển giao)' : '' }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif

                            @if($unassignedLecs->isNotEmpty())
                                <optgroup label="── Giảng viên chưa phân khoa ──">
                                    @foreach ($unassignedLecs as $lec)
                                        <option value="{{ $lec->id }}" {{ old('dean_id', $faculty->dean_id) == $lec->id ? 'selected' : '' }}>
                                            [{{ $lec->lecturer_code }}] {{ $lec->degree }} {{ $lec->full_name }} — Chưa phân khoa
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        @error('dean_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text small">
                            Có thể chọn giảng viên trong khoa hoặc điều chuyển bất kỳ giảng viên nào trong trường về làm Trưởng khoa.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label small fw-semibold">
                            Email liên hệ khoa
                        </label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email', $faculty->email) }}" maxlength="100">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label small fw-semibold">
                            Số điện thoại văn phòng
                        </label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" name="phone" value="{{ old('phone', $faculty->phone) }}" maxlength="20">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="office_location" class="form-label small fw-semibold">
                            Địa chỉ văn phòng khoa
                        </label>
                        <input type="text" class="form-control @error('office_location') is-invalid @enderror" 
                               id="office_location" name="office_location" value="{{ old('office_location', $faculty->office_location) }}" maxlength="150">
                        @error('office_location')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label small fw-semibold">
                            Mô tả / Giới thiệu khoa
                        </label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $faculty->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('admin.faculties.index') }}" class="btn btn-sm btn-light border px-3 py-1.5">
                        Hủy
                    </a>
                    <button type="submit" class="btn btn-sm btn-dark px-3 py-1.5">
                        Cập nhật thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
