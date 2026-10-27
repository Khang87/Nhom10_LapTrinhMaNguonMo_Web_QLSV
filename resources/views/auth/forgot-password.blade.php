@extends('layouts.app')

@section('title', 'Quên mật khẩu')
@section('main-class', 'flex-grow-1 p-0')

@section('content')
    @vite(['resources/css/app.css'])
    <div class="login-page min-vh-100 login-wrapper">
        <div class="login-bg-shape login-bg-shape-1" aria-hidden="true"></div>
        <div class="login-bg-shape login-bg-shape-2" aria-hidden="true"></div>
        <section class="login-card" aria-labelledby="forgot-password-title">
            <div class="login-brand">
                <img src="{{ asset('images/HUIT_logo.png') }}" width="44" height="44"
                    alt="Logo Trường Đại học Công Thương TP. Hồ Chí Minh">
            </div>
            <h1 class="login-title" id="forgot-password-title">Quên mật khẩu</h1>
            <p class="login-subtitle">Bước 1/2 · Nhập email và xác thực mã OTP</p>

            @if (session('status'))
                <div class="alert-custom alert-custom-info" role="status">
                    <i class="bi bi-info-circle alert-custom-icon" aria-hidden="true"></i>
                    <span class="alert-custom-content">{{ session('status') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="login-form-group">
                    <label class="login-form-label" for="email">Email</label>
                    <div class="login-input-group">
                        <input class="login-input" id="email" name="email" type="email"
                            value="{{ $email ?: old('email') }}" autocomplete="email" maxlength="255" required
                            @if ($email !== '') readonly @else autofocus @endif>
                        <i class="bi bi-envelope input-icon" aria-hidden="true"></i>
                    </div>
                </div>
                <button class="btn btn-custom {{ $email !== '' ? 'btn-custom-outline-primary' : 'btn-custom-primary' }} w-100"
                    type="submit" @if ($email !== '') data-otp-resend data-cooldown-seconds="{{ $cooldownSeconds }}" @endif
                    @disabled($email !== '' && $cooldownSeconds > 0)>
                    @if ($email === '')
                        Gửi mã OTP
                    @elseif ($cooldownSeconds > 0)
                        Gửi lại mã ({{ $cooldownSeconds }}s)
                    @else
                        Gửi lại mã OTP
                    @endif
                </button>
            </form>

            @if ($email !== '')
                <p class="login-footer-text mt-3">Mã OTP có hiệu lực trong 15 phút. Hãy kiểm tra cả hộp thư rác.</p>
                <form method="POST" action="{{ route('password.verify') }}">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <div class="login-form-group">
                        <label class="login-form-label" for="otp">Mã OTP</label>
                        <div class="login-input-group">
                            <input class="login-input" id="otp" name="otp" type="text" inputmode="numeric"
                                pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
                                aria-describedby="otp-help" required autofocus>
                            <i class="bi bi-shield-lock input-icon" aria-hidden="true"></i>
                        </div>
                        <div class="fs-xs text-muted-green mt-2" id="otp-help">
                            Nhập mã gồm 6 chữ số được gửi đến email.
                        </div>
                    </div>
                    <button class="btn btn-custom btn-custom-primary w-100" type="submit">Xác nhận mã OTP</button>
                </form>
                <p class="login-footer-text mt-3">
                    <a href="{{ route('password.request', ['change_email' => 1]) }}">Đổi email</a>
                </p>
            @endif

            <div class="login-divider mt-4" aria-hidden="true"></div>
            <p class="login-footer-text">
                <a href="{{ route('login') }}">Quay lại đăng nhập</a>
            </p>
        </section>
    </div>
@endsection
