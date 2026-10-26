@extends('layouts.spark')
@section('title', 'Thêm chuyên ngành')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Thêm chuyên ngành</h1>
        <a class="btn btn-outline-secondary" href="{{ route('admin.majors.index') }}">Quay lại</a>
    </div>
    @if ($departments->isEmpty())
        <div class="alert alert-warning">Cần tạo khoa trước khi khai báo chuyên ngành.</div>
        <a class="btn btn-outline-primary" href="{{ route('admin.departments.create') }}">Thêm khoa</a>
    @else
        <form method="POST" action="{{ route('admin.majors.store') }}">
            @csrf
            @include('admin.majors.form')
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm mới
            </button>
        </form>
    @endif
@endsection
