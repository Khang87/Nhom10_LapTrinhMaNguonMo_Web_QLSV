<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GradeController extends Controller
{
    public function index(): View
    {
        $grades = Grade::with(['user', 'subject'])->orderByDesc('updated_at')->paginate(10);

        return view('admin.grades.index', [
            'grades' => $grades,
            'students' => User::where('role', 'student')->orderBy('name')->get(),
            'subjects' => Subject::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'score' => ['required', 'numeric', 'min:0', 'max:10'],
        ]);

        $student = User::where('role', 'student')->findOrFail($data['user_id']);
        $isRegistered = DB::table('student_subject')
            ->where('user_id', $student->id)
            ->where('subject_id', $data['subject_id'])
            ->exists();

        if (! $isRegistered) {
            throw ValidationException::withMessages([
                'user_id' => 'Sinh viên chưa đăng ký môn học này.',
            ]);
        }

        Grade::updateOrCreate(
            ['user_id' => $student->id, 'subject_id' => $data['subject_id']],
            ['score' => $data['score']],
        );

        return redirect()->route('admin.grades.index')->with('success', 'Đã cập nhật điểm.');
    }

    public function destroy(Grade $grade): RedirectResponse
    {
        $grade->delete();

        return redirect()->route('admin.grades.index')->with('success', 'Đã xóa điểm.');
    }
}
