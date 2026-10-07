<?php

namespace App\Http\Controllers;

use App\Enums\PublicationStatus;
use App\Enums\StudentStatus;
use App\Http\Requests\DashboardRequest;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\LearningInformation;
use App\Models\Material;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->role->value.'.dashboard');
    }

    public function administrator(): View
    {
        return view('dashboards.administrator', ['statistics' => [
            'Siswa' => Student::count(),
            'Guru' => Teacher::count(),
            'Kelas' => Classroom::count(),
            'Mata pelajaran' => Subject::count(),
            'Materi' => Material::count(),
            'Tugas' => Assignment::count(),
        ]]);
    }

    public function teacher(DashboardRequest $request): View
    {
        $teachingQuery = TeachingAssignment::query()->active()->where('teacher_id', $request->user()->teacher?->id);
        $statistics = [
            'Kelas aktif' => (clone $teachingQuery)->distinct()->count('classroom_id'),
            'Mata pelajaran' => (clone $teachingQuery)->distinct()->count('subject_id'),
            'Materi tersedia' => Material::visibleTo($request->user())->count(),
            'Tugas terbit aktif' => $this->activeAssignments($request)->count(),
        ];
        $teachingAssignments = $teachingQuery->with(['classroom.academicYear', 'subject'])
            ->orderBy('classroom_id')->orderBy('subject_id')->paginate(10)->withQueryString();

        $todaySchedules = $this->todaySchedules($request);
        $latestMaterials = Material::visibleTo($request->user())->latest()->limit(5)->get();
        $latestAssignments = Assignment::visibleTo($request->user())->latest()->limit(5)->get();
        $latestInformation = LearningInformation::visibleTo($request->user())->latest()->limit(5)->get();

        return view('dashboards.teacher', compact('teachingAssignments', 'todaySchedules', 'latestMaterials', 'latestAssignments', 'latestInformation', 'statistics'));
    }

    public function student(DashboardRequest $request): View
    {
        $student = $request->user()->student?->load('classroom.academicYear');
        $hasActiveClass = $student?->status === StudentStatus::Active
            && $student->classroom?->is_active && $student->classroom->academicYear->is_active;
        $subjects = Subject::query()->when(! $hasActiveClass, fn ($query) => $query->whereKey([]))
            ->whereHas('teachingAssignments', fn ($query) => $query->active()->where('classroom_id', $student?->classroom_id))
            ->orderBy('name')->paginate(10, ['*'], 'subjects_page')->withQueryString();
        $activeAssignmentCount = $this->activeAssignments($request)->count();

        $todaySchedules = $this->todaySchedules($request);
        $latestMaterials = Material::visibleTo($request->user())->latest()->limit(5)->get();
        $latestAssignments = Assignment::visibleTo($request->user())->latest()->limit(5)->get();
        $latestInformation = LearningInformation::visibleTo($request->user())->latest()->limit(5)->get();

        return view('dashboards.student', compact('student', 'todaySchedules', 'latestMaterials', 'latestAssignments', 'latestInformation', 'subjects', 'hasActiveClass', 'activeAssignmentCount'));
    }

    private function activeAssignments(Request $request): Builder
    {
        return Assignment::visibleTo($request->user())->where('status', PublicationStatus::Published)
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('deadline_at')->orWhere('deadline_at', '>=', now()));
    }

    private function todaySchedules(Request $request): Collection
    {
        return Schedule::visibleTo($request->user())->where('day_of_week', now()->isoWeekday())
            ->with(['teachingAssignment.subject', 'teachingAssignment.classroom'])
            ->orderBy('starts_at')->orderBy('id')->limit(5)->get();
    }
}
