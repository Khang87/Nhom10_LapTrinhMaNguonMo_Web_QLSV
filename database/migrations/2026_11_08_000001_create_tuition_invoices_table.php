<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Một thông báo học phí cho một sinh viên trong một học kỳ (SPEC học phí/VietQR).
 * Số tiền là số liệu phát hành, không suy lại từ đăng ký mỗi lần xem: sinh viên đã nhận thông
 * báo rồi thì thay đổi đăng ký sau đó không được làm đổi số tiền họ phải nộp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tuition_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('credits');
            $table->unsignedBigInteger('amount');
            $table->string('content', 60);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'semester_id'], 'tuition_one_per_semester_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuition_invoices');
    }
};
