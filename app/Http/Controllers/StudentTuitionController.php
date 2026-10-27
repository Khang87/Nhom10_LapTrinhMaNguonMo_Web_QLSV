<?php

namespace App\Http\Controllers;

use App\Models\TuitionInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentTuitionController extends Controller
{
    /**
     * Display tuition debt & dynamic VietQR code.
     */
    public function index(): View
    {
        $student = Auth::user()->student;
        $invoices = $student ? $student->tuitionInvoices()->latest()->get() : collect();
        $currentInvoice = $invoices->first();

        return view('student.tuition.index', compact('student', 'invoices', 'currentInvoice'));
    }

    /**
     * Simulate payment confirmation / Webhook mock.
     */
    public function simulatePayment(TuitionInvoice $invoice): RedirectResponse
    {
        $invoice->update([
            'paid_amount' => $invoice->total_amount,
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        return back()->with('success', "Giao dịch thanh toán thành công cho mã {$invoice->payment_code}!");
    }
}
