<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentGradeController extends Controller
{
    public function index(): View
    {
        $grades = Auth::user()
            ->grades()
            ->with('offering.subject')
            ->orderByDesc('updated_at')
            ->paginate(10);

        return view('student.grades.index', compact('grades'));
    }
}
