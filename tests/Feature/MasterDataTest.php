<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    User::factory()->create(['email' => 'taken@example.test']);
    $this->actingAs($this->admin);
    $this->year = AcademicYear::create(['name' => '2026/2027', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30', 'is_active' => true]);
    $this->classroom = $this->year->classrooms()->create(['name' => 'Kelas 5A', 'grade' => 5, 'section' => 'A']);
    $this->teacher = Teacher::create(['user_id' => User::factory()->teacher()->create()->id]);
    $this->student = Student::create(['user_id' => User::factory()->create()->id, 'nis' => 'NIS0001', 'classroom_id' => $this->classroom->id]);
    $this->subject = Subject::create(['name' => 'Matematika', 'code' => 'MTK']);
    $this->teaching = TeachingAssignment::create(['teacher_id' => $this->teacher->id, 'classroom_id' => $this->classroom->id, 'subject_id' => $this->subject->id]);
    $this->teacherData = [
        'name' => 'Guru Contoh', 'email' => 'guru@example.test', 'password' => 'Guru12345',
        'password_confirmation' => 'Guru12345', 'is_active' => 1, 'profile_status' => 'active',
        'employee_number' => 'GURU001', 'phone' => '081234567890',
    ];
    $this->studentData = [
        'name' => 'Siswa Contoh', 'email' => 'siswa@example.test', 'password' => 'Siswa12345',
        'password_confirmation' => 'Siswa12345', 'is_active' => 1, 'profile_status' => 'active',
        'nis' => 'NIS0002', 'classroom_id' => $this->classroom->id,
    ];
});

test('administrator can open all master data listings and forms', function () {
    foreach ([
        'users' => $this->admin, 'teachers' => $this->teacher, 'students' => $this->student,
        'academic-years' => $this->year, 'classrooms' => $this->classroom,
        'subjects' => $this->subject, 'teaching-assignments' => $this->teaching,
    ] as $resource => $record) {
        $this->get(route('administrator.'.$resource.'.index'))->assertOk();
        $this->get(route('administrator.'.$resource.'.create'))->assertOk()->assertSee('name="_token"', false);
        $this->get(route('administrator.'.$resource.'.edit', $record))->assertOk()->assertSee('name="_method" value="PUT"', false);
    }
});

test('every master data action rejects nonadministrators', function (UserRole $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));
    foreach ([
        'users' => $this->admin, 'teachers' => $this->teacher, 'students' => $this->student,
        'academic-years' => $this->year, 'classrooms' => $this->classroom,
        'subjects' => $this->subject, 'teaching-assignments' => $this->teaching,
    ] as $resource => $record) {
        $prefix = 'administrator.'.$resource.'.';
        $this->get(route($prefix.'index'))->assertForbidden();
        $this->get(route($prefix.'create'))->assertForbidden();
        $this->get(route($prefix.'edit', $record))->assertForbidden();
        $this->post(route($prefix.'store'), [])->assertForbidden();
        $this->put(route($prefix.'update', $record), [])->assertForbidden();
        $this->delete(route($prefix.'destroy', $record))->assertForbidden();
    }
    foreach (['users' => $this->admin, 'teachers' => $this->teacher, 'students' => $this->student] as $resource => $record) {
        $this->patch(route('administrator.'.$resource.'.activate', $record))->assertForbidden();
    }
})->with([UserRole::Teacher, UserRole::Student]);

test('teacher creation stores the user and profile together with a hashed password', function () {
    $this->post(route('administrator.teachers.store'), [...$this->teacherData, 'role' => 'administrator'])
        ->assertRedirect(route('administrator.teachers.index'))->assertSessionHasNoErrors();

    $user = User::where('email', 'guru@example.test')->firstOrFail();
    expect($user->role)->toBe(UserRole::Teacher)
        ->and(Hash::check('Guru12345', $user->password))->toBeTrue()
        ->and($user->teacher->employee_number)->toBe('GURU001');
});

test('teacher update preserves a blank password and changes only validated fields', function () {
    $hash = $this->teacher->user->password;
    $this->put(route('administrator.teachers.update', $this->teacher), [
        ...$this->teacherData, 'password' => '', 'password_confirmation' => '', 'user_id' => $this->admin->id,
    ])->assertRedirect(route('administrator.teachers.index'))->assertSessionHasNoErrors();

    expect($this->teacher->refresh()->user->password)->toBe($hash)
        ->and($this->teacher->user->name)->toBe('Guru Contoh')
        ->and($this->teacher->user_id)->not->toBe($this->admin->id);
});

