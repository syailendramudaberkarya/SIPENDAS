# Fase 9 — Dashboard per role

Administrator: jumlah siswa, guru, kelas, mata pelajaran, materi, dan tugas dari database. Master dihitung untuk semua status; konten terarsip tidak dihitung. Tautan menuju penugasan, jadwal, materi, tugas, dan informasi.

Guru: jumlah kelas dan mata pelajaran distinct dari penugasan aktif, jumlah materi yang dapat diakses, dan jumlah tugas terbit aktif. Penugasan ditampilkan menggunakan pagination server-side dan hanya relasi aktif milik guru. Jadwal hari ini sesuai scope, maksimal lima; materi/tugas/informasi terbaru masing-masing maksimal lima sesuai policy.

Siswa: kelas dan tahun ajaran sendiri, daftar mata pelajaran dari penugasan aktif kelasnya, jadwal hari ini, serta konten terbaru yang boleh diakses. Daftar mata pelajaran menggunakan pagination server-side dan tidak menduplikasi subject meskipun ada lebih dari satu guru. Profil/kelas/tahun tidak aktif tidak menampilkan mata pelajaran. Akun tanpa kelas memperoleh empty state.

Tugas terbit aktif berarti status published, tanggal publikasi tidak mendatang, dan deadline kosong atau belum lewat. Ini hanya ringkasan; tugas lewat deadline tetap dapat dibaca, tanpa submission/penilaian. Halaman dashboard tidak menambah wewenang: middleware auth/active/role tetap berlaku, dan query memakai scope resource.

Query page dan subjects_page divalidasi dengan pesan Bahasa Indonesia. Waktu mengikuti config app.timezone. Automated test meliputi statistik database, arsip, scope kelas/guru, master/profile tidak aktif, pagination, konten terbaru, dan akses lintas role. Test memakai SQLite in-memory; MySQL dan pemeriksaan visual responsif tetap perlu diverifikasi pada environment pemilik project.
