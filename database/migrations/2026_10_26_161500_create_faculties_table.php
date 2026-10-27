<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tạo bảng faculties
        if (!Schema::hasTable('faculties')) {
            Schema::create('faculties', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();
                $table->string('name', 150);
                $table->unsignedBigInteger('dean_id')->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('office_location', 150)->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 2. Thêm faculty_id vào specializations
        if (Schema::hasTable('specializations') && !Schema::hasColumn('specializations', 'faculty_id')) {
            Schema::table('specializations', function (Blueprint $table) {
                $table->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            });
        }

        // 3. Thêm faculty_id vào lecturers
        if (Schema::hasTable('lecturers') && !Schema::hasColumn('lecturers', 'faculty_id')) {
            Schema::table('lecturers', function (Blueprint $table) {
                $table->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            });
        }

        // 4. Di chuyển dữ liệu mặc định: Khoa Công nghệ Thông tin
        $now = now();
        $facultyId = DB::table('faculties')->insertGetId([
            'code' => 'CNTT',
            'name' => 'Khoa Công nghệ Thông tin',
            'phone' => '028.38161673',
            'email' => 'cntt@huit.edu.vn',
            'office_location' => 'Phòng B.302, Tòa nhà B, Trường ĐH Công Thương TP.HCM',
            'description' => 'Khoa đào tạo các chuyên ngành thuộc lĩnh vực CNTT, kỹ thuật phần mềm, hệ thống thông tin, an toàn thông tin và khoa học máy tính.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Cập nhật toàn bộ chuyên ngành hiện tại vào Khoa CNTT
        if (Schema::hasTable('specializations')) {
            DB::table('specializations')->whereNull('faculty_id')->update(['faculty_id' => $facultyId]);
        }

        // Cập nhật toàn bộ giảng viên hiện tại vào Khoa CNTT
        if (Schema::hasTable('lecturers')) {
            DB::table('lecturers')->whereNull('faculty_id')->update(['faculty_id' => $facultyId]);

            // Bổ nhiệm 1 giảng viên Tiến sĩ làm Trưởng khoa mặc định
            $dean = DB::table('lecturers')
                ->where('degree', 'LIKE', '%Tiến sĩ%')
                ->orWhere('degree', 'LIKE', '%TS%')
                ->first();

            if (!$dean) {
                $dean = DB::table('lecturers')->first();
            }

            if ($dean) {
                DB::table('faculties')->where('id', $facultyId)->update(['dean_id' => $dean->id]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lecturers') && Schema::hasColumn('lecturers', 'faculty_id')) {
            Schema::table('lecturers', function (Blueprint $table) {
                $table->dropForeign(['faculty_id']);
                $table->dropColumn('faculty_id');
            });
        }

        if (Schema::hasTable('specializations') && Schema::hasColumn('specializations', 'faculty_id')) {
            Schema::table('specializations', function (Blueprint $table) {
                $table->dropForeign(['faculty_id']);
                $table->dropColumn('faculty_id');
            });
        }

        Schema::dropIfExists('faculties');
    }
};
