<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('semester_code', 30)->unique();
            $table->string('semester_name', 100);
            $table->string('academic_year', 20); // 2025-2026, 2026-2027
            $table->string('term_type', 20); // TERM_1, TERM_2, SUMMER
            $table->unsignedTinyInteger('order_index'); // 1, 2, 3, 4, 5, 6...
            $table->date('start_date');
            $table->date('end_date');
            $table->dateTime('registration_start_date'); // Trước 2 tháng so với start_date
            $table->dateTime('registration_end_date');
            $table->boolean('is_registration_open')->default(true);
            $table->timestamps();
        });

        if (Schema::hasTable('course_sections') && !Schema::hasColumn('course_sections', 'specialization_name')) {
            Schema::table('course_sections', function (Blueprint $table) {
                $table->string('specialization_name', 100)->nullable()->default('Công nghệ phần mềm')->after('course_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('course_sections') && Schema::hasColumn('course_sections', 'specialization_name')) {
            Schema::table('course_sections', function (Blueprint $table) {
                $table->dropColumn('specialization_name');
            });
        }
        Schema::dropIfExists('semesters');
    }
};
