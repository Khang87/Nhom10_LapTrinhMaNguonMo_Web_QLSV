<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->timestamps();
        });

        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->timestamps();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('major_id')->nullable()->constrained('majors')->nullOnDelete();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('total_sessions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('major_id');
            $table->dropColumn(['description', 'total_sessions']);
        });
        Schema::dropIfExists('majors');
        Schema::dropIfExists('departments');
    }
};
