<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class AcademicYearRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/', Rule::unique('academic_years')->ignore($this->route('academic_year'))],
            'starts_at' => ['required', 'date_format:Y-m-d'],
            'ends_at' => ['required', 'date_format:Y-m-d'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($this->input('ends_at') <= $this->input('starts_at')) {
                $validator->errors()->add('ends_at', 'Tanggal selesai harus setelah tanggal mulai.');
            }
            [$start, $end] = explode('/', $this->input('name'));
            if ((int) $end !== (int) $start + 1) {
                $validator->errors()->add('name', 'Tahun ajaran harus terdiri dari dua tahun berurutan, misalnya 2026/2027.');
            }
        }];
    }
}
