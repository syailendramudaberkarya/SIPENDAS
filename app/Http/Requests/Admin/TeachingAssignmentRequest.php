<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Validation\Rule;

class TeachingAssignmentRequest extends AdminRequest
{
    public function rules(): array
    {
        $teaching = $this->route('teaching_assignment');

        return [
            'teacher_id' => ['required', 'integer', Rule::exists('teachers', 'id')->where(fn ($query) => $query
                ->where('status', 'active')->whereIn('user_id', User::select('id')->where('role', 'teacher')->where('is_active', true)))],
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')->where(fn ($query) => $query
                ->where('is_active', true)->whereIn('academic_year_id', AcademicYear::select('id')->where('is_active', true)))],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')->where('is_active', true),
                Rule::unique('teaching_assignments', 'subject_id')->where('teacher_id', is_scalar($this->input('teacher_id')) ? $this->input('teacher_id') : 0)
                    ->where('classroom_id', is_scalar($this->input('classroom_id')) ? $this->input('classroom_id') : 0)->ignore($teaching)],
        ];
    }

    public function messages(): array
    {
        return ['subject_id.unique' => 'Kombinasi guru, kelas, dan mata pelajaran sudah terdaftar.'];
    }
}
