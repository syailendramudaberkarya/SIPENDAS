<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RendersCrudViews;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\ScheduleRequest;
use App\Models\Schedule;
use App\Models\TeachingAssignment;
use App\Services\MasterDataService;
use App\Services\ScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    use RendersCrudViews;

    protected string $resource = 'schedules';

    protected string $title = 'Jadwal Pembelajaran';

    public function index(IndexRequest $request): View
    {
        Gate::authorize('viewAny', Schedule::class);
        $q = $request->validated('q') ?? '';
        $records = Schedule::with(['teachingAssignment.teacher.user', 'teachingAssignment.classroom.academicYear', 'teachingAssignment.subject'])
            ->when($q !== '', fn ($query) => $query->whereHas('teachingAssignment', fn ($teaching) => $teaching->where(fn ($filter) => $filter
                ->whereHas('teacher.user', fn ($users) => $users->where('name', 'like', '%'.$q.'%'))
                ->orWhereHas('classroom', fn ($classes) => $classes->where('name', 'like', '%'.$q.'%'))
                ->orWhereHas('subject', fn ($subjects) => $subjects->where('name', 'like', '%'.$q.'%')))))
            ->orderBy('day_of_week')->orderBy('starts_at')->orderBy('id')->paginate(15)->withQueryString();

        return $this->listing($records, [
            'day_name' => 'Hari', 'start_time' => 'Mulai', 'end_time' => 'Selesai',
            'teachingAssignment.classroom.name' => 'Kelas', 'teachingAssignment.subject.name' => 'Mata pelajaran',
            'teachingAssignment.teacher.user.name' => 'Guru', 'teachingAssignment.classroom.academicYear.name' => 'Tahun ajaran',
        ], $q);
    }

    public function create(): View
    {
        Gate::authorize('create', Schedule::class);

        return $this->form(new Schedule, $this->fields());
    }

    public function store(ScheduleRequest $request, ScheduleService $schedules): RedirectResponse
    {
        Gate::authorize('create', Schedule::class);
        $schedules->save($request->validated());

        return to_route('administrator.schedules.index')->with('status', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(Schedule $schedule): View
    {
        Gate::authorize('update', $schedule);

        return $this->form($schedule, $this->fields($schedule));
    }

    public function update(ScheduleRequest $request, Schedule $schedule, ScheduleService $schedules): RedirectResponse
    {
        Gate::authorize('update', $schedule);
        $schedules->save($request->validated(), $schedule);

        return to_route('administrator.schedules.index')->with('status', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Schedule $schedule, MasterDataService $master): RedirectResponse
    {
        Gate::authorize('delete', $schedule);
        $master->delete($schedule);

        return to_route('administrator.schedules.index')->with('status', 'Jadwal berhasil dihapus.');
    }

    private function fields(?Schedule $schedule = null): array
    {
        $teaching = TeachingAssignment::with(['teacher.user', 'classroom.academicYear', 'subject'])
            ->where(function ($query) use ($schedule) {
                $query->where(fn ($active) => $active->active());
                if ($schedule) {
                    $query->orWhere('id', $schedule->teaching_assignment_id);
                }
            })->orderBy('classroom_id')->orderBy('subject_id')->get();

        return [
            'teaching_assignment_id' => ['label' => 'Penugasan mengajar', 'type' => 'select', 'required' => true,
                'options' => $teaching->mapWithKeys(fn ($item) => [$item->id => $item->classroom->name.' — '.$item->subject->name.' — '.$item->teacher->user->name.' ('.$item->classroom->academicYear->name.')'])->all(),
                'help' => 'Pilih penugasan aktif. Kelas, guru, dan mata pelajaran mengikuti penugasan ini.'],
            'day_of_week' => ['label' => 'Hari', 'type' => 'select', 'options' => Schedule::DAYS, 'required' => true],
            'starts_at' => ['label' => 'Jam mulai', 'type' => 'time', 'value' => $schedule?->start_time, 'required' => true],
            'ends_at' => ['label' => 'Jam selesai', 'type' => 'time', 'value' => $schedule?->end_time, 'required' => true],
        ];
    }
}
