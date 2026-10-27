@extends('layouts.spark')
@section('title', 'Sửa môn học')
@section('content')
    <x-page-header title="Sửa môn học: {{ $subject->code }}">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.subjects.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form method="POST" action="{{ route('admin.subjects.update', $subject) }}">
            @csrf @method('PUT')
            @include('admin.subjects.form')

            <div class="row g-3 mt-3">
                <div class="col-lg-6">
                    <label class="form-label-custom" for="prerequisite_ids">Môn tiên quyết</label>
                    <select class="form-select form-select-custom" id="prerequisite_ids" name="prerequisite_ids[]"
                        multiple size="6">
                        @foreach ($otherSubjects as $option)
                            <option value="{{ $option->id }}" @selected($subject->prerequisites->contains($option->id))>
                                {{ $option->code }} - {{ $option->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="fs-xs text-muted-green mt-1">Giữ Ctrl (hoặc Cmd) để chọn nhiều môn. Hệ thống chặn xếp tiên quyết tạo vòng lặp.</div>
                </div>
                <div class="col-lg-6">
                    <label class="form-label-custom" for="concurrent_ids">Môn song hành</label>
                    <select class="form-select form-select-custom" id="concurrent_ids" name="concurrent_ids[]"
                        multiple size="6">
                        @foreach ($otherSubjects as $option)
                            <option value="{{ $option->id }}" @selected($subject->concurrents->contains($option->id))>
                                {{ $option->code }} - {{ $option->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="fs-xs text-muted-green mt-1">
                        @if ($subject->concurrentOf->isNotEmpty())
                            Đang được khai báo ở:
                            {{ $subject->concurrentOf->map(fn ($option) => $option->code)->implode(', ') }}
                        @else
                            Quan hệ song hành đọc được ở cả hai chiều dù chỉ khai báo một lần.
                        @endif
                    </div>
                </div>
            </div>

            <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật
            </button>
        </form>
    </div>
@endsection
