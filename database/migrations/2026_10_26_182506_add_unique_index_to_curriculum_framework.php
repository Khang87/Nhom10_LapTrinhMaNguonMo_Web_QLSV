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
        Schema::table('curriculum_framework', function (Blueprint $table) {
            $table->unique(
                ['specialization_id', 'semester_order', 'course_id'],
                'uq_curriculum_spec_sem_course'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curriculum_framework', function (Blueprint $table) {
            $table->dropUnique('uq_curriculum_spec_sem_course');
        });
    }
};
