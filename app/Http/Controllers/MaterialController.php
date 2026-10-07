<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialIndexRequest;
use App\Http\Requests\MaterialRequest;
use App\Models\Material;
use App\Models\TeachingAssignment;
use App\Services\MaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    public function index(MaterialIndexRequest $request)
    {
        $q = $request->validated('q') ?? '';
        $materials = Material::query()
            ->when($request->user()->role->value !== 'student', fn ($query) => $query->withTrashed())
            ->visibleTo($request->user())->with(['teachingAssignment.classroom', 'teachingAssignment.subject'])
            ->when($q !== '', fn ($query) => $query->where(fn ($search) => $search->where('title', 'like', '%'.$q.'%')->orWhere('content', 'like', '%'.$q.'%')))
            ->latest()->orderByDesc('id')->paginate(15)->withQueryString();

        return view('learning.materials.index', compact('materials', 'q'));
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Material::class);

        return $this->form($request, new Material(['status' => 'draft']));
    }

    public function store(MaterialRequest $request, MaterialService $service)
    {
        $material = $service->save($request->user(), $request->validated());

        return to_route($request->user()->role->value.'.materials.show', $material)->with('status', 'Materi berhasil dibuat.');
    }

    public function show(Material $material)
    {
        Gate::authorize('view', $material);

        return view('learning.materials.show', compact('material'));
    }

    public function edit(Request $request, Material $material)
    {
        Gate::authorize('update', $material);

        return $this->form($request, $material);
    }

    public function update(MaterialRequest $request, Material $material, MaterialService $service)
    {
        $service->save($request->user(), $request->validated(), $material);

        return to_route($request->user()->role->value.'.materials.show', $material)->with('status', 'Materi berhasil diperbarui.');
    }

    public function destroy(Request $request, Material $material, MaterialService $service)
    {
        $service->archive($request->user(), $material);

        return to_route($request->user()->role->value.'.materials.index')->with('status', 'Materi berhasil diarsipkan.');
    }

    public function restore(Request $request, int $material, MaterialService $service)
    {
        $service->restore($request->user(), $material);

        return to_route($request->user()->role->value.'.materials.index')->with('status', 'Materi berhasil diaktifkan kembali.');
    }

    public function download(Material $material)
    {
        Gate::authorize('download', $material);
        abort_unless($material->attachment_disk === 'learning' && MaterialService::safePath($material->attachment_path), 404);
        abort_unless(Storage::disk('learning')->exists($material->attachment_path), 404);

        return Storage::disk('learning')->download($material->attachment_path, $material->attachment_original_name, [
            'Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store', 'Content-Security-Policy' => 'sandbox',
        ]);
    }

    private function form(Request $request, Material $material)
    {
        $teachings = TeachingAssignment::query()->active()
            ->when($request->user()->role->value !== 'administrator', fn ($query) => $query->where('teacher_id', $request->user()->teacher?->id))
            ->with(['teacher.user', 'classroom', 'subject'])->orderBy('classroom_id')->get();

        return view('learning.materials.form', compact('material', 'teachings'));
    }
}
