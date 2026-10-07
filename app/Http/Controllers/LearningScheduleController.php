<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleIndexRequest;
use App\Models\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearningScheduleController extends Controller
{
    public function index(ScheduleIndexRequest $request): View
    {
        Gate::authorize('viewAny', Schedule::class);
        $filters = $request->validated();
        $q = $filters['q'] ?? '';
        $day = $filters['day_of_week'] ?? null;
        $schedules = Schedule::visibleTo($request->user())
            ->with(['teachingAssignment.classroom', 'teachingAssignment.subject', 'teachingAssignment.teacher.user'])
            ->when($day !== null, fn ($query) => $query->where('day_of_week', $day))
            ->when($q !== '', fn ($query) => $query->whereHas('teachingAssignment', fn ($teaching) => $teaching->where(fn ($filter) => $filter
                ->whereHas('subject', fn ($subjects) => $subjects->where('name', 'like', '%'.$q.'%'))
                ->orWhereHas('classroom', fn ($classes) => $classes->where('name', 'like', '%'.$q.'%')))))
            ->orderBy('day_of_week')->orderBy('starts_at')->orderBy('id')->paginate(15)->withQueryString();

        return view('learning.schedules.index', compact('schedules', 'q', 'day'));
    }

    public function show(Schedule $schedule): View
    {
        Gate::authorize('view', $schedule);
        $schedule->load(['teachingAssignment.classroom.academicYear', 'teachingAssignment.subject', 'teachingAssignment.teacher.user']);

        return view('learning.schedules.show', compact('schedule'));
    }
}
