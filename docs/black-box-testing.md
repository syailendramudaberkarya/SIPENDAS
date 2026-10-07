# Skenario black-box SIPENDAS

Status dokumen: rencana pengujian manual, bukan klaim seluruh skenario manual sudah dijalankan. Automated regression dijalankan terpisah menggunakan SQLite in-memory. Catat hasil aktual, tanggal, environment, penguji, bukti screenshot, dan status Lulus/Gagal untuk setiap ID saat pengujian manual pada MySQL.

Prasyarat: konfigurasi MySQL testing terpisah dari production, migration terbaru, data demo akun/master/jadwal/materi/tugas/informasi, browser desktop dan mobile. Jangan menjalankan migrate:fresh pada database berisi data penting. Seeder demo hanya local/testing. Credential dummy terdapat pada dokumentasi authentication, tidak digunakan pada production.

| ID | Skenario/input | Hasil yang diharapkan |
|---|---|---|
| AUTH-01 | Login benar untuk masing-masing role | Session baru, dashboard role sesuai |
| AUTH-02 | Email/password salah atau akun nonaktif | Pesan generik Indonesia, tidak login |
| AUTH-03 | Lima kegagalan lalu percobaan keenam | Ditolak sementara; dapat mencoba kembali setelah 5 menit |
| AUTH-04 | Logout lalu buka URL terlindungi | Redirect login; data session lama tidak dapat digunakan |
| AUTH-05 | Admin reset password pengguna di browser lain | Session pengguna lama ditolak pada request berikutnya; password lama gagal |
| AUTH-06 | Edit nama tanpa mengubah password | Session tetap berlaku |
| ROLE-01 | Guru/siswa membuka URL admin | 403; tidak ada mutasi |
| ROLE-02 | Guru mengganti ID edit/update/delete konten guru lain | 403; data tetap |
| ROLE-03 | Siswa mengganti ID materi/tugas/informasi kelas lain | 403 termasuk unduhan |
| ROLE-04 | Siswa mencoba endpoint mutasi guru | 403; tidak ada perubahan |
| DATA-01 | Admin membuat/edit guru dan siswa | Akun+profil konsisten, password hashed, kelas/NIS tersimpan |
| DATA-02 | Email/NIS duplikat, FK palsu, required kosong | Pesan validasi Indonesia, tidak tersimpan |
| DATA-03 | Kelola tahun ajaran/kelas/mapel/penugasan | Relasi benar; kombinasi unik dijaga |
| DATA-04 | Nonaktifkan master berelasi/akun | Data terkait tidak orphan; akses pembelajaran mengikuti status |
| DATA-05 | Nonaktifkan akun sendiri/admin terakhir | Ditolak dengan pesan yang sesuai |
| SCH-01 | Jadwal jam selesai sebelum mulai | Ditolak |
| SCH-02 | Jam bentrok pada guru/kelas yang sama | Ditolak; jadwal bersebelahan boleh |
| SCH-03 | Lihat jadwal sebagai guru/siswa | Hanya sesuai penugasan/kelas aktif |
| CONTENT-01 | CRUD materi/tugas/informasi pada penugasan sendiri | Berhasil; feedback tersedia; konten escaped |
| CONTENT-02 | Pilih penugasan bukan miliknya | Ditolak, resource tetap |
| CONTENT-03 | Draft atau tanggal publikasi mendatang | Tidak terlihat siswa |
| CONTENT-04 | Tugas deadline sebelum publikasi | Ditolak; deadline opsional |
| CONTENT-05 | Tugas melewati deadline | Tetap dapat dibaca; tidak ada submission/nilai |
| CONTENT-06 | Arsip konten lalu akses URL lama | Tidak tersedia, termasuk lampiran |
| FILE-01 | PDF/JPEG/PNG sah ≤5 MB | Disimpan private dengan nama acak; unduh hanya berizin |
| FILE-02 | PHP, file palsu, double extension, >5 MB | Ditolak; tidak ada file tersimpan |
| FILE-03 | Ganti/hapus lampiran | Metadata benar; file lama dibersihkan setelah commit |
| FILE-04 | Unduh tanpa login/role/kelas yang sesuai | Tidak memperoleh file |
| SEC-01 | Kirim form tanpa CSRF | 419; tidak ada mutasi |
| SEC-02 | Konten script atau payload SQL pada form/search | Ditampilkan escaped/tidak mengubah struktur query |
| SEC-03 | Resource hilang/exception dengan debug=false | Error ramah; tanpa SQL/path/credential |
| DASH-01 | Statistik berubah setelah CRUD/arsip | Sesuai database, tidak palsu |
| DASH-02 | Konten dashboard guru/siswa berbeda | Sesuai kewenangan, maksimum lima terbaru per jenis |
| UX-01 | Search, pagination, input array invalid | Server validation; form tetap dapat dibuka |
| UX-02 | Database kosong/tanpa penugasan/kelas | Empty state, tidak error |
| UX-03 | Desktop/tablet/mobile dan keyboard | Tabel dapat digulir, form terbaca, navigasi dapat digunakan |

Automated suite: php artisan test --compact. Area tersedia: AuthenticationTest, LearningAuthorizationTest, MasterDataTest, ScheduleTest, MaterialTest, AssignmentTest, LearningInformationTest, DashboardTest, SecurityReviewTest, SessionRevocationTest, schema dan demo seeders. CSRF diperiksa dengan bypass testing framework dimatikan pada skenario khusus. Fixture yang berpindah akun membersihkan fingerprint session akun sebelumnya untuk mensimulasikan login berbeda; test revokasi mempertahankan fingerprint lama pada akun yang sama.

Batas verifikasi: belum menjalankan manual black-box/visual responsive atau transaksi/concurrency pada MySQL aktual. Session production, HTTPS, cookie Secure, cache persisten, privilege MySQL, backup, dan konfigurasi web server perlu bukti dari environment deployment. Ini bukan penetration test atau antivirus scan.
