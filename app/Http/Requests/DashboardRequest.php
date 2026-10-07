<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    public function rules(): array
    {
        return ['page' => ['nullable', 'integer', 'min:1'], 'subjects_page' => ['nullable', 'integer', 'min:1']];
    }

    public function messages(): array
    {
        return ['integer' => 'Halaman harus berupa angka.', 'min' => 'Halaman minimal 1.'];
    }
}
