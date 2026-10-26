<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('adviser_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();

            // Duyệt 2026-10-25: phòng học thuộc về buổi học (class_slot), không thuộc về tập sinh viên.
            $table->dropColumn('room');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('adviser_id');
            $table->dropConstrainedForeignId('semester_id');
            $table->string('room', 50)->nullable();
        });
    }
};
