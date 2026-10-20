@extends('layouts.spark')

@section('title', 'Sửa lớp học')

@section('content')
    <h1 class="h3 mb-3">Sửa lớp học</h1>
    <form method="POST" action="{{ route('admin.classes.update', $class) }}">
        @csrf
        @method('PUT')
        @include('admin.classes.form')
        <button class="btn btn-primary" type="submit">Cập nhật</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.classes.index') }}">Quay lại</a>
    </form>
@endsection
