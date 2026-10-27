@extends('layouts.spark')
@section('title', 'Thêm chuyên ngành')
@section('content')
    <x-page-header title="Thêm chuyên ngành">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.majors.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    @if ($departments->isEmpty())
        <div class="alert-custom alert-custom-warning" role="alert">Cần tạo khoa trước khi khai báo chuyên ngành.</div>
        <a class="btn btn-custom btn-custom-outline-primary" href="{{ route('admin.departments.create') }}">Thêm khoa</a>
    @else
        <div class="card">
            <form method="POST" action="{{ route('admin.majors.store') }}">
                @csrf
                @include('admin.majors.form')
                <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm mới
                </button>
            </form>
        </div>
    @endif
@endsection
