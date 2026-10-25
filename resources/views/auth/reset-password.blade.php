@extends('layouts.app')

@section('title', 'Đặt lại mật khẩu')
@section('main-class', 'flex-grow-1 p-0')

@section('content')
    @vite('resources/css/app.css')
    <div class="login-page min-vh-100 d-flex justify-content-center align-items-center px-3 py-4">
        <section class="card login-card w-100" aria-labelledby="reset-password-title">
            <div class="card-body p-4 p-sm-5">
                <header class="text-center mb-4">
                    <img class="login-logo d-block mx-auto mb-3" src="{{ asset('images/HUIT_logo.png') }}"
                        alt="Logo Trường Đại học Công Thương TP. Hồ Chí Minh">
                    <h1 class="h3 mb-2" id="reset-password-title">Đặt lại mật khẩu</h1>
                    <p class="text-secondary mb-0">Bước 2/2 · Mã OTP đã được xác thực</p>
                    <p class="small text-secondary mt-2 mb-0">
                        Mật khẩu cần tối thiểu 8 ký tự, có chữ hoa, chữ thường, số, ký tự đặc biệt và khác mật khẩu hiện tại.
                    </p>
                </header>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="password">Mật khẩu mới</label>
                        <input class="form-control" id="password" name="password" type="password"
                            autocomplete="new-password" minlength="8" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">Xác nhận mật khẩu mới</label>
                        <input class="form-control" id="password_confirmation" name="password_confirmation"
                            type="password" autocomplete="new-password" minlength="8" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Cập nhật mật khẩu</button>
                </form>

                <p class="text-center mt-4 mb-0">
                    <a href="{{ route('password.request', ['change_email' => 1]) }}">Bắt đầu lại</a>
                </p>
            </div>
        </section>
    </div>
@endsection
