<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Models\Subject;
use App\Support\PrerequisiteRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $subjects = Subject::query()
            ->with('major')
            ->withCount(['prerequisites', 'dependentSubjects', 'concurrents', 'concurrentOf', 'studyPlans'])
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
        return view('admin.subjects.create', [
            'subject' => new Subject,
            'majors' => Major::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Subject::create($this->validatedData($request));

        return redirect()->route('admin.subjects.index')->with('success', 'Đã thêm môn học.');
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', [
            'subject' => $subject->load(['prerequisites', 'concurrents', 'concurrentOf']),
            'majors' => Major::orderBy('name')->get(),
            'otherSubjects' => Subject::whereKeyNot($subject->id)->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $data = $this->validatedData($request, $subject);
        $prerequisiteIds = $this->idList($request->input('prerequisite_ids'));
        $concurrentIds = $this->idList($request->input('concurrent_ids'));

        try {
            $prerequisiteIds->each(fn (int $id) => PrerequisiteRules::assertAcyclic($subject->id, $id));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $subject->update($data);
        $subject->prerequisites()->sync($prerequisiteIds);
        $subject->concurrents()->sync($concurrentIds);

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
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'total_sessions' => ['nullable', 'integer', 'min:1', 'max:255'],
            'prerequisite_ids' => ['nullable', 'array'],
            'prerequisite_ids.*' => ['integer', 'exists:subjects,id'],
            'concurrent_ids' => ['nullable', 'array'],
            'concurrent_ids.*' => ['integer', 'exists:subjects,id'],
        ]);
    }

    private function idList(mixed $value): Collection
    {
        return collect(is_array($value) ? $value : [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }
}
