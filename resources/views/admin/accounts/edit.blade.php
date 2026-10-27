@extends('layouts.spark')
@section('title', 'Sửa tài khoản')
@section('content')
    <x-page-header title="Sửa tài khoản: {{ $account->name }}">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.accounts.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form method="POST" action="{{ route('admin.accounts.update', $account) }}">
            @csrf @method('PUT')
            @include('admin.accounts.form', ['editing' => true])
            <button class="btn btn-custom btn-custom-primary mt-3" type="submit">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật
            </button>
        </form>
    </div>
@endsection
