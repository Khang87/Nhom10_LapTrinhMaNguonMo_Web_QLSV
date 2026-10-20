@extends('layouts.app')

@section('title', 'Đăng nhập')
@section('main-class', 'flex-grow-1 p-0')

@section('content')
    @vite('resources/css/app.css')
    <div class="login-page min-vh-100 d-flex justify-content-center align-items-center px-3 py-4">
        <section class="card login-card w-100" aria-labelledby="login-title">
            <div class="card-body p-4 p-sm-5">
                <header class="text-center mb-4">
                    <img class="login-logo d-block mx-auto mb-3" src="{{ asset('images/HUIT_logo.png') }}"
                        alt="Logo Trường Đại học Công Thương TP. Hồ Chí Minh">
                    <h1 class="h3 mb-2" id="login-title">Quản lý sinh viên</h1>
                    <p class="text-secondary mb-0">Hệ thống quản lý sinh viên và lớp học</p>
                </header>
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" id="email" name="email" type="email"
                            value="{{ old('email') }}" placeholder="Nhập email của bạn"
                            autocomplete="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Mật khẩu</label>
                        <div class="input-group">
                            <input class="form-control" id="password" name="password" type="password"
                                placeholder="Nhập mật khẩu" autocomplete="current-password" required>
                            <button class="btn btn-outline-secondary" type="button"
                                data-password-toggle aria-label="Hiện mật khẩu">Hiện</button>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 mt-3" type="submit">Đăng nhập</button>
                </form>
                <footer class="text-center text-secondary small mt-4">
                    © {{ date('Y') }} HUIT. All rights reserved.
                </footer>
            </div>
        </section>
    </div>
@endsection