test('deactivating a teacher preserves teaching assignments and disables the account', function () {
    $this->delete(route('administrator.teachers.destroy', $this->teacher))->assertRedirect(route('administrator.teachers.index'));
    expect($this->teacher->refresh()->user->is_active)->toBeFalse()
        ->and($this->teacher->status->value)->toBe('inactive')
        ->and(TeachingAssignment::find($this->teaching->id))->not->toBeNull();
});

test('inactive teacher account can be activated again with its profile', function () {
    $this->delete(route('administrator.teachers.destroy', $this->teacher))->assertRedirect();

    $this->patch(route('administrator.teachers.activate', $this->teacher))
        ->assertRedirect(route('administrator.teachers.index'))
        ->assertSessionHas('status', 'Guru dan akun berhasil diaktifkan.');

    expect($this->teacher->refresh()->user->is_active)->toBeTrue()
        ->and($this->teacher->status->value)->toBe('active');
});

test('inactive account listing shows activate action instead of deactivate', function () {
    $user = User::factory()->administrator()->inactive()->create();

    $this->get(route('administrator.users.index'))->assertOk()
        ->assertSee(route('administrator.users.activate', $user), false)
        ->assertSee('Aktifkan kembali akun ini?')
        ->assertSee('>Aktifkan<', false);
});

test('student CRUD assigns a classroom and preserves data when deactivated', function () {
    $this->post(route('administrator.students.store'), $this->studentData)
        ->assertRedirect(route('administrator.students.index'))->assertSessionHasNoErrors();
    $student = Student::where('nis', 'NIS0002')->firstOrFail();
    expect($student->user->role)->toBe(UserRole::Student)->and($student->classroom_id)->toBe($this->classroom->id);

    $this->put(route('administrator.students.update', $student), [...$this->studentData, 'name' => 'Nama Diperbarui', 'password' => null])
        ->assertRedirect(route('administrator.students.index'))->assertSessionHasNoErrors();
    expect($student->refresh()->user->name)->toBe('Nama Diperbarui');

    $this->delete(route('administrator.students.destroy', $student))->assertRedirect(route('administrator.students.index'));
    expect($student->refresh()->user->is_active)->toBeFalse()->and($student->classroom_id)->toBe($this->classroom->id);
});

test('admin page only lists and creates administrator accounts', function () {
    $this->get(route('administrator.users.index'))->assertOk()
        ->assertSee($this->admin->email)
        ->assertDontSee($this->teacher->user->email)
        ->assertDontSee($this->student->user->email);

    $this->post(route('administrator.users.store'), [
        'name' => 'Admin Kedua', 'email' => 'admin2@example.test', 'password' => 'Admin12345',
        'password_confirmation' => 'Admin12345', 'role' => 'teacher', 'is_active' => 1,
    ])->assertRedirect(route('administrator.users.index'))->assertSessionHasNoErrors();

    $created = User::where('email', 'admin2@example.test')->firstOrFail();
    expect($created->role)->toBe(UserRole::Administrator)
        ->and($created->teacher)->toBeNull()
        ->and($created->student)->toBeNull();
});

test('an administrator can securely reset another account password', function () {
    $otherAdmin = User::factory()->administrator()->create();
    $this->put(route('administrator.users.update', $otherAdmin), [
        'name' => $otherAdmin->name, 'email' => $otherAdmin->email,
        'password' => 'Admin12345', 'password_confirmation' => 'Admin12345',
        'role' => 'student', 'is_active' => 1,
    ])
        ->assertRedirect(route('administrator.users.index'))->assertSessionHasNoErrors();
    expect(Hash::check('Admin12345', $otherAdmin->fresh()->password))->toBeTrue()
        ->and($otherAdmin->fresh()->role)->toBe(UserRole::Administrator);
});

test('admin routes reject nonadmin accounts and self deactivation', function () {
    $this->get(route('administrator.users.edit', $this->teacher->user))->assertNotFound();
    $this->delete(route('administrator.users.destroy', $this->teacher->user))->assertNotFound();
    $this->patch(route('administrator.users.activate', $this->teacher->user))->assertNotFound();
    $this->delete(route('administrator.users.destroy', $this->admin))->assertSessionHasErrors('is_active');
    $this->put(route('administrator.users.update', $this->admin), [
        'name' => $this->admin->name, 'email' => $this->admin->email, 'role' => 'administrator', 'is_active' => 0,
    ])->assertSessionHasErrors('is_active');
    expect($this->admin->refresh()->is_active)->toBeTrue();
});

