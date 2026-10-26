@extends('layouts.spark')

@section('title', 'Trang giảng viên')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Trang giảng viên</h1>
            <p class="page-subtitle">Khu làm việc của {{ $teacher->roleLabel() }}.</p>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <div class="col">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <img class="rounded-circle border" width="56" height="56" alt=""
                        src="{{ e($teacher->avatarUrl()) }}">
                    <div>
                        <h2 class="h5 card-title mb-1">{{ $teacher->name }}</h2>
                        <p class="card-text text-muted mb-0">{{ $teacher->email }}</p>
                        <p class="card-text text-muted mb-0 small">{{ $teacher->roleLabel() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-secondary mt-4" role="status">
        Chưa có lớp học phần nào được phân công cho bạn.
    </div>
@endsection
