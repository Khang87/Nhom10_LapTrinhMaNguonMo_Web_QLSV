<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Quản lý sinh viên - lớp học')</title>
    <link rel="icon" href="{{ asset('images/HUIT_logo.png') }}">
    <link rel="stylesheet" href="{{ asset('vendor/spark-admin/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/spark-admin/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/spark-admin/assets/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/spark-layout.css') }}">
</head>
<body>
    <aside class="sidebar-wrapper" id="sidebar" aria-label="Điều hướng {{ auth()->user()->roleLabel() }}">
        <a class="sidebar-brand" href="{{ route(auth()->user()->dashboardRoute()) }}">
            <img src="{{ asset('images/HUIT_logo.png') }}" alt="" width="32" height="32">
            <span>Quản lý sinh viên</span>
        </a>

        <div class="flex-grow-1 overflow-y-auto">
            <section class="sidebar-menu-section" aria-labelledby="spark-overview-title">
                <h2 class="sidebar-menu-title" id="spark-overview-title">Tổng quan</h2>
                <ul class="sidebar-menu-list">
                    <li class="sidebar-menu-item">
                        <a class="sidebar-menu-link {{ request()->routeIs(auth()->user()->dashboardRoute()) ? 'active' : '' }}"
                            href="{{ route(auth()->user()->dashboardRoute()) }}"
                            @if (request()->routeIs(auth()->user()->dashboardRoute())) aria-current="page" @endif>
                            <i class="bi bi-grid-fill" aria-hidden="true"></i>
                            <span>Trang chủ</span>
                        </a>
                    </li>
                </ul>
            </section>

            @if (auth()->user()->role === 'admin')
                <section class="sidebar-menu-section" aria-labelledby="spark-management-title">
                    <h2 class="sidebar-menu-title" id="spark-management-title">Quản lý</h2>
                    <ul class="sidebar-menu-list">
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}"
                                href="{{ route('admin.classes.index') }}"
                                @if (request()->routeIs('admin.classes.*')) aria-current="page" @endif>
                                <i class="bi bi-building" aria-hidden="true"></i>
                                <span>Lớp học</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}"
                                href="{{ route('admin.subjects.index') }}"
                                @if (request()->routeIs('admin.subjects.*')) aria-current="page" @endif>
                                <i class="bi bi-book" aria-hidden="true"></i>
                                <span>Môn học</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}"
                                href="{{ route('admin.students.index') }}"
                                @if (request()->routeIs('admin.students.*')) aria-current="page" @endif>
                                <i class="bi bi-people" aria-hidden="true"></i>
                                <span>Sinh viên</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}"
                                href="{{ route('admin.grades.index') }}"
                                @if (request()->routeIs('admin.grades.*')) aria-current="page" @endif>
                                <i class="bi bi-journal-check" aria-hidden="true"></i>
                                <span>Điểm</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.attendances.*') ? 'active' : '' }}"
                                href="{{ route('admin.attendances.index') }}"
                                @if (request()->routeIs('admin.attendances.*')) aria-current="page" @endif>
                                <i class="bi bi-calendar-check" aria-hidden="true"></i>
                                <span>Điểm danh</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}"
                                href="{{ route('admin.accounts.index') }}"
                                @if (request()->routeIs('admin.accounts.*')) aria-current="page" @endif>
                                <i class="bi bi-person-badge" aria-hidden="true"></i>
                                <span>Tài khoản</span>
                            </a>
                        </li>
                    </ul>
                </section>
            @elseif (auth()->user()->role === 'student')
                <section class="sidebar-menu-section" aria-labelledby="spark-learning-title">
                    <h2 class="sidebar-menu-title" id="spark-learning-title">Học tập</h2>
                    <ul class="sidebar-menu-list">
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('student.subjects.*') ? 'active' : '' }}"
                                href="{{ route('student.subjects.index') }}"
                                @if (request()->routeIs('student.subjects.*')) aria-current="page" @endif>
                                <i class="bi bi-journal-plus" aria-hidden="true"></i>
                                <span>Đăng ký học phần</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('student.grades.*') ? 'active' : '' }}"
                                href="{{ route('student.grades.index') }}"
                                @if (request()->routeIs('student.grades.*')) aria-current="page" @endif>
                                <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                                <span>Kết quả học tập</span>
                            </a>
                        </li>
                    </ul>
                </section>
            @endif
        </div>

        <div class="sidebar-profile">
            <img src="{{ e(auth()->user()->avatarUrl()) }}" alt="" class="sidebar-profile-img">
            <div class="sidebar-profile-info">
                <div class="sidebar-profile-name">{{ auth()->user()->name }}</div>
                <div class="sidebar-profile-email">{{ auth()->user()->email }}</div>
            </div>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="navbar-custom">
            <div class="navbar-left">
                <button class="btn-desktop-toggle d-none d-xl-flex align-items-center justify-content-center me-3"
                    type="button" id="desktop-sidebar-toggle" aria-label="Thu gọn thanh điều hướng">
                    <i class="bi bi-chevron-bar-left" aria-hidden="true"></i>
                </button>
                <button class="sidebar-toggle-btn me-2" type="button" id="sidebar-toggle"
                    aria-label="Mở thanh điều hướng" aria-controls="sidebar" aria-expanded="false">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                @if (auth()->user()->role === 'admin')
                    <a class="btn-quick-action" href="{{ route('admin.students.create') }}">
                        <i class="bi bi-person-plus" aria-hidden="true"></i>
                        <span>Thêm sinh viên</span>
                    </a>
                @elseif (auth()->user()->role === 'student')
                    <a class="btn-quick-action" href="{{ route('student.subjects.index') }}">
                        <i class="bi bi-journal-plus" aria-hidden="true"></i>
                        <span>Đăng ký học phần</span>
                    </a>
                @endif
            </div>

            <div class="navbar-actions">
                <div class="dropdown">
                    <button class="navbar-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false" id="spark-profile-dropdown">
                        <img src="{{ e(auth()->user()->avatarUrl()) }}" alt="" class="navbar-profile-img">
                        <span class="navbar-profile-name d-none d-md-inline">{{ auth()->user()->name }}</span>
                        <i class="bi bi-chevron-down navbar-profile-caret" aria-hidden="true"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-profile"
                        aria-labelledby="spark-profile-dropdown">
                        <div class="dropdown-header">{{ auth()->user()->email }}</div>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main id="main-content">
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

        <footer class="footer-custom">
            <div class="footer-left">
                <span class="footer-logo"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i> HUIT</span>
                <span class="footer-separator">|</span>
                <span class="footer-copy">Hệ thống quản lý sinh viên và lớp học</span>
            </div>
        </footer>
    </div>

    <script src="{{ asset('vendor/spark-admin/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/spark-layout.js') }}"></script>
</body>
</html>
