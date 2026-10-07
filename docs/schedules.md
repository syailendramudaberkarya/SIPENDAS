# Jadwal pembelajaran — Fase 5

Administrator mengelola jadwal melalui menu Jadwal pembelajaran. Guru melihat jadwal penugasannya; siswa melihat jadwal kelasnya. Modul menggunakan tabel schedules dan teaching_assignments yang sudah dibuat pada Fase 2, tanpa migration tambahan.

## Data dan validasi

Jadwal menyimpan teaching_assignment_id, day_of_week, starts_at, dan ends_at. Kelas, guru, mata pelajaran, serta tahun ajaran diturunkan dari penugasan, bukan disalin ke kolom jadwal.

- Hari menggunakan ISO 1–7: Senin sampai Minggu.
- Form menerima jam HH:mm; database menyimpan HH:mm:ss pada kolom time.
- Jam selesai harus setelah jam mulai pada hari yang sama. Jadwal melewati tengah malam tidak diterima.
- Penugasan harus ada, dengan profil/akun guru aktif, kelas aktif pada tahun ajaran aktif, dan mata pelajaran aktif.
- Input nested, foreign key palsu, format jam salah, dan rentang waktu tidak logis ditolak oleh server dengan pesan Indonesia.
- Kolom tambahan seperti teacher_id atau classroom_id pada request tidak dapat mengubah guru/kelas di luar penugasan yang dipilih.

## Bentrok dan transaction

ScheduleService menyimpan jadwal dalam transaction. Pada MySQL, service mengunci baris tahun ajaran sebelum memeriksa dan mengubah jadwal, serta mengunci penugasan dan record jadwal terkait. Pemeriksaan interval memakai locking read. Penguncian tahun ajaran menserialkan perubahan jadwal pada tahun yang sama, termasuk saat belum ada record jadwal pada slot tersebut; retry transaction menangani deadlock.

Dua jadwal pada hari dan tahun ajaran yang sama bentrok jika memiliki guru atau kelas yang sama dan memenuhi:

```text
existing.starts_at < new.ends_at
AND existing.ends_at > new.starts_at
```

Jadwal 08:00–09:00 dan 09:00–10:00 diperbolehkan. Jadwal 08:00–09:00 dan 08:30–09:30 ditolak. Record yang sedang diedit dikecualikan dari pemeriksaan. Jadwal historis dari tahun ajaran berbeda tidak membatasi tahun berjalan.

Penguncian MySQL dirancang pada service, tetapi uji concurrency MySQL langsung masih menunggu konfigurasi koneksi lokal. Test SQLite memverifikasi aturan interval dan rollback perubahan; tidak memverifikasi row locking MySQL.

## Route dan kewenangan

| Area | URL | Nama route | Akses |
| --- | --- | --- | --- |
| Admin | /administrator/schedules | administrator.schedules.index/store | Administrator |
| Admin | /administrator/schedules/create | administrator.schedules.create | Administrator |
| Admin | /administrator/schedules/{schedule}/edit | administrator.schedules.edit | Administrator |
| Admin | /administrator/schedules/{schedule} | administrator.schedules.update/destroy | Administrator |
| Guru | /guru/jadwal | teacher.schedules.index | Guru; query terbatas pada penugasannya |
| Guru | /guru/jadwal/{schedule} | teacher.schedules.show | Guru; SchedulePolicy memeriksa kepemilikan |
| Siswa | /siswa/jadwal | student.schedules.index | Siswa; query terbatas pada kelasnya |
| Siswa | /siswa/jadwal/{schedule} | student.schedules.show | Siswa; SchedulePolicy memeriksa kelas |

Admin memakai middleware auth/active/role, AdminRequest, dan SchedulePolicy untuk setiap aksi. Pembaca hanya mempunyai endpoint GET. Scope Schedule::visibleTo dipakai pada daftar, policy detail, dan dashboard agar batas akses konsisten. Profil kosong/nonaktif, penugasan nonaktif, dan data tahun ajaran historis tidak tampil pada halaman pembaca.

Daftar memakai pagination 15 record di server. Admin dapat mencari nama guru/kelas/mata pelajaran. Guru/siswa dapat mencari kelas/mata pelajaran dan memfilter hari. Query pencarian dikelompokkan dalam batas akses sehingga filter tidak membuka data kelas/guru lain.

Form dilindungi CSRF dan method spoofing. Penghapusan meminta konfirmasi browser. Nama kelas/guru/pelajaran ditampilkan melalui escaped Blade output.

## Dashboard dan waktu

Dashboard guru/siswa menampilkan hingga lima jadwal hari ini, terurut berdasarkan jam, dengan tautan ke daftar lengkap. APP_TIMEZONE mengatur zona waktu sekolah melalui config/app.php; `.env.example` memakai Asia/Jakarta. Jam jadwal adalah waktu lokal sekolah, bukan timestamp UTC. Test mencakup pergantian hari antara UTC dan WIB.

## Demo dan pengujian

Setelah MySQL tersedia dan migration diterapkan:

```sh
php artisan db:seed --class=DemoScheduleSeeder
```

Seeder ini memanggil DemoSchoolSeeder, kemudian membuat sembilan jadwal demo tanpa bentrok. Hanya local/testing yang diizinkan; menjalankan ulang tidak menduplikasi slot existing.

ScheduleTest mencakup CRUD admin, bentrok guru/kelas, interval bersambung/tercakup, perbedaan hari/tahun, update tanpa perubahan ketika gagal, validasi jam/foreign key/nested input, master nonaktif, akses daftar/detail berdasarkan role, IDOR, pagination, filter, CSRF, XSS, serta dashboard hari ini. DemoScheduleSeederTest memeriksa idempotensi, larangan production, dan tidak adanya bentrok pada data demo.

Koneksi serta concurrency MySQL langsung dan pemeriksaan visual browser tetap belum diverifikasi sampai konfigurasi MySQL lokal tersedia.
