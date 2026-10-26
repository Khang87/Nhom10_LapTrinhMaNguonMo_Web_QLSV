@extends('layouts.spark')

@section('title', 'Không hoàn thành thao tác')

@section('content')
    <div class="bg-white p-4 rounded">
        <h1 class="h4 mb-2">Không hoàn thành thao tác</h1>
        <p class="mb-1">{{ $exception->getMessage() ?: 'Yêu cầu không hợp lệ.' }}</p>
        <p class="text-muted small mb-3">
            Hệ thống giữ nguyên danh sách đăng ký của bạn. Khắc phục lý do ở trên rồi thử lại.
        </p>
        <a class="btn btn-primary" href="{{ url()->previous(route('student.course-offerings.index')) }}">Quay lại</a>
    </div>
@endsection
