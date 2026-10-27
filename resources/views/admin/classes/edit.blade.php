@extends('layouts.spark')

@section('title', 'Sửa lớp học')

@section('content')
    <x-page-header title="Sửa lớp học: {{ $class->class_code }}">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.classes.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form method="POST" action="{{ route('admin.classes.update', $class) }}">
            @csrf @method('PUT')
            @include('admin.classes.form')
            <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật
            </button>
        </form>
    </div>
@endsection
