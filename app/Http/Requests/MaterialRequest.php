<?php

namespace App\Http\Requests;

use App\Enums\PublicationStatus;
use App\Models\Material;
use App\Rules\SafeLearningAttachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('material')
            ? Gate::allows('update', $this->route('material'))
            : Gate::allows('create', Material::class);
    }

    public function rules(): array
    {
        return [
            'teaching_assignment_id' => ['required', 'integer', 'exists:teaching_assignments,id'],
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', 'max:50000'],
            'status' => ['required', Rule::enum(PublicationStatus::class)],
            'published_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'attachment' => ['bail', 'nullable', 'file', 'max:5120', 'extensions:pdf,jpg,jpeg,png', 'mimes:pdf,jpg,jpeg,png', new SafeLearningAttachment],
            'remove_attachment' => ['nullable', 'boolean', Rule::prohibitedIf(fn () => $this->hasFile('attachment'))],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.', 'string' => ':attribute harus berupa teks.',
            'integer' => ':attribute harus berupa angka.', 'exists' => ':attribute tidak ditemukan.',
            'max' => ':attribute melebihi batas yang diperbolehkan.', 'enum' => ':attribute tidak valid.',
            'date_format' => ':attribute harus berupa tanggal dan jam yang valid.',
            'file' => 'Lampiran harus berupa file.', 'extensions' => 'Ekstensi lampiran tidak diperbolehkan.',
            'mimes' => 'Jenis lampiran tidak diperbolehkan.', 'boolean' => ':attribute tidak valid.',
            'prohibited' => 'Pilih mengganti atau menghapus lampiran, bukan keduanya.',
        ];
    }

    public function attributes(): array
    {
        return ['teaching_assignment_id' => 'Penugasan mengajar', 'title' => 'Judul', 'content' => 'Konten', 'status' => 'Status', 'published_at' => 'Tanggal publikasi', 'attachment' => 'Lampiran', 'remove_attachment' => 'Hapus lampiran'];
    }
}
