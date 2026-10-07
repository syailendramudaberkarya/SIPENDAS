# Autentikasi dan kewenangan — Fase 3

SIPENDAS memakai satu guard `web` berbasis session dan Eloquent User. Tidak ada registrasi publik. Akun dibuat administrator pada fase master data.

## Route

| Method | URL | Nama route | Pemeriksaan server |
| --- | --- | --- | --- |
| GET | /login | login | guest |
| POST | /login | login.store | guest, CSRF, LoginRequest, rate limiter |
| POST | /logout | logout | auth, CSRF |
| GET | /dashboard | dashboard | auth, active; redirect sesuai role |
| GET | /administrator/dashboard | administrator.dashboard | auth, active, role:administrator |
| GET | /guru/dashboard | teacher.dashboard | auth, active, role:teacher |
| GET | /siswa/dashboard | student.dashboard | auth, active, role:student |

Dashboard sudah menyediakan statistik administrator, penugasan aktif guru, kelas/mapel siswa, jadwal hari ini, dan konten terbaru sesuai kewenangan. Lihat dashboards.md.

## Login dan sesi

- Email dinormalisasi menjadi lowercase dan trim sebelum validasi.
- Pesan validasi login berbahasa Indonesia. Password tidak ditampilkan kembali dan tidak dimasukkan ke log.
- Laravel Auth::attempt memeriksa password hash dan is_active; kesalahan password dan akun nonaktif memakai pesan generik yang sama.
- RateLimiter membatasi lima kegagalan per kombinasi email + alamat IP selama 300 detik. Percobaan berikutnya dibatasi, termasuk dengan password benar. Login berhasil membersihkan penghitung.
- ID session diregenerate setelah login. Logout menghapus autentikasi, menginvalidasi session, dan meregenerate token CSRF.
- AuthenticateSession bawaan Laravel memeriksa fingerprint password yang disimpan saat login. Perubahan password menolak session lama pada request berikutnya. Lihat SessionRevocationTest.
- Middleware active memeriksa kembali akun pada setiap request dashboard; akun yang dinonaktifkan akan dikeluarkan dari sesi yang sudah aktif.
- Password::defaults menetapkan minimal delapan karakter dengan huruf dan angka untuk alur pembuatan/perubahan password berikutnya. Aturan login tidak menolak password lama berdasarkan kompleksitas sebelum pemeriksaan hash.
- Cookie menggunakan HttpOnly dan SameSite=Lax melalui config Laravel. Production HTTPS harus mengisi SESSION_SECURE_COOKIE=true dan APP_DEBUG=false.

## Policy konten

MaterialPolicy, AssignmentPolicy, dan LearningInformationPolicy ditemukan melalui konvensi Laravel. Ketiganya memakai aturan pada LearningContentPolicy:

- Guru hanya dapat mengakses, mengubah, atau menghapus konten penugasan miliknya sendiri, dengan profil guru aktif serta kelas/tahun ajaran/mata pelajaran aktif.
- Siswa aktif hanya dapat membaca/download konten kelasnya sendiri, berstatus published dengan published_at tidak melebihi waktu sekarang.
- Resource soft-deleted tidak dapat diakses melalui policy.
- Administrator dapat memeriksa konten untuk kebutuhan administratif, tetapi tidak mengubah atau menghapus konten guru.
- Akun tanpa profil tidak memperoleh akses konten.

Endpoint CRUD/download materi/tugas/informasi memanggil Gate/policy dan membatasi query daftar di server. Policy create tidak membuktikan assignment kiriman milik pengguna: service memeriksa ulang penugasan aktif dan kepemilikannya sebelum penyimpanan.

## Demo lokal

Setelah MySQL `.env` dikonfigurasi dan migration diterapkan:

```sh
php artisan db:seed --class=DemoAccountSeeder
```

| Role | Email |
| --- | --- |
| Administrator | administrator@sipendas.example.test |
| Guru | teacher@sipendas.example.test |
| Siswa | student@sipendas.example.test |

Password demo awal adalah Demo12345 pada local/testing, atau nilai DEMO_PASSWORD jika diisi. Seeder ditolak pada production. Menjalankan ulang tidak mereset password, tidak mengaktifkan ulang akun, dan tidak menduplikasi profil. Seeder akun membuat akun/profil; seeder demo sekolah/jadwal/konten melengkapi data untuk presentasi. Lihat capstone.md.

## Verifikasi

AuthenticationTest menguji login, kegagalan, akun nonaktif, validasi, rate limit dan kedaluwarsanya, regenerasi session, logout, pemisahan URL role, escaping nama, serta CSRF aktif. LearningAuthorizationTest menguji ketiga policy untuk kepemilikan guru, pembatasan kelas siswa, draft, tanggal publikasi, master nonaktif, profil kosong, dan soft delete. DemoAccountSeederTest menguji hashing, login akun demo, idempotensi, larangan production, serta penolakan password lemah.

CSRF pada Laravel 13 mencakup pemeriksaan origin dan token melalui PreventRequestForgery. Test khusus mematikan bypass environment testing, sehingga request tanpa token benar-benar ditolak 419. Semua form aplikasi tetap menggunakan @csrf.
