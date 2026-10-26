<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\User;
use App\Support\Csv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GradeController extends Controller
{
    public function index(): View
    {
        return view('admin.grades.index', [
            'grades' => Grade::with(['user', 'offering.subject'])
                ->orderByDesc('updated_at')
                ->paginate(10),
            'students' => User::where('role', 'student')->orderBy('name')->get(),
            'offerings' => CourseOffering::with(['subject', 'semester'])
                ->orderBy('group_code')
                ->get(),
        ]);
    }

    public function export(): Response
    {
        $rows = Grade::with(['user', 'offering.subject', 'offering.semester'])
            ->orderBy('user_id')
            ->orderBy('course_offering_id')
            ->get()
            ->map(fn (Grade $grade) => [
                $grade->user->name,
                $grade->user->email,
                $grade->subject?->code,
                $grade->subject?->name,
                $grade->offering->group_code,
                $grade->subject?->credits,
                number_format((float) $grade->final_score, 1, ',', ''),
                $grade->updated_at?->format('d/m/Y H:i'),
            ]);

        return Csv::download(
            'diem-'.today()->toDateString().'.csv',
            ['Sinh viên', 'Email', 'Mã môn', 'Môn học', 'Nhóm', 'Tín chỉ', 'Điểm', 'Cập nhật lúc'],
            $rows
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'course_offering_id' => ['required', 'exists:course_offerings,id'],
            'final_score' => ['required', 'numeric', 'min:0', 'max:10'],
        ]);

        $student = User::where('role', 'student')->findOrFail($data['user_id']);
        $isEnrolled = Enrollment::where('user_id', $student->id)
            ->where('course_offering_id', $data['course_offering_id'])
            ->where('status', 'ACTIVE')
            ->exists();

        if (! $isEnrolled) {
            throw ValidationException::withMessages([
                'course_offering_id' => 'Sinh viên chưa đăng ký học phần này.',
            ]);
        }

        Grade::updateOrCreate(
            ['user_id' => $student->id, 'course_offering_id' => $data['course_offering_id']],
            ['final_score' => $data['final_score']],
        );

        return redirect()->route('admin.grades.index')->with('success', 'Đã cập nhật điểm.');
    }

    public function destroy(Grade $grade): RedirectResponse
    {
        $grade->delete();

        return redirect()->route('admin.grades.index')->with('success', 'Đã xóa điểm.');
    }
}
