<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Truy cập bị từ chối | HUIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .error-card {
            max-width: 540px;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 1rem;
        }
        .error-code {
            font-size: 5rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -2px;
            color: #dc3545;
        }
    </style>
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center p-3">
    <div class="card error-card shadow-sm w-100 bg-white text-center p-4 p-sm-5">
        <div class="mb-4">
            <img src="{{ asset('images/HUIT_logo.png') }}" alt="Logo HUIT" width="70" height="70" class="mb-3">
            <div class="error-code mb-2">403</div>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 rounded-pill fw-semibold">
                <i class="bi bi-shield-slash-fill me-1"></i> TRUY CẬP BỊ TỪ CHỐI
            </span>
        </div>

        <h1 class="h4 fw-bold text-dark mb-2">Bạn không có quyền truy cập</h1>
        <p class="text-secondary small mb-4">
            Đường dẫn hoặc chức năng này yêu cầu quyền hạn đặc biệt mà tài khoản hiện tại của bạn không được cấp phép. 
            Vui lòng quay về Cổng làm việc được phân công hoặc liên hệ Phòng Đào tạo nếu cần hỗ trợ.
        </p>

        @php
            $user = auth()->user();
            $role = $user ? strtolower($user->role) : null;
            $host = request()->getHost();
            $homeUrl = match ($role) {
                'admin' => "http://{$host}:8000/admin/dashboard",
                'lecturer' => "http://{$host}:8001/lecturer/dashboard",
                'student' => "http://{$host}:8002/student/dashboard",
                default => url('/login'),
            };
            $homeLabel = match ($role) {
                'admin' => 'Về Cổng Quản Trị Viên (:8000)',
                'lecturer' => 'Về Cổng Giảng Viên (:8001)',
                'student' => 'Về Cổng Sinh Viên (:8002)',
                default => 'Về trang Đăng nhập',
            };
        @endphp

        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center mb-4">
            <a href="{{ $homeUrl }}" class="btn btn-primary px-4 py-2">
                <i class="bi bi-house-door-fill me-1"></i> {{ $homeLabel }}
            </a>
            @if ($user)
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary px-3 py-2 w-100">
                        <i class="bi bi-box-arrow-right me-1"></i> Đăng xuất
                    </button>
                </form>
            @endif
        </div>

        <footer class="text-secondary small border-top pt-3">
            © {{ date('Y') }} Trường Đại học Công Thương TP. Hồ Chí Minh (HUIT)
        </footer>
    </div>
</body>
</html>
