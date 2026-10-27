<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CurriculumFramework;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\StudentClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeController extends Controller
{
    /**
     * Quản lý điểm sinh viên theo phân cấp chuẩn:
     * Khoa -> Chuyên ngành -> Khóa tuyển sinh -> Lớp danh nghĩa -> Sinh viên & Bảng điểm
     */
    public function index(Request $request): View
    {
        $selectedFacultyId = $request->query('faculty_id');
        $selectedSpecId = $request->query('specialization_id');
        $selectedCohort = $request->query('cohort');
        $selectedClassCode = $request->query('class_code');
        $selectedStudentId = $request->query('student_id');
        $keyword = trim((string) $request->query('keyword'));

        // 1. Tự động phân giải các cấp cha nếu chọn sinh viên
        $student = null;
        if ($selectedStudentId) {
            $student = Student::with(['specialization.faculty', 'enrollments.section.course', 'enrollments.section.lecturer'])->find($selectedStudentId);
            if ($student) {
                $selectedClassCode = $selectedClassCode ?: $student->class_code;
                $selectedCohort = $selectedCohort ?: $student->cohort_year;
                $selectedSpecId = $selectedSpecId ?: $student->specialization_id;
                $selectedFacultyId = $selectedFacultyId ?: $student->specialization?->faculty_id;
            }
        }

        // Tự động phân giải nếu chọn lớp danh nghĩa
        if ($selectedClassCode && (!$selectedSpecId || !$selectedCohort || !$selectedFacultyId)) {
            $matchedClass = StudentClass::with('specialization.faculty')->where('class_code', $selectedClassCode)->first();
            if ($matchedClass) {
                $selectedCohort = $selectedCohort ?: $matchedClass->cohort_year;
                $selectedSpecId = $selectedSpecId ?: $matchedClass->specialization_id;
                $selectedFacultyId = $selectedFacultyId ?: $matchedClass->specialization?->faculty_id;
            }
        }

        if ($selectedSpecId && !$selectedFacultyId) {
            $specObj = Specialization::find($selectedSpecId);
            if ($specObj) {
                $selectedFacultyId = $specObj->faculty_id;
            }
        }

        $selectedFaculty = $selectedFacultyId ? Faculty::find($selectedFacultyId) : null;
        $selectedSpecialization = $selectedSpecId ? Specialization::with('faculty')->find($selectedSpecId) : null;

        // Dữ liệu Level 1: Danh sách Khoa (Faculties)
        $allFaculties = Faculty::orderBy('name')->get();
        $facultiesSummary = [];
        foreach ($allFaculties as $f) {
            $specCount = Specialization::where('faculty_id', $f->id)->count();
            $classesCount = StudentClass::whereHas('specialization', fn ($q) => $q->where('faculty_id', $f->id))->count();
            $studentsCount = Student::whereHas('specialization', fn ($q) => $q->where('faculty_id', $f->id))->count();

            $facultiesSummary[] = [
                'id' => $f->id,
                'code' => $f->code,
                'name' => $f->name,
                'specializations_count' => $specCount,
                'classes_count' => $classesCount,
                'students_count' => $studentsCount,
            ];
        }

        // Dữ liệu Level 2: Danh sách Chuyên ngành thuộc Khoa đã chọn
        $facultySpecs = collect();
        if ($selectedFacultyId) {
            $specs = Specialization::where('faculty_id', $selectedFacultyId)->orderBy('name')->get();
            foreach ($specs as $s) {
                $clsCount = StudentClass::where('specialization_id', $s->id)->count();
                $stuCount = Student::where('specialization_id', $s->id)->count();
                $facultySpecs->push((object) [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                    'classes_count' => $clsCount,
                    'students_count' => $stuCount,
                ]);
            }
        }

        // Dữ liệu Level 3: Danh sách Khóa tuyển sinh của Chuyên ngành đã chọn
        $allCohorts = array_values(array_unique(array_merge(
            Student::whereNotNull('cohort_year')->pluck('cohort_year')->toArray(),
            StudentClass::whereNotNull('cohort_year')->pluck('cohort_year')->toArray(),
            ['2026', '2023']
        )));
        rsort($allCohorts);

        $specCohortsSummary = [];
        if ($selectedSpecId) {
            foreach ($allCohorts as $ch) {
                $year = (int) $ch;
                $kNum = $year >= 2010 ? ($year - 2009) : null;
                $clsCount = StudentClass::where('specialization_id', $selectedSpecId)->where('cohort_year', $ch)->count();
                $stuCount = Student::where('specialization_id', $selectedSpecId)->where('cohort_year', $ch)->count();

                $specCohortsSummary[] = [
                    'cohort_year' => $ch,
                    'k_label' => $kNum ? "Niên khóa K{$kNum}" : "Khóa {$ch}",
                    'classes_count' => $clsCount,
                    'students_count' => $stuCount,
                ];
            }
        }

        // Dữ liệu Level 4: Danh sách Lớp danh nghĩa thuộc Chuyên ngành trong Khóa đó
        $classesSummary = collect();
        if ($selectedCohort) {
            $classesQuery = StudentClass::with(['lecturer', 'specialization'])
                ->where('cohort_year', $selectedCohort);
            if ($selectedSpecId) {
                $classesQuery->where('specialization_id', $selectedSpecId);
            }
            $classesSummary = $classesQuery->orderBy('class_code')->get();
        }

        // Dữ liệu Level 5: Danh sách sinh viên trong lớp (hoặc tìm kiếm keyword)
        $allStudents = collect();
        if ($selectedClassCode || $keyword !== '') {
            $studentsQuery = Student::with('specialization');
            if ($selectedCohort) {
                $studentsQuery->where('cohort_year', $selectedCohort);
            }
            if ($selectedClassCode) {
                $studentsQuery->where('class_code', $selectedClassCode);
            }
            if ($keyword !== '') {
                $studentsQuery->where(function ($q) use ($keyword) {
                    $q->where('student_code', 'like', "%{$keyword}%")
                      ->orWhere('full_name', 'like', "%{$keyword}%");
                });
            }
        }

        $curriculumBySemester = [];
        $semesterStats = [];
        $cumulativeGpa = 0.0;
        $cumulativeAvg10 = 0.0;
        $totalEarnedCredits = 0;
        $totalFrameworkCredits = 0;
        $totalFrameworkCourses = 0;
        $totalPassedCourses = 0;
        $totalFailedCourses = 0;
        $totalWeightedPoints4 = 0.0;
        $totalWeightedPoints10 = 0.0;

        if ($student) {
            // Lấy toàn bộ enrollment của sinh viên key theo course_id
            $studentEnrollments = $student->enrollments->keyBy(function ($enrollment) {
                return $enrollment->section?->course_id;
            });

            // Lấy chương trình khung của chuyên ngành sinh viên
            $curriculumItems = CurriculumFramework::with('course')
                ->where('specialization_id', $student->specialization_id)
                ->orderBy('semester_order')
                ->get();

            if ($curriculumItems->isEmpty()) {
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

                    $section = $enrollment?->section ?? CourseSection::with('lecturer')->where('course_id', $item->course_id)->first();
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
            $cumulativeGpa > 0 => 'Yếu / Cảnh báo',
            default => 'Chưa tích lũy',
        };

        $activeSemester = (int) $request->query('semester', 1);
        if ($activeSemester < 1 || $activeSemester > 8) {
            $activeSemester = 1;
        }

        return view('admin.grades.index', compact(
            'selectedFacultyId',
            'selectedFaculty',
            'selectedSpecId',
            'selectedSpecialization',
            'selectedCohort',
            'selectedClassCode',
            'facultiesSummary',
            'facultySpecs',
            'specCohortsSummary',
            'allFaculties',
            'allCohorts',
            'classesSummary',
            'keyword',
            'allStudents',
            'student',
            'curriculumBySemester',
            'semesterStats',
            'cumulativeGpa',
            'cumulativeAvg10',
            'totalEarnedCredits',
            'totalFrameworkCredits',
            'totalFrameworkCourses',
            'totalPassedCourses',
            'totalFailedCourses',
            'completionRate',
            'academicRank',
            'activeSemester'
        ));
    }

    /**
     * Tùy chỉnh / nhập điểm cho sinh viên (QT, GK, CK)
     */
    public function updateScore(Request $request): RedirectResponse
    {
        // Tự động tìm lại course_id nếu chỉ có course_code được gửi lên
        if (!$request->filled('course_id') && $request->filled('course_code')) {
            $matchedCourse = Course::where('course_code', trim($request->input('course_code')))->first();
            if ($matchedCourse) {
                $request->merge(['course_id' => $matchedCourse->id]);
            }
        }

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'process_score' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'midterm_score' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'final_score' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'semester_order' => ['nullable', 'integer', 'min:1', 'max:8'],
        ], [
            'student_id.required' => 'Không tìm thấy thông tin sinh viên.',
            'student_id.exists' => 'Sinh viên không tồn tại trong hệ thống.',
            'course_id.required' => 'Không tìm thấy thông tin môn học cần nhập điểm.',
            'course_id.exists' => 'Môn học không tồn tại trong hệ thống.',
            'process_score.numeric' => 'Điểm quá trình phải là số từ 0 đến 10.',
            'process_score.min' => 'Điểm quá trình không được nhỏ hơn 0.',
            'process_score.max' => 'Điểm quá trình không được lớn hơn 10.',
            'midterm_score.numeric' => 'Điểm giữa kỳ phải là số từ 0 đến 10.',
            'midterm_score.min' => 'Điểm giữa kỳ không được nhỏ hơn 0.',
            'midterm_score.max' => 'Điểm giữa kỳ không được lớn hơn 10.',
            'final_score.numeric' => 'Điểm cuối kỳ phải là số từ 0 đến 10.',
            'final_score.min' => 'Điểm cuối kỳ không được nhỏ hơn 0.',
            'final_score.max' => 'Điểm cuối kỳ không được lớn hơn 10.',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $course = Course::findOrFail($validated['course_id']);

        // Tìm enrollment hiện có của sinh viên cho môn học này
        $enrollment = Enrollment::where('student_id', $student->id)
            ->whereHas('section', fn ($q) => $q->where('course_id', $course->id))
            ->first();

        if (!$enrollment) {
            // Nếu chưa có lớp học phần nào, tìm hoặc tạo một section
            $section = CourseSection::where('course_id', $course->id)->first();
            if (!$section) {
                $section = CourseSection::create([
                    'section_code' => '0101' . rand(10000000, 99999999),
                    'course_id' => $course->id,
                    'semester_code' => 'HK1_2026_2027',
                    'specialization_name' => $student->specialization_name,
                    'max_capacity' => 60,
                    'day_of_week' => 2,
                    'start_period' => 1,
                    'end_period' => 3,
                    'room_name' => 'B201',
                ]);
            }

            $enrollment = Enrollment::create([
                'student_id' => $student->id,
                'section_id' => $section->id,
                'registration_type' => 'FIRST_TIME',
            ]);
        }

        // Cập nhật các cột điểm trực tiếp từ form gửi lên (nếu có nhập thì cập nhật, nếu xóa rỗng thì về null)
        $enrollment->process_score = $request->filled('process_score') ? (float) $validated['process_score'] : null;
        $enrollment->midterm_score = $request->filled('midterm_score') ? (float) $validated['midterm_score'] : null;
        $enrollment->final_score = $request->filled('final_score') ? (float) $validated['final_score'] : null;

        // Tính toán tổng kết và thang điểm A B C D F
        $enrollment->calculateGrade();
        $enrollment->save();

        $sem = $validated['semester_order'] ?? 1;

        return redirect()->route('admin.grades.index', array_filter([
            'faculty_id' => $request->input('faculty_id', $student->specialization?->faculty_id),
            'specialization_id' => $request->input('specialization_id', $student->specialization_id),
            'cohort' => $request->input('cohort', $student->cohort_year),
            'class_code' => $request->input('class_code', $student->class_code),
            'student_id' => $student->id,
            'semester' => $sem,
        ]))->with('success', "Đã cập nhật điểm học phần [{$course->course_name}] cho sinh viên {$student->full_name}. " . ($enrollment->total_score_10 !== null ? "Điểm tổng kết: {$enrollment->total_score_10} ({$enrollment->letter_grade})" : "Chưa đủ điểm thành phần"));
    }
}
