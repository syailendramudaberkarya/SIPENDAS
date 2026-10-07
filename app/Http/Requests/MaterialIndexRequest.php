<?php

namespace App\Http\Requests;

use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class MaterialIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Material::class);
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']];
    }

    public function messages(): array
    {
        return ['q.string' => 'Pencarian harus berupa teks.', 'q.max' => 'Pencarian maksimal 100 karakter.', 'page.integer' => 'Halaman harus berupa angka.', 'page.min' => 'Halaman tidak valid.'];
    }
}
