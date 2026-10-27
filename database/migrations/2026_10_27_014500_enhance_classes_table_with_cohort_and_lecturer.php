<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'cohort_year')) {
                $table->string('cohort_year', 10)->nullable()->after('class_name');
            }
            if (!Schema::hasColumn('classes', 'lecturer_id')) {
                $table->foreignId('lecturer_id')->nullable()->after('cohort_year')->constrained('lecturers')->nullOnDelete();
            }
            if (!Schema::hasColumn('classes', 'specialization_id')) {
                $table->foreignId('specialization_id')->nullable()->after('lecturer_id')->constrained('specializations')->nullOnDelete();
            }
            if (!Schema::hasColumn('classes', 'description')) {
                $table->text('description')->nullable()->after('room');
            }
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'cohort_year')) {
                $table->dropColumn('cohort_year');
            }
            if (Schema::hasColumn('classes', 'lecturer_id')) {
                $table->dropConstrainedForeignId('lecturer_id');
            }
            if (Schema::hasColumn('classes', 'specialization_id')) {
                $table->dropConstrainedForeignId('specialization_id');
            }
            if (Schema::hasColumn('classes', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
