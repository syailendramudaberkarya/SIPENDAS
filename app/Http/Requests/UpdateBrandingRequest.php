<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Rules\SafeSiteLogo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true && $this->user()->role === UserRole::Administrator;
    }

    public function rules(): array
    {
        return [
            'site_title' => ['required', 'string', 'max:80'],
            'logo' => ['bail', 'nullable', 'file', 'max:2048', 'extensions:jpg,jpeg,png', 'mimes:jpg,jpeg,png', new SafeSiteLogo],
            'remove_logo' => ['nullable', 'boolean', Rule::prohibitedIf(fn () => $this->hasFile('logo'))],
        ];
    }

    public function messages(): array
    {
        return [
            'site_title.required' => 'Judul website wajib diisi.',
            'site_title.string' => 'Judul website harus berupa teks.',
            'site_title.max' => 'Judul website maksimal 80 karakter.',
            'logo.file' => 'Logo harus berupa file.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
            'logo.extensions' => 'Ekstensi logo harus JPG, JPEG, atau PNG.',
            'logo.mimes' => 'Jenis logo harus JPG, JPEG, atau PNG.',
            'remove_logo.boolean' => 'Pilihan hapus logo tidak valid.',
            'remove_logo.prohibited' => 'Pilih mengganti atau menghapus logo, bukan keduanya.',
        ];
    }
}
