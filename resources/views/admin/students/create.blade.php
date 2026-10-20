@extends('layouts.spark')
@section('title', 'Thêm sinh viên')
@section('content')
    <h1 class="h3 mb-3">Thêm sinh viên</h1>
    <form method="POST" action="{{ route('admin.students.store') }}">
        @csrf
        @include('admin.students.form')
        <button class="btn btn-primary" type="submit">Lưu</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.students.index') }}">Quay lại</a>
    </form>
@endsection
