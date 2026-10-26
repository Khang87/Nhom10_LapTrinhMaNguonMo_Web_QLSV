<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferingController extends Controller
{
    public function index(Request $request): View
    {
        return view('teacher.offerings.index', [
            'offerings' => $request->user()->assignedOfferings()
                ->with(['subject', 'semester'])
                ->withCount(['enrollments as active_count' => fn ($query) => $query->where('status', 'ACTIVE')])
                ->orderByDesc('semester_id')
                ->orderBy('subject_id')
                ->orderBy('group_code')
                ->get(),
        ]);
    }
}
