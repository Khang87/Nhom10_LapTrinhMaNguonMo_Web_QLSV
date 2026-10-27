@extends('layouts.spark')

@section('title', 'Sửa học kỳ')

@section('content')
    <x-page-header title="Sửa học kỳ" subtitle="Mã kỳ hiện tại: {{ $semester->slug }}">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.semesters.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form method="POST" action="{{ route('admin.semesters.update', $semester) }}">
            @csrf
            @method('PUT')
            @include('admin.semesters.form')
            <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật
            </button>
        </form>
    </div>
@endsection
