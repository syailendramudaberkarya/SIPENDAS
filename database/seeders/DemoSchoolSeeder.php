<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class DemoSchoolSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Data demo sekolah hanya boleh dibuat pada environment local atau testing.');
        }

        DB::transaction(function () {
            $this->call(DemoAccountSeeder::class);
            $admin = User::where('email', 'administrator@sipendas.example.test')->firstOrFail();
            $year = AcademicYear::where('is_active', true)->first();
            if (! $year) {
                $year = AcademicYear::firstOrCreate(['name' => '2026/2027'], [
                    'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30', 'is_active' => true,
                ]);
                if (! $year->is_active) {
                    $year->update(['is_active' => true]);
                }
            }

            $classes = collect([[5, 'A'], [5, 'B'], [6, 'A']])->map(fn ($data) => Classroom::firstOrCreate([
                'academic_year_id' => $year->id, 'grade' => $data[0], 'section' => $data[1],
            ], ['name' => 'Kelas '.$data[0].$data[1], 'is_active' => true]));
            $subjects = collect(['Matematika', 'Bahasa Indonesia', 'IPA'])->map(fn ($name) => Subject::firstOrCreate(['name' => $name], ['is_active' => true]));

            Student::where('user_id', User::where('email', 'student@sipendas.example.test')->firstOrFail()->id)
                ->whereNull('classroom_id')->update(['classroom_id' => $classes[0]->id]);

            $accounts = app(AccountService::class);
            for ($i = 1; $i <= 2; $i++) {
                $email = 'guru'.$i.'@sipendas.example.test';
                if (! User::where('email', $email)->exists()) {
                    $accounts->save([
                        'name' => 'Guru Contoh '.$i, 'email' => $email, 'password' => config('demo.password') ?: 'Demo12345',
                        'role' => UserRole::Teacher->value, 'is_active' => true, 'profile_status' => 'active',
                        'employee_number' => 'DEMO-G'.$i,
                    ], $admin);
                }
            }
            for ($i = 1; $i <= 5; $i++) {
                $email = 'siswa'.$i.'@sipendas.example.test';
                if (! User::where('email', $email)->exists()) {
                    $accounts->save([
                        'name' => 'Siswa Contoh '.$i, 'email' => $email, 'password' => config('demo.password') ?: 'Demo12345',
                        'role' => UserRole::Student->value, 'is_active' => true, 'profile_status' => 'active',
                        'nis' => 'DEMO-S'.$i, 'classroom_id' => $classes[($i - 1) % 3]->id,
                    ], $admin);
                }
            }

            $teachers = Teacher::whereHas('user', fn ($query) => $query->whereIn('email', [
                'teacher@sipendas.example.test', 'guru1@sipendas.example.test', 'guru2@sipendas.example.test',
            ]))->orderBy('id')->get();
            foreach ($classes as $classIndex => $classroom) {
                foreach ($subjects as $subjectIndex => $subject) {
                    TeachingAssignment::firstOrCreate([
                        'teacher_id' => $teachers[($classIndex + $subjectIndex) % $teachers->count()]->id,
                        'classroom_id' => $classroom->id, 'subject_id' => $subject->id,
                    ]);
                }
            }
        }, 3);
    }
}
