<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\TeachingAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function save(array $data, ?Schedule $schedule = null): Schedule
    {
        $candidate = TeachingAssignment::with('classroom')->findOrFail($data['teaching_assignment_id']);
        $yearId = $candidate->classroom->academic_year_id;

        return DB::transaction(function () use ($data, $schedule, $yearId) {
            // Serialize schedule changes within the academic year on MySQL.
            $year = AcademicYear::whereKey($yearId)->lockForUpdate()->firstOrFail();
            $teaching = TeachingAssignment::whereKey($data['teaching_assignment_id'])->lockForUpdate()->firstOrFail();
            if (! $year->is_active || $teaching->classroom->academic_year_id !== $yearId
                || ! TeachingAssignment::active()->whereKey($teaching->id)->exists()) {
                throw ValidationException::withMessages(['teaching_assignment_id' => 'Penugasan mengajar tidak aktif atau telah berubah. Muat ulang form.']);
            }

            $record = $schedule ? Schedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail() : new Schedule;
            $startsAt = $data['starts_at'].':00';
            $endsAt = $data['ends_at'].':00';
            $conflict = Schedule::where('day_of_week', $data['day_of_week'])
                ->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)
                ->when($record->exists, fn ($query) => $query->where('id', '!=', $record->id))
                ->whereHas('teachingAssignment', fn ($query) => $query
                    ->whereHas('classroom', fn ($classes) => $classes->where('academic_year_id', $yearId))
                    ->where(fn ($scope) => $scope->where('teacher_id', $teaching->teacher_id)->orWhere('classroom_id', $teaching->classroom_id)))
                ->lockForUpdate()->first();

            if ($conflict) {
                throw ValidationException::withMessages(['starts_at' => 'Jadwal bentrok dengan jadwal kelas atau guru pada hari dan rentang waktu yang sama.']);
            }

            $record->fill([
                'teaching_assignment_id' => $teaching->id, 'day_of_week' => $data['day_of_week'],
                'starts_at' => $startsAt, 'ends_at' => $endsAt,
            ])->save();

            return $record;
        }, 3);
    }
}
