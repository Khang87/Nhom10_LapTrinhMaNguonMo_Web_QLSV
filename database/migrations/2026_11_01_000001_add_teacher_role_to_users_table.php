<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'teacher', 'student'])->default('student')->change();
        });
    }

    public function down(): void
    {
        if (DB::table('users')->where('role', 'teacher')->exists()) {
            throw new RuntimeException(
                'Vẫn còn tài khoản giảng viên. Đổi role của họ về student hoặc admin trước khi rollback enum.'
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'student'])->default('student')->change();
        });
    }
};
