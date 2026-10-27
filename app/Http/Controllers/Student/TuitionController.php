<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\TuitionInvoice;
use App\Support\Tuition;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Trang học phí của sinh viên: xem số phải nộp theo từng học kỳ và ảnh QR chuyển khoản.
 */
class TuitionController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $current = Semester::current();

        $invoices = TuitionInvoice::with('semester')
            ->where('user_id', $student->id)
            ->orderByDesc('semester_id')
            ->get();

        return view('student.tuition', [
            'invoices' => $invoices,
            'current' => $current,
            'pendingCredits' => $current === null ? 0 : Tuition::creditsFor($student, $current),
            'perCredit' => Tuition::perCredit(),
            'bank' => (array) config('academic.tuition_bank'),
        ]);
    }
}
