@extends('layouts.spark')
@section('title', 'Thêm khoa')
@section('content')
    <x-page-header title="Thêm khoa">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.departments.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form method="POST" action="{{ route('admin.departments.store') }}">
            @csrf
            @include('admin.departments.form')
            <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm mới
            </button>
        </form>
    </div>
@endsection
