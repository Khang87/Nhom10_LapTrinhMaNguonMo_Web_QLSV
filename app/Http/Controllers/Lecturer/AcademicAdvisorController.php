<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\TuitionInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AcademicAdvisorController extends Controller
{
    /**
     * Danh sách các lớp sinh hoạt giảng viên làm Cố vấn học tập (CVHT)
     */
    public function index(): View
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        $homeroomClasses = $lecturer
            ? $lecturer->homeroomClasses()
                ->with(['specialization.faculty'])
                ->withCount('students')
                ->get()
            : collect();

        // Thống kê nhanh cho từng lớp
        $classesStats = $homeroomClasses->map(function ($cls) {
            $students = $cls->cohortStudents()->get();
            $totalCount = $students->count();

            // Tính GPA bình quân và số cảnh báo học vụ
            $warningCount = 0;
            $unpaidCount = 0;
            $gpaSum = 0;
            $gpaValidCount = 0;

            foreach ($students as $stu) {
                $enrs = Enrollment::where('student_id', $stu->id)
                    ->whereNotNull('total_score_4')
                    ->get();

                $gpa = $enrs->isNotEmpty() ? $enrs->avg('total_score_4') : 0;
                if ($enrs->isNotEmpty()) {
                    $gpaSum += $gpa;
                    $gpaValidCount++;
                }

                if ($gpa > 0 && $gpa < 2.0) {
                    $warningCount++;
                }

                // Kiểm tra công nợ học phí kỳ hiện tại
                $hasDebt = TuitionInvoice::where('student_id', $stu->id)
                    ->where('status', '!=', 'PAID')
                    ->exists();
                if ($hasDebt) {
                    $unpaidCount++;
                }
            }

            $avgGpa = $gpaValidCount > 0 ? round($gpaSum / $gpaValidCount, 2) : 0;

            return (object) [
                'class' => $cls,
                'total_students' => $totalCount,
                'avg_gpa' => $avgGpa,
                'warning_count' => $warningCount,
                'unpaid_count' => $unpaidCount,
            ];
        });

        return view('lecturer.advisor.index', compact('lecturer', 'classesStats'));
    }

    /**
     * Chi tiết tình hình học tập và hồ sơ sinh viên của một lớp cố vấn
     */
    public function showClass(StudentClass $class, Request $request): View
    {
        $user = Auth::user();
        $lecturer = $user->lecturer;

        // Kiểm tra quyền: Chỉ CVHT của lớp hoặc Admin mới được xem
        if (!$user->isAdmin() && (!$lecturer || $class->lecturer_id !== $lecturer->id)) {
            abort(403, 'Bạn không phải là Cố vấn học tập của lớp sinh hoạt này.');
        }

        $class->load(['specialization.faculty', 'lecturer']);

        // Bộ lọc tìm kiếm và trạng thái cảnh báo
        $search = $request->input('search');
        $filterStatus = $request->input('filter', 'all'); // all, warning, debt, good

        $currentSemester = Semester::where('is_registration_open', true)->first()
            ?? Semester::where('semester_code', 'HK1_2026_2027')->first()
            ?? Semester::first();
        $currentSemesterCode = $currentSemester?->semester_code ?? 'HK1_2026_2027';

        // Lấy toàn bộ sinh viên trong lớp
        $query = $class->cohortStudents()->with(['user']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('student_code', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $rawStudents = $query->orderBy('student_code')->get();

        // Thu thập thông tin chi tiết từng sinh viên
        $studentsData = $rawStudents->map(function ($stu) use ($currentSemesterCode) {
            $enrs = Enrollment::where('student_id', $stu->id)->get();
            $scoredEnrs = $enrs->whereNotNull('total_score_4');

            // GPA
            $gpa4 = $scoredEnrs->isNotEmpty() ? round($scoredEnrs->avg('total_score_4'), 2) : 0;
            $gpa10 = $scoredEnrs->isNotEmpty() ? round($scoredEnrs->avg('total_score_10'), 2) : 0;

            // Số tín chỉ tích lũy (môn có điểm >= 4.0 trên thang 10)
            $earnedCredits = Enrollment::where('student_id', $stu->id)
                ->where('total_score_10', '>=', 4.0)
                ->join('course_sections', 'enrollments.section_id', '=', 'course_sections.id')
                ->join('courses', 'course_sections.course_id', '=', 'courses.id')
                ->sum('courses.credits');

            // Trạng thái học vụ
            $academicStatus = 'NORMAL';
            $academicStatusLabel = 'Bình thường';
            $academicBadgeClass = 'bg-light text-success border border-success-subtle';

            if ($gpa4 > 0 && $gpa4 < 1.6) {
                $academicStatus = 'DANGER';
                $academicStatusLabel = 'Cảnh báo mức 2 (Nguy cơ)';
                $academicBadgeClass = 'bg-danger text-white';
            } elseif ($gpa4 >= 1.6 && $gpa4 < 2.0) {
                $academicStatus = 'WARNING';
                $academicStatusLabel = 'Cảnh báo mức 1';
                $academicBadgeClass = 'bg-warning text-dark';
            } elseif ($gpa4 >= 3.6) {
                $academicStatusLabel = 'Xuất sắc';
                $academicBadgeClass = 'bg-primary text-white';
            } elseif ($gpa4 >= 3.2) {
                $academicStatusLabel = 'Giỏi';
                $academicBadgeClass = 'bg-info text-dark';
            }

            // Tình hình công nợ kỳ này
            $invoice = TuitionInvoice::where('student_id', $stu->id)
                ->where('semester_code', $currentSemesterCode)
                ->first();

            $debt = $invoice ? max(0, $invoice->total_amount - $invoice->paid_amount) : 0;
            $hasDebt = $debt > 0;

            return (object) [
                'student' => $stu,
                'gpa_4' => $gpa4,
                'gpa_10' => $gpa10,
                'earned_credits' => (int) $earnedCredits,
                'academic_status' => $academicStatus,
                'academic_status_label' => $academicStatusLabel,
                'academic_badge_class' => $academicBadgeClass,
                'has_debt' => $hasDebt,
                'debt_amount' => $debt,
            ];
        });

        // Áp dụng bộ lọc
        if ($filterStatus === 'warning') {
            $studentsData = $studentsData->filter(fn($s) => in_array($s->academic_status, ['WARNING', 'DANGER']));
        } elseif ($filterStatus === 'debt') {
            $studentsData = $studentsData->filter(fn($s) => $s->has_debt);
        } elseif ($filterStatus === 'good') {
            $studentsData = $studentsData->filter(fn($s) => $s->gpa_4 >= 3.2);
        }

        // Thống kê tóm tắt
        $totalStudents = $rawStudents->count();
        $totalWarnings = $studentsData->whereIn('academic_status', ['WARNING', 'DANGER'])->count();
        $totalDebts = $studentsData->where('has_debt', true)->count();
        $avgClassGpa = $studentsData->where('gpa_4', '>', 0)->avg('gpa_4');
        $avgClassGpaFormatted = $avgClassGpa ? number_format($avgClassGpa, 2) : '0.00';

        return view('lecturer.advisor.class', compact(
            'class',
            'lecturer',
            'studentsData',
            'search',
            'filterStatus',
            'totalStudents',
            'totalWarnings',
            'totalDebts',
            'avgClassGpaFormatted',
            'currentSemester'
        ));
    }
}
