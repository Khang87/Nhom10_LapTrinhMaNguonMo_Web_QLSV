<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cohorts', function (Blueprint $table) {
            $table->id();
            $table->string('cohort_year', 10)->unique();
            $table->string('name', 100)->nullable();
            $table->timestamps();
        });

        // Nạp sẵn các khóa hiện có
        DB::table('cohorts')->insertOrIgnore([
            ['cohort_year' => '2026', 'name' => 'Khóa 2026 (Niên khóa K17)', 'created_at' => now(), 'updated_at' => now()],
            ['cohort_year' => '2023', 'name' => 'Khóa 2023 (Niên khóa K14)', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cohorts');
    }
};
