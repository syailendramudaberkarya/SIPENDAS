<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignmentIndexRequest;
use App\Http\Requests\AssignmentRequest;
use App\Models\Assignment;
use App\Models\TeachingAssignment;
use App\Services\AssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AssignmentController extends Controller
{
    public function index(AssignmentIndexRequest $request)
    {
        $q = $request->validated('q') ?? '';
        $assignments = Assignment::query()->visibleTo($request->user())->with(['teachingAssignment.classroom', 'teachingAssignment.subject'])
            ->when($q !== '', fn ($query) => $query->where(fn ($search) => $search->where('title', 'like', '%'.$q.'%')->orWhere('description', 'like', '%'.$q.'%')))
            ->latest()->orderByDesc('id')->paginate(15)->withQueryString();

        return view('learning.assignments.index', compact('assignments', 'q'));
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Assignment::class);

        return $this->form($request, new Assignment(['status' => 'draft']));
    }

    public function store(AssignmentRequest $request, AssignmentService $service)
    {
        $assignment = $service->save($request->user(), $request->validated());

        return to_route($request->user()->role->value.'.assignments.show', $assignment)->with('status', 'Tugas berhasil dibuat.');
    }

    public function show(Assignment $assignment)
    {
        Gate::authorize('view', $assignment);

        return view('learning.assignments.show', compact('assignment'));
    }

    public function edit(Request $request, Assignment $assignment)
    {
        Gate::authorize('update', $assignment);

        return $this->form($request, $assignment);
    }

    public function update(AssignmentRequest $request, Assignment $assignment, AssignmentService $service)
    {
        $service->save($request->user(), $request->validated(), $assignment);

        return to_route($request->user()->role->value.'.assignments.show', $assignment)->with('status', 'Tugas berhasil diperbarui.');
    }

    public function destroy(Request $request, Assignment $assignment, AssignmentService $service)
    {
        $service->archive($request->user(), $assignment);

        return to_route($request->user()->role->value.'.assignments.index')->with('status', 'Tugas berhasil diarsipkan.');
    }

    public function download(Assignment $assignment)
    {
        Gate::authorize('download', $assignment);
        abort_unless($assignment->attachment_disk === 'learning' && AssignmentService::safePath($assignment->attachment_path), 404);
        abort_unless(Storage::disk('learning')->exists($assignment->attachment_path), 404);

        return Storage::disk('learning')->download($assignment->attachment_path, $assignment->attachment_original_name, [
            'Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store', 'Content-Security-Policy' => 'sandbox',
        ]);
    }

    private function form(Request $request, Assignment $assignment)
    {
        $teachings = TeachingAssignment::query()->active()
            ->when($request->user()->role->value !== 'administrator', fn ($query) => $query->where('teacher_id', $request->user()->teacher?->id))
            ->with(['teacher.user', 'classroom', 'subject'])->orderBy('classroom_id')->get();

        return view('learning.assignments.form', compact('assignment', 'teachings'));
    }
}
