@extends('layouts.spark')

@section('title', 'Sửa lớp học phần')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Sửa lớp học phần: {{ $offering->label() }}</h1>
        <a class="btn btn-outline-secondary" href="{{ route('admin.course-offerings.index') }}">Quay lại</a>
    </div>
    <form method="POST" action="{{ route('admin.course-offerings.update', $offering) }}">
        @csrf
        @method('PUT')
        @include('admin.course-offerings.form')
        <button class="btn btn-primary mt-3" type="submit">
            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật
        </button>
    </form>
@endsection
