<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\TeachingAssignment;
use App\Services\ScheduleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class DemoScheduleSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Jadwal demo hanya boleh dibuat pada environment local atau testing.');
        }

        DB::transaction(function () {
            $this->call(DemoSchoolSeeder::class);
            $assignments = TeachingAssignment::active()
                ->whereHas('teacher.user', fn ($query) => $query->whereIn('email', [
                    'teacher@sipendas.example.test', 'guru1@sipendas.example.test', 'guru2@sipendas.example.test',
                ]))
                ->whereHas('classroom', fn ($query) => $query->whereIn('name', ['Kelas 5A', 'Kelas 5B', 'Kelas 6A']))
                ->whereHas('subject', fn ($query) => $query->whereIn('name', ['Matematika', 'Bahasa Indonesia', 'IPA']))
                ->orderBy('classroom_id')->orderBy('subject_id')->get();
            $schedules = app(ScheduleService::class);

            foreach ($assignments as $index => $teaching) {
                $day = ($index % 3) + 1;
                $hour = 8 + intdiv($index, 3);
                $start = sprintf('%02d:00', $hour);
                if (Schedule::where('teaching_assignment_id', $teaching->id)->where('day_of_week', $day)->where('starts_at', $start.':00')->exists()) {
                    continue;
                }
                $schedules->save([
                    'teaching_assignment_id' => $teaching->id, 'day_of_week' => $day,
                    'starts_at' => $start, 'ends_at' => sprintf('%02d:45', $hour),
                ]);
            }
        }, 3);
    }
}
