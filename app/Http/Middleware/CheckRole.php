<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $userRole = strtolower($user?->role ?? '');
        $allowed = array_map('strtolower', $roles);

        if (! in_array($userRole, $allowed, true)) {
            // Trường hợp gọi qua API / AJAX / Fetch
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Bạn không có quyền truy cập vào tài nguyên này.',
                    'required_roles' => $roles,
                ], 403);
            }

            // Nếu người dùng đã đăng nhập, tự động chuyển hướng về đúng Cổng & Dashboard của họ
            if ($user) {
                $targetPort = match ($userRole) {
                    'admin' => 8000,
                    'lecturer' => 8001,
                    'student' => 8002,
                    default => 8000,
                };

                $roleLabel = match ($userRole) {
                    'student' => 'Sinh viên',
                    'lecturer' => 'Giảng viên',
                    'admin' => 'Quản trị viên',
                    default => 'Người dùng',
                };

                $host = $request->getHost();
                $targetUrl = "http://{$host}:{$targetPort}/{$userRole}/dashboard";

                return redirect()->away($targetUrl)->with(
                    'error',
                    "Tài khoản của bạn ({$roleLabel}) không có quyền truy cập vào phân hệ này! Hệ thống đã tự động chuyển bạn về đúng Cổng làm việc."
                );
            }

            // Nếu chưa đăng nhập hoặc không xác định
            abort(403, 'Bạn không có quyền truy cập vào trang này.');
        }

        return $next($request);
    }
}
