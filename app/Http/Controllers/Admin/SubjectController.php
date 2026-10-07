<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RendersCrudViews;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\SubjectRequest;
use App\Models\Subject;
use App\Services\MasterDataService;

class SubjectController extends Controller
{
    use RendersCrudViews;

    protected string $resource = 'subjects';

    protected string $title = 'Mata Pelajaran';

    public function index(IndexRequest $request)
    {
        $q = $request->validated('q') ?? '';
        $records = Subject::query()->when($q !== '', fn ($query) => $query->where(fn ($filter) => $filter->where('name', 'like', '%'.$q.'%')->orWhere('code', 'like', '%'.$q.'%')))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();

        return $this->listing($records, ['name' => 'Mata pelajaran', 'code' => 'Kode', 'is_active' => 'Aktif'], $q);
    }

    public function create()
    {
        return $this->form(new Subject, $this->fields());
    }

    public function store(SubjectRequest $request)
    {
        Subject::create($request->validated());

        return to_route('administrator.subjects.index')->with('status', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function edit(Subject $subject)
    {
        return $this->form($subject, $this->fields());
    }

    public function update(SubjectRequest $request, Subject $subject)
    {
        $subject->update($request->validated());

        return to_route('administrator.subjects.index')->with('status', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Subject $subject, MasterDataService $master)
    {
        $master->delete($subject);

        return to_route('administrator.subjects.index')->with('status', 'Mata pelajaran berhasil dihapus.');
    }

    private function fields(): array
    {
        return [
            'name' => ['label' => 'Nama mata pelajaran', 'required' => true],
            'code' => ['label' => 'Kode mata pelajaran'],
            'description' => ['label' => 'Deskripsi', 'type' => 'textarea'],
            'is_active' => ['label' => 'Mata pelajaran aktif', 'type' => 'checkbox', 'default' => true],
        ];
    }
}
