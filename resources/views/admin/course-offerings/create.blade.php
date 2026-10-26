@extends('layouts.spark')

@section('title', 'Thêm lớp học phần')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Thêm lớp học phần</h1>
        <a class="btn btn-outline-secondary" href="{{ route('admin.course-offerings.index') }}">Quay lại</a>
    </div>

    @if ($subjects->isEmpty() || $semesters->isEmpty())
        <div class="alert alert-warning">
            Cần có môn học và học kỳ trước khi mở lớp học phần.
        </div>
        @if ($semesters->isEmpty())
            <a class="btn btn-outline-primary" href="{{ route('admin.semesters.create') }}">Thêm học kỳ</a>
        @endif
        @if ($subjects->isEmpty())
            <a class="btn btn-outline-primary" href="{{ route('admin.subjects.create') }}">Thêm môn học</a>
        @endif
    @else
        <form method="POST" action="{{ route('admin.course-offerings.store') }}">
            @csrf
            @include('admin.course-offerings.form')
            <button class="btn btn-primary mt-3" type="submit">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm mới
            </button>
        </form>
    @endif
@endsection
