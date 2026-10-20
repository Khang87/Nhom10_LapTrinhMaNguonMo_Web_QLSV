<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Quản lý sinh viên - lớp học')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light min-vh-100 d-flex flex-column">
    @unless (request()->routeIs('login'))
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">Quản lý sinh viên</a>
                @auth
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false"
                        aria-label="Mở hoặc đóng menu">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="mainNavbar">
                        <ul class="navbar-nav me-auto">
                            @if (auth()->user()->role === 'admin')
                                <li class="nav-item"><a class="nav-link" href="{{ route('admin.classes.index') }}">Lớp học</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('admin.subjects.index') }}">Môn học</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('admin.students.index') }}">Sinh viên</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('admin.grades.index') }}">Điểm</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('admin.attendances.index') }}">Điểm danh</a></li>
                            @else
                                <li class="nav-item"><a class="nav-link" href="{{ route('student.subjects.index') }}">Đăng ký môn</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('student.grades.index') }}">Kết quả học tập</a></li>
                            @endif
                        </ul>
                        <div class="d-flex align-items-center gap-3">
                            <span class="navbar-text text-white">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="btn btn-outline-light btn-sm" type="submit">Đăng xuất</button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </nav>
    @endunless

    <main class="@yield('main-class', 'container py-4 flex-grow-1')">
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
