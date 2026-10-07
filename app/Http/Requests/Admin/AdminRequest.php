<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

abstract class AdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true
            && $this->user()->role === UserRole::Administrator;
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nama', 'email' => 'Email', 'password' => 'Password',
            'role' => 'Role', 'is_active' => 'Status akun', 'employee_number' => 'Nomor identitas guru',
            'phone' => 'Nomor kontak', 'profile_status' => 'Status profil', 'nis' => 'NIS',
            'classroom_id' => 'Kelas', 'teacher_id' => 'Guru', 'subject_id' => 'Mata pelajaran',
            'academic_year_id' => 'Tahun ajaran', 'grade' => 'Tingkat kelas', 'section' => 'Bagian kelas',
            'code' => 'Kode', 'description' => 'Deskripsi', 'starts_at' => 'Tanggal mulai',
            'ends_at' => 'Tanggal selesai', 'q' => 'Pencarian', 'page' => 'Halaman',
        ];
    }
}
