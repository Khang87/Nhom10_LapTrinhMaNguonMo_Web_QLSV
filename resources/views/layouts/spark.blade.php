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
    @php
        $userRole = auth()->user()->role ?? 'student';
        $brandRoute = match ($userRole) {
            'admin' => route('admin.dashboard'),
            'lecturer' => route('lecturer.dashboard'),
            default => route('student.dashboard'),
        };
        $isHomeActive = match ($userRole) {
            'admin' => request()->routeIs('admin.dashboard'),
            'lecturer' => request()->routeIs('lecturer.dashboard'),
            default => request()->routeIs('student.dashboard'),
        };
        $homeLabel = 'Trang chủ';
        $homeIcon = 'bi-grid-fill';
        $roleLabel = match ($userRole) {
            'admin' => 'quản trị',
            'lecturer' => 'giảng viên',
            default => 'sinh viên',
        };
    @endphp
    <aside class="sidebar-wrapper" id="sidebar" aria-label="Điều hướng {{ $roleLabel }}">
        <a class="sidebar-brand" href="{{ $brandRoute }}">
            <img src="{{ asset('images/HUIT_logo.png') }}" alt="" width="32" height="32">
            <span>Quản lý sinh viên</span>
        </a>

        <div class="flex-grow-1 overflow-y-auto">
            <section class="sidebar-menu-section" aria-labelledby="spark-overview-title">
                <h2 class="sidebar-menu-title" id="spark-overview-title">Tổng quan</h2>
                <ul class="sidebar-menu-list">
                    <li class="sidebar-menu-item">
                        <a class="sidebar-menu-link {{ $isHomeActive ? 'active' : '' }}"
                            href="{{ $brandRoute }}"
                            @if ($isHomeActive) aria-current="page" @endif>
                            <i class="bi {{ $homeIcon }}" aria-hidden="true"></i>
                            <span>{{ $homeLabel }}</span>
                        </a>
                    </li>
                </ul>
            </section>

            @if (auth()->user()->isAdmin())
                <section class="sidebar-menu-section" aria-labelledby="spark-management-title">
                    <h2 class="sidebar-menu-title" id="spark-management-title">Quản lý đào tạo</h2>
                    <ul class="sidebar-menu-list">
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.faculties.*') ? 'active' : '' }}"
                                href="{{ route('admin.faculties.index') }}"
                                @if (request()->routeIs('admin.faculties.*')) aria-current="page" @endif>
                                <i class="bi bi-building" aria-hidden="true"></i>
                                <span>Quản lý Khoa</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.specializations.*') ? 'active' : '' }}"
                                href="{{ route('admin.specializations.index') }}"
                                @if (request()->routeIs('admin.specializations.*')) aria-current="page" @endif>
                                <i class="bi bi-mortarboard-fill" aria-hidden="true"></i>
                                <span>Quản lý Chuyên ngành</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}"
                                href="{{ route('admin.subjects.index') }}"
                                @if (request()->routeIs('admin.subjects.*')) aria-current="page" @endif>
                                <i class="bi bi-book" aria-hidden="true"></i>
                                <span>Môn học & Khung CT</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.lecturers.*') ? 'active' : '' }}"
                                href="{{ route('admin.lecturers.index') }}"
                                @if (request()->routeIs('admin.lecturers.*')) aria-current="page" @endif>
                                <i class="bi bi-person-video3" aria-hidden="true"></i>
                                <span>Quản lý Giảng viên</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}"
                                href="{{ route('admin.students.index') }}"
                                @if (request()->routeIs('admin.students.*')) aria-current="page" @endif>
                                <i class="bi bi-people" aria-hidden="true"></i>
                                <span>Quản lý Sinh viên</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.semesters.*') ? 'active' : '' }}"
                                href="{{ route('admin.semesters.index') }}"
                                @if (request()->routeIs('admin.semesters.*')) aria-current="page" @endif>
                                <i class="bi bi-calendar2-range" aria-hidden="true"></i>
                                <span>Học kỳ & Đợt ĐK</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.course-sections.*') ? 'active' : '' }}"
                                href="{{ route('admin.course-sections.index') }}"
                                @if (request()->routeIs('admin.course-sections.*')) aria-current="page" @endif>
                                <i class="bi bi-diagram-3" aria-hidden="true"></i>
                                <span>Mở Lớp học phần</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}"
                                href="{{ route('admin.grades.index') }}"
                                @if (request()->routeIs('admin.grades.*')) aria-current="page" @endif>
                                <i class="bi bi-journal-check" aria-hidden="true"></i>
                                <span>Quản lý Điểm</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.grades.approvals') || request()->routeIs('admin.grades.appeals') ? 'active' : '' }}"
                                href="{{ route('admin.grades.approvals') }}"
                                @if (request()->routeIs('admin.grades.approvals')) aria-current="page" @endif>
                                <i class="bi bi-patch-check" aria-hidden="true"></i>
                                <span>Phê duyệt Bảng điểm</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.schedule_requests.*') ? 'active' : '' }}"
                                href="{{ route('admin.schedule_requests.index') }}"
                                @if (request()->routeIs('admin.schedule_requests.*')) aria-current="page" @endif>
                                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                <span>Duyệt Đổi lịch dạy</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.attendances.*') ? 'active' : '' }}"
                                href="{{ route('admin.attendances.index') }}"
                                @if (request()->routeIs('admin.attendances.*')) aria-current="page" @endif>
                                <i class="bi bi-calendar-check" aria-hidden="true"></i>
                                <span>Báo cáo Điểm danh</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}"
                                href="{{ route('admin.classes.index') }}"
                                @if (request()->routeIs('admin.classes.*')) aria-current="page" @endif>
                                <i class="bi bi-building" aria-hidden="true"></i>
                                <span>Lớp học danh nghĩa</span>
                            </a>
                        </li>
                    </ul>
                </section>
            @elseif (auth()->user()->isLecturer())
                <section class="sidebar-menu-section" aria-labelledby="spark-lecturer-title">
                    <h2 class="sidebar-menu-title" id="spark-lecturer-title">Giảng viên</h2>
                    <ul class="sidebar-menu-list">
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('lecturer.schedule.*') ? 'active' : '' }}"
                                href="{{ route('lecturer.schedule.index') }}"
                                @if (request()->routeIs('lecturer.schedule.*')) aria-current="page" @endif>
                                <i class="bi bi-calendar-week" aria-hidden="true"></i>
                                <span>Lịch giảng dạy tuần</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('lecturer.attendances.*') ? 'active' : '' }}"
                                href="{{ route('lecturer.attendances.index') }}"
                                @if (request()->routeIs('lecturer.attendances.*')) aria-current="page" @endif>
                                <i class="bi bi-calendar-check" aria-hidden="true"></i>
                                <span>Điểm danh lớp học</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('lecturer.grades.*') ? 'active' : '' }}"
                                href="{{ route('lecturer.grades.index') }}"
                                @if (request()->routeIs('lecturer.grades.*')) aria-current="page" @endif>
                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                <span>Nhập điểm học phần</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('lecturer.advisor.*') ? 'active' : '' }}"
                                href="{{ route('lecturer.advisor.index') }}"
                                @if (request()->routeIs('lecturer.advisor.*')) aria-current="page" @endif>
                                <i class="bi bi-person-workspace" aria-hidden="true"></i>
                                <span>Cố vấn học tập (CVHT)</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('lecturer.appeals.*') ? 'active' : '' }}"
                                href="{{ route('lecturer.appeals.index') }}"
                                @if (request()->routeIs('lecturer.appeals.*')) aria-current="page" @endif>
                                <i class="bi bi-chat-left-dots" aria-hidden="true"></i>
                                <span>Phúc khảo điểm</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('lecturer.requests.*') ? 'active' : '' }}"
                                href="{{ route('lecturer.requests.index') }}"
                                @if (request()->routeIs('lecturer.requests.*')) aria-current="page" @endif>
                                <i class="bi bi-send-exclamation" aria-hidden="true"></i>
                                <span>Báo nghỉ & Dạy bù</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('lecturer.profile*') ? 'active' : '' }}"
                                href="{{ route('lecturer.profile') }}"
                                @if (request()->routeIs('lecturer.profile*')) aria-current="page" @endif>
                                <i class="bi bi-person-badge" aria-hidden="true"></i>
                                <span>Thông tin cá nhân</span>
                            </a>
                        </li>
                    </ul>
                </section>
            @else
                <section class="sidebar-menu-section" aria-labelledby="spark-general-title">
                    <h2 class="sidebar-menu-title" id="spark-general-title">Thông tin chung</h2>
                    <ul class="sidebar-menu-list">
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('student.profile') ? 'active' : '' }}"
                                href="{{ route('student.profile', [], false) }}"
                                @if (request()->routeIs('student.profile')) aria-current="page" @endif>
                                <i class="bi bi-person-badge" aria-hidden="true"></i>
                                <span>Thông tin sinh viên</span>
                            </a>
                        </li>
                    </ul>
                </section>

                <section class="sidebar-menu-section" aria-labelledby="spark-learning-title">
                    <h2 class="sidebar-menu-title" id="spark-learning-title">Học tập</h2>
                    <ul class="sidebar-menu-list">
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('student.schedule.*') ? 'active' : '' }}"
                                href="{{ route('student.schedule.index', [], false) }}"
                                @if (request()->routeIs('student.schedule.*')) aria-current="page" @endif>
                                <i class="bi bi-calendar-week" aria-hidden="true"></i>
                                <span>Lịch theo tuần</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('student.grades.*') ? 'active' : '' }}"
                                href="{{ route('student.grades.index', [], false) }}"
                                @if (request()->routeIs('student.grades.*')) aria-current="page" @endif>
                                <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                                <span>Kết quả học tập</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('student.attendance.*') ? 'active' : '' }}"
                                href="{{ route('student.attendance.index', [], false) }}"
                                @if (request()->routeIs('student.attendance.*')) aria-current="page" @endif>
                                <i class="bi bi-person-check" aria-hidden="true"></i>
                                <span>Điểm danh học phần</span>
                            </a>
                        </li>
                    </ul>
                </section>

                <section class="sidebar-menu-section" aria-labelledby="spark-register-title">
                    <h2 class="sidebar-menu-title" id="spark-register-title">Đăng ký học phần</h2>
                    <ul class="sidebar-menu-list">
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('student.curriculum.*') ? 'active' : '' }}"
                                href="{{ route('student.curriculum.index', [], false) }}"
                                @if (request()->routeIs('student.curriculum.*')) aria-current="page" @endif>
                                <i class="bi bi-diagram-3" aria-hidden="true"></i>
                                <span>Chương trình khung</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('student.subjects.*') ? 'active' : '' }}"
                                href="{{ route('student.subjects.index', [], false) }}"
                                @if (request()->routeIs('student.subjects.*')) aria-current="page" @endif>
                                <i class="bi bi-journal-plus" aria-hidden="true"></i>
                                <span>Đăng ký môn học</span>
                            </a>
                        </li>
                    </ul>
                </section>

                <section class="sidebar-menu-section" aria-labelledby="spark-tuition-title">
                    <h2 class="sidebar-menu-title" id="spark-tuition-title">Học phí</h2>
                    <ul class="sidebar-menu-list">
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-link {{ request()->routeIs('student.tuition.*') ? 'active' : '' }}"
                                href="{{ route('student.tuition.index', [], false) }}"
                                @if (request()->routeIs('student.tuition.*')) aria-current="page" @endif>
                                <i class="bi bi-credit-card-2-front" aria-hidden="true"></i>
                                <span>Tra cứu nợ & VietQR</span>
                            </a>
                        </li>
                    </ul>
                </section>
            @endif
        </div>

        <div class="sidebar-profile">
            <img src="{{ asset('images/HUIT_logo.png') }}" alt="" class="sidebar-profile-img">
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
                @if (auth()->user()->isAdmin())
                    <a class="btn-quick-action" href="{{ route('admin.students.create') }}">
                        <i class="bi bi-person-plus" aria-hidden="true"></i>
                        <span>Thêm sinh viên</span>
                    </a>
                @elseif (auth()->user()->isLecturer())
                    <a class="btn-quick-action" href="{{ route('lecturer.schedule.index') }}">
                        <i class="bi bi-calendar-week" aria-hidden="true"></i>
                        <span>Lịch giảng dạy</span>
                    </a>
                @else
                    <a class="btn-quick-action" href="{{ route('student.schedule.index') }}">
                        <i class="bi bi-calendar-week" aria-hidden="true"></i>
                        <span>Lịch theo tuần</span>
                    </a>
                @endif
            </div>

            <div class="navbar-actions">
                <div class="dropdown">
                    <button class="navbar-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false" id="spark-profile-dropdown">
                        <img src="{{ asset('images/HUIT_logo.png') }}" alt="" class="navbar-profile-img">
                        <span class="navbar-profile-name d-none d-md-inline">{{ auth()->user()->name }}</span>
                        <i class="bi bi-chevron-down navbar-profile-caret" aria-hidden="true"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-profile"
                        aria-labelledby="spark-profile-dropdown">
                        <div class="dropdown-header">{{ auth()->user()->email }}</div>
                        <div class="dropdown-divider"></div>
                        @if(auth()->user()->isLecturer())
                            <a class="dropdown-item" href="{{ route('lecturer.profile') }}">
                                <i class="bi bi-person-badge me-2" aria-hidden="true"></i>
                                Thông tin cá nhân
                            </a>
                            <div class="dropdown-divider"></div>
                        @elseif(auth()->user()->isStudent())
                            <a class="dropdown-item" href="{{ route('student.profile') }}">
                                <i class="bi bi-person-badge me-2" aria-hidden="true"></i>
                                Thông tin cá nhân
                            </a>
                            <div class="dropdown-divider"></div>
                        @endif
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
            @if (isset($errors) && $errors->any())
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Tự động load dữ liệu ngay lập tức khi người dùng chọn bất kỳ dropdown bộ lọc nào (trừ form có class no-auto-submit)
            document.querySelectorAll('form[method="GET"]:not(.no-auto-submit) select:not(.no-auto-submit), form select[onchange*="submit"]').forEach(function (select) {
                // Đảm bảo không trùng lặp nếu đã có onchange inline
                select.addEventListener('change', function () {
                    if (this.form && !this.form.classList.contains('no-auto-submit') && !this.classList.contains('no-auto-submit')) {
                        this.form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>
