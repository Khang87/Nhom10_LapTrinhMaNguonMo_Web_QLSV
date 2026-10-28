<?php

namespace App\Http\Controllers;

use App\Models\CurriculumFramework;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CurriculumController extends Controller
{
    /**
     * Display curriculum framework from semester 1 to 8 without tuition fees,
     * mirroring the admin interface design.
     */
    public function index(Request $request): View
    {
        $student = Auth::user()->student;
        $activeTab = (int) $request->input('semester', 1);
        if ($activeTab < 1 || $activeTab > 8) {
            $activeTab = 1;
        }

        $specialization = null;
        if ($student?->specialization_id) {
            $specialization = Specialization::with('faculty')->find($student->specialization_id);
        }

        if (!$specialization && $student?->specialization_name) {
            $specialization = Specialization::with('faculty')
                ->where('name', $student->specialization_name)
                ->first();
        }

        $query = CurriculumFramework::with('course')->orderBy('semester_order');
        if ($specialization) {
            $query->where(function ($q) use ($specialization) {
                $q->where('specialization_id', $specialization->id)
                  ->orWhere('specialization_name', $specialization->name);
            });
        }

        $allItems = $query->get();
        $curriculumBySemester = [];
        for ($sem = 1; $sem <= 8; $sem++) {
            $curriculumBySemester[$sem] = $allItems->where('semester_order', $sem)->values();
        }

        $totalFrameworkCourses = $allItems->count();
        $totalFrameworkCredits = $allItems->sum(fn($i) => $i->course?->credits ?? 0);

        return view('student.curriculum.index', compact(
            'student',
            'specialization',
            'curriculumBySemester',
            'activeTab',
            'totalFrameworkCourses',
            'totalFrameworkCredits'
        ));
    }
}
