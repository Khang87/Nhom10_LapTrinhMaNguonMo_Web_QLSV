<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Models\StudyPlan;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudyPlanController extends Controller
{
    public function index(Request $request): View
    {
        $majors = Major::with('department')->orderBy('name')->get();
        $major = $majors->firstWhere('id', $request->integer('major_id')) ?? $majors->first();
        $plans = $major
            ? StudyPlan::with('subject')->where('major_id', $major->id)->orderBy('plan_semester')->orderBy('id')->get()
            : collect();

        return view('admin.study-plans.index', [
            'majors' => $majors,
            'major' => $major,
            'plans' => $plans,
            'availableSubjects' => $major
                ? Subject::whereNotIn('id', $plans->pluck('subject_id'))->orderBy('code')->get()
                : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'major_id' => ['required', 'integer', 'exists:majors,id'],
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
                Rule::unique('study_plans')->where(
                    fn ($query) => $query->where('major_id', $request->input('major_id'))
                ),
            ],
            'plan_semester' => ['required', 'integer', 'between:1,8'],
            'kind' => ['required', Rule::in(StudyPlan::KINDS)],
        ]);

        StudyPlan::create($data);

        return redirect()
            ->route('admin.study-plans.index', ['major_id' => $data['major_id']])
            ->with('success', 'Đã thêm môn vào khung chương trình.');
    }

    public function sync(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'major_id' => ['required', 'integer', 'exists:majors,id'],
            'entries' => ['required', 'array'],
            'entries.*.plan_semester' => ['required', 'integer', 'between:1,8'],
            'entries.*.kind' => ['required', Rule::in(StudyPlan::KINDS)],
        ]);

        StudyPlan::where('major_id', $data['major_id'])
            ->whereIn('id', array_keys($data['entries']))
            ->get()
            ->each(function (StudyPlan $plan) use ($data): void {
                $plan->update($data['entries'][$plan->id]);
            });

        return redirect()
            ->route('admin.study-plans.index', ['major_id' => $data['major_id']])
            ->with('success', 'Đã cập nhật khung chương trình.');
    }

    public function destroy(StudyPlan $studyPlan): RedirectResponse
    {
        $majorId = $studyPlan->major_id;
        $studyPlan->delete();

        return redirect()
            ->route('admin.study-plans.index', ['major_id' => $majorId])
            ->with('success', 'Đã xóa môn khỏi khung chương trình.');
    }
}
