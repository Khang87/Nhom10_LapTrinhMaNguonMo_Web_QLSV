<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SemesterController extends Controller
{
    /**
     * Display list of academic years and semesters with registration windows.
     */
    public function index(): View
    {
        $semesters = Semester::withCount('courseSections')
            ->orderBy('order_index')
            ->get();

        return view('admin.semesters.index', compact('semesters'));
    }

    /**
     * Store a new semester with automatic 2-month registration opening rule.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'semester_code' => 'required|string|unique:semesters,semester_code|max:30',
            'semester_name' => 'required|string|max:100',
            'academic_year' => 'required|string|max:20',
            'term_type' => 'required|in:TERM_1,TERM_2,SUMMER',
            'order_index' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'registration_start_date' => 'nullable|date',
            'registration_end_date' => 'nullable|date',
        ]);

        $startDate = Carbon::parse($validated['start_date']);

        // Tự động tính ngày mở đăng ký trước 2 tháng so với ngày bắt đầu học kỳ nếu không nhập thủ công
        $regStart = !empty($validated['registration_start_date'])
            ? Carbon::parse($validated['registration_start_date'])
            : (clone $startDate)->subMonths(2)->startOfDay();

        $regEnd = !empty($validated['registration_end_date'])
            ? Carbon::parse($validated['registration_end_date'])
            : (clone $startDate)->subDay()->endOfDay();

        Semester::create([
            'semester_code' => $validated['semester_code'],
            'semester_name' => $validated['semester_name'],
            'academic_year' => $validated['academic_year'],
            'term_type' => $validated['term_type'],
            'order_index' => $validated['order_index'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'registration_start_date' => $regStart,
            'registration_end_date' => $regEnd,
            'is_registration_open' => true,
        ]);

        return redirect()->route('admin.semesters.index')->with('success', "Đã tạo thành công [{$validated['semester_name']}] với thời gian mở đăng ký tự động trước 2 tháng ({$regStart->format('d/m/Y')}).");
    }

    /**
     * Update dates and registration window for a semester.
     */
    public function update(Request $request, Semester $semester): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'registration_start_date' => 'required|date',
            'registration_end_date' => 'required|date|after:registration_start_date',
        ]);

        $semester->update($validated);

        return redirect()->route('admin.semesters.index')->with('success', "Đã cập nhật thời gian học kỳ và đợt đăng ký cho [{$semester->semester_name}].");
    }

    /**
     * 1-Click Toggle registration open/closed status.
     */
    public function toggleRegistration(Semester $semester): RedirectResponse
    {
        $semester->update([
            'is_registration_open' => !$semester->is_registration_open,
        ]);

        $statusStr = $semester->is_registration_open ? 'MỞ' : 'ĐÓNG';

        return redirect()->route('admin.semesters.index')->with('success', "Đã {$statusStr} đợt đăng ký học phần cho [{$semester->semester_name}] thành công!");
    }
}
