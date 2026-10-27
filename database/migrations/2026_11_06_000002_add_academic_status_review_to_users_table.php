<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mốc thời gian admin xác nhận "Xét duyệt" tình trạng học tập (SPEC-grading 6.6). Bản thân
 * giá trị academic_status đã có từ trước; cột này chỉ để phân biệt "mặc định STUDYING" với
 * "STUDYING do hội đồng duyệt".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('academic_status_updated_at')->nullable()->after('academic_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('academic_status_updated_at');
        });
    }
};
