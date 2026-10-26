<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Major;
use App\Models\StudentClass;
use App\Models\User;
use App\Support\PersonLabels;
use App\Support\StudentExport;
use App\Support\StudentImport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filters = $this->filters($request);
        $students = $this->query($filters)->orderBy('name')->paginate(10)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'rows' => view('admin.students.rows', compact('students'))->render(),
                'pagination' => $students->render()->toHtml(),
                'total' => $students->total(),
            ]);
        }

        return view('admin.students.index', [
            'students' => $students,
            'filters' => $filters,
        ] + $this->filterOptions($filters));
    }

    public function create(): View
    {
        return view('admin.students.create', [
            'student' => new User(['role' => 'student']),
            'classes' => $this->classOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'student';
        $data['academic_status'] ??= 'STUDYING';
        $data['avatar'] = $this->storeAvatar($request);
        User::create($data);

        return redirect()->route('admin.students.index')->with('success', 'Đã thêm sinh viên.');
    }

    public function show(User $student): View
    {
        abort_unless($student->role === 'student', 404);
        $student->load(['studentClass.department', 'studentClass.major', 'subjects', 'grades.subject', 'attendances']);

        return view('admin.students.show', [
            'student' => $student,
            'presentCount' => $student->attendances->where('status', 'present')->count(),
        ]);
    }

    public function edit(User $student): View
    {
        abort_unless($student->role === 'student', 404);

        return view('admin.students.edit', [
            'student' => $student,
            'classes' => $this->classOptions(),
        ]);
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        $data = $this->validatedData($request, $student);
        unset($data['password']);
        if ($avatar = $this->storeAvatar($request)) {
            $this->deleteAvatar($student->avatar);
            $data['avatar'] = $avatar;
        }
        $student->update($data);

        return redirect()->route('admin.students.index')->with('success', 'Đã cập nhật sinh viên.');
    }

    public function destroy(User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);
        $this->deleteAvatar($student->avatar);
        $student->delete();

        return redirect()->route('admin.students.index')->with('success', 'Đã xóa sinh viên.');
    }

    public function export(Request $request): StreamedResponse
    {
        $students = $this->query($this->filters($request))->orderBy('name')->with('studentClass')->get();

        return $this->download(
            StudentExport::spreadsheet($students),
            'danh-sach-sinh-vien-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    public function template(): StreamedResponse
    {
        return $this->download(StudentExport::templateSpreadsheet(), 'mau-nhap-sinh-vien.xlsx');
    }

    public function importForm(): View
    {
        return view('admin.students.import', [
            'result' => session('import_result'),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => [
                'required',
                File::types(['xlsx'])->max(25 * 1024),
            ],
        ], [
            'file.required' => 'Hãy chọn file .xlsx cần nhập.',
            'file.types' => 'Chỉ nhận file Excel định dạng .xlsx.',
            'file.max' => 'File vượt quá 25 MB.',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $parsed = StudentImport::read($file);

        if (! StudentImport::headerMatches($parsed['header'])) {
            return $this->rejectImport('File không đúng mẫu. Hãy tải "mau-nhap-sinh-vien.xlsx" và điền vào cột có sẵn.');
        }

        if (count($parsed['rows']) > StudentImport::MAX_ROWS) {
            return $this->rejectImport('Mỗi lần chỉ nhập tối đa '.StudentImport::MAX_ROWS.' dòng.');
        }

        $result = StudentImport::persist($parsed['rows']);

        return redirect()
            ->route('admin.students.import')
            ->with('success', $result->summary())
            ->with('import_result', $result->toArray());
    }

    private function rejectImport(string $message): RedirectResponse
    {
        return redirect()->back()->withInput()->withErrors(['file' => $message]);
    }

    private function download(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /**
     * @return array{search: string, intake_year: int|null, department_id: int|null, major_id: int|null, class_id: int|string|null, academic_status: string|null}
     */
    private function filters(Request $request): array
    {
        $status = $request->query('academic_status');
        $classId = $request->query('class_id');

        return [
            'search' => trim((string) $request->query('search')),
            'intake_year' => $request->integer('intake_year') ?: null,
            'department_id' => $request->integer('department_id') ?: null,
            'major_id' => $request->integer('major_id') ?: null,
            'class_id' => $classId === 'none' ? 'none' : ($request->integer('class_id') ?: null),
            'academic_status' => in_array($status, PersonLabels::statusCodes(), true) ? $status : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        return User::query()
            ->where('role', 'student')
            ->with('studentClass')
            ->when($filters['search'] !== '', fn ($query) => $query->where(
                fn (Builder $query) => $query->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%")
                    ->orWhere('student_code', 'like', "%{$filters['search']}%"),
            ))
            ->when($filters['intake_year'], fn ($query, $year) => $query->whereHas(
                'studentClass',
                fn (Builder $class) => $class->where('intake_year', $year),
            ))
            ->when($filters['department_id'], fn ($query, $id) => $query->whereHas(
                'studentClass',
                fn (Builder $class) => $class->where('department_id', $id),
            ))
            ->when($filters['major_id'], fn ($query, $id) => $query->whereHas(
                'studentClass',
                fn (Builder $class) => $class->where('major_id', $id),
            ))
            ->when($filters['class_id'] === 'none', fn ($query) => $query->whereNull('class_id'))
            ->when(is_int($filters['class_id']), fn ($query) => $query->where('class_id', $filters['class_id']))
            ->when($filters['academic_status'], fn ($query, $status) => $query->where('academic_status', $status));
    }

    /**
     * Bộ lọc chỉ liệt kê giá trị đang tồn tại trong dữ liệu, tránh chọn phải tổ hợp rỗng.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function filterOptions(array $filters): array
    {
        $statuses = User::query()->where('role', 'student')->whereNotNull('academic_status')
            ->distinct()->orderBy('academic_status')->pluck('academic_status');

        return [
            'intakeYears' => StudentClass::whereHas('students')->whereNotNull('intake_year')
                ->distinct()->orderByDesc('intake_year')->pluck('intake_year'),
            'classOptions' => StudentClass::whereHas('students')->orderBy('class_code')->get(['id', 'class_code', 'class_name']),
            'departments' => Department::whereHas('classes.students')->orderBy('code')->get(),
            'majors' => Major::whereHas('classes.students')->orderBy('code')->get(),
            'activeStatuses' => $statuses->mapWithKeys(fn (string $code) => [$code => PersonLabels::status($code)]),
            'hasUnassigned' => User::where('role', 'student')->whereNull('class_id')->exists(),
        ];
    }

    private function classOptions()
    {
        return StudentClass::orderBy('class_code')->get();
    }

    private function storeAvatar(Request $request): ?string
    {
        return $request->hasFile('avatar')
            ? $request->file('avatar')->store('avatars', 'public')
            : null;
    }

    private function deleteAvatar(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?User $student = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($student?->id),
            ],
            'password' => [$student ? 'nullable' : 'required', 'string', 'min:8'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'student_code' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'student_code')->ignore($student?->id),
            ],
            'dob' => ['nullable', 'date', 'before:'.now()->subYears(15)->toDateString()],
            'gender' => ['nullable', Rule::in(PersonLabels::genderCodes())],
            'hometown' => ['nullable', 'string', 'max:200'],
            'phone' => ['nullable', 'regex:/^0[0-9]{8,10}$/'],
            'academic_status' => ['nullable', Rule::in(PersonLabels::statusCodes())],
            'avatar' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(2 * 1024),
            ],
        ], [
            'student_code.unique' => 'Mã sinh viên này đã được sử dụng.',
            'student_code.regex' => 'Mã sinh viên chỉ gồm chữ, số và dấu chấm, gạch nối.',
            'dob.before' => 'Ngày sinh phải cách đây ít nhất 15 năm.',
            'phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 và có 10–11 chữ số.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'academic_status.in' => 'Tình trạng học tập không hợp lệ.',
        ]);
    }
}
