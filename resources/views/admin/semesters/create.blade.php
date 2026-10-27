@extends('layouts.spark')

@section('title', 'Thêm học kỳ')

@section('content')
    <x-page-header title="Thêm học kỳ">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.semesters.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form method="POST" action="{{ route('admin.semesters.store') }}">
            @csrf
            @include('admin.semesters.form')
            <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm mới
            </button>
        </form>
    </div>
@endsection
