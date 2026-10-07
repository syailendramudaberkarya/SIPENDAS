<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RendersCrudViews;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcademicYearRequest;
use App\Http\Requests\Admin\IndexRequest;
use App\Models\AcademicYear;
use App\Services\MasterDataService;

class AcademicYearController extends Controller
{
    use RendersCrudViews;

    protected string $resource = 'academic-years';

    protected string $title = 'Tahun Ajaran';

    public function index(IndexRequest $request)
    {
        $q = $request->validated('q') ?? '';
        $records = AcademicYear::query()->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.$q.'%'))
            ->orderByDesc('starts_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return $this->listing($records, ['name' => 'Tahun ajaran', 'starts_at' => 'Mulai', 'ends_at' => 'Selesai', 'is_active' => 'Aktif'], $q);
    }

    public function create()
    {
        return $this->form(new AcademicYear, $this->fields());
    }

    public function store(AcademicYearRequest $request, MasterDataService $master)
    {
        $master->saveAcademicYear($request->validated());

        return to_route('administrator.academic-years.index')->with('status', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function edit(AcademicYear $academicYear)
    {
        return $this->form($academicYear, $this->fields());
    }

    public function update(AcademicYearRequest $request, AcademicYear $academicYear, MasterDataService $master)
    {
        $master->saveAcademicYear($request->validated(), $academicYear);

        return to_route('administrator.academic-years.index')->with('status', 'Tahun ajaran berhasil diperbarui.');
    }

    public function destroy(AcademicYear $academicYear, MasterDataService $master)
    {
        $master->delete($academicYear);

        return to_route('administrator.academic-years.index')->with('status', 'Tahun ajaran berhasil dihapus.');
    }

    private function fields(): array
    {
        return [
            'name' => ['label' => 'Tahun ajaran', 'required' => true, 'help' => 'Contoh: 2026/2027.'],
            'starts_at' => ['label' => 'Tanggal mulai', 'type' => 'date', 'required' => true],
            'ends_at' => ['label' => 'Tanggal selesai', 'type' => 'date', 'required' => true],
            'is_active' => ['label' => 'Tahun ajaran aktif', 'type' => 'checkbox', 'help' => 'Mengaktifkan tahun ini akan menonaktifkan tahun ajaran lain.'],
        ];
    }
}
