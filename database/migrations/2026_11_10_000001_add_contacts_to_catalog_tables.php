<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Danh mục khoa trong hệ thống thật (MaNguon) không chỉ có mã và tên: mỗi khoa còn số điện thoại
 * phòng, email liên hệ, địa điểm làm việc và mô tả chương trình. Thêm đúng bốn ô đó cho khoa và
 * một ô mô tả cho chuyên ngành để bảng danh mục hiển thị được thông tin người dùng cần tìm,
 * thay vì bắt họ mở một trang web khác.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('name');
            $table->string('email', 100)->nullable()->after('phone');
            $table->string('office', 150)->nullable()->after('email');
            $table->text('description')->nullable()->after('office');
        });

        Schema::table('majors', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email', 'office', 'description']);
        });

        Schema::table('majors', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
