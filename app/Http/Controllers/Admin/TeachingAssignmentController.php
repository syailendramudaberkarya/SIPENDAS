<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RendersCrudViews;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\TeachingAssignmentRequest;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Services\MasterDataService;

class TeachingAssignmentController extends Controller
{
    use RendersCrudViews;

    protected string $resource = 'teaching-assignments';

    protected string $title = 'Penugasan Mengajar';

    public function index(IndexRequest $request)
    {
        $q = $request->validated('q') ?? '';
        $records = TeachingAssignment::with(['teacher.user', 'classroom.academicYear', 'subject'])
            ->when($q !== '', fn ($query) => $query->where(fn ($filter) => $filter
                ->whereHas('teacher.user', fn ($users) => $users->where('name', 'like', '%'.$q.'%'))
                ->orWhereHas('classroom', fn ($classes) => $classes->where('name', 'like', '%'.$q.'%'))
                ->orWhereHas('subject', fn ($subjects) => $subjects->where('name', 'like', '%'.$q.'%'))))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return $this->listing($records, ['teacher.user.name' => 'Guru', 'classroom.name' => 'Kelas', 'subject.name' => 'Mata pelajaran', 'classroom.academicYear.name' => 'Tahun ajaran'], $q);
    }

    public function create()
    {
        return $this->form(new TeachingAssignment, $this->fields());
    }

    public function store(TeachingAssignmentRequest $request, MasterDataService $master)
    {
        $master->saveTeachingAssignment($request->validated());

        return to_route('administrator.teaching-assignments.index')->with('status', 'Penugasan berhasil ditambahkan.');
    }

    public function edit(TeachingAssignment $teachingAssignment)
    {
        return $this->form($teachingAssignment, $this->fields());
    }

    public function update(TeachingAssignmentRequest $request, TeachingAssignment $teachingAssignment, MasterDataService $master)
    {
        $master->saveTeachingAssignment($request->validated(), $teachingAssignment);

        return to_route('administrator.teaching-assignments.index')->with('status', 'Penugasan berhasil diperbarui.');
    }

    public function destroy(TeachingAssignment $teachingAssignment, MasterDataService $master)
    {
        $master->delete($teachingAssignment);

        return to_route('administrator.teaching-assignments.index')->with('status', 'Penugasan berhasil dihapus.');
    }

    private function fields(): array
    {
        $teachers = Teacher::with('user')->where('status', 'active')->whereHas('user', fn ($query) => $query->where('role', 'teacher')->where('is_active', true))->get();
        $classes = Classroom::with('academicYear')->where('is_active', true)->whereHas('academicYear', fn ($query) => $query->where('is_active', true))->orderBy('grade')->orderBy('section')->get();

        return [
            'teacher_id' => ['label' => 'Guru', 'type' => 'select', 'options' => $teachers->mapWithKeys(fn ($teacher) => [$teacher->id => $teacher->user->name.' — '.$teacher->user->email])->all(), 'required' => true],
            'classroom_id' => ['label' => 'Kelas', 'type' => 'select', 'options' => $classes->mapWithKeys(fn ($class) => [$class->id => $class->name.' — '.$class->academicYear->name])->all(), 'required' => true],
            'subject_id' => ['label' => 'Mata pelajaran', 'type' => 'select', 'options' => Subject::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(), 'required' => true],
        ];
    }
}
