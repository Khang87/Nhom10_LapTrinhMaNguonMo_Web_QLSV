<?php

namespace App\Http\Controllers;

use App\Models\CurriculumFramework;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentGradeController extends Controller
{
    /**
     * Display student grades categorized by Curriculum Framework across 8 semesters.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $student = $user->student;

        $activeSemester = (int) $request->input('semester', 1);
        if ($activeSemester < 1 || $activeSemester > 8) {
            $activeSemester = 1;
        }

        $curriculumBySemester = [];
        $semesterStats = [];
        $totalFrameworkCredits = 0;
        $totalEarnedCredits = 0;
        $totalFrameworkCourses = 0;
        $totalPassedCourses = 0;
        $totalFailedCourses = 0;
        $totalWeightedPoints4 = 0.0;
        $totalWeightedPoints10 = 0.0;
        $cumulativeGpa = 0.0;
        $cumulativeAvg10 = 0.0;

        if ($student) {
            // Lấy toàn bộ enrollment của sinh viên key theo course_id
            $studentEnrollments = $student->enrollments()
                ->with(['section.course', 'section.lecturer'])
                ->get()
                ->keyBy(function ($enrollment) {
                    return $enrollment->section?->course_id;
                });

            // Lấy chương trình khung của chuyên ngành sinh viên
            $curriculumItems = collect();
            if ($student->specialization_id) {
                $curriculumItems = CurriculumFramework::with('course')
                    ->where('specialization_id', $student->specialization_id)
                    ->orderBy('semester_order')
                    ->get();
            }

            if ($curriculumItems->isEmpty() && $student->specialization_name) {
                $curriculumItems = CurriculumFramework::with('course')
                    ->where('specialization_name', $student->specialization_name)
                    ->orderBy('semester_order')
                    ->get();
            }

            $totalFrameworkCourses = $curriculumItems->count();
            $totalFrameworkCredits = $curriculumItems->sum(fn($i) => $i->course?->credits ?? 3);

            for ($sem = 1; $sem <= 8; $sem++) {
                $items = $curriculumItems->where('semester_order', $sem)->values();
                $semTotalCredits = $items->sum(fn($i) => $i->course?->credits ?? 3);
                $semEarnedCredits = 0;
                $semPoints4 = 0.0;
                $semPoints10 = 0.0;
                $passedCount = 0;
                $failedCount = 0;
                $pendingCount = 0;

                $enrichedItems = $items->map(function ($item) use (
                    $studentEnrollments,
                    &$semEarnedCredits,
                    &$semPoints4,
                    &$semPoints10,
                    &$passedCount,
                    &$failedCount,
                    &$pendingCount
                ) {
                    $enrollment = $studentEnrollments->get($item->course_id);
                    $credits = $item->course?->credits ?? 3;

                    if ($enrollment && $enrollment->total_score_4 !== null) {
                        $isPassed = $enrollment->isPassed();
                        if ($isPassed) {
                            $passedCount++;
                            $semEarnedCredits += $credits;
                        } else {
                            $failedCount++;
                        }
                        $semPoints4 += ($enrollment->total_score_4 * $credits);
                        $semPoints10 += (($enrollment->total_score_10 ?? 0) * $credits);
                    } else {
                        $pendingCount++;
                    }

                    $section = $enrollment?->section;
                    $lecturer = $section?->lecturer;

                    return [
                        'item' => $item,
                        'course' => $item->course,
                        'enrollment' => $enrollment,
                        'section' => $section,
                        'lecturer' => $lecturer,
                    ];
                });

                $gradedCredits = ($passedCount + $failedCount) > 0
                    ? $items->filter(fn($i) => isset($studentEnrollments[$i->course_id]) && $studentEnrollments[$i->course_id]->total_score_4 !== null)
                            ->sum(fn($i) => $i->course?->credits ?? 3)
                    : 0;

                $curriculumBySemester[$sem] = $enrichedItems;
                $semesterStats[$sem] = [
                    'total_courses' => $items->count(),
                    'total_credits' => $semTotalCredits,
                    'earned_credits' => $semEarnedCredits,
                    'passed_count' => $passedCount,
                    'failed_count' => $failedCount,
                    'pending_count' => $pendingCount,
                    'gpa' => $gradedCredits > 0 ? round($semPoints4 / $gradedCredits, 2) : null,
                    'avg10' => $gradedCredits > 0 ? round($semPoints10 / $gradedCredits, 2) : null,
                ];

                $totalEarnedCredits += $semEarnedCredits;
                $totalPassedCourses += $passedCount;
                $totalFailedCourses += $failedCount;
                $totalWeightedPoints4 += $semPoints4;
                $totalWeightedPoints10 += $semPoints10;
            }

            $totalGradedCredits = ($totalPassedCourses + $totalFailedCourses) > 0
                ? $curriculumItems->filter(fn($i) => isset($studentEnrollments[$i->course_id]) && $studentEnrollments[$i->course_id]->total_score_4 !== null)
                        ->sum(fn($i) => $i->course?->credits ?? 3)
                : 0;

            $cumulativeGpa = $totalGradedCredits > 0 ? round($totalWeightedPoints4 / $totalGradedCredits, 2) : 0.0;
            $cumulativeAvg10 = $totalGradedCredits > 0 ? round($totalWeightedPoints10 / $totalGradedCredits, 2) : 0.0;
        }

        $completionRate = $totalFrameworkCredits > 0 ? round(($totalEarnedCredits / $totalFrameworkCredits) * 100, 1) : 0.0;

        $academicRank = match (true) {
            $cumulativeGpa >= 3.6 => 'Xuất sắc',
            $cumulativeGpa >= 3.2 => 'Giỏi',
            $cumulativeGpa >= 2.5 => 'Khá',
            $cumulativeGpa >= 2.0 => 'Trung bình',
            $totalEarnedCredits > 0 => 'Yếu',
            default => 'Chưa xếp loại',
        };

        // Lấy danh sách các đơn phúc khảo của sinh viên
        $myAppeals = $student
            ? \App\Models\GradeAppeal::with(['section.course'])
                ->where('student_id', $student->id)
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();

        return view('student.grades.index', compact(
            'student',
            'curriculumBySemester',
            'semesterStats',
            'activeSemester',
            'totalFrameworkCredits',
            'totalEarnedCredits',
            'totalFrameworkCourses',
            'totalPassedCourses',
            'totalFailedCourses',
            'cumulativeGpa',
            'cumulativeAvg10',
            'completionRate',
            'academicRank',
            'myAppeals'
        ));
    }

    /**
     * Sinh viên gửi đơn phúc khảo / thắc mắc điểm
     */
    public function storeAppeal(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            abort(403, 'Tài khoản không phải là sinh viên.');
        }

        $validated = $request->validate([
            'enrollment_id' => ['required', 'exists:enrollments,id'],
            'component' => ['required', 'in:PROCESS,MIDTERM,FINAL,TOTAL'],
            'desired_score' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $enrollment = Enrollment::where('id', $validated['enrollment_id'])
            ->where('student_id', $student->id)
            ->firstOrFail();

        $currentScore = match ($validated['component']) {
            'PROCESS' => $enrollment->process_score,
            'MIDTERM' => $enrollment->midterm_score,
            'FINAL' => $enrollment->final_score,
            'TOTAL' => $enrollment->total_score_10,
            default => 0,
        };

        // Kiểm tra xem đã gửi phúc khảo cho thành phần điểm này của lớp này chưa
        $existing = \App\Models\GradeAppeal::where('enrollment_id', $enrollment->id)
            ->where('component', $validated['component'])
            ->whereIn('status', ['PENDING', 'REVIEWED'])
            ->exists();

        if ($existing) {
            return back()->with('error', 'Bạn đã có một yêu cầu phúc khảo cho thành phần điểm này đang được xử lý.');
        }

        \App\Models\GradeAppeal::create([
            'student_id' => $student->id,
            'section_id' => $enrollment->section_id,
            'enrollment_id' => $enrollment->id,
            'component' => $validated['component'],
            'current_score' => $currentScore,
            'desired_score' => $validated['desired_score'] !== null ? (float) $validated['desired_score'] : null,
            'reason' => $validated['reason'],
            'status' => 'PENDING',
        ]);

        return back()->with('success', 'Đơn phúc khảo điểm đã được gửi thành công đến Giảng viên phụ trách môn học. Vui lòng theo dõi kết quả phản hồi.');
    }
}
