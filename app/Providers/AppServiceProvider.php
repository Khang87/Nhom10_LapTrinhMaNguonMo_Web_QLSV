<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Tách biệt Session Cookie theo cổng để cho phép mở cùng lúc 3 localhost độc lập:
        // Port 8000: Admin, Port 8001: Giảng viên, Port 8002: Sinh viên
        $serverPort = $_SERVER['SERVER_PORT'] ?? '8000';
        config(['session.cookie' => 'huit_session_' . $serverPort]);
    }
}
