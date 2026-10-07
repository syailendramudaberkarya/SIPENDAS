<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Admin\Concerns\RendersCrudViews;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\User;

abstract class AccountController extends Controller
{
    use RendersCrudViews;

    protected function fields(?User $user = null, ?string $fixedRole = null): array
    {
        $roles = collect(UserRole::cases())->mapWithKeys(fn ($role) => [$role->value => $role->label()])->all();
        $role = $fixedRole ?? $user?->role?->value;
        $fields = [
            'name' => ['label' => 'Nama', 'value' => $user?->name, 'required' => true],
            'email' => ['label' => 'Email', 'value' => $user?->email, 'type' => 'email', 'required' => true],
            'role' => ['label' => 'Role', 'type' => $role ? 'hidden' : 'select', 'options' => $roles, 'value' => $role, 'required' => true],
            'password' => ['label' => $user?->exists ? 'Password baru (kosongkan jika tidak diubah)' : 'Password', 'type' => 'password', 'required' => ! $user?->exists, 'help' => 'Minimal 8 karakter, mengandung huruf dan angka.'],
            'password_confirmation' => ['label' => 'Konfirmasi password', 'type' => 'password'],
            'is_active' => ['label' => 'Akun aktif', 'type' => 'checkbox', 'value' => $user?->is_active ?? true],
        ];
        if ($role !== 'administrator') {
            if ($role === null || $role === 'teacher') {
                $fields['employee_number'] = ['label' => 'Nomor identitas guru', 'value' => $user?->teacher?->employee_number, 'group' => 'teacher'];
                $fields['phone'] = ['label' => 'Nomor kontak guru', 'value' => $user?->teacher?->phone, 'type' => 'tel', 'inputmode' => 'numeric', 'pattern' => '[0-9]*', 'digits_only' => true, 'group' => 'teacher'];
            }
            if ($role === null || $role === 'student') {
                $fields['nis'] = ['label' => 'NIS', 'value' => $user?->student?->nis, 'group' => 'student'];
                $classes = Classroom::with('academicYear')->where(function ($query) use ($user) {
                    $query->where(fn ($active) => $active->where('is_active', true)->whereHas('academicYear', fn ($year) => $year->where('is_active', true)));
                    if ($user?->student?->classroom_id) {
                        $query->orWhere('id', $user->student->classroom_id);
                    }
                })->orderBy('grade')->orderBy('section')->get();
                $fields['classroom_id'] = ['label' => 'Kelas siswa', 'type' => 'select', 'options' => $classes->mapWithKeys(fn ($class) => [$class->id => $class->name.' — '.$class->academicYear->name])->all(), 'value' => $user?->student?->classroom_id, 'group' => 'student'];
            }
            $fields['profile_status'] = ['label' => 'Status profil', 'type' => 'select', 'value' => $user?->teacher?->status?->value ?? $user?->student?->status?->value ?? 'active',
                'options' => $role === 'teacher' ? ['active' => 'Aktif', 'inactive' => 'Nonaktif'] : ['active' => 'Aktif', 'inactive' => 'Nonaktif', 'graduated' => 'Lulus (siswa)', 'transferred' => 'Pindah (siswa)'], 'group' => 'profile'];
        }

        return $fields;
    }
}
