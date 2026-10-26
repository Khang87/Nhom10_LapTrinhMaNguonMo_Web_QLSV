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

    @if ($offerings->isEmpty())
        <div class="alert alert-secondary mt-4" role="status">
            Chưa có lớp học phần nào được phân công cho bạn.
        </div>
    @else
        <div class="card mt-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="h5 card-title mb-0">Lớp học phần của tôi ({{ $offerings->count() }})</h2>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('teacher.offerings.index') }}">
                        Xem chi tiết
                    </a>
                </div>
                <ul class="list-unstyled mb-0">
                    @foreach ($offerings as $offering)
                        <li class="border-bottom py-2">
                            {{ $offering->label() }} · {{ $offering->active_count }} sinh viên
                            @unless ($offering->is_open)
                                · <span class="badge text-bg-secondary">Đóng</span>
                            @endunless
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
@endsection
