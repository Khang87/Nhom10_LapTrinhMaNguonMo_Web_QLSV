@extends('layouts.spark')
@section('title', 'Sửa tài khoản')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Sửa tài khoản: {{ $account->name }}</h1>
        <a class="btn btn-outline-secondary" href="{{ route('admin.accounts.index') }}">Quay lại</a>
    </div>
    <form method="POST" action="{{ route('admin.accounts.update', $account) }}">
        @csrf @method('PUT')
        @include('admin.accounts.form', ['editing' => true])
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật
        </button>
    </form>
@endsection
