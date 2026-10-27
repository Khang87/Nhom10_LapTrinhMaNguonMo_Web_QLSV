@extends('layouts.spark')

@section('title', 'Thêm lớp học')

@section('content')
    <x-page-header title="Thêm lớp học">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.classes.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form method="POST" action="{{ route('admin.classes.store') }}">
            @csrf
            @include('admin.classes.form')
            <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm mới
            </button>
        </form>
    </div>
@endsection
