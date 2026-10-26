<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SemesterController extends Controller
{
    public function index(): View
    {
        $semesters = Semester::query()
            ->withCount('offerings')
            ->orderByDesc('academic_year')
            ->orderByDesc('term')
            ->paginate(10);

        return view('admin.semesters.index', [
            'semesters' => $semesters,
            'current' => Semester::current(),
        ]);
    }

    public function create(): View
    {
        return view('admin.semesters.create', ['semester' => new Semester]);
    }

    public function store(Request $request): RedirectResponse
    {
        Semester::create($this->validatedData($request));

        return redirect()->route('admin.semesters.index')->with('success', 'Đã thêm học kỳ.');
    }

    public function edit(Semester $semester): View
    {
        return view('admin.semesters.edit', ['semester' => $semester]);
    }

    public function update(Request $request, Semester $semester): RedirectResponse
    {
        $semester->update($this->validatedData($request, $semester));

        return redirect()->route('admin.semesters.index')->with('success', 'Đã cập nhật học kỳ.');
    }

    public function activate(Semester $semester): RedirectResponse
    {
        Semester::activate($semester->id);

        return redirect()->route('admin.semesters.index')
            ->with('success', 'Đã đặt '.$semester->label.' làm học kỳ hiện hành.');
    }

    public function destroy(Semester $semester): RedirectResponse
    {
        if ($semester->is_active) {
            return redirect()->route('admin.semesters.index')
                ->with('error', 'Không xóa học kỳ hiện hành. Hãy đặt một kỳ khác làm hiện hành trước.');
        }

        if ($semester->offerings()->exists()) {
            return redirect()->route('admin.semesters.index')->with('error', 'Học kỳ "'
                .$semester->label.'" chưa xóa được vì đã có lớp học phần. Đóng từng lớp nếu lớp không còn sử dụng.');
        }

        $semester->delete();

        return redirect()->route('admin.semesters.index')->with('success', 'Đã xóa học kỳ.');
    }

    private function validatedData(Request $request, ?Semester $semester = null): array
    {
        $data = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => [
                'required',
                'integer',
                'between:1,3',
                Rule::unique('semesters', 'term')
                    ->where(fn ($query) => $query->where('academic_year', $request->input('academic_year')))
                    ->ignore($semester?->id),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'registration_starts_at' => ['nullable', 'date'],
            'registration_ends_at' => ['nullable', 'date'],
        ], [
            'term.unique' => 'Học kỳ này của năm học đã tồn tại.',
            'term.between' => 'Học kỳ chỉ nhận giá trị 1, 2 hoặc 3.',
            'ends_at.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
        ]);

        $this->assertRegistrationWindow($data['registration_starts_at'] ?? null, $data['registration_ends_at'] ?? null);

        foreach (['registration_starts_at', 'registration_ends_at'] as $column) {
            $data[$column] = filled($data[$column] ?? null) ? Carbon::parse($data[$column]) : null;
        }

        return $data;
    }

    /**
     * Cổng đăng ký chỉ có nghĩa khi biết cả hai mốc; một mình mốc mở khiến kỳ đóng vĩnh viễn.
     */
    private function assertRegistrationWindow(mixed $starts, mixed $ends): void
    {
        if (filled($starts) && blank($ends)) {
            throw ValidationException::withMessages([
                'registration_ends_at' => 'Cổng đăng ký cần cả mốc đóng, nếu không cổng sẽ mở vô thời hạn.',
            ]);
        }

        if (blank($starts) && filled($ends)) {
            throw ValidationException::withMessages([
                'registration_starts_at' => 'Cổng đăng ký cần cả mốc mở.',
            ]);
        }

        if (filled($starts) && filled($ends) && Carbon::parse($ends)->lte(Carbon::parse($starts))) {
            throw ValidationException::withMessages([
                'registration_ends_at' => 'Mốc đóng cổng phải sau mốc mở cổng.',
            ]);
        }
    }
}
