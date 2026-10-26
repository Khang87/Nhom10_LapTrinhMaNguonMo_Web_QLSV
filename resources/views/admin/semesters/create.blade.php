@extends('layouts.spark')

@section('title', 'Thêm học kỳ')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Thêm học kỳ</h1>
        <a class="btn btn-outline-secondary" href="{{ route('admin.semesters.index') }}">Quay lại</a>
    </div>
    <form method="POST" action="{{ route('admin.semesters.store') }}">
        @csrf
        @include('admin.semesters.form')
        <button class="btn btn-primary mt-3" type="submit">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm mới
        </button>
    </form>
@endsection
