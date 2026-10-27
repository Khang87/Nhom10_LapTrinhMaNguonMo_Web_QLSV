@extends('layouts.spark')
@section('title', 'Sửa sinh viên')
@section('content')
    <x-page-header title="Sửa sinh viên">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.students.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form method="POST" action="{{ route('admin.students.update', $student) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            @include('admin.students.form', ['editing' => true])
            <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật
            </button>
        </form>
    </div>
@endsection
