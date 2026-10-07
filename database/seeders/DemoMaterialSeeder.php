<?php

namespace Database\Seeders;

use App\Models\TeachingAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class DemoMaterialSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Materi demo hanya boleh dibuat pada environment local atau testing.');
        }
        DB::transaction(function () {
            $this->call(DemoSchoolSeeder::class);
            $teachings = TeachingAssignment::active()->whereHas('teacher.user', fn ($query) => $query->whereIn('email', [
                'teacher@sipendas.example.test', 'guru1@sipendas.example.test', 'guru2@sipendas.example.test',
            ]))->with('subject')->get();
            foreach ($teachings as $teaching) {
                foreach (['published', 'draft'] as $status) {
                    $teaching->materials()->firstOrCreate(['title' => 'Materi demo '.$teaching->subject->name.' — '.$status], [
                        'content' => 'Baca penjelasan dasar dan diskusikan contoh sederhana bersama guru di kelas.',
                        'status' => $status, 'published_at' => $status === 'published' ? now()->subDay() : null,
                    ]);
                }
            }
        }, 3);
    }
}
