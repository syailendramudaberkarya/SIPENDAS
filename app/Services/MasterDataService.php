<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\TeachingAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterDataService
{
    public function saveClassroom(array $data, ?Classroom $classroom = null): Classroom
    {
        return DB::transaction(function () use ($data, $classroom) {
            $record = $classroom ? Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail() : new Classroom;
            if ($record->exists && (int) $data['academic_year_id'] !== $record->academic_year_id
                && ($record->students()->exists() || $record->teachingAssignments()->exists())) {
                throw ValidationException::withMessages(['academic_year_id' => 'Tahun ajaran kelas yang sudah digunakan tidak boleh diubah. Buat kelas baru untuk tahun ajaran berikutnya.']);
            }
            $record->fill($data)->save();

            return $record;
        }, 3);
    }

    public function saveTeachingAssignment(array $data, ?TeachingAssignment $teaching = null): TeachingAssignment
    {
        return DB::transaction(function () use ($data, $teaching) {
            $record = $teaching ? TeachingAssignment::whereKey($teaching->id)->lockForUpdate()->firstOrFail() : new TeachingAssignment;
            $changed = $record->exists && collect(['teacher_id', 'classroom_id', 'subject_id'])->contains(fn ($key) => (int) $data[$key] !== $record->{$key});
            if ($changed && ($record->schedules()->exists() || $record->materials()->withTrashed()->exists()
                || $record->assignments()->withTrashed()->exists() || $record->learningInformation()->withTrashed()->exists())) {
                throw ValidationException::withMessages(['teacher_id' => 'Penugasan sudah digunakan. Buat penugasan baru agar kepemilikan konten lama tetap terjaga.']);
            }
            $record->fill($data)->save();

            return $record;
        }, 3);
    }

    public function saveAcademicYear(array $data, ?AcademicYear $year = null): AcademicYear
    {
        return DB::transaction(function () use ($data, $year) {
            AcademicYear::query()->lockForUpdate()->get();
            if ((bool) $data['is_active']) {
                AcademicYear::query()->when($year, fn ($query) => $query->where('id', '!=', $year->id))->update(['is_active' => false]);
            }
            $record = $year ? AcademicYear::findOrFail($year->id) : new AcademicYear;
            $record->fill($data)->save();

            return $record;
        }, 3);
    }

    public function delete(Model $record): void
    {
        try {
            DB::transaction(function () use ($record) {
                $record->newQuery()->whereKey($record->getKey())->lockForUpdate()->firstOrFail()->delete();
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23503'], true)) {
                throw ValidationException::withMessages(['resource' => 'Data masih digunakan dan tidak dapat dihapus. Nonaktifkan data jika diperlukan.']);
            }
            throw $exception;
        }
    }
}
