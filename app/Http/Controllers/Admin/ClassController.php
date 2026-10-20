<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $classes = StudentClass::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('class_code', 'like', "%{$search}%")
                        ->orWhere('class_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('class_code')
            ->paginate(10)
            ->withQueryString();

        return view('admin.classes.index', compact('classes', 'search'));
    }

    public function create(): View
    {
        return view('admin.classes.create', ['class' => new StudentClass]);
    }

    public function store(Request $request): RedirectResponse
    {
        StudentClass::create($this->validatedData($request));

        return redirect()->route('admin.classes.index')->with('success', 'Đã thêm lớp học.');
    }

    public function edit(StudentClass $class): View
    {
        return view('admin.classes.edit', compact('class'));
    }

    public function update(Request $request, StudentClass $class): RedirectResponse
    {
        $class->update($this->validatedData($request, $class));

        return redirect()->route('admin.classes.index')->with('success', 'Đã cập nhật lớp học.');
    }

    public function destroy(StudentClass $class): RedirectResponse
    {
        DB::transaction(function () use ($class) {
            $class->students()->update(['class_id' => null]);
            $class->delete();
        });

        return redirect()->route('admin.classes.index')->with('success', 'Đã xóa lớp học.');
    }

    private function validatedData(Request $request, ?StudentClass $class = null): array
    {
        return $request->validate([
            'class_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('classes', 'class_code')->ignore($class?->id),
            ],
            'class_name' => ['required', 'string', 'max:100'],
            'room' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
