<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CurriculumFramework;
use App\Models\Lecturer;
use App\Models\Semester;
use App\Models\Specialization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseSectionManagementController extends Controller
{
    /**
     * Quản lý mở lớp học phần: Lọc theo Học kỳ & Chuyên ngành
     */
    public function index(Request $request): View
    {
        $semesters = Semester::orderBy('order_index')->get();
        $defaultSemCode = $semesters->first(fn($s) => $s->is_registration_open)?->semester_code
            ?? $semesters->last()?->semester_code;
        $selectedSemesterCode = $request->input('semester', $defaultSemCode);
        $selectedSemester = $semesters->firstWhere('semester_code', $selectedSemesterCode);

        $specializations = Specialization::orderBy('name')->get();
        $selectedSpecId = $request->input('specialization_id');
        $selectedSpec = null;
        if ($selectedSpecId) {
            $selectedSpec = $specializations->firstWhere('id', $selectedSpecId);
        }
        if (!$selectedSpec && $specializations->isNotEmpty()) {
            $selectedSpec = $specializations->first();
        }

        $selectedSemesterOrder = (int) $request->input('semester_order', 1);

        // Danh sách học phần theo chương trình khung của kỳ và ngành được chọn
        $frameworkCourses = collect();
        $frameworkCourseIds = collect();
        if ($selectedSpec) {
            $frameworkCourses = CurriculumFramework::with('course')
                ->where('specialization_id', $selectedSpec->id)
                ->where('semester_order', $selectedSemesterOrder)
                ->get()
                ->map(function ($fw) use ($selectedSemesterCode, $selectedSpec) {
                    $fw->opened_sections_count = CourseSection::where('semester_code', $selectedSemesterCode)
                        ->where('course_id', $fw->course_id)
                        ->where(function ($q) use ($selectedSpec) {
                            $q->where('specialization_name', $selectedSpec->name)
                              ->orWhereNull('specialization_name');
                        })
                        ->count();
                    return $fw;
                });
            $frameworkCourseIds = $frameworkCourses->pluck('course_id');
        }

        // Map course_id -> semester_order theo chuyên ngành đang chọn để hiển thị nhãn Kỳ trong bảng
        $curriculumMap = $selectedSpec
            ? CurriculumFramework::where('specialization_id', $selectedSpec->id)->pluck('semester_order', 'course_id')
            : collect();

        // KHỐI 2: Thể hiện các lớp học phần đã được mở ở kỳ đó
        // view_scope: 'current' (mặc định: lọc theo học kỳ khung đã chọn) hoặc 'all' (tất cả các lớp trong đợt)
        $viewScope = $request->input('view_scope', 'current');
        $sectionFilterSpec = $request->input('section_filter_spec', 'all');

        $sectionsQuery = CourseSection::with(['course', 'lecturer.specialization'])
            ->withCount('enrollments')
            ->where('semester_code', $selectedSemesterCode);

        // Nếu xem theo kỳ khung hiện tại và có môn học khung
        if ($viewScope === 'current' && $frameworkCourseIds->isNotEmpty()) {
            $sectionsQuery->whereIn('course_id', $frameworkCourseIds);
        }

        if ($sectionFilterSpec !== 'all' && !empty($sectionFilterSpec)) {
            $sectionsQuery->where('specialization_name', $sectionFilterSpec);
        }

        $sections = $sectionsQuery->orderBy('specialization_name')
            ->orderBy('day_of_week')
            ->orderBy('start_period')
            ->get();

        $totalSectionsInSemester = CourseSection::where('semester_code', $selectedSemesterCode)->count();
        $totalSectionsInCurrentFramework = CourseSection::where('semester_code', $selectedSemesterCode)
            ->whereIn('course_id', $frameworkCourseIds)
            ->count();

        $courses = Course::orderBy('course_name')->get();
        // Giảng viên thuộc chuyên ngành được ưu tiên
        $lecturers = Lecturer::with('specialization')->orderBy('full_name')->get();

        return view('admin.course_sections.index', compact(
            'semesters',
            'selectedSemesterCode',
            'selectedSemester',
            'specializations',
            'selectedSpec',
            'selectedSemesterOrder',
            'frameworkCourses',
            'sections',
            'totalSectionsInSemester',
            'totalSectionsInCurrentFramework',
            'viewScope',
            'sectionFilterSpec',
            'curriculumMap',
            'courses',
            'lecturers'
        ));
    }

    /**
     * Mở lớp học phần tự động theo Chương trình khung của Chuyên ngành & Kỳ học
     * Giảng viên được gán random theo đúng chuyên ngành của giảng viên
     */
    public function openByCurriculum(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'semester_code' => ['required', 'string', 'exists:semesters,semester_code'],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'semester_order' => ['required', 'integer', 'min:1', 'max:8'],
        ]);

        // Kiểm tra học kỳ có đang mở đợt đăng ký / mở lớp không
        $semester = Semester::where('semester_code', $validated['semester_code'])->first();
        if (!$semester || !$semester->is_registration_open) {
            return back()->with('error', "Đợt học kỳ [{$semester?->semester_name}] hiện ĐÃ ĐÓNG đăng ký. Không thể mở thêm lớp học phần mới!");
        }

        $specialization = Specialization::findOrFail($validated['specialization_id']);

        $frameworkCourses = CurriculumFramework::with('course')
            ->where('specialization_id', $specialization->id)
            ->where('semester_order', $validated['semester_order'])
            ->get();

        if ($frameworkCourses->isEmpty()) {
            return back()->with('error', "Không tìm thấy học phần nào trong chương trình khung Kỳ {$validated['semester_order']} của chuyên ngành [{$specialization->name}].");
        }

        // Lấy danh sách giảng viên thuộc chuyên ngành này
        $specLecturers = Lecturer::where('specialization_id', $specialization->id)->get();
        if ($specLecturers->isEmpty()) {
            // Fallback nếu chuyên ngành chưa có GV riêng: lấy tất cả giảng viên khoa CNTT
            $specLecturers = Lecturer::where('faculty_name', 'Khoa Công nghệ Thông tin')->get();
        }
        if ($specLecturers->isEmpty()) {
            $specLecturers = Lecturer::all();
        }

        $presetSlots = [
            ['day' => 2, 'start' => 1, 'end' => 3, 'room' => 'B201 - Cơ sở 140 Lê Trọng Tấn'],
            ['day' => 3, 'start' => 4, 'end' => 6, 'room' => 'A110 - Phòng máy tính'],
            ['day' => 4, 'start' => 7, 'end' => 9, 'room' => 'B202 - Cơ sở 140 Lê Trọng Tấn'],
            ['day' => 5, 'start' => 1, 'end' => 3, 'room' => 'C301 - Cơ sở 140 Lê Trọng Tấn'],
            ['day' => 6, 'start' => 4, 'end' => 6, 'room' => 'A102 - Phòng học lý thuyết'],
        ];

        $openedCount = 0;

        foreach ($frameworkCourses as $item) {
            $course = $item->course;
            if (!$course) continue;

            $existingCount = CourseSection::where('semester_code', $validated['semester_code'])
                ->where('course_id', $course->id)
                ->where('specialization_name', $specialization->name)
                ->count();

            // Mở 2 lớp học phần cho mỗi môn nếu chưa mở đủ
            $targetSections = 2;
            for ($opt = $existingCount; $opt < $targetSections; $opt++) {
                $slot = $presetSlots[($opt + $openedCount) % count($presetSlots)];
                // RANDOM giảng viên theo chuyên ngành của giảng viên
                $assignedLecturer = $specLecturers->isNotEmpty() ? $specLecturers->random() : null;

                $sectionCode = '0101' . rand(10000000, 99999999);
                CourseSection::create([
                    'section_code' => $sectionCode,
                    'course_id' => $course->id,
                    'lecturer_id' => $assignedLecturer?->id,
                    'semester_code' => $validated['semester_code'],
                    'specialization_name' => $specialization->name,
                    'max_capacity' => 60,
                    'day_of_week' => $slot['day'],
                    'start_period' => $slot['start'],
                    'end_period' => $slot['end'],
                    'room_name' => $slot['room'],
                ]);
                $openedCount++;
            }
        }

        return redirect()->route('admin.course-sections.index', [
            'semester' => $validated['semester_code'],
            'specialization_id' => $specialization->id,
            'semester_order' => $validated['semester_order'],
        ])->with('success', "Đã mở thành công {$openedCount} lớp học phần cho Kỳ {$validated['semester_order']} chuyên ngành [{$specialization->name}]. Giảng viên đã được gán ngẫu nhiên theo chuyên ngành!");
    }

    /**
     * Mở lớp học phần thủ công
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'lecturer_id' => ['nullable', 'exists:lecturers,id'],
            'semester_code' => ['required', 'string', 'exists:semesters,semester_code'],
            'specialization_id' => ['nullable', 'exists:specializations,id'],
            'max_capacity' => ['required', 'integer', 'min:10', 'max:150'],
            'day_of_week' => ['required', 'integer', 'min:2', 'max:8'],
            'start_period' => ['required', 'integer', 'min:1', 'max:12'],
            'end_period' => ['required', 'integer', 'min:1', 'max:12', 'gte:start_period'],
            'room_name' => ['required', 'string', 'max:100'],
        ]);

        // Kiểm tra học kỳ có đang mở đợt đăng ký / mở lớp không
        $semester = Semester::where('semester_code', $validated['semester_code'])->first();
        if (!$semester || !$semester->is_registration_open) {
            return back()->with('error', "Đợt học kỳ [{$semester?->semester_name}] hiện ĐÃ ĐÓNG đăng ký. Không thể tạo thêm lớp học phần mới!");
        }

        $specName = null;
        if (!empty($validated['specialization_id'])) {
            $spec = Specialization::find($validated['specialization_id']);
            $specName = $spec?->name;
        }

        $sectionCode = '0101' . rand(10000000, 99999999);

        CourseSection::create([
            'section_code' => $sectionCode,
            'course_id' => $validated['course_id'],
            'lecturer_id' => $validated['lecturer_id'] ?? null,
            'semester_code' => $validated['semester_code'],
            'specialization_name' => $specName,
            'max_capacity' => $validated['max_capacity'],
            'day_of_week' => $validated['day_of_week'],
            'start_period' => $validated['start_period'],
            'end_period' => $validated['end_period'],
            'room_name' => $validated['room_name'],
        ]);

        return redirect()->route('admin.course-sections.index', [
            'semester' => $validated['semester_code'],
            'specialization_id' => $validated['specialization_id'],
        ])->with('success', 'Đã tạo lớp học phần mới thành công.');
    }

    /**
     * Cập nhật thông tin Lớp học phần (Đổi giảng viên, phòng học, thứ, tiết, sĩ số)
     */
    public function update(Request $request, CourseSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'lecturer_id' => ['nullable', 'exists:lecturers,id'],
            'max_capacity' => ['required', 'integer', 'min:10', 'max:150'],
            'day_of_week' => ['required', 'integer', 'min:2', 'max:8'],
            'start_period' => ['required', 'integer', 'min:1', 'max:12'],
            'end_period' => ['required', 'integer', 'min:1', 'max:12', 'gte:start_period'],
            'room_name' => ['required', 'string', 'max:100'],
        ]);

        $section->update($validated);

        return back()->with('success', "Đã cập nhật thông tin lớp học phần [{$section->section_code}] thành công.");
    }

    /**
     * Xóa lớp học phần (Chỉ xóa khi chưa có SV hoặc xác nhận)
     */
    public function destroy(CourseSection $section): RedirectResponse
    {
        $enrolledCount = $section->enrollments()->count();
        if ($enrolledCount > 0) {
            return back()->with('error', "Không thể xóa lớp [{$section->section_code}]: Hiện đã có {$enrolledCount} sinh viên ghi danh trong lớp này!");
        }

        $code = $section->section_code;
        $section->delete();

        return back()->with('success', "Đã xóa lớp học phần [{$code}] thành công.");
    }
}
