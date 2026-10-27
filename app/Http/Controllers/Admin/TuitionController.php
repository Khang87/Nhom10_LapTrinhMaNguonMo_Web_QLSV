<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\TuitionInvoice;
use App\Support\Tuition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Bảng theo dõi học phí theo học kỳ: phát hành thông báo hàng loạt và ghi nhận thu tiền.
 */
class TuitionController extends Controller
{
    public function index(Request $request): View
    {
        $semesters = Semester::orderByDesc('academic_year')->orderByDesc('term')->get();
        $semester = $semesters->firstWhere('id', (int) $request->query('semester_id')) ?: Semester::current();

        $invoices = TuitionInvoice::where('semester_id', $semester?->id)
            ->with(['student', 'semester', 'collector'])
            ->orderBy('user_id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.tuition.index', [
            'semesters' => $semesters,
            'semester' => $semester,
            'invoices' => $invoices,
            'perCredit' => Tuition::perCredit(),
            'collected' => $this->sum($semester, true),
            'outstanding' => $this->sum($semester, false),
        ]);
    }

    /**
     * Số tiền đã thu hoặc còn chưa thu của cả học kỳ. Tính bằng SUM chứ không cộng từ danh
     * sách đang chia trang, vì thẻ thống kê phải đúng dù trang chỉ hiện 10 thông báo.
     */
    private function sum(?Semester $semester, bool $paid): array
    {
        $query = TuitionInvoice::where('semester_id', $semester?->id)
            ->when($paid, fn ($q) => $q->whereNotNull('paid_at'), fn ($q) => $q->whereNull('paid_at'));

        return ['amount' => (int) $query->sum('amount'), 'students' => $query->count()];
    }

    public function declare(Request $request): RedirectResponse
    {
        $data = $request->validate(['semester_id' => ['required', 'integer', 'exists:semesters,id']]);
        $semester = Semester::findOrFail($data['semester_id']);

        if (Tuition::perCredit() === 0) {
            return redirect()->route('admin.tuition.index', ['semester_id' => $semester->id])
                ->with('error', 'Chưa cấu hình mức thu một tín chỉ nên không phát hành được thông báo học phí.');
        }

        $result = Tuition::declare($semester);

        return redirect()->route('admin.tuition.index', ['semester_id' => $semester->id])
            ->with('success', 'Đã phát hành '.$result['created'].' thông báo học phí mới, cập nhật '
                .$result['refreshed'].' thông báo chưa thu cho '.$semester->label.'.');
    }

    public function markPaid(Request $request, TuitionInvoice $invoice): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);

        $invoice->update([
            'paid_at' => now(),
            'collected_by' => $request->user()->id,
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('success', 'Đã ghi nhận thu '.$invoice->amountLabel().' của '
            .$invoice->student?->name.'.');
    }

    public function markUnpaid(TuitionInvoice $invoice): RedirectResponse
    {
        $invoice->update(['paid_at' => null, 'collected_by' => null, 'note' => null]);

        return back()->with('success', 'Đã hủy ghi nhận thu của '.$invoice->student?->name.'.');
    }
}
