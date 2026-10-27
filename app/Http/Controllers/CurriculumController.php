<?php

namespace App\Http\Controllers;

use App\Models\CurriculumFramework;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CurriculumController extends Controller
{
    /**
     * Display curriculum framework from semester 1 to 8.
     */
    public function index(): View
    {
        $student = Auth::user()->student;
        $specId = $student?->specialization_id;
        $specialization = $student?->specialization?->name ?? $student?->specialization_name;

        $query = CurriculumFramework::with('course')->orderBy('semester_order');

        if ($specId) {
            $query->where(function ($q) use ($specId, $specialization) {
                $q->where('specialization_id', $specId)
                  ->orWhere('specialization_name', $specialization);
            });
        } elseif ($specialization) {
            $query->where('specialization_name', $specialization);
        }

        $items = $query->get()->groupBy('semester_order');

        return view('student.curriculum.index', compact('items', 'specialization'));
    }
}
