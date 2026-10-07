<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1'],
            'day_of_week' => ['nullable', 'integer', 'between:1,7']];
    }

    public function attributes(): array
    {
        return ['q' => 'Pencarian', 'page' => 'Halaman', 'day_of_week' => 'Hari'];
    }
}
