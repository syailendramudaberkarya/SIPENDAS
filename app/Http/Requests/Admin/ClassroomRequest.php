<?php

namespace App\Http\Requests\Admin;

use App\Models\Classroom;
use Illuminate\Validation\Rule;

class ClassroomRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('section') === null || is_string($this->input('section'))) {
            $this->merge(['section' => strtoupper(trim($this->input('section') ?? ''))]);
        }
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')],
            'name' => ['required', 'string', 'max:50'],
            'grade' => ['required', 'integer', 'between:1,6'],
            'section' => ['present', 'nullable', 'string', 'max:10', 'regex:/^[A-Z0-9]*$/'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $classroom = $this->route('classroom');
            $duplicate = Classroom::where('academic_year_id', $this->input('academic_year_id'))
                ->where('grade', $this->input('grade'))->where('section', $this->input('section'))
                ->when($classroom, fn ($query) => $query->where('id', '!=', $classroom->id))->exists();
            if ($duplicate) {
                $validator->errors()->add('section', 'Kombinasi tahun ajaran, tingkat, dan bagian kelas sudah digunakan.');
            }
            if ($classroom && (int) $this->input('academic_year_id') !== $classroom->academic_year_id
                && ($classroom->students()->exists() || $classroom->teachingAssignments()->exists())) {
                $validator->errors()->add('academic_year_id', 'Tahun ajaran kelas yang sudah digunakan tidak boleh diubah. Buat kelas baru untuk tahun ajaran berikutnya.');
            }
        }];
    }
}
