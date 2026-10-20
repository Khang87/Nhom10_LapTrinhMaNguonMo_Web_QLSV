@extends('layouts.spark')
@section('title', 'Sửa môn học')
@section('content')
    <h1 class="h3 mb-3">Sửa môn học</h1>
    <form method="POST" action="{{ route('admin.subjects.update', $subject) }}">
        @csrf @method('PUT')
        @include('admin.subjects.form')
        <button class="btn btn-primary" type="submit">Cập nhật</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.subjects.index') }}">Quay lại</a>
    </form>
@endsection
