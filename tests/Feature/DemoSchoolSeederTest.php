<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('school demo data creates coherent master data and can be seeded repeatedly', function () {
    $this->seed(DemoSchoolSeeder::class);
    $this->seed(DemoSchoolSeeder::class);

    expect(User::count())->toBe(10)
        ->and(Teacher::count())->toBe(3)
        ->and(Student::count())->toBe(6)
        ->and(Classroom::count())->toBe(3)
        ->and(Subject::count())->toBe(3)
        ->and(TeachingAssignment::count())->toBe(9)
        ->and(AcademicYear::where('is_active', true)->count())->toBe(1)
        ->and(Student::whereNull('classroom_id')->count())->toBe(0);
});

test('school demo data is refused in production', function () {
    $this->app['env'] = 'production';
    $this->seed(DemoSchoolSeeder::class);
})->throws(LogicException::class);
