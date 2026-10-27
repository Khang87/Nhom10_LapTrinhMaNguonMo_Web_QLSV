@extends('layouts.spark')

@section('title', 'Không hoàn thành thao tác')

@section('content')
    <x-page-header title="Không hoàn thành thao tác" />

    <div class="card">
        <div class="card-body">
            <p class="mb-1">{{ $exception->getMessage() ?: 'Yêu cầu không hợp lệ.' }}</p>
            <p class="fs-xs text-muted-green mb-3">
                Hệ thống giữ nguyên danh sách đăng ký của bạn. Khắc phục lý do ở trên rồi thử lại.
            </p>
            <a class="btn btn-custom btn-custom-light"
                href="{{ url()->previous(route('student.course-offerings.index')) }}">Quay lại</a>
        </div>
    </div>
@endsection
