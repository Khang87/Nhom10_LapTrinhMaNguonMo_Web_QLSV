@extends('layouts.spark')

@section('title', 'Thêm Chuyên ngành mới')

@section('content')
<div class="container-fluid px-0" style="max-width: 760px;">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Thêm Chuyên ngành mới</h1>
            <p class="text-secondary small mb-0">Thiết lập thông tin mã, tên chuyên ngành và khoa phụ trách</p>
        </div>
        <a href="{{ route('admin.specializations.index') }}" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-arrow-left"></i>
            <span>Quay lại</span>
        </a>
    </div>

    <!-- Form Card -->
    <div class="card border shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.specializations.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="code" class="form-label small fw-semibold">
                            Mã chuyên ngành <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control font-monospace text-uppercase @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code') }}" 
                               placeholder="VD: CNPM, HTTT..." required autofocus maxlength="20">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8">
                        <label for="name" class="form-label small fw-semibold">
                            Tên chuyên ngành <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" 
                               placeholder="VD: Công nghệ phần mềm" required maxlength="100">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="faculty_id" class="form-label small fw-semibold">
                            Khoa phụ trách <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('faculty_id') is-invalid @enderror" id="faculty_id" name="faculty_id" required>
                            <option value="">-- Chọn Khoa phụ trách --</option>
                            @foreach ($faculties as $fac)
                                <option value="{{ $fac->id }}" {{ old('faculty_id', request('faculty_id')) == $fac->id ? 'selected' : '' }}>
                                    [{{ $fac->code }}] {{ $fac->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('faculty_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label small fw-semibold">
                            Mô tả định hướng đào tạo
                        </label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3" 
                                  placeholder="Mô tả mục tiêu đào tạo của chuyên ngành...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('admin.specializations.index') }}" class="btn btn-sm btn-light border px-3 py-1.5">
                        Hủy
                    </a>
                    <button type="submit" class="btn btn-sm btn-dark px-3 py-1.5">
                        Lưu chuyên ngành
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
