# Fase 8 — Informasi pembelajaran

Menggunakan tabel learning_information existing tanpa migration baru. Kolom: teaching_assignment_id, title, content, status, published_at, timestamps, deleted_at. LearningInformation belongsTo TeachingAssignment; guru, kelas, dan mata pelajaran berasal dari relasi tersebut.

Guru mengelola informasi hanya pada penugasan aktif miliknya. Siswa membaca informasi terbit untuk kelasnya, dengan tanggal publikasi tidak mendatang. Administrator membaca seluruh informasi nonarsip, tanpa mutasi. Semua endpoint memeriksa middleware autentikasi/role dan policy server-side; list, pencarian, serta dashboard memakai scope pengguna yang sama dengan aturan policy.

Form Request memvalidasi judul/konten/status/tanggal dan foreign key dengan pesan Bahasa Indonesia. Service memeriksa ulang kepemilikan penugasan, menggunakan transaction, row lock, dan whitelist input. Publikasi kosong pada status terbit memakai waktu sekarang; draft menghapus tanggal publikasi efektif. Konten ditampilkan sebagai teks escaped dan mempertahankan baris. Mutasi menggunakan CSRF dan method spoofing. Penghapusan berupa arsip soft delete; detail arsip tidak dapat dibuka. Modul tidak mendukung lampiran, komentar, email, atau chat.

Route guru: teacher.information.index/create/store/show/edit/update/destroy pada /guru/informasi. Siswa: student.information.index/show pada /siswa/informasi. Admin: administrator.information.index/show pada /administrator/informasi. Daftar memakai pagination server-side 15 item dan pencarian judul/konten maksimal 100 karakter. Dashboard guru/siswa menampilkan maksimal lima informasi terbaru sesuai kewenangan.

Demo lokal: php artisan db:seed --class=DemoLearningInformationSeeder. Seeder idempotent membuat 18 informasi (9 draft, 9 terbit) dan menolak environment production. Data hanya dummy.

Black-box: CRUD guru; URL ID milik guru lain; pemilihan penugasan ilegal; siswa kelas berbeda; draft/publikasi mendatang/tanpa tanggal; master/profile tidak aktif; required dan input invalid; XSS; CSRF; arsip; pagination/search; dashboard scoping; rollback. Automated test: php artisan test --compact. Test memakai SQLite in-memory; verifikasi MySQL aktual menunggu konfigurasi akun database. Pemeriksaan visual responsif dilakukan pada fase UI polish.
