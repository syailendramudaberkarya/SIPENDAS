<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Http\Request;

class UserController extends AccountController
{
    protected string $resource = 'users';

    protected string $title = 'Admin';

    public function index(IndexRequest $request)
    {
        $q = $request->validated('q') ?? '';
        $records = User::query()->where('role', UserRole::Administrator)
            ->when($q !== '', fn ($query) => $query->where(fn ($filter) => $filter
                ->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%')))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();

        return $this->listing($records, ['name' => 'Nama', 'email' => 'Email', 'is_active' => 'Akun aktif'], $q);
    }

    public function create()
    {
        return $this->form(new User, $this->fields(fixedRole: UserRole::Administrator->value));
    }

    public function store(UserRequest $request, AccountService $accounts)
    {
        $accounts->save($request->validated(), $request->user());

        return to_route('administrator.users.index')->with('status', 'Admin berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->ensureAdministrator($user);

        return $this->form($user, $this->fields($user, UserRole::Administrator->value));
    }

    public function update(UserRequest $request, User $user, AccountService $accounts)
    {
        $this->ensureAdministrator($user);
        $accounts->save($request->validated(), $request->user(), $user);

        return to_route('administrator.users.index')->with('status', 'Admin berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user, AccountService $accounts)
    {
        $this->ensureAdministrator($user);
        $accounts->deactivate($user, $request->user());

        return to_route('administrator.users.index')->with('status', 'Akun Admin berhasil dinonaktifkan.');
    }

    public function activate(User $user, AccountService $accounts)
    {
        $this->ensureAdministrator($user);
        $accounts->activate($user);

        return to_route('administrator.users.index')->with('status', 'Akun Admin berhasil diaktifkan.');
    }

    private function ensureAdministrator(User $user): void
    {
        abort_unless($user->role === UserRole::Administrator, 404);
    }
}
