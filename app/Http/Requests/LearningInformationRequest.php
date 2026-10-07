<?php

namespace App\Http\Requests;

use App\Enums\PublicationStatus;
use App\Models\LearningInformation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LearningInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('information')
            ? Gate::allows('update', $this->route('information'))
            : Gate::allows('create', LearningInformation::class);
    }

    public function rules(): array
    {
        return [
            'teaching_assignment_id' => ['required', 'integer', 'exists:teaching_assignments,id'],
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', 'max:50000'],
            'status' => ['required', Rule::enum(PublicationStatus::class)],
            'published_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.', 'string' => ':attribute harus berupa teks.',
            'integer' => ':attribute harus berupa angka.', 'exists' => ':attribute tidak ditemukan.',
            'max' => ':attribute melebihi batas yang diperbolehkan.', 'enum' => ':attribute tidak valid.',
            'date_format' => ':attribute harus berupa tanggal dan jam yang valid.',
        ];
    }

    public function attributes(): array
    {
        return ['teaching_assignment_id' => 'Penugasan mengajar', 'title' => 'Judul', 'content' => 'Konten', 'status' => 'Status', 'published_at' => 'Tanggal publikasi'];
    }
}
