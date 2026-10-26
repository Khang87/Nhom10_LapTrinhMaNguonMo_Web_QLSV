@extends('layouts.spark')
@section('title', 'Thêm khoa')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Thêm khoa</h1>
        <a class="btn btn-outline-secondary" href="{{ route('admin.departments.index') }}">Quay lại</a>
    </div>
    <form method="POST" action="{{ route('admin.departments.store') }}">
        @csrf
        @include('admin.departments.form')
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm mới
        </button>
    </form>
@endsection
