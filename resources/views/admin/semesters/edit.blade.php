@extends('layouts.spark')

@section('title', 'Sửa học kỳ')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Sửa học kỳ</h1>
            <p class="text-muted mb-0 small">Mã kỳ hiện tại: {{ $semester->slug }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.semesters.index') }}">Quay lại</a>
    </div>
    <form method="POST" action="{{ route('admin.semesters.update', $semester) }}">
        @csrf
        @method('PUT')
        @include('admin.semesters.form')
        <button class="btn btn-primary mt-3" type="submit">
            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật
        </button>
    </form>
@endsection
