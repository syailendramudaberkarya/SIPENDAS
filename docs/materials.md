# Fase 6 — Materi pembelajaran

Guru dapat membuat, membaca, mengubah, dan mengarsipkan materi pada penugasan aktif miliknya. Administrator hanya membaca. Siswa hanya membaca materi terbit dengan tanggal publikasi tidak melewati waktu sekarang dan kelas yang sama. Policy diperiksa pada setiap resource, termasuk unduhan; query daftar dibatasi berdasarkan pengguna.

Kolom materi menggunakan migration sebelumnya: teaching_assignment_id, title, content, status, published_at, metadata lampiran, timestamps, deleted_at. Relasi belongsTo TeachingAssignment; kelas, guru, dan mata pelajaran tidak diduplikasi.

Lampiran tahap ini: PDF, JPG/JPEG, PNG maksimum 5 MB. Form Request memeriksa extension, MIME server, signature, ukuran, dan nama berbahaya. File disimpan dengan nama acak pada disk learning di storage/app/learning-private, tanpa public URL atau serving otomatis. Download memerlukan autentikasi, role, policy, dan path internal valid; respons menggunakan attachment, nosniff, private/no-store, dan sandbox.

File baru dibersihkan jika transaksi database gagal. File lama dibersihkan sesudah commit saat diganti/dihapus. Arsip materi menggunakan soft delete dan mempertahankan lampiran; tidak ada UI pemulihan pada scope ini. Materi terarsip tidak dapat dibuka atau diunduh.

Route: teacher.materials.index/create/store/show/edit/update/destroy/download; student.materials.index/show/download; administrator.materials.index/show/download. Semua mutasi menggunakan CSRF, validasi server, dan input whitelist. Konten ditampilkan sebagai teks escaped, bukan HTML mentah.

Pengujian black-box: CRUD guru; percobaan ID guru lain; publikasi draft/mendatang; kelas siswa berbeda; file berbahaya/terlalu besar; unduhan tanpa izin; ganti/hapus lampiran; XSS; pencarian dan pagination. Jalankan php artisan test --compact. Pengujian MySQL aktual tetap membutuhkan konfigurasi akun database yang diberikan pemilik project.

Data demo lokal: php artisan db:seed --class=DemoMaterialSeeder. Seeder idempotent menyediakan 18 materi (9 terbit, 9 draft), tanpa file lampiran atau data pribadi nyata. Seeder menolak environment production.
