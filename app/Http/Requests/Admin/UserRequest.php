<?php

namespace App\Http\Requests\Admin;

use App\Enums\StudentStatus;
use App\Enums\TeacherStatus;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends AdminRequest
{
    public function account(): ?User
    {
        return $this->route('user') ?? $this->route('teacher')?->user ?? $this->route('student')?->user;
    }

    protected function prepareForValidation(): void
    {
        $role = match (true) {
            $this->routeIs('administrator.teachers.*') => UserRole::Teacher->value,
            $this->routeIs('administrator.students.*') => UserRole::Student->value,
            $this->routeIs('administrator.users.*') => UserRole::Administrator->value,
            default => $this->input('role'),
        };
        $this->merge(['role' => $role]);
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        $user = $this->account();
        $teacher = $this->route('teacher') ?? $user?->teacher;
        $student = $this->route('student') ?? $user?->student;
        $role = $this->input('role');
        $roleRules = ['required', Rule::enum(UserRole::class)];
        if ($user) {
            $roleRules[] = Rule::in([$user->role->value]);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'max:255', 'confirmed', Password::defaults()],
            'role' => $roleRules,
            'is_active' => ['required', 'boolean'],
            'employee_number' => ['exclude_unless:role,teacher', 'nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9.\/-]+$/', Rule::unique(Teacher::class)->ignore($teacher)],
            'phone' => ['exclude_unless:role,teacher', 'nullable', 'string', 'max:25', 'regex:/^[0-9]+$/'],
            'profile_status' => ['exclude_if:role,administrator', 'required', Rule::enum($role === 'teacher' ? TeacherStatus::class : StudentStatus::class)],
            'nis' => ['exclude_unless:role,student', 'required', 'string', 'max:30', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique(Student::class)->ignore($student)],
            'classroom_id' => ['exclude_unless:role,student', Rule::requiredIf($role === 'student' && $this->input('profile_status') === 'active'), 'nullable', 'integer',
                Rule::exists('classrooms', 'id')->where(function ($query) use ($student) {
                    $query->where(function ($classes) use ($student) {
                        $classes->where(function ($active) {
                            $active->where('is_active', true)->whereIn('academic_year_id', AcademicYear::select('id')->where('is_active', true));
                        });
                        if ($student?->classroom_id) {
                            $classes->orWhere('id', $student->classroom_id);
                        }
                    });
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return ['role.in' => 'Role akun existing tidak boleh diubah. Kelola profil sesuai role akun.',
            'nis.regex' => 'NIS hanya boleh berisi huruf, angka, dan tanda hubung.',
            'phone.regex' => 'Nomor kontak guru hanya boleh berisi angka.'];
    }
}