test('the last active administrator cannot be disabled by the account service', function () {
    $actor = User::factory()->administrator()->inactive()->create();
    try {
        app(AccountService::class)->deactivate($this->admin, $actor);
        $this->fail('Penonaktifan administrator terakhir harus ditolak.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['is_active'][0])->toContain('terakhir');
    }
    expect($this->admin->fresh()->is_active)->toBeTrue();
});

test('profile failure rolls back account creation', function () {
    $count = User::count();
    try {
        app(AccountService::class)->save([...$this->studentData, 'role' => 'student', 'nis' => $this->student->nis], $this->admin);
        $this->fail('NIS duplikat harus ditolak database.');
    } catch (QueryException) {
        expect(User::count())->toBe($count)->and(User::where('email', 'siswa@example.test')->exists())->toBeFalse();
    }
});

test('account forms reject unsafe or invalid input in Indonesian', function (array $overrides, string $field) {
    $this->post(route('administrator.students.store'), [...$this->studentData, ...$overrides])->assertSessionHasErrors($field);
    expect(User::where('email', 'siswa@example.test')->exists())->toBeFalse();
})->with([
    'required name' => [['name' => ''], 'name'],
    'email' => [['email' => 'invalid'], 'email'],
    'duplicate email' => [['email' => 'taken@example.test'], 'email'],
    'weak password' => [['password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh'], 'password'],
    'unconfirmed password' => [['password_confirmation' => 'different'], 'password'],
    'missing NIS' => [['nis' => ''], 'nis'],
    'invalid NIS' => [['nis' => '<script>'], 'nis'],
    'unknown class' => [['classroom_id' => 999999], 'classroom_id'],
    'missing active class' => [['classroom_id' => null], 'classroom_id'],
    'invalid status' => [['profile_status' => 'admin'], 'profile_status'],
    'invalid boolean' => [['is_active' => 'yes'], 'is_active'],
    'array name' => [['name' => ['bad']], 'name'],
    'array email' => [['email' => ['bad']], 'email'],
    'array NIS' => [['nis' => ['bad']], 'nis'],
    'array class' => [['classroom_id' => ['bad']], 'classroom_id'],
]);

test('teacher phone only accepts numbers', function () {
    $this->post(route('administrator.teachers.store'), [...$this->teacherData, 'phone' => '0812abc'])
        ->assertSessionHasErrors(['phone' => 'Nomor kontak guru hanya boleh berisi angka.']);
});

test('only one academic year is active after activation', function () {
    $this->post(route('administrator.academic-years.store'), [
        'name' => '2027/2028', 'starts_at' => '2027-07-01', 'ends_at' => '2028-06-30', 'is_active' => 1,
    ])->assertRedirect(route('administrator.academic-years.index'))->assertSessionHasNoErrors();
    expect(AcademicYear::where('is_active', true)->count())->toBe(1)->and($this->year->fresh()->is_active)->toBeFalse();
    $year = AcademicYear::where('name', '2027/2028')->firstOrFail();
    $this->put(route('administrator.academic-years.update', $year), [
        'name' => '2027/2028', 'starts_at' => '2027-07-02', 'ends_at' => '2028-06-30', 'is_active' => 1,
    ])->assertRedirect()->assertSessionHasNoErrors();
});

test('academic years validate adjacent years and chronological dates', function (array $overrides, string $field) {
    $this->post(route('administrator.academic-years.store'), [
        'name' => '2027/2028', 'starts_at' => '2027-07-01', 'ends_at' => '2028-06-30', 'is_active' => 0, ...$overrides,
    ])->assertSessionHasErrors($field);
})->with([
    [['name' => '2027/2029'], 'name'], [['name' => '2027'], 'name'],
    [['ends_at' => '2027-06-30'], 'ends_at'], [['starts_at' => 'invalid'], 'starts_at'],
    [['starts_at' => ['bad']], 'starts_at'],
]);

test('classroom CRUD normalizes sections and validates uniqueness including empty sections', function () {
    $payload = ['academic_year_id' => $this->year->id, 'name' => 'Kelas 6B', 'grade' => 6, 'section' => ' b ', 'is_active' => 1];
    $this->post(route('administrator.classrooms.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $classroom = Classroom::where('grade', 6)->firstOrFail();
    expect($classroom->section)->toBe('B');
    $this->put(route('administrator.classrooms.update', $classroom), [...$payload, 'name' => 'Kelas Enam B'])->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('administrator.classrooms.store'), $payload)->assertSessionHasErrors('section');
    $empty = [...$payload, 'section' => ''];
    $this->post(route('administrator.classrooms.store'), $empty)->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('administrator.classrooms.store'), $empty)->assertSessionHasErrors('section');
    $this->delete(route('administrator.classrooms.destroy', $classroom))->assertRedirect()->assertSessionHasNoErrors();
    expect(Classroom::find($classroom->id))->toBeNull();
});

test('classroom requests reject invalid grade foreign keys and nested values', function (array $overrides, string $field) {
    $this->post(route('administrator.classrooms.store'), [
        'academic_year_id' => $this->year->id, 'name' => 'Kelas 6', 'grade' => 6, 'section' => '', 'is_active' => 1, ...$overrides,
    ])->assertSessionHasErrors($field);
})->with([
    [['grade' => 7], 'grade'], [['academic_year_id' => 999999], 'academic_year_id'],
    [['academic_year_id' => ['bad']], 'academic_year_id'], [['grade' => ['bad']], 'grade'], [['section' => ['bad']], 'section'],
]);

test('subject CRUD uses validated data and protects referenced master records', function () {
    $payload = ['name' => 'Bahasa Indonesia', 'code' => 'BIND', 'description' => 'Pelajaran bahasa', 'is_active' => 1];
    $this->post(route('administrator.subjects.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $subject = Subject::where('code', 'BIND')->firstOrFail();
    $this->put(route('administrator.subjects.update', $subject), [...$payload, 'description' => 'Deskripsi baru'])->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('administrator.subjects.store'), $payload)->assertSessionHasErrors(['name', 'code']);
    $this->delete(route('administrator.subjects.destroy', $subject))->assertRedirect()->assertSessionHasNoErrors();
    expect(Subject::find($subject->id))->toBeNull();
    $this->delete(route('administrator.subjects.destroy', $this->subject))->assertSessionHasErrors('resource');
    $this->delete(route('administrator.classrooms.destroy', $this->classroom))->assertSessionHasErrors('resource');
    $this->delete(route('administrator.academic-years.destroy', $this->year))->assertSessionHasErrors('resource');
});

test('teaching assignment CRUD enforces active master data and duplicates', function () {
    $subject = Subject::create(['name' => 'IPA']);
    $payload = ['teacher_id' => $this->teacher->id, 'classroom_id' => $this->classroom->id, 'subject_id' => $subject->id];
    $this->post(route('administrator.teaching-assignments.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $teaching = TeachingAssignment::where('subject_id', $subject->id)->firstOrFail();
    $this->put(route('administrator.teaching-assignments.update', $teaching), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('administrator.teaching-assignments.store'), $payload)->assertSessionHasErrors('subject_id');
    $this->delete(route('administrator.teaching-assignments.destroy', $teaching))->assertRedirect()->assertSessionHasNoErrors();
    $subject->update(['is_active' => false]);
    $this->post(route('administrator.teaching-assignments.store'), $payload)->assertSessionHasErrors('subject_id');
    $subject->update(['is_active' => true]);
    $this->teacher->user->update(['name' => 'Nonaktif']);
    $this->teacher->user->is_active = false;
    $this->teacher->user->save();
    $this->post(route('administrator.teaching-assignments.store'), $payload)->assertSessionHasErrors('teacher_id');
});

test('teaching assignments with archived content cannot be retargeted or deleted', function () {
    $material = $this->teaching->materials()->create(['title' => 'Pecahan', 'content' => 'Isi']);
    $material->delete();
    $subject = Subject::create(['name' => 'IPA']);
    $this->put(route('administrator.teaching-assignments.update', $this->teaching), [
        'teacher_id' => $this->teacher->id, 'classroom_id' => $this->classroom->id, 'subject_id' => $subject->id,
    ])->assertSessionHasErrors('teacher_id');
    $this->delete(route('administrator.teaching-assignments.destroy', $this->teaching))->assertSessionHasErrors('resource');
    expect($this->teaching->fresh()->subject_id)->toBe($this->subject->id);
});

test('a referenced classroom cannot move to another academic year', function () {
    $year = AcademicYear::create(['name' => '2027/2028', 'starts_at' => '2027-07-01', 'ends_at' => '2028-06-30']);
    $this->put(route('administrator.classrooms.update', $this->classroom), [
        'name' => 'Kelas 5A', 'grade' => 5, 'section' => 'A', 'academic_year_id' => $year->id, 'is_active' => 1,
    ])->assertSessionHasErrors('academic_year_id');
});

test('teaching requests reject forged foreign keys and nested input', function (array $overrides, string $field) {
    $subject = Subject::create(['name' => 'IPA']);
    $this->post(route('administrator.teaching-assignments.store'), [
        'teacher_id' => $this->teacher->id, 'classroom_id' => $this->classroom->id, 'subject_id' => $subject->id, ...$overrides,
    ])->assertSessionHasErrors($field);
})->with([
    [['teacher_id' => 999999], 'teacher_id'], [['classroom_id' => 999999], 'classroom_id'], [['subject_id' => 999999], 'subject_id'],
    [['teacher_id' => ['bad']], 'teacher_id'], [['classroom_id' => ['bad']], 'classroom_id'], [['subject_id' => ['bad']], 'subject_id'],
]);

test('search is server side paginated validated and safely escaped', function () {
    for ($i = 1; $i <= 20; $i++) {
        Subject::create(['name' => 'Pelajaran '.$i]);
    }
    $this->get(route('administrator.subjects.index', ['q' => 'Pelajaran']))->assertOk()
        ->assertViewHas('records', fn ($records) => $records->total() === 20 && $records->count() === 15);
    $this->get(route('administrator.subjects.index', ['q' => '<script>alert(1)</script>']))->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false);
    $this->get(route('administrator.subjects.index', ['q' => ['invalid']]))->assertSessionHasErrors('q');
    $this->get(route('administrator.subjects.index', ['page' => -1]))->assertSessionHasErrors('page');
    $this->get(route('administrator.subjects.index', ['q' => "' OR 1=1 --"]))->assertOk()
        ->assertViewHas('records', fn ($records) => $records->total() === 0);
});

test('validation feedback uses Indonesian messages', function () {
    $this->post(route('administrator.subjects.store'), [])->assertSessionHasErrors(['name' => 'Nama wajib diisi.']);
});

test('inactive classes and years cannot receive new student enrollments or teaching assignments', function () {
    $this->classroom->update(['is_active' => false]);
    $this->post(route('administrator.students.store'), $this->studentData)->assertSessionHasErrors('classroom_id');
    $this->post(route('administrator.teaching-assignments.store'), [
        'teacher_id' => $this->teacher->id, 'classroom_id' => $this->classroom->id, 'subject_id' => $this->subject->id,
    ])->assertSessionHasErrors('classroom_id');
    $this->classroom->update(['is_active' => true]);
    $this->year->update(['is_active' => false]);
    $this->post(route('administrator.students.store'), $this->studentData)->assertSessionHasErrors('classroom_id');
});

test('master data POST requests are csrf protected', function () {
    $this->app['env'] = 'local';
    $this->post(route('administrator.subjects.store'), ['name' => 'IPA', 'is_active' => 1])->assertStatus(419);
    expect(Subject::where('name', 'IPA')->exists())->toBeFalse();
});

test('admin creation cannot be forged into another role', function () {
    $this->post(route('administrator.users.store'), [
        'name' => 'Admin Aman', 'email' => 'admin-aman@example.test',
        'password' => 'Admin12345', 'password_confirmation' => 'Admin12345',
        'role' => ['teacher'], 'is_active' => 1,
    ])->assertRedirect(route('administrator.users.index'))->assertSessionHasNoErrors();

    expect(User::where('email', 'admin-aman@example.test')->firstOrFail()->role)->toBe(UserRole::Administrator);
});

test('forms still render validation feedback after malformed nested input', function () {
    $this->from(route('administrator.students.create'))->post(route('administrator.students.store'), [
        ...$this->studentData, 'email' => ['bad'], 'nis' => ['bad'],
    ])->assertRedirect(route('administrator.students.create'))->assertSessionHasErrors(['email', 'nis']);
    $this->withCookie(config('session.cookie'), session()->getId())
        ->get(route('administrator.students.create'))->assertOk()->assertSee('Periksa data berikut');
});
