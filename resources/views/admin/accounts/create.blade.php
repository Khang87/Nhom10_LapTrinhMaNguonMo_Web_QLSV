@extends('layouts.spark')
@section('title', 'Thêm tài khoản')
@section('content')
    <x-page-header title="Thêm tài khoản">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.accounts.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form method="POST" action="{{ route('admin.accounts.store') }}">
            @csrf
            @include('admin.accounts.form', ['editing' => false])
            <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm mới
            </button>
        </form>
    </div>
@endsection
