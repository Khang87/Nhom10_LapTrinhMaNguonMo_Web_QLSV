<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user();

        return view('teacher.dashboard', [
            'teacher' => $teacher,
            'offerings' => $teacher->assignedOfferings()
                ->with(['subject', 'semester'])
                ->withCount(['enrollments as active_count' => fn ($query) => $query->where('status', 'ACTIVE')])
                ->orderBy('subject_id')
                ->orderBy('group_code')
                ->get(),
        ]);
    }
}
