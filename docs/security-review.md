# Fase 10 — Security review

Review source dan automated test, bukan penetration test atau sertifikasi keamanan. Belum diverifikasi pada server production/MySQL aktual.

## Kontrol yang diperiksa

- Login: Hash Laravel, minimal password 8 karakter huruf+angka untuk pembuatan/reset; kredensial tidak disimpan plaintext. RateLimiter 5 kegagalan per kombinasi email/IP dalam 300 detik, pesan Indonesia. Akun tidak aktif ditolak.
- Session: regenerate setelah login; invalidate dan regenerate CSRF token saat logout/penonaktifan akun pada request berikutnya; timeout default 120 menit; HttpOnly dan SameSite=Lax; Secure tersedia melalui environment.
- Authorization: middleware auth/active/role, policy tiap resource pembelajaran, ownership penugasan pada service, scope list/dashboard. Admin tidak menggunakan endpoint mutasi guru. Arsip tidak dapat diakses via binding.
- CSRF: form state-changing memakai token dan method spoofing; middleware framework tidak dinonaktifkan.
- SQL/mass assignment: Eloquent, parameter binding, input tervalidasi/whitelist. whereRaw yang ditemukan hanya konstanta 1 = 0, bukan input pengguna.
- XSS: konten user tampil escaped; tidak ditemukan output Blade mentah untuk input pengguna.
- Pemulihan form materi/tugas: input penugasan berbentuk array ditolak server dan tidak menyebabkan array-to-string error ketika form ditampilkan kembali.
- Upload: whitelist PDF/JPEG/PNG 5 MB, extension+MIME server+signature; nama berbahaya ditolak. File acak/private, unduh melalui policy/path check. Pemeriksaan ini bukan antivirus/sanitasi PDF; file sah dapat memuat konten berbahaya untuk aplikasi pembaca, sehingga unduhan selalu attachment dan tidak dirender inline.
- Integritas: FK/restrict, transaksi operasi multi-tabel, master dinonaktifkan dan konten soft delete. Pembersihan file dilakukan setelah commit dan kompensasi file baru saat rollback.
- Error: halaman 403/404/413/419/500 Indonesia. Halaman 500 diuji dengan debug=false agar tidak membocorkan detail exception.
- Header baru: nosniff, frame DENY, referrer strict-origin-when-cross-origin, Permissions-Policy menolak camera/microphone/geolocation. Halaman login tetap publik; respons pengguna terautentikasi private/no-store.

## Persyaratan deployment yang belum dapat dibuktikan

APP_ENV=production, APP_DEBUG=false, HTTPS dan SESSION_SECURE_COOKIE=true wajib diatur pada environment production. Jangan menyalin credential demo atau menggunakan root MySQL. Gunakan user database berprivilege minimum dan jaringan private; document root hanya public/. Lindungi .env, storage private, backup, dan log dari akses web. Cache rate limiter/session harus persisten (bukan array) pada deployment. Sesuaikan batas request PHP/web server agar upload 5 MB tidak gagal sebelum validasi Laravel. HSTS/CSP ketat perlu konfigurasi deployment dan pengujian kompatibilitas; belum diterapkan secara global.

Fase 11 mengaktifkan AuthenticateSession bawaan Laravel pada web middleware. Fingerprint password disimpan saat login; perubahan password membuat session lama ditolak pada request berikutnya, termasuk session yang belum membuka dashboard. Session di-flush oleh framework dan pengguna harus login ulang. Revokasi berlaku lintas driver session; request yang sudah berjalan tidak dibatalkan. Status master/role yang berubah tetap diperiksa pada request berikutnya.

Jalankan php artisan test --compact untuk regression autentikasi, rate limit, role/IDOR, CSRF, validasi, XSS, upload/private download, dan header/error handling. Visual responsif dan konfigurasi infra bukan bagian yang sudah terbukti oleh test unit/feature.
