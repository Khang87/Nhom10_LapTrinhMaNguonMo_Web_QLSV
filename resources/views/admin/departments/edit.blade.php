@extends('layouts.spark')
@section('title', 'Sửa khoa')
@section('content')
    <x-page-header title="Sửa khoa: {{ $department->code }}">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.departments.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form method="POST" action="{{ route('admin.departments.update', $department) }}">
            @csrf @method('PUT')
            @include('admin.departments.form')
            <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật
            </button>
        </form>
    </div>
@endsection
