<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            $role = strtolower(Auth::user()->role);
            if ($role === 'lecturer') {
                return redirect()->route('lecturer.schedule.index');
            }
            return redirect()->route($role.'.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = trim($request->input('email'));
        $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $field => $loginInput,
            'password' => $request->input('password'),
        ];

        // Thử đăng nhập theo trường chính (email hoặc username).
        // Nếu không khớp, thử tiếp với trường phụ (hỗ trợ cả trường hợp SV dùng email hoặc GV dùng mã GV)
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $altField = ($field === 'email') ? 'username' : 'email';
            $altCredentials = [
                $altField => $loginInput,
                'password' => $request->input('password'),
            ];
            if (! Auth::attempt($altCredentials, $request->boolean('remember'))) {
                return back()->withErrors([
                    'email' => 'Tài khoản (Email / Mã sinh viên / Mã GV) hoặc mật khẩu không chính xác.',
                ])->onlyInput('email');
            }
        }

        $request->session()->regenerate();

        $role = strtolower(Auth::user()->role);
        if ($role === 'lecturer') {
            return redirect()->route('lecturer.schedule.index');
        }

        return redirect()->route($role.'.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
