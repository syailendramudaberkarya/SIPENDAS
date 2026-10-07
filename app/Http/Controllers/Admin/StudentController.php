<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Student;
use App\Services\AccountService;
use Illuminate\Http\Request;

class StudentController extends AccountController
{
    protected string $resource = 'students';

    protected string $title = 'Siswa';

    public function index(IndexRequest $request)
    {
        $q = $request->validated('q') ?? '';
        $records = Student::with(['user', 'classroom'])->when($q !== '', fn ($query) => $query->where(fn ($filter) => $filter
            ->where('nis', 'like', '%'.$q.'%')->orWhereHas('user', fn ($users) => $users->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%'))))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return $this->listing($records, ['user.name' => 'Nama', 'nis' => 'NIS', 'classroom.name' => 'Kelas', 'user.email' => 'Email', 'user.is_active' => 'Akun aktif'], $q);
    }

    public function create()
    {
        return $this->form(new Student, $this->fields(null, 'student'));
    }

    public function store(UserRequest $request, AccountService $accounts)
    {
        $accounts->save($request->validated(), $request->user());

        return to_route('administrator.students.index')->with('status', 'Siswa dan akun berhasil ditambahkan.');
    }

    public function edit(Student $student)
    {
        return $this->form($student, $this->fields($student->user, 'student'));
    }

    public function update(UserRequest $request, Student $student, AccountService $accounts)
    {
        $accounts->save($request->validated(), $request->user(), $student->user);

        return to_route('administrator.students.index')->with('status', 'Siswa berhasil diperbarui.');
    }

    public function destroy(Request $request, Student $student, AccountService $accounts)
    {
        $accounts->deactivate($student->user, $request->user());

        return to_route('administrator.students.index')->with('status', 'Siswa dan akun berhasil dinonaktifkan.');
    }

    public function activate(Student $student, AccountService $accounts)
    {
        $accounts->activate($student->user);

        return to_route('administrator.students.index')->with('status', 'Siswa dan akun berhasil diaktifkan.');
    }
}
