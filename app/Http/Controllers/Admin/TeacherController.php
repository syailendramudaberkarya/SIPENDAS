<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Teacher;
use App\Services\AccountService;
use Illuminate\Http\Request;

class TeacherController extends AccountController
{
    protected string $resource = 'teachers';

    protected string $title = 'Guru';

    public function index(IndexRequest $request)
    {
        $q = $request->validated('q') ?? '';
        $records = Teacher::with('user')->when($q !== '', fn ($query) => $query->where(fn ($filter) => $filter
            ->where('employee_number', 'like', '%'.$q.'%')->orWhereHas('user', fn ($users) => $users->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%'))))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return $this->listing($records, ['user.name' => 'Nama', 'employee_number' => 'Identitas', 'user.email' => 'Email', 'phone' => 'Kontak', 'user.is_active' => 'Akun aktif'], $q);
    }

    public function create()
    {
        return $this->form(new Teacher, $this->fields(null, 'teacher'));
    }

    public function store(UserRequest $request, AccountService $accounts)
    {
        $accounts->save($request->validated(), $request->user());

        return to_route('administrator.teachers.index')->with('status', 'Guru dan akun berhasil ditambahkan.');
    }

    public function edit(Teacher $teacher)
    {
        return $this->form($teacher, $this->fields($teacher->user, 'teacher'));
    }

    public function update(UserRequest $request, Teacher $teacher, AccountService $accounts)
    {
        $accounts->save($request->validated(), $request->user(), $teacher->user);

        return to_route('administrator.teachers.index')->with('status', 'Guru berhasil diperbarui.');
    }

    public function destroy(Request $request, Teacher $teacher, AccountService $accounts)
    {
        $accounts->deactivate($teacher->user, $request->user());

        return to_route('administrator.teachers.index')->with('status', 'Guru dan akun berhasil dinonaktifkan.');
    }

    public function activate(Teacher $teacher, AccountService $accounts)
    {
        $accounts->activate($teacher->user);

        return to_route('administrator.teachers.index')->with('status', 'Guru dan akun berhasil diaktifkan.');
    }
}
