<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\TuitionInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentTuitionController extends Controller
{
    /**
     * Display tuition debt, all registered courses with payment status,
     * and dynamic VietQR code for payment.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $student = $user->student;

        $invoices = $student ? $student->tuitionInvoices()->latest()->get() : collect();
        $invoicesBySemester = $invoices->keyBy('semester_code');

        // Lấy tất cả các học phần đã đăng ký của sinh viên (tất cả các kỳ)
        $allEnrollments = $student
            ? Enrollment::with(['section.course', 'section.lecturer'])
                ->where('student_id', $student->id)
                ->latest()
                ->get()
            : collect();

        // Map thông tin từng môn học: trạng thái đã thanh toán hay chưa, giá tiền
        $registeredCourses = $allEnrollments->map(function ($enr) use ($invoicesBySemester) {
            $sec = $enr->section;
            $course = $sec?->course;
            $credits = $course?->credits ?? 3;
            $unitPrice = $course?->tuition_fee_per_credit ?? 550000;
            $fee = $credits * $unitPrice;
            $semesterCode = $sec?->semester_code ?? 'HK1_2026_2027';

            $invoice = $invoicesBySemester->get($semesterCode);
            // Môn được xem là Đã thanh toán nếu hóa đơn của kỳ đó có status PAID hoặc paid_amount >= total_amount
            $isPaid = $invoice && ($invoice->status === 'PAID' || ($invoice->total_amount > 0 && $invoice->paid_amount >= $invoice->total_amount));

            return (object) [
                'enrollment' => $enr,
                'section' => $sec,
                'course' => $course,
                'semester_code' => $semesterCode,
                'credits' => $credits,
                'unit_price' => $unitPrice,
                'fee' => $fee,
                'is_paid' => $isPaid,
                'invoice' => $invoice,
            ];
        });

        // Tính tổng học phí, tổng đã thanh toán, tổng công nợ
        $totalTuitionAmount = $registeredCourses->sum('fee');
        $totalPaidAmount = $registeredCourses->where('is_paid', true)->sum('fee');
        $totalDebtAmount = $registeredCourses->where('is_paid', false)->sum('fee');

        // Hóa đơn hiện tại còn nợ (ưu tiên hóa đơn UNPAID)
        $currentInvoice = $invoices->firstWhere('status', 'UNPAID') ?? $invoices->first();

        // Nếu sinh viên có môn chưa thanh toán nhưng chưa có invoice thì tự động tạo/cập nhật
        if ($totalDebtAmount > 0 && (!$currentInvoice || $currentInvoice->status === 'PAID')) {
            $unpaidCourse = $registeredCourses->firstWhere('is_paid', false);
            if ($unpaidCourse && $student) {
                $semCode = $unpaidCourse->semester_code;
                $semesterShort = strtoupper(str_replace(['_', '-'], '', $semCode));
                $paymentCode = 'HUIT ' . $student->student_code . ' ' . substr($semesterShort, 0, 10);
                
                $currentInvoice = TuitionInvoice::firstOrCreate(
                    ['student_id' => $student->id, 'semester_code' => $semCode],
                    [
                        'payment_code' => $paymentCode,
                        'total_credits' => $registeredCourses->where('semester_code', $semCode)->sum('credits'),
                        'total_amount' => $registeredCourses->where('semester_code', $semCode)->sum('fee'),
                        'paid_amount' => 0,
                        'status' => 'UNPAID',
                    ]
                );
            }
        }

        return view('student.tuition.index', compact(
            'student',
            'invoices',
            'currentInvoice',
            'registeredCourses',
            'totalTuitionAmount',
            'totalPaidAmount',
            'totalDebtAmount'
        ));
    }

    /**
     * Simulate payment confirmation / Webhook mock for a specific invoice.
     */
    public function simulatePayment(TuitionInvoice $invoice): RedirectResponse
    {
        $invoice->update([
            'paid_amount' => $invoice->total_amount,
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        return back()->with('success', "Thanh toán học phí thành công cho mã giao dịch {$invoice->payment_code}! Công nợ học phần đã được xóa.");
    }

    /**
     * Settle all remaining tuition debt.
     */
    public function payAll(): RedirectResponse
    {
        $student = Auth::user()->student;
        if ($student) {
            TuitionInvoice::where('student_id', $student->id)
                ->where('status', 'UNPAID')
                ->update([
                    'status' => 'PAID',
                    'paid_at' => now(),
                    'paid_amount' => \DB::raw('total_amount'),
                ]);
        }

        return back()->with('success', "Thanh toán toàn bộ công nợ học phí thành công!");
    }
}
