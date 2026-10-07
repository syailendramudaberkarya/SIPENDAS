<?php

use App\Models\Schedule;
use Database\Seeders\DemoScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('demo schedules are idempotent and do not overlap a teacher or classroom', function () {
    $this->seed(DemoScheduleSeeder::class);
    $this->seed(DemoScheduleSeeder::class);
    expect(Schedule::count())->toBe(9);

    $records = Schedule::with('teachingAssignment')->get();
    foreach ($records as $schedule) {
        $conflicting = $records->contains(fn ($other) => $other->id !== $schedule->id
            && $other->day_of_week === $schedule->day_of_week
            && $other->starts_at < $schedule->ends_at && $other->ends_at > $schedule->starts_at
            && ($other->teachingAssignment->teacher_id === $schedule->teachingAssignment->teacher_id
                || $other->teachingAssignment->classroom_id === $schedule->teachingAssignment->classroom_id));
        expect($conflicting)->toBeFalse();
    }
});

test('demo schedules are refused in production', function () {
    $this->app['env'] = 'production';
    $this->seed(DemoScheduleSeeder::class);
})->throws(LogicException::class);
