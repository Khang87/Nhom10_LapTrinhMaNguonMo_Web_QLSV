<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $subjects = Subject::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            }))
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        return view('admin.subjects.index', compact('subjects', 'search'));
    }

    public function create(): View
    {
        return view('admin.subjects.create', ['subject' => new Subject]);
    }

    public function store(Request $request): RedirectResponse
    {
        Subject::create($this->validatedData($request));

        return redirect()->route('admin.subjects.index')->with('success', 'Đã thêm môn học.');
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $subject->update($this->validatedData($request, $subject));

        return redirect()->route('admin.subjects.index')->with('success', 'Đã cập nhật môn học.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return redirect()->route('admin.subjects.index')->with('success', 'Đã xóa môn học.');
    }

    private function validatedData(Request $request, ?Subject $subject = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('subjects', 'code')->ignore($subject?->id),
            ],
            'name' => ['required', 'string', 'max:100'],
            'credits' => ['required', 'integer', 'min:1', 'max:30'],
        ]);
    }
}
