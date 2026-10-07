<?php

namespace App\Http\Requests\Admin;

use App\Models\TeachingAssignment;
use Illuminate\Validation\Rule;

class ScheduleRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'teaching_assignment_id' => ['bail', 'required', 'integer', Rule::exists('teaching_assignments', 'id')],
            'day_of_week' => ['bail', 'required', 'integer', 'between:1,7'],
            'starts_at' => ['bail', 'required', 'date_format:H:i'],
            'ends_at' => ['bail', 'required', 'date_format:H:i'],
        ];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'teaching_assignment_id' => 'Penugasan mengajar',
            'day_of_week' => 'Hari', 'starts_at' => 'Jam mulai', 'ends_at' => 'Jam selesai'];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($this->input('ends_at') <= $this->input('starts_at')) {
                $validator->errors()->add('ends_at', 'Jam selesai harus setelah jam mulai.');
            }
            if (! TeachingAssignment::active()->whereKey($this->input('teaching_assignment_id'))->exists()) {
                $validator->errors()->add('teaching_assignment_id', 'Penugasan mengajar tidak aktif atau tidak tersedia.');
            }
        }];
    }
}
