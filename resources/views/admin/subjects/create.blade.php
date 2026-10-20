@extends('layouts.spark')
@section('title', 'Thêm môn học')
@section('content')
    <h1 class="h3 mb-3">Thêm môn học</h1>
    <form method="POST" action="{{ route('admin.subjects.store') }}">
        @csrf
        @include('admin.subjects.form')
        <button class="btn btn-primary" type="submit">Lưu</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.subjects.index') }}">Quay lại</a>
    </form>
@endsection
