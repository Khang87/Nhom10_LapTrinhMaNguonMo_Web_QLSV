@extends('layouts.spark')
@section('title', 'Thêm môn học')
@section('content')
    <x-page-header title="Thêm môn học">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.subjects.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form method="POST" action="{{ route('admin.subjects.store') }}">
            @csrf
            @include('admin.subjects.form')
            <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm mới
            </button>
        </form>
    </div>

    <p class="fs-xs text-muted-green">Môn tiên quyết và môn song hành khai báo trong trang sửa của từng môn.</p>
@endsection
