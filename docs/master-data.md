# Master data — Fase 4

Administrator mengelola pengguna, guru, siswa, tahun ajaran, kelas/rombel, mata pelajaran, dan penugasan mengajar melalui menu sidebar. Semua route berada pada prefix `/administrator` dan menggunakan middleware `auth`, `active`, serta `role:administrator`. Menu Blade hanya membantu navigasi; middleware dan `AdminRequest::authorize()` memeriksa akses di server.

## Operasi

Setiap resource menyediakan index, create, store, edit, update, dan destroy dengan route model binding. Nama route mengikuti `administrator.{resource}.{action}`, misalnya `administrator.teachers.store`. Resource: `users`, `teachers`, `students`, `academic-years`, `classrooms`, `subjects`, dan `teaching-assignments`.

Daftar memakai pencarian tervalidasi, eager loading, urutan stabil, serta pagination 15 record per halaman di server. Form memakai CSRF, method spoofing, pesan Indonesia, ringkasan kesalahan, dan feedback sukses. Kolom yang tidak diizinkan tidak diteruskan ke model.

## Akun dan profil

- AccountService menyimpan user dan profil guru/siswa dalam satu transaction dengan retry deadlock. Kegagalan penyimpanan profil me-roll back perubahan akun.
- Nama, email, dan password hanya berada pada users. Password memakai cast hashed Laravel dan aturan minimal delapan karakter berisi huruf serta angka, dengan konfirmasi.
- Mengosongkan password pada edit mempertahankan password existing. Reset password dilakukan dengan mengisi password baru dan konfirmasinya; password tidak pernah ditampilkan atau dikirim kembali ke browser.
- Form guru/siswa menetapkan role sesuai resource di server. Akun existing tidak dapat berganti role. Field role/is_active diubah secara eksplisit oleh service, bukan melalui mass assignment.
- Pengguna ber-role guru/siswa dibuat bersama profilnya. Siswa aktif memerlukan kelas; kelas baru harus aktif dan berada pada tahun ajaran aktif. Kelas existing dapat dipertahankan saat mengedit data historis.
- Tombol Nonaktifkan mempertahankan akun, profil, dan relasi pembelajaran. Akun tidak aktif tidak dapat login, dan sesi yang masih terbuka akan dikeluarkan oleh middleware pada request berikutnya.
- Akun administrator sendiri dan administrator aktif terakhir tidak dapat dinonaktifkan. Service mengunci baris administrator selama transaction untuk memeriksa aturan ini.

## Integritas master

- Mengaktifkan tahun ajaran menonaktifkan tahun lain dalam transaction. Rentang tanggal harus valid dan dua tahun pada nama harus berurutan.
- Tingkat kelas dibatasi 1–6. Bagian kelas dinormalisasi menjadi uppercase; kelas tanpa bagian memakai string kosong. Kombinasi tahun + tingkat + bagian dicek pada validasi dan database.
- Kelas yang memiliki siswa/penugasan tidak boleh dipindahkan ke tahun ajaran lain. Buat kelas baru pada tahun berikutnya.
- Penugasan baru hanya menerima guru dengan profil dan akun aktif, kelas aktif pada tahun ajaran aktif, serta mata pelajaran aktif.
- Kombinasi guru + kelas + mata pelajaran harus unik.
- Penugasan dengan jadwal atau konten (termasuk soft-deleted) tidak dapat diganti guru/kelas/mata pelajarannya. Buat penugasan baru agar kepemilikan dan sasaran konten lama tetap terjaga.
- Tahun ajaran, kelas, mata pelajaran, dan penugasan yang masih direferensikan tidak dapat dihapus. Foreign key menegakkan aturan ini; error integritas ditampilkan sebagai pesan ramah, bukan detail SQL.
- Status profil guru/siswa dan status login akun adalah dua informasi berbeda. Tombol Nonaktifkan menonaktifkan keduanya; administrator dapat menyesuaikannya melalui form edit.

## Data demo

Setelah `.env` MySQL lengkap dan migration diterapkan:

```sh
php artisan db:seed --class=DemoSchoolSeeder
```

Seeder khusus local/testing menyediakan 1 administrator, 3 guru, 6 siswa, 3 kelas, 3 mata pelajaran, dan 9 penugasan. Domain email `example.test` dan identitas DEMO merupakan data dummy. Password akun baru mengikuti DEMO_PASSWORD, atau Demo12345 jika belum diisi. Seeder tidak mereset password akun existing. Jadwal dan konten akan ditambahkan pada fase modul terkait.

## Pengujian

MasterDataTest memeriksa seluruh halaman admin, penolakan seluruh aksi untuk guru/siswa, CRUD, perubahan password, field tambahan/role palsu, rollback transaksi, deactivation, foreign key, unique constraint, validasi nested input, pencarian/pagination, escaping, CSRF, dan perlindungan konten arsip. DemoSchoolSeederTest memeriksa integritas dan idempotensi data demo serta larangan seeding production.

Automated tests memakai SQLite in-memory. Koneksi MySQL langsung dan pemeriksaan visual browser belum dianggap terverifikasi sampai konfigurasi MySQL lokal tersedia. Build frontend dan pengujian render Blade dapat dijalankan tanpa mengubah database aplikasi.
