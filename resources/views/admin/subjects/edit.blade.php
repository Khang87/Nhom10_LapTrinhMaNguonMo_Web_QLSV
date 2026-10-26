@extends('layouts.spark')
@section('title', 'Sửa môn học')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Sửa môn học: {{ $subject->code }}</h1>
        <a class="btn btn-outline-secondary" href="{{ route('admin.subjects.index') }}">Quay lại</a>
    </div>
    <form method="POST" action="{{ route('admin.subjects.update', $subject) }}">
        @csrf @method('PUT')
        @include('admin.subjects.form')

        <div class="row">
            <div class="col-lg-6 mb-3">
                <label class="form-label" for="prerequisite_ids">Môn tiên quyết</label>
                <select class="form-select" id="prerequisite_ids" name="prerequisite_ids[]" multiple size="6">
                    @foreach ($otherSubjects as $option)
                        <option value="{{ $option->id }}" @selected($subject->prerequisites->contains($option->id))>
                            {{ $option->code }} - {{ $option->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Giữ Ctrl (hoặc Cmd) để chọn nhiều môn. Hệ thống chặn xếp tiên quyết tạo vòng lặp.</div>
            </div>
            <div class="col-lg-6 mb-3">
                <label class="form-label" for="concurrent_ids">Môn song hành</label>
                <select class="form-select" id="concurrent_ids" name="concurrent_ids[]" multiple size="6">
                    @foreach ($otherSubjects as $option)
                        <option value="{{ $option->id }}" @selected($subject->concurrents->contains($option->id))>
                            {{ $option->code }} - {{ $option->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">
                    @if ($subject->concurrentOf->isNotEmpty())
                        Đang được khai báo ở:
                        {{ $subject->concurrentOf->map(fn ($option) => $option->code)->implode(', ') }}
                    @else
                        Quan hệ song hành đọc được ở cả hai chiều dù chỉ khai báo một lần.
                    @endif
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit">
            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật
        </button>
    </form>
@endsection
