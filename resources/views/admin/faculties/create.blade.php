@extends('layouts.spark')

@section('title', 'Thêm Khoa mới')

@section('content')
<div class="container-fluid px-0" style="max-width: 780px;">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Thêm Khoa mới</h1>
            <p class="text-secondary small mb-0">Thiết lập thông tin khoa đào tạo và bổ nhiệm trưởng khoa</p>
        </div>
        <a href="{{ route('admin.faculties.index') }}" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-arrow-left"></i>
            <span>Quay lại</span>
        </a>
    </div>

    <!-- Form Card -->
    <div class="card border shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.faculties.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="code" class="form-label small fw-semibold">
                            Mã khoa <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control font-monospace text-uppercase @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code') }}" 
                               placeholder="VD: CNTT, QTKD..." required autofocus maxlength="20">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8">
                        <label for="name" class="form-label small fw-semibold">
                            Tên khoa đào tạo <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" 
                               placeholder="VD: Khoa Công nghệ Thông tin" required maxlength="150">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="dean_id" class="form-label small fw-semibold">
                            Trưởng khoa phụ trách
                        </label>
                        <select class="form-select @error('dean_id') is-invalid @enderror" id="dean_id" name="dean_id">
                            <option value="">-- Chưa bổ nhiệm / Bổ nhiệm sau --</option>
                            @php
                                $assignedLecs = $lecturers->filter(fn($l) => !empty($l->faculty_id));
                                $unassignedLecs = $lecturers->filter(fn($l) => empty($l->faculty_id));
                            @endphp

                            @if($unassignedLecs->isNotEmpty())
                                <optgroup label="── Giảng viên chưa phân khoa ──">
                                    @foreach ($unassignedLecs as $lec)
                                        <option value="{{ $lec->id }}" {{ old('dean_id') == $lec->id ? 'selected' : '' }}>
                                            [{{ $lec->lecturer_code }}] {{ $lec->degree }} {{ $lec->full_name }} — Chưa phân khoa
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif

                            @if($assignedLecs->isNotEmpty())
                                <optgroup label="── Giảng viên các khoa khác (Sẽ điều chuyển về khoa này khi tạo) ──">
                                    @foreach ($assignedLecs as $lec)
                                        @php
                                            $isDeanElsewhere = $lec->headedFaculty;
                                        @endphp
                                        <option value="{{ $lec->id }}" {{ old('dean_id') == $lec->id ? 'selected' : '' }}>
                                            [{{ $lec->lecturer_code }}] {{ $lec->degree }} {{ $lec->full_name }} — [{{ $lec->faculty->name ?? 'Khoa khác' }}]
                                            {{ $isDeanElsewhere ? ' (Trưởng khoa ' . $isDeanElsewhere->name . ' - sẽ chuyển giao)' : '' }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        @error('dean_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text small">Chọn giảng viên đảm nhiệm chức danh Trưởng khoa (có thể bổ nhiệm sau).</div>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label small fw-semibold">
                            Email liên hệ khoa
                        </label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" 
                               placeholder="VD: cntt@huit.edu.vn" maxlength="100">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label small fw-semibold">
                            Số điện thoại văn phòng
                        </label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" name="phone" value="{{ old('phone') }}" 
                               placeholder="VD: 028.38161673" maxlength="20">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="office_location" class="form-label small fw-semibold">
                            Địa chỉ văn phòng khoa
                        </label>
                        <input type="text" class="form-control @error('office_location') is-invalid @enderror" 
                               id="office_location" name="office_location" value="{{ old('office_location') }}" 
                               placeholder="VD: Phòng B.302, Tòa nhà B, Cơ sở chính" maxlength="150">
                        @error('office_location')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label small fw-semibold">
                            Mô tả / Giới thiệu khoa
                        </label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3" 
                                  placeholder="Mô tả định hướng đào tạo, ngành mũi nhọn của khoa...">{{ old('description') }}</textarea>
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
                        Lưu thông tin khoa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
