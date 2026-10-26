<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->unsignedSmallInteger('intake_year')->nullable()->after('class_name');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete()->after('intake_year');
            $table->foreignId('major_id')->nullable()->constrained('majors')->nullOnDelete()->after('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('major_id');
            $table->dropColumn('intake_year');
        });
    }
};
