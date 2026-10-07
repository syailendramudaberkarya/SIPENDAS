<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RendersCrudViews;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClassroomRequest;
use App\Http\Requests\Admin\IndexRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Services\MasterDataService;

class ClassroomController extends Controller
{
    use RendersCrudViews;

    protected string $resource = 'classrooms';

    protected string $title = 'Kelas / Rombel';

    public function index(IndexRequest $request)
    {
        $q = $request->validated('q') ?? '';
        $records = Classroom::with('academicYear')->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.$q.'%'))
            ->orderBy('grade')->orderBy('section')->orderBy('id')->paginate(15)->withQueryString();

        return $this->listing($records, ['name' => 'Kelas', 'academicYear.name' => 'Tahun ajaran', 'grade' => 'Tingkat', 'section' => 'Bagian', 'is_active' => 'Aktif'], $q);
    }

    public function create()
    {
        return $this->form(new Classroom, $this->fields());
    }

    public function store(ClassroomRequest $request, MasterDataService $master)
    {
        $master->saveClassroom($request->validated());

        return to_route('administrator.classrooms.index')->with('status', 'Kelas berhasil ditambahkan.');
    }

    public function edit(Classroom $classroom)
    {
        return $this->form($classroom, $this->fields());
    }

    public function update(ClassroomRequest $request, Classroom $classroom, MasterDataService $master)
    {
        $master->saveClassroom($request->validated(), $classroom);

        return to_route('administrator.classrooms.index')->with('status', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom, MasterDataService $master)
    {
        $master->delete($classroom);

        return to_route('administrator.classrooms.index')->with('status', 'Kelas berhasil dihapus.');
    }

    private function fields(): array
    {
        return [
            'academic_year_id' => ['label' => 'Tahun ajaran', 'type' => 'select', 'options' => AcademicYear::orderByDesc('starts_at')->pluck('name', 'id')->all(), 'required' => true],
            'name' => ['label' => 'Nama kelas', 'required' => true],
            'grade' => ['label' => 'Tingkat kelas', 'type' => 'select', 'options' => array_combine(range(1, 6), range(1, 6)), 'required' => true],
            'section' => ['label' => 'Bagian kelas', 'help' => 'Contoh A atau B; kosongkan jika tidak ada.'],
            'is_active' => ['label' => 'Kelas aktif', 'type' => 'checkbox', 'default' => true],
        ];
    }
}
