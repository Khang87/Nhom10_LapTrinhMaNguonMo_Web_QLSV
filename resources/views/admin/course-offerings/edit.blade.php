@extends('layouts.spark')

@section('title', 'Sửa lớp học phần')

@section('content')
    <x-page-header title="Sửa lớp học phần: {{ $offering->label() }}">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.course-offerings.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form method="POST" action="{{ route('admin.course-offerings.update', $offering) }}">
            @csrf
            @method('PUT')
            @include('admin.course-offerings.form')
            <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật
            </button>
        </form>
    </div>
@endsection
