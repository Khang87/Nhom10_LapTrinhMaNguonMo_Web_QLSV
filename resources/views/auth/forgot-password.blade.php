@extends('layouts.app')

@section('title', 'Quên mật khẩu')
@section('main-class', 'flex-grow-1 p-0')

@section('content')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <div class="login-page min-vh-100 d-flex justify-content-center align-items-center px-3 py-4">
        <section class="card login-card w-100" aria-labelledby="forgot-password-title">
            <div class="card-body p-4 p-sm-5">
                <header class="text-center mb-4">
                    <img class="login-logo d-block mx-auto mb-3" src="{{ asset('images/HUIT_logo.png') }}"
                        alt="Logo Trường Đại học Công Thương TP. Hồ Chí Minh">
                    <h1 class="h3 mb-2" id="forgot-password-title">Quên mật khẩu</h1>
                    <p class="text-secondary mb-0">Bước 1/2 · Nhập email và xác thực mã OTP</p>
                </header>

                @if (session('status'))
                    <div class="alert alert-info" role="status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" id="email" name="email" type="email"
                            value="{{ $email ?: old('email') }}" autocomplete="email" maxlength="255" required
                            @if ($email !== '') readonly @else autofocus @endif>
                    </div>
                    <button class="btn {{ $email !== '' ? 'btn-outline-primary' : 'btn-primary' }} w-100"
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
                    <p class="small text-secondary mt-3">Mã OTP có hiệu lực trong 15 phút. Hãy kiểm tra cả hộp thư rác.</p>
                    <form method="POST" action="{{ route('password.verify') }}">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <div class="mb-3">
                            <label class="form-label" for="otp">Mã OTP</label>
                            <input class="form-control" id="otp" name="otp" type="text" inputmode="numeric"
                                pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
                                aria-describedby="otp-help" required autofocus>
                            <div class="form-text" id="otp-help">Nhập mã gồm 6 chữ số được gửi đến email.</div>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Xác nhận mã OTP</button>
                    </form>
                    <p class="text-center mt-3 mb-0">
                        <a href="{{ route('password.request', ['change_email' => 1]) }}">Đổi email</a>
                    </p>
                @endif

                <p class="text-center mt-4 mb-0">
                    <a href="{{ route('login') }}">Quay lại đăng nhập</a>
                </p>
            </div>
        </section>
    </div>
@endsection
