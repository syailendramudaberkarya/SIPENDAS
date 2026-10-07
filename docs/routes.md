# Daftar route aplikasi SIPENDAS

Snapshot dari php artisan route:list --json --except-vendor. Route framework/vendor tidak disertakan. Web middleware juga mencakup CSRF dan AuthenticateSession; policy/service memeriksa resource dan kepemilikan selain middleware yang tercantum.

| Method | URL | Nama | Controller/action | Middleware |
|---|---|---|---|---|
| GET / HEAD | // | — | Closure | web |
| GET / HEAD | /administrator/academic-years | administrator.academic-years.index | Admin\AcademicYearController@index | web, auth, active, role:administrator |
| POST | /administrator/academic-years | administrator.academic-years.store | Admin\AcademicYearController@store | web, auth, active, role:administrator |
| GET / HEAD | /administrator/academic-years/create | administrator.academic-years.create | Admin\AcademicYearController@create | web, auth, active, role:administrator |
| PUT / PATCH | /administrator/academic-years/{academic_year} | administrator.academic-years.update | Admin\AcademicYearController@update | web, auth, active, role:administrator |
| DELETE | /administrator/academic-years/{academic_year} | administrator.academic-years.destroy | Admin\AcademicYearController@destroy | web, auth, active, role:administrator |
| GET / HEAD | /administrator/academic-years/{academic_year}/edit | administrator.academic-years.edit | Admin\AcademicYearController@edit | web, auth, active, role:administrator |
| GET / HEAD | /administrator/classrooms | administrator.classrooms.index | Admin\ClassroomController@index | web, auth, active, role:administrator |
| POST | /administrator/classrooms | administrator.classrooms.store | Admin\ClassroomController@store | web, auth, active, role:administrator |
| GET / HEAD | /administrator/classrooms/create | administrator.classrooms.create | Admin\ClassroomController@create | web, auth, active, role:administrator |
| PUT / PATCH | /administrator/classrooms/{classroom} | administrator.classrooms.update | Admin\ClassroomController@update | web, auth, active, role:administrator |
| DELETE | /administrator/classrooms/{classroom} | administrator.classrooms.destroy | Admin\ClassroomController@destroy | web, auth, active, role:administrator |
| GET / HEAD | /administrator/classrooms/{classroom}/edit | administrator.classrooms.edit | Admin\ClassroomController@edit | web, auth, active, role:administrator |
| GET / HEAD | /administrator/dashboard | administrator.dashboard | DashboardController@administrator | web, auth, active, role:administrator |
| GET / HEAD | /administrator/informasi | administrator.information.index | LearningInformationController@index | web, auth, active, role:administrator |
| GET / HEAD | /administrator/informasi/{information} | administrator.information.show | LearningInformationController@show | web, auth, active, role:administrator |
| GET / HEAD | /administrator/materi | administrator.materials.index | MaterialController@index | web, auth, active, role:administrator |
| GET / HEAD | /administrator/materi/{material} | administrator.materials.show | MaterialController@show | web, auth, active, role:administrator |
| GET / HEAD | /administrator/materi/{material}/unduh | administrator.materials.download | MaterialController@download | web, auth, active, role:administrator |
| GET / HEAD | /administrator/schedules | administrator.schedules.index | Admin\ScheduleController@index | web, auth, active, role:administrator |
| POST | /administrator/schedules | administrator.schedules.store | Admin\ScheduleController@store | web, auth, active, role:administrator |
| GET / HEAD | /administrator/schedules/create | administrator.schedules.create | Admin\ScheduleController@create | web, auth, active, role:administrator |
| PUT / PATCH | /administrator/schedules/{schedule} | administrator.schedules.update | Admin\ScheduleController@update | web, auth, active, role:administrator |
| DELETE | /administrator/schedules/{schedule} | administrator.schedules.destroy | Admin\ScheduleController@destroy | web, auth, active, role:administrator |
| GET / HEAD | /administrator/schedules/{schedule}/edit | administrator.schedules.edit | Admin\ScheduleController@edit | web, auth, active, role:administrator |
| GET / HEAD | /administrator/students | administrator.students.index | Admin\StudentController@index | web, auth, active, role:administrator |
| POST | /administrator/students | administrator.students.store | Admin\StudentController@store | web, auth, active, role:administrator |
| GET / HEAD | /administrator/students/create | administrator.students.create | Admin\StudentController@create | web, auth, active, role:administrator |
| PUT / PATCH | /administrator/students/{student} | administrator.students.update | Admin\StudentController@update | web, auth, active, role:administrator |
| DELETE | /administrator/students/{student} | administrator.students.destroy | Admin\StudentController@destroy | web, auth, active, role:administrator |
| GET / HEAD | /administrator/students/{student}/edit | administrator.students.edit | Admin\StudentController@edit | web, auth, active, role:administrator |
| GET / HEAD | /administrator/subjects | administrator.subjects.index | Admin\SubjectController@index | web, auth, active, role:administrator |
| POST | /administrator/subjects | administrator.subjects.store | Admin\SubjectController@store | web, auth, active, role:administrator |
| GET / HEAD | /administrator/subjects/create | administrator.subjects.create | Admin\SubjectController@create | web, auth, active, role:administrator |
| PUT / PATCH | /administrator/subjects/{subject} | administrator.subjects.update | Admin\SubjectController@update | web, auth, active, role:administrator |
| DELETE | /administrator/subjects/{subject} | administrator.subjects.destroy | Admin\SubjectController@destroy | web, auth, active, role:administrator |
| GET / HEAD | /administrator/subjects/{subject}/edit | administrator.subjects.edit | Admin\SubjectController@edit | web, auth, active, role:administrator |
| GET / HEAD | /administrator/teachers | administrator.teachers.index | Admin\TeacherController@index | web, auth, active, role:administrator |
| POST | /administrator/teachers | administrator.teachers.store | Admin\TeacherController@store | web, auth, active, role:administrator |
| GET / HEAD | /administrator/teachers/create | administrator.teachers.create | Admin\TeacherController@create | web, auth, active, role:administrator |
| PUT / PATCH | /administrator/teachers/{teacher} | administrator.teachers.update | Admin\TeacherController@update | web, auth, active, role:administrator |
| DELETE | /administrator/teachers/{teacher} | administrator.teachers.destroy | Admin\TeacherController@destroy | web, auth, active, role:administrator |
| GET / HEAD | /administrator/teachers/{teacher}/edit | administrator.teachers.edit | Admin\TeacherController@edit | web, auth, active, role:administrator |
| GET / HEAD | /administrator/teaching-assignments | administrator.teaching-assignments.index | Admin\TeachingAssignmentController@index | web, auth, active, role:administrator |
| POST | /administrator/teaching-assignments | administrator.teaching-assignments.store | Admin\TeachingAssignmentController@store | web, auth, active, role:administrator |
| GET / HEAD | /administrator/teaching-assignments/create | administrator.teaching-assignments.create | Admin\TeachingAssignmentController@create | web, auth, active, role:administrator |
| PUT / PATCH | /administrator/teaching-assignments/{teaching_assignment} | administrator.teaching-assignments.update | Admin\TeachingAssignmentController@update | web, auth, active, role:administrator |
| DELETE | /administrator/teaching-assignments/{teaching_assignment} | administrator.teaching-assignments.destroy | Admin\TeachingAssignmentController@destroy | web, auth, active, role:administrator |
| GET / HEAD | /administrator/teaching-assignments/{teaching_assignment}/edit | administrator.teaching-assignments.edit | Admin\TeachingAssignmentController@edit | web, auth, active, role:administrator |
| GET / HEAD | /administrator/tugas | administrator.assignments.index | AssignmentController@index | web, auth, active, role:administrator |
| GET / HEAD | /administrator/tugas/{assignment} | administrator.assignments.show | AssignmentController@show | web, auth, active, role:administrator |
| GET / HEAD | /administrator/tugas/{assignment}/unduh | administrator.assignments.download | AssignmentController@download | web, auth, active, role:administrator |
| GET / HEAD | /administrator/users | administrator.users.index | Admin\UserController@index | web, auth, active, role:administrator |
| POST | /administrator/users | administrator.users.store | Admin\UserController@store | web, auth, active, role:administrator |
| GET / HEAD | /administrator/users/create | administrator.users.create | Admin\UserController@create | web, auth, active, role:administrator |
| PUT / PATCH | /administrator/users/{user} | administrator.users.update | Admin\UserController@update | web, auth, active, role:administrator |
| DELETE | /administrator/users/{user} | administrator.users.destroy | Admin\UserController@destroy | web, auth, active, role:administrator |
| GET / HEAD | /administrator/users/{user}/edit | administrator.users.edit | Admin\UserController@edit | web, auth, active, role:administrator |
| GET / HEAD | /dashboard | dashboard | DashboardController@index | web, auth, active |
| GET / HEAD | /guru/dashboard | teacher.dashboard | DashboardController@teacher | web, auth, active, role:teacher |
| GET / HEAD | /guru/informasi | teacher.information.index | LearningInformationController@index | web, auth, active, role:teacher |
| POST | /guru/informasi | teacher.information.store | LearningInformationController@store | web, auth, active, role:teacher |
| GET / HEAD | /guru/informasi/create | teacher.information.create | LearningInformationController@create | web, auth, active, role:teacher |
| GET / HEAD | /guru/informasi/{information} | teacher.information.show | LearningInformationController@show | web, auth, active, role:teacher |
| PUT / PATCH | /guru/informasi/{information} | teacher.information.update | LearningInformationController@update | web, auth, active, role:teacher |
| DELETE | /guru/informasi/{information} | teacher.information.destroy | LearningInformationController@destroy | web, auth, active, role:teacher |
| GET / HEAD | /guru/informasi/{information}/edit | teacher.information.edit | LearningInformationController@edit | web, auth, active, role:teacher |
| GET / HEAD | /guru/jadwal | teacher.schedules.index | LearningScheduleController@index | web, auth, active, role:teacher |
| GET / HEAD | /guru/jadwal/{schedule} | teacher.schedules.show | LearningScheduleController@show | web, auth, active, role:teacher |
| GET / HEAD | /guru/materi | teacher.materials.index | MaterialController@index | web, auth, active, role:teacher |
| POST | /guru/materi | teacher.materials.store | MaterialController@store | web, auth, active, role:teacher |
| GET / HEAD | /guru/materi/create | teacher.materials.create | MaterialController@create | web, auth, active, role:teacher |
| GET / HEAD | /guru/materi/{material} | teacher.materials.show | MaterialController@show | web, auth, active, role:teacher |
| PUT / PATCH | /guru/materi/{material} | teacher.materials.update | MaterialController@update | web, auth, active, role:teacher |
| DELETE | /guru/materi/{material} | teacher.materials.destroy | MaterialController@destroy | web, auth, active, role:teacher |
| GET / HEAD | /guru/materi/{material}/edit | teacher.materials.edit | MaterialController@edit | web, auth, active, role:teacher |
| GET / HEAD | /guru/materi/{material}/unduh | teacher.materials.download | MaterialController@download | web, auth, active, role:teacher |
| GET / HEAD | /guru/tugas | teacher.assignments.index | AssignmentController@index | web, auth, active, role:teacher |
| POST | /guru/tugas | teacher.assignments.store | AssignmentController@store | web, auth, active, role:teacher |
| GET / HEAD | /guru/tugas/create | teacher.assignments.create | AssignmentController@create | web, auth, active, role:teacher |
| GET / HEAD | /guru/tugas/{assignment} | teacher.assignments.show | AssignmentController@show | web, auth, active, role:teacher |
| PUT / PATCH | /guru/tugas/{assignment} | teacher.assignments.update | AssignmentController@update | web, auth, active, role:teacher |
| DELETE | /guru/tugas/{assignment} | teacher.assignments.destroy | AssignmentController@destroy | web, auth, active, role:teacher |
| GET / HEAD | /guru/tugas/{assignment}/edit | teacher.assignments.edit | AssignmentController@edit | web, auth, active, role:teacher |
| GET / HEAD | /guru/tugas/{assignment}/unduh | teacher.assignments.download | AssignmentController@download | web, auth, active, role:teacher |
| GET / HEAD | /login | login | Auth\AuthenticatedSessionController@create | web, guest |
| POST | /login | login.store | Auth\AuthenticatedSessionController@store | web, guest |
| POST | /logout | logout | Auth\AuthenticatedSessionController@destroy | web, auth |
| GET / HEAD | /siswa/dashboard | student.dashboard | DashboardController@student | web, auth, active, role:student |
| GET / HEAD | /siswa/informasi | student.information.index | LearningInformationController@index | web, auth, active, role:student |
| GET / HEAD | /siswa/informasi/{information} | student.information.show | LearningInformationController@show | web, auth, active, role:student |
| GET / HEAD | /siswa/jadwal | student.schedules.index | LearningScheduleController@index | web, auth, active, role:student |
| GET / HEAD | /siswa/jadwal/{schedule} | student.schedules.show | LearningScheduleController@show | web, auth, active, role:student |
| GET / HEAD | /siswa/materi | student.materials.index | MaterialController@index | web, auth, active, role:student |
| GET / HEAD | /siswa/materi/{material} | student.materials.show | MaterialController@show | web, auth, active, role:student |
| GET / HEAD | /siswa/materi/{material}/unduh | student.materials.download | MaterialController@download | web, auth, active, role:student |
| GET / HEAD | /siswa/tugas | student.assignments.index | AssignmentController@index | web, auth, active, role:student |
| GET / HEAD | /siswa/tugas/{assignment} | student.assignments.show | AssignmentController@show | web, auth, active, role:student |
| GET / HEAD | /siswa/tugas/{assignment}/unduh | student.assignments.download | AssignmentController@download | web, auth, active, role:student |
