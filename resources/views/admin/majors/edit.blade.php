@extends('layouts.spark')
@section('title', 'Sửa chuyên ngành')
@section('content')
    <x-page-header title="Sửa chuyên ngành: {{ $major->code }}">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.majors.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form method="POST" action="{{ route('admin.majors.update', $major) }}">
            @csrf @method('PUT')
            @include('admin.majors.form')
            <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật
            </button>
        </form>
    </div>
@endsection
