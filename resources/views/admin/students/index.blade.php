@extends('layouts.spark')
@section('title', 'Quản lý sinh viên')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Quản lý sinh viên</h1>
        <a class="btn btn-primary" href="{{ route('admin.students.create') }}">Thêm sinh viên</a>
    </div>
    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.students.index') }}" data-live-search
        data-target="#student-table-body" aria-label="Tìm sinh viên">
        <div class="col-sm-8 col-md-5">
            <label class="visually-hidden" for="search">Tìm sinh viên</label>
            <input class="form-control" id="search" name="search" value="{{ $search }}" placeholder="Tên hoặc email"
                autocomplete="off">
        </div>
        <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Tìm kiếm</button></div>
    </form>
    <p class="text-muted small mb-2" id="live-search-status" aria-live="polite">
        {{ $students->total() }} sinh viên.
    </p>
    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light"><tr><th>Họ tên</th><th>Email</th><th>Lớp</th><th>Thao tác</th></tr></thead>
            <tbody id="student-table-body">
                @include('admin.students.rows')
            </tbody>
        </table>
    </div>
    <div id="student-pagination">{{ $students->links() }}</div>
@endsection
