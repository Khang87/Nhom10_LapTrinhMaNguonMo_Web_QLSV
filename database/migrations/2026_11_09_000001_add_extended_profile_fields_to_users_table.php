<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hồ sơ một sinh viên ngoài đời là tờ lý lịch trích ngang, không phải bốn ô tên - email - ngày
 * sinh - quê quán. Bảng dưới lấy đúng nhóm trường mà MaNguon (hệ thống đang chạy thật) lưu:
 * giấy tờ tùy thân, hộ khẩu, dân tộc - tôn giáo - quốc tịch, diện ưu tiên và khu vực tuyển
 * sinh. Tất cả đều nullable vì sinh viên cũ chưa ai được thu thập các thông tin này.
 *
 * `degree` và `department_id` là phía giảng viên: danh sách giảng viên cần học vị và khoa phụ
 * trách, hai thứ mà bảng tài khoản hiện tại không có chỗ chứa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('degree', 60)->nullable()->after('teacher_code');
            $table->foreignId('department_id')->nullable()->after('degree')
                ->constrained('departments')->nullOnDelete();

            $table->date('enrolled_on')->nullable()->after('hometown');
            $table->string('education_level', 30)->nullable()->after('enrolled_on');
            $table->string('training_type', 30)->nullable()->after('education_level');
            $table->string('admission_region', 20)->nullable()->after('training_type');
            $table->string('priority_category', 30)->nullable()->after('admission_region');

            $table->string('ethnicity', 50)->nullable()->after('priority_category');
            $table->string('religion', 50)->nullable()->after('ethnicity');
            $table->string('nationality', 50)->nullable()->after('religion');
            $table->string('birth_place', 100)->nullable()->after('nationality');
            $table->string('permanent_residence', 200)->nullable()->after('birth_place');
            $table->string('contact_address', 200)->nullable()->after('permanent_residence');

            $table->string('id_card_number', 20)->nullable()->after('contact_address');
            $table->date('id_card_issued_at')->nullable()->after('id_card_number');
            $table->string('id_card_issued_place', 100)->nullable()->after('id_card_issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn([
                'degree',
                'enrolled_on',
                'education_level',
                'training_type',
                'admission_region',
                'priority_category',
                'ethnicity',
                'religion',
                'nationality',
                'birth_place',
                'permanent_residence',
                'contact_address',
                'id_card_number',
                'id_card_issued_at',
                'id_card_issued_place',
            ]);
        });
    }
};
