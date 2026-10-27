@extends('layouts.app')

@section('title', 'Đăng nhập')
@section('main-class', 'flex-grow-1 p-0')

@section('content')
    @vite(['resources/css/app.css'])
    <div class="login-page min-vh-100 login-wrapper">
        <div class="login-bg-shape login-bg-shape-1" aria-hidden="true"></div>
        <div class="login-bg-shape login-bg-shape-2" aria-hidden="true"></div>
        <section class="login-card" aria-labelledby="login-title">
            <div class="login-brand">
                <img src="{{ asset('images/HUIT_logo.png') }}" width="44" height="44"
                    alt="Logo Trường Đại học Công Thương TP. Hồ Chí Minh">
            </div>
            <h1 class="login-title" id="login-title">Quản lý sinh viên</h1>
            <p class="login-subtitle">Hệ thống quản lý sinh viên và lớp học</p>
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="login-form-group">
                    <label class="login-form-label" for="email">Email</label>
                    <div class="login-input-group">
                        <input class="login-input" id="email" name="email" type="email"
                            value="{{ old('email') }}" placeholder="Nhập email của bạn"
                            autocomplete="username" required autofocus>
                        <i class="bi bi-envelope input-icon" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="login-form-group">
                    <label class="login-form-label" for="password">Mật khẩu</label>
                    <div class="login-input-group">
                        <input class="login-input login-input-password" id="password" name="password" type="password"
                            placeholder="Nhập mật khẩu" autocomplete="current-password" required>
                        <i class="bi bi-lock input-icon" aria-hidden="true"></i>
                        <button class="password-toggle-btn" type="button"
                            data-password-toggle aria-label="Hiện mật khẩu">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <button class="btn btn-custom btn-custom-primary w-100 mt-3" type="submit">Đăng nhập</button>
            </form>
            <p class="login-footer-text mt-3">
                <a href="{{ route('password.request') }}">Quên mật khẩu?</a>
            </p>
            <footer class="login-footer-text mt-4">
                © {{ date('Y') }} HUIT. All rights reserved.
            </footer>
        </section>
    </div>
@endsection
