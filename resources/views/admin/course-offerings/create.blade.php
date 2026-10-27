@extends('layouts.spark')

@section('title', 'Thêm lớp học phần')

@section('content')
    <x-page-header title="Thêm lớp học phần">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.course-offerings.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    @if ($subjects->isEmpty() || $semesters->isEmpty())
        <div class="alert-custom alert-custom-warning" role="alert">
            Cần có môn học và học kỳ trước khi mở lớp học phần.
        </div>
        @if ($semesters->isEmpty())
            <a class="btn btn-custom btn-custom-outline-primary" href="{{ route('admin.semesters.create') }}">Thêm học kỳ</a>
        @endif
        @if ($subjects->isEmpty())
            <a class="btn btn-custom btn-custom-outline-primary" href="{{ route('admin.subjects.create') }}">Thêm môn học</a>
        @endif
    @else
        <div class="card">
            <form method="POST" action="{{ route('admin.course-offerings.store') }}">
                @csrf
                @include('admin.course-offerings.form')
                <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm mới
                </button>
            </form>
        </div>
    @endif
@endsection
