@extends('layouts.spark')
@section('title', 'Thêm môn học')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Thêm môn học</h1>
        <a class="btn btn-outline-secondary" href="{{ route('admin.subjects.index') }}">Quay lại</a>
    </div>
    <form method="POST" action="{{ route('admin.subjects.store') }}">
        @csrf
        @include('admin.subjects.form')
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm mới
        </button>
    </form>
    <p class="form-text mt-2">Môn tiên quyết và môn song hành khai báo trong trang sửa của từng môn.</p>
@endsection
