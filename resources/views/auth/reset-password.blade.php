@extends('layouts.app')

@section('title', 'Đặt lại mật khẩu')
@section('main-class', 'flex-grow-1 p-0')

@section('content')
    @vite(['resources/css/app.css'])
    <div class="login-page min-vh-100 login-wrapper">
        <div class="login-bg-shape login-bg-shape-1" aria-hidden="true"></div>
        <div class="login-bg-shape login-bg-shape-2" aria-hidden="true"></div>
        <section class="login-card" aria-labelledby="reset-password-title">
            <div class="login-brand">
                <img src="{{ asset('images/HUIT_logo.png') }}" width="44" height="44"
                    alt="Logo Trường Đại học Công Thương TP. Hồ Chí Minh">
            </div>
            <h1 class="login-title" id="reset-password-title">Đặt lại mật khẩu</h1>
            <p class="login-subtitle">Bước 2/2 · Mã OTP đã được xác thực</p>
            <p class="fs-xs text-muted-green mb-4">
                Mật khẩu cần tối thiểu 8 ký tự, có chữ hoa, chữ thường, số, ký tự đặc biệt và khác mật khẩu hiện tại.
            </p>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <div class="login-form-group">
                    <label class="login-form-label" for="password">Mật khẩu mới</label>
                    <div class="login-input-group">
                        <input class="login-input login-input-password" id="password" name="password" type="password"
                            autocomplete="new-password" minlength="8" required>
                        <i class="bi bi-lock input-icon" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="login-form-group">
                    <label class="login-form-label" for="password_confirmation">Xác nhận mật khẩu mới</label>
                    <div class="login-input-group">
                        <input class="login-input login-input-password" id="password_confirmation"
                            name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                        <i class="bi bi-lock input-icon" aria-hidden="true"></i>
                    </div>
                </div>
                <button class="btn btn-custom btn-custom-primary w-100" type="submit">Cập nhật mật khẩu</button>
            </form>

            <div class="login-divider mt-4" aria-hidden="true"></div>
            <p class="login-footer-text">
                <a href="{{ route('password.request', ['change_email' => 1]) }}">Bắt đầu lại</a>
            </p>
        </section>
    </div>
@endsection
