# Fase 7 — Tugas pembelajaran

Modul menggunakan tabel assignments yang sudah ada; tidak diperlukan migration baru. Assignment belongsTo TeachingAssignment, sehingga guru, kelas, dan mata pelajaran diperoleh dari relasi, tanpa duplikasi. Kolom utama: judul, deskripsi, status draft/published, published_at, deadline_at opsional, metadata lampiran, timestamps, deleted_at.

Guru mengelola tugas pada penugasan aktif miliknya. Siswa hanya melihat tugas terbit pada kelasnya dengan publikasi tidak mendatang. Administrator hanya membaca. Tugas melewati deadline tetap dapat dibaca: deadline adalah informasi, bukan mekanisme pengumpulan. Tidak ada submission, nilai, atau koreksi.

Form Request memvalidasi input whitelist dan pesan Bahasa Indonesia, termasuk deadline setelah tanggal publikasi. Publikasi terbit tanpa tanggal memakai waktu sekarang; draft tidak memiliki tanggal publikasi efektif. Semua mutasi dilindungi CSRF dan policy server-side. Arsip menggunakan soft delete dan mempertahankan lampiran; resource terarsip tidak dapat dibuka atau diunduh.

Lampiran PDF/JPG/JPEG/PNG maksimum 5 MB menggunakan rule keamanan bersama modul materi: extension, MIME server, signature, ukuran, dan nama berbahaya. Penyimpanan disk learning private pada folder assignments memakai nama acak. Unduhan melalui controller dengan policy, pemeriksaan path, attachment, nosniff, sandbox, private/no-store. File baru dibersihkan jika transaksi gagal; file lama dibersihkan setelah commit saat diganti/dihapus.

Route guru: teacher.assignments.index/create/store/show/edit/update/destroy/download. Route siswa dan admin hanya index/show/download pada student.assignments dan administrator.assignments. Pencarian judul/deskripsi dan pagination dilakukan server-side. Dashboard guru/siswa menampilkan maksimal lima tugas terbaru dari scope pengguna.

Demo lokal: php artisan db:seed --class=DemoAssignmentSeeder. Seeder idempotent membuat 18 tugas (9 terbit dan 9 draft), tanpa lampiran, serta menolak production.

Black-box: CRUD guru; URL tugas guru lain; pemilihan penugasan ilegal; siswa kelas lain; draft/publikasi mendatang; deadline tidak logis; lampiran palsu/berbahaya/lebih besar dari 5 MB; unduhan tanpa izin; perubahan/penghapusan lampiran; rollback; arsip; XSS; CSRF; pencarian dan pagination. Automated test: php artisan test --compact. Test default menggunakan SQLite in-memory; MySQL aktual memerlukan konfigurasi akun database pemilik project. Pemeriksaan visual responsif dilakukan pada fase UI polish.
