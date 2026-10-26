<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vết ai thực hiện enrollment (SPEC-enrollment 5.6): sinh viên tự đăng ký thì ghi id của họ,
 * quản trị viên đăng ký hộ thì ghi id quản trị viên. Xóa người ghi không được xóa lịch sử học.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('enrolled_by')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enrolled_by');
        });
    }
};
