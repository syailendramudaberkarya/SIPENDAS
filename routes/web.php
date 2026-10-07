<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\TeachingAssignmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LearningInformationController;
use App\Http\Controllers\LearningScheduleController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::get('/identitas/logo', [SettingController::class, 'logo'])->name('settings.logo');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/administrator/dashboard', [DashboardController::class, 'administrator'])
        ->middleware('role:administrator')->name('administrator.dashboard');
    Route::get('/guru/dashboard', [DashboardController::class, 'teacher'])
        ->middleware('role:teacher')->name('teacher.dashboard');
    Route::get('/siswa/dashboard', [DashboardController::class, 'student'])
        ->middleware('role:student')->name('student.dashboard');
    Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/pengaturan/profil', [SettingController::class, 'updateProfile'])->name('settings.profile');
    Route::get('/pengaturan/foto-profil', [SettingController::class, 'avatar'])->name('settings.avatar');
    Route::get('/pengaturan/password', [SettingController::class, 'password'])->name('settings.password.edit');
    Route::put('/pengaturan/password', [SettingController::class, 'updatePassword'])->name('settings.password');
    Route::get('/pengaturan/identitas', [SettingController::class, 'branding'])
        ->middleware('role:administrator')->name('settings.branding.edit');
    Route::put('/pengaturan/identitas', [SettingController::class, 'updateBranding'])
        ->middleware('role:administrator')->name('settings.branding');
});

Route::prefix('administrator')->name('administrator.')->middleware(['auth', 'active', 'role:administrator'])->group(function () {
    Route::patch('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::patch('/teachers/{teacher}/activate', [TeacherController::class, 'activate'])->name('teachers.activate');
    Route::patch('/students/{student}/activate', [StudentController::class, 'activate'])->name('students.activate');
    Route::resources([
        'users' => UserController::class,
        'teachers' => TeacherController::class,
        'students' => StudentController::class,
        'academic-years' => AcademicYearController::class,
        'classrooms' => ClassroomController::class,
        'subjects' => SubjectController::class,
        'teaching-assignments' => TeachingAssignmentController::class,
        'schedules' => ScheduleController::class,
    ], ['except' => ['show']]);
});

Route::prefix('guru')->name('teacher.')->middleware(['auth', 'active', 'role:teacher'])->group(function () {
    Route::resource('informasi', LearningInformationController::class)->parameters(['informasi' => 'information'])->names('information');
    Route::get('/tugas/{assignment}/unduh', [AssignmentController::class, 'download'])->name('assignments.download');
    Route::resource('tugas', AssignmentController::class)->parameters(['tugas' => 'assignment'])->names('assignments');
    Route::get('/materi/{material}/unduh', [MaterialController::class, 'download'])->name('materials.download');
    Route::patch('/materi/{material}/aktifkan', [MaterialController::class, 'restore'])->name('materials.restore');
    Route::resource('materi', MaterialController::class)->parameters(['materi' => 'material'])->names('materials');
    Route::get('/jadwal', [LearningScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/jadwal/{schedule}', [LearningScheduleController::class, 'show'])->name('schedules.show');
});

Route::prefix('siswa')->name('student.')->middleware(['auth', 'active', 'role:student'])->group(function () {
    Route::get('/informasi', [LearningInformationController::class, 'index'])->name('information.index');
    Route::get('/informasi/{information}', [LearningInformationController::class, 'show'])->name('information.show');
    Route::get('/tugas', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/tugas/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
    Route::get('/tugas/{assignment}/unduh', [AssignmentController::class, 'download'])->name('assignments.download');
    Route::get('/materi', [MaterialController::class, 'index'])->name('materials.index');
    Route::get('/materi/{material}', [MaterialController::class, 'show'])->name('materials.show');
    Route::get('/materi/{material}/unduh', [MaterialController::class, 'download'])->name('materials.download');
    Route::get('/jadwal', [LearningScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/jadwal/{schedule}', [LearningScheduleController::class, 'show'])->name('schedules.show');
});

Route::prefix('administrator')->name('administrator.')->middleware(['auth', 'active', 'role:administrator'])->group(function () {
    Route::get('/tugas/{assignment}/unduh', [AssignmentController::class, 'download'])->name('assignments.download');
    Route::get('/materi/{material}/unduh', [MaterialController::class, 'download'])->name('materials.download');
    Route::patch('/materi/{material}/aktifkan', [MaterialController::class, 'restore'])->name('materials.restore');
    Route::resource('informasi', LearningInformationController::class)->parameters(['informasi' => 'information'])->names('information');
    Route::resource('tugas', AssignmentController::class)->parameters(['tugas' => 'assignment'])->names('assignments');
    Route::resource('materi', MaterialController::class)->parameters(['materi' => 'material'])->names('materials');
});
