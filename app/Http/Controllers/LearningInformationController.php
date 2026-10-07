<?php

namespace App\Http\Controllers;

use App\Http\Requests\LearningInformationIndexRequest;
use App\Http\Requests\LearningInformationRequest;
use App\Models\LearningInformation;
use App\Models\TeachingAssignment;
use App\Services\LearningInformationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearningInformationController extends Controller
{
    public function index(LearningInformationIndexRequest $request): View
    {
        $q = $request->validated('q') ?? '';
        $informationItems = LearningInformation::query()->visibleTo($request->user())->with(['teachingAssignment.classroom', 'teachingAssignment.subject'])
            ->when($q !== '', fn ($query) => $query->where(fn ($search) => $search->where('title', 'like', '%'.$q.'%')->orWhere('content', 'like', '%'.$q.'%')))
            ->latest()->orderByDesc('id')->paginate(15)->withQueryString();

        return view('learning.information.index', compact('informationItems', 'q'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', LearningInformation::class);

        return $this->form($request, new LearningInformation(['status' => 'draft']));
    }

    public function store(LearningInformationRequest $request, LearningInformationService $service): RedirectResponse
    {
        $information = $service->save($request->user(), $request->validated());

        return to_route($request->user()->role->value.'.information.show', $information)->with('status', 'Informasi berhasil dibuat.');
    }

    public function show(LearningInformation $information): View
    {
        Gate::authorize('view', $information);

        return view('learning.information.show', compact('information'));
    }

    public function edit(Request $request, LearningInformation $information): View
    {
        Gate::authorize('update', $information);

        return $this->form($request, $information);
    }

    public function update(LearningInformationRequest $request, LearningInformation $information, LearningInformationService $service): RedirectResponse
    {
        $service->save($request->user(), $request->validated(), $information);

        return to_route($request->user()->role->value.'.information.show', $information)->with('status', 'Informasi berhasil diperbarui.');
    }

    public function destroy(Request $request, LearningInformation $information, LearningInformationService $service): RedirectResponse
    {
        $service->archive($request->user(), $information);

        return to_route($request->user()->role->value.'.information.index')->with('status', 'Informasi berhasil diarsipkan.');
    }

    private function form(Request $request, LearningInformation $information): View
    {
        $teachings = TeachingAssignment::query()->active()
            ->when($request->user()->role->value !== 'administrator', fn ($query) => $query->where('teacher_id', $request->user()->teacher?->id))
            ->with(['teacher.user', 'classroom', 'subject'])->orderBy('classroom_id')->get();

        return view('learning.information.form', compact('information', 'teachings'));
    }
}
