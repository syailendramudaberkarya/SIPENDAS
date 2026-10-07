<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\SafeProfilePhoto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user())],
            'avatar' => ['bail', 'nullable', 'file', 'max:2048', 'extensions:jpg,jpeg,png', 'mimes:jpg,jpeg,png', new SafeProfilePhoto],
            'remove_avatar' => ['nullable', 'boolean', Rule::prohibitedIf(fn () => $this->hasFile('avatar'))],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.', 'name.string' => 'Nama harus berupa teks.',
            'email.required' => 'Email wajib diisi.', 'email.email' => 'Format email tidak valid.', 'email.unique' => 'Email sudah digunakan.',
            'avatar.max' => 'Ukuran foto profil maksimal 2 MB.', 'avatar.extensions' => 'Ekstensi foto profil harus JPG, JPEG, atau PNG.',
            'avatar.mimes' => 'Jenis foto profil harus JPG, JPEG, atau PNG.',
            'remove_avatar.prohibited' => 'Pilih mengganti atau menghapus foto, bukan keduanya.',
        ];
    }
}
