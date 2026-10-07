<?php

namespace App\Http\Requests;

use App\Enums\PublicationStatus;
use App\Models\Assignment;
use App\Rules\SafeLearningAttachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('assignment')
            ? Gate::allows('update', $this->route('assignment'))
            : Gate::allows('create', Assignment::class);
    }

    public function rules(): array
    {
        return [
            'teaching_assignment_id' => ['required', 'integer', 'exists:teaching_assignments,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:50000'],
            'status' => ['required', Rule::enum(PublicationStatus::class)],
            'published_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'deadline_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
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

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('deadline_at')) {
                return;
            }
            $publication = $this->filled('published_at')
                ? Carbon::createFromFormat('Y-m-d\TH:i', $this->input('published_at'))->startOfMinute()
                : ($this->input('status') === 'published' ? now()->startOfMinute() : null);
            $deadline = Carbon::createFromFormat('Y-m-d\TH:i', $this->input('deadline_at'))->startOfMinute();
            if ($publication && $deadline->lte($publication)) {
                $validator->errors()->add('deadline_at', 'Batas waktu harus setelah tanggal publikasi.');
            }
        }];
    }

    public function attributes(): array
    {
        return ['teaching_assignment_id' => 'Penugasan mengajar', 'title' => 'Judul', 'description' => 'Deskripsi', 'status' => 'Status', 'published_at' => 'Tanggal publikasi', 'deadline_at' => 'Batas waktu', 'attachment' => 'Lampiran', 'remove_attachment' => 'Hapus lampiran'];
    }
}
