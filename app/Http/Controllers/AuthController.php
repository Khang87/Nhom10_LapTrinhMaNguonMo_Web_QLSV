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
        $currentPort = (int) (request()->getPort() ?: 8000);

        if (Auth::check()) {
            $role = strtolower(Auth::user()->role);
            return redirect()->route($role.'.dashboard');
        }

        return view('auth.login', compact('currentPort'));
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
        $currentPort = (int) ($request->getPort() ?: 8000);
        $targetPort = match ($role) {
            'admin' => 8000,
            'lecturer' => 8001,
            'student' => 8002,
            default => 8000,
        };

        // Nếu người dùng đăng nhập từ cổng 8000/8001/8002 và role khác cổng, tự động chuyển về đúng cổng của role
        if (in_array($currentPort, [8000, 8001, 8002]) && $currentPort !== $targetPort) {
            $host = $request->getHost();
            return redirect()->away("http://{$host}:{$targetPort}/{$role}/dashboard");
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
