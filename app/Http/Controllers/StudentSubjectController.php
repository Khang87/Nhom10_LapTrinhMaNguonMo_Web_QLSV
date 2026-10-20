<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentSubjectController extends Controller
{
    public function index(): View
    {
        $student = Auth::user();

        return view('student.subjects.index', [
            'registeredSubjects' => $student->subjects()->orderBy('code')->get(),
            'openSubjects' => Subject::query()
                ->whereDoesntHave('students', fn ($query) => $query->whereKey($student->id))
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function register(Subject $subject): RedirectResponse
    {
        $student = Auth::user();

        if ($student->subjects()->whereKey($subject->id)->exists()) {
            return redirect()->route('student.subjects.index')
                ->with('error', 'Bạn đã đăng ký môn học này.');
        }

        $student->subjects()->attach($subject->id);

        return redirect()->route('student.subjects.index')
            ->with('success', 'Đăng ký môn học thành công.');
    }

    public function unregister(Subject $subject): RedirectResponse
    {
        $student = Auth::user();

        if (! $student->subjects()->whereKey($subject->id)->exists()) {
            return redirect()->route('student.subjects.index')
                ->with('error', 'Môn học này chưa được đăng ký.');
        }

        $student->subjects()->detach($subject->id);

        return redirect()->route('student.subjects.index')
            ->with('success', 'Đã hủy đăng ký môn học.');
    }
}
