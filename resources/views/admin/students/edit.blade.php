@extends('layouts.spark')
@section('title', 'Sửa sinh viên')
@section('content')
    <h1 class="h3 mb-3">Sửa sinh viên</h1>
    <form method="POST" action="{{ route('admin.students.update', $student) }}">
        @csrf @method('PUT')
        @include('admin.students.form')
        <button class="btn btn-primary" type="submit">Cập nhật</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.students.index') }}">Quay lại</a>
    </form>
@endsection
