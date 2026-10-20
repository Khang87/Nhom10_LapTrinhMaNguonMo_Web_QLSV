@extends('layouts.spark')

@section('title', 'Thêm lớp học')

@section('content')
    <h1 class="h3 mb-3">Thêm lớp học</h1>
    <form method="POST" action="{{ route('admin.classes.store') }}">
        @csrf
        @include('admin.classes.form')
        <button class="btn btn-primary" type="submit">Lưu</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.classes.index') }}">Quay lại</a>
    </form>
@endsection
