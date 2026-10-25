<?php
/**
 * Database Migration & Seeder Script
 * Generator Absensi dan Berita Acara Ujian - SMK Negeri 1 Klungkung
 */

require_once __DIR__ . '/../config/database.php';

echo "=== Initializing Database Tables ===\n";
$db = getDb();

// 1. Table users
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    password_plain VARCHAR(255) DEFAULT '',
    nama_lengkap VARCHAR(100) DEFAULT '',
    role VARCHAR(20) DEFAULT 'admin',
    foto VARCHAR(255) DEFAULT NULL,
    created_at DATETIME,
    updated_at DATETIME
);");

// 2. Table kelas
$db->exec("CREATE TABLE IF NOT EXISTS kelas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama_kelas VARCHAR(50) NOT NULL,
    tingkat VARCHAR(10) DEFAULT 'XII',
    program_keahlian VARCHAR(100) DEFAULT '',
    aktif INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);");

// 3. Table siswa
$db->exec("CREATE TABLE IF NOT EXISTS siswa (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_id INTEGER NOT NULL,
    nama_kelas VARCHAR(50) DEFAULT '',
    nis VARCHAR(30) DEFAULT '',
    nisn VARCHAR(30) DEFAULT '',
    nama VARCHAR(150) NOT NULL,
    program_keahlian VARCHAR(100) DEFAULT '',
    aktif INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);");

// 4. Table template_ujian
$db->exec("CREATE TABLE IF NOT EXISTS template_ujian (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama_ujian VARCHAR(150) NOT NULL,
    tahun_ajaran VARCHAR(20) NOT NULL,
    judul_berita_acara TEXT,
    redaksi_isi TEXT,
    redaksi_pembuka TEXT,
    redaksi_poin_1 TEXT,
    redaksi_poin_2 TEXT,
    label_catatan TEXT,
    aktif INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);");

// 5. Table absensi
$db->exec("CREATE TABLE IF NOT EXISTS absensi (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama_ujian VARCHAR(150) NOT NULL,
    tahun_ajaran VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    class_id INTEGER NOT NULL,
    nama_kelas VARCHAR(50) NOT NULL,
    tingkat VARCHAR(10) DEFAULT 'XII',
    mata_pelajaran VARCHAR(150) NOT NULL,
    sesi VARCHAR(20) DEFAULT '1',
    ruang VARCHAR(50) DEFAULT '',
    tanggal_ujian DATE NOT NULL,
    jam_mulai VARCHAR(10) NOT NULL,
    jam_selesai VARCHAR(10) NOT NULL,
    peserta_hadir INTEGER DEFAULT 0,
    peserta_dispensasi INTEGER DEFAULT 0,
    peserta_izin INTEGER DEFAULT 0,
    peserta_sakit INTEGER DEFAULT 0,
    peserta_tidak_hadir INTEGER DEFAULT 0,
    total_peserta INTEGER DEFAULT 0,
    nama_pengawas_1 VARCHAR(100) NOT NULL,
    nip_pengawas_1 VARCHAR(50) DEFAULT '',
    nama_pengawas_2 VARCHAR(100) DEFAULT '',
    nip_pengawas_2 VARCHAR(50) DEFAULT '',
    signature_mode VARCHAR(20) DEFAULT 'digital',
    ttd_pengawas_1 TEXT,
    ttd_pengawas_2 TEXT,
    pdf_path VARCHAR(255) DEFAULT '',
    file_size INTEGER DEFAULT 0,
    data_siswa TEXT,
    created_at DATETIME,
    updated_at DATETIME
);");

// 6. Table berita_acara
$db->exec("CREATE TABLE IF NOT EXISTS berita_acara (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_id INTEGER DEFAULT 1,
    nama_ujian VARCHAR(150) NOT NULL,
    tahun_ajaran VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    class_id INTEGER NOT NULL,
    nama_kelas VARCHAR(50) NOT NULL,
    tingkat VARCHAR(10) DEFAULT 'XII',
    mata_pelajaran VARCHAR(150) NOT NULL,
    tanggal_ujian DATE NOT NULL,
    jam_mulai VARCHAR(10) NOT NULL,
    jam_selesai VARCHAR(10) NOT NULL,
    ruang VARCHAR(50) NOT NULL,
    peserta_seharusnya INTEGER DEFAULT 0,
    peserta_hadir INTEGER DEFAULT 0,
    peserta_tidak_hadir INTEGER DEFAULT 0,
    blangko_berita_acara INTEGER DEFAULT 1,
    blangko_daftar_hadir INTEGER DEFAULT 1,
    catatan_pelaksanaan TEXT,
    nama_pengawas_1 VARCHAR(100) NOT NULL,
    nip_pengawas_1 VARCHAR(50) DEFAULT '',
    nama_pengawas_2 VARCHAR(100) DEFAULT '',
    nip_pengawas_2 VARCHAR(50) DEFAULT '',
    signature_mode VARCHAR(20) DEFAULT 'digital',
    ttd_pengawas_1 TEXT,
    ttd_pengawas_2 TEXT,
    foto_dokumentasi TEXT,
    pdf_path VARCHAR(255) DEFAULT '',
    file_size INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);");

// 7. Table pengaturan
$db->exec("CREATE TABLE IF NOT EXISTS pengaturan (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT
);");

// 8. Table activity_logs
$db->exec("CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    waktu DATETIME NOT NULL,
    aktivitas VARCHAR(50) NOT NULL,
    keterangan TEXT NOT NULL,
    ip_address VARCHAR(50) DEFAULT '127.0.0.1'
);");

// 9. Table guru
$db->exec("CREATE TABLE IF NOT EXISTS guru (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama VARCHAR(150) NOT NULL,
    nip VARCHAR(50) DEFAULT '-',
    jabatan VARCHAR(100) DEFAULT 'Guru / Pengawas',
    aktif INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);");

// 10. Table mapel
$db->exec("CREATE TABLE IF NOT EXISTS mapel (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kode_mapel VARCHAR(50) DEFAULT '',
    nama_mapel VARCHAR(150) NOT NULL,
    kelompok VARCHAR(50) DEFAULT 'Kejuruan',
    aktif INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);");

echo "Tables created successfully!\n\n";

// --- SEED ADMIN USER ---
$userCount = dbValue("SELECT COUNT(*) FROM users WHERE username = 'admin'");
if (!$userCount) {
    dbInsert('users', [
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_BCRYPT),
        'nama_lengkap' => 'Administrator SAS SMK',
        'role' => 'admin',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    echo "Default user 'admin' (password: admin123) seeded.\n";
}

$operatorCount = dbValue("SELECT COUNT(*) FROM users WHERE username = 'operator'");
if (!$operatorCount) {
    dbInsert('users', [
        'username' => 'operator',
        'password' => password_hash('operator123', PASSWORD_BCRYPT),
        'nama_lengkap' => 'Operator SAS SMK',
        'role' => 'operator',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    echo "Default user 'operator' (password: operator123) seeded.\n";
}

// --- SEED SETTINGS ---
$defaultSettings = [
    'nama_sekolah' => 'SMK NEGERI FYJ Channel',
    'alamat_sekolah' => 'Jl. Merpati No. 1',
    'kop_mode' => 'image',
    'kop_image_path' => 'assets/img/kop-header.png',
    'kop_baris_1' => 'PEMERINTAH PROVINSI FYJ',
    'kop_baris_2' => 'DINAS PENDIDIKAN KEPEMUDAAN DAN OLAHRAGA',
    'kop_baris_3' => 'SMK NEGERI FYJ Channel',
    'kop_telepon' => 'Telp. (0300) 2002 | Email: smkn@yahoo.com',
    'kop_website' => 'Website: smkn.sch.id',
    'logo_path' => 'storage/uploads/logo/logo_default.png',
    'google_drive_folder_url' => 'https://drive.google.com/drive/folders/1sHpxyW6DbHSp1D7KNN-_xTu8fG9rY35h?usp=sharing'
];

foreach ($defaultSettings as $k => $v) {
    $exists = dbValue("SELECT COUNT(*) FROM pengaturan WHERE setting_key = :k", ['k' => $k]);
    if (!$exists) {
        dbInsert('pengaturan', ['setting_key' => $k, 'setting_value' => $v]);
    }
}
echo "Settings seeded.\n";

// --- SEED TEMPLATE UJIAN ---
$tplCount = dbValue("SELECT COUNT(*) FROM template_ujian WHERE id = 1");
if (!$tplCount) {
    dbInsert('template_ujian', [
        'id' => 1,
        'nama_ujian' => 'Sumatif Akhir Semester (SAS) Ganjil',
        'tahun_ajaran' => '2026/2027',
        'judul_berita_acara' => "BERITA ACARA \r\nPENYELENGGARAAN SUMATIF AKHIR SEMESTER (SAS) GANJIL\r\nTAHUN AJARAN [tahun_ajaran]",
        'redaksi_isi' => '<p>Pada hari ini&nbsp;<strong>[hari]</strong>, tanggal&nbsp;<strong>[tanggal]</strong>&nbsp;(<strong><i>[tanggal_teks]</i></strong>) bulan&nbsp;<strong>[bulan]</strong>&nbsp;tahun&nbsp;<strong>[tahun]</strong>&nbsp;(<strong><i>[tahun_teks]</i></strong>):</p><ol style="margin-top: 6px; margin-bottom: 12px;"><li style="margin-bottom: 8px;">Telah diselenggarakan&nbsp;<strong>[nama_ujian]</strong>&nbsp;Tahun Ajaran&nbsp;<strong>[tahun_ajaran]</strong>&nbsp;dari pukul&nbsp;<strong>[jam_mulai]</strong>&nbsp;sampai dengan pukul&nbsp;<strong>[jam_selesai]</strong>&nbsp;WITA.<ul style="margin-bottom: 6px; margin-left: 20px; list-style-type: disc;"><li>Pada Sekolah:&nbsp;<strong>[sekolah]</strong></li><li>Kelas:&nbsp;<strong>[kelas]</strong></li><li>Mata Pelajaran:&nbsp;<strong>[mata_pelajaran]</strong></li><li>Ruang:&nbsp;<strong>[ruang]</strong></li><li>Jumlah Peserta Seharusnya:&nbsp;<strong>[peserta_seharusnya]</strong>&nbsp;orang</li><li>Jumlah Peserta yang Tidak Hadir:&nbsp;<strong>[peserta_tidak_hadir]</strong>&nbsp;orang</li><li>Jumlah Peserta yang Hadir:&nbsp;<strong>[peserta_hadir]</strong>&nbsp;orang</li></ul></li><li style="margin-bottom: 8px;">Telah mengisi&nbsp;lembar blangko berita acara sebanyak&nbsp;<strong>[blangko_berita_acara]</strong>&nbsp;eksemplar dan blangko daftar hadir sebanyak&nbsp;<strong>[blangko_daftar_hadir]</strong>&nbsp;eksemplar, untuk Mata Pelajaran&nbsp;<strong>[mata_pelajaran]</strong>&nbsp;di ruang&nbsp;<strong>[ruang]</strong>&nbsp;dan kelas&nbsp;<strong>[kelas]</strong>&nbsp;dengan disaksikan oleh para peserta.</li><li style="margin-bottom: 8px;">Catatan selama pelaksanaan ujian (kendala teknis, kejadian khusus, atau keterangan lain):<br><em>[catatan_pelaksanaan]</em></li></ol><p style="margin-top: 15px;">Demikian Berita Acara ini dibuat dengan sesungguhnya.</p><p style="margin-top: 15px;"><span style="font-size: 14.6667px;"><br></span></p><p style="text-align: center; margin-top: 15px;"><span style="font-size: 14.6667px;">Yang membuat berita acara :</span></p>',
        'redaksi_pembuka' => 'Pada hari ini [hari], tanggal [tanggal] bulan [bulan] tahun [tahun].',
        'redaksi_poin_1' => "Telah diselenggarakan [nama_ujian] Tahun Ajaran [tahun_ajaran] dari pukul [jam_mulai] sampai dengan pukul [jam_selesai].\n- Pada Sekolah: [sekolah]\n- Mata Pelajaran: [mata_pelajaran]\n- Kelas: [kelas]\n- Ruang: [ruang]\n- Jumlah Peserta Seharusnya: [peserta_seharusnya] orang\n- Jumlah Peserta yang Tidak Hadir: [peserta_tidak_hadir] orang\n- Jumlah Peserta yang Hadir: [peserta_hadir] orang",
        'redaksi_poin_2' => 'Telah dibagikan tautan soal ujian online untuk Mata Pelajaran [mata_pelajaran] di ruang [ruang] dengan disaksikan oleh para peserta, serta telah dibagikan lembar blangko berita acara sebanyak [blangko_berita_acara] eksemplar dan blangko daftar hadir sebanyak [blangko_daftar_hadir] eksemplar.',
        'label_catatan' => 'Catatan selama pelaksanaan ujian (kendala teknis, kejadian khusus, atau keterangan lain):',
        'aktif' => 1,
        'created_at' => '2026-10-14 11:23:07',
        'updated_at' => '2026-10-18 13:24:00'
    ]);
    echo "Exam template seeded.\n";
}

// --- SEED KELAS & SISWA ---
if (file_exists(__DIR__ . '/../seed_data.json')) {
    $seedData = json_decode(file_get_contents(__DIR__ . '/../seed_data.json'), true);
    $kelasSeeded = 0;
    $siswaSeeded = 0;
    foreach ($seedData as $cid => $item) {
        $cInfo = $item['class'] ?? [];
        $students = $item['students'] ?? [];
        
        $kExists = dbValue("SELECT COUNT(*) FROM kelas WHERE id = :id", ['id' => $cid]);
        if (!$kExists) {
            dbInsert('kelas', [
                'id' => $cid,
                'nama_kelas' => $cInfo['nama_kelas'] ?? "Kelas {$cid}",
                'tingkat' => $cInfo['tingkat'] ?? 'XII',
                'program_keahlian' => $cInfo['program_keahlian'] ?? '',
                'aktif' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $kelasSeeded++;
        }
        
        foreach ($students as $s) {
            $sid = $s['id'];
            $sExists = dbValue("SELECT COUNT(*) FROM siswa WHERE id = :id", ['id' => $sid]);
            if (!$sExists) {
                dbInsert('siswa', [
                    'id' => $sid,
                    'class_id' => $cid,
                    'nis' => $s['nis'] ?? '',
                    'nisn' => $s['nisn'] ?? '',
                    'nama' => $s['nama'] ?? '',
                    'aktif' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $siswaSeeded++;
            }
        }
    }
    echo "Kelas seeded: {$kelasSeeded}, Siswa seeded: {$siswaSeeded}.\n";
}

// --- SEED ABSENSI & BERITA ACARA RECORDS ---
if (file_exists(__DIR__ . '/../seed_records.json')) {
    $recData = json_decode(file_get_contents(__DIR__ . '/../seed_records.json'), true);
    $dhSeeded = 0;
    $baSeeded = 0;
    
    foreach ($recData['absensi'] ?? [] as $dh) {
        $dhId = $dh['id'];
        $exists = dbValue("SELECT COUNT(*) FROM absensi WHERE id = :id", ['id' => $dhId]);
        if (!$exists) {
            dbInsert('absensi', [
                'id' => $dhId,
                'nama_ujian' => $dh['nama_ujian'] ?? 'Sumatif Akhir Semester (SAS) Ganjil',
                'tahun_ajaran' => $dh['tahun_ajaran'] ?? '2026/2027',
                'semester' => $dh['semester'] ?? 'Ganjil',
                'class_id' => $dh['class_id'] ?? 0,
                'nama_kelas' => $dh['nama_kelas'] ?? '',
                'tingkat' => $dh['tingkat'] ?? 'XII',
                'mata_pelajaran' => $dh['mata_pelajaran'] ?? '',
                'sesi' => $dh['sesi'] ?? '1',
                'ruang' => $dh['ruang'] ?? '',
                'tanggal_ujian' => $dh['tanggal_ujian'] ?? date('Y-m-d'),
                'jam_mulai' => $dh['jam_mulai'] ?? '07:30',
                'jam_selesai' => $dh['jam_selesai'] ?? '09:30',
                'peserta_hadir' => $dh['peserta_hadir'] ?? 0,
                'peserta_dispensasi' => $dh['peserta_dispensasi'] ?? 0,
                'peserta_izin' => $dh['peserta_izin'] ?? 0,
                'peserta_sakit' => $dh['peserta_sakit'] ?? 0,
                'peserta_tidak_hadir' => $dh['peserta_tidak_hadir'] ?? 0,
                'total_peserta' => $dh['total_peserta'] ?? 0,
                'nama_pengawas_1' => $dh['nama_pengawas_1'] ?? '',
                'nip_pengawas_1' => $dh['nip_pengawas_1'] ?? '',
                'nama_pengawas_2' => $dh['nama_pengawas_2'] ?? '',
                'nip_pengawas_2' => $dh['nip_pengawas_2'] ?? '',
                'signature_mode' => $dh['signature_mode'] ?? 'digital',
                'ttd_pengawas_1' => '',
                'ttd_pengawas_2' => '',
                'pdf_path' => $dh['pdf_path'] ?? '',
                'file_size' => $dh['file_size'] ?? 0,
                'data_siswa' => '[]',
                'created_at' => $dh['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $dh['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
            $dhSeeded++;
        }
    }
    
    foreach ($recData['berita_acara'] ?? [] as $ba) {
        $baId = $ba['id'];
        $exists = dbValue("SELECT COUNT(*) FROM berita_acara WHERE id = :id", ['id' => $baId]);
        if (!$exists) {
            dbInsert('berita_acara', [
                'id' => $baId,
                'template_id' => 1,
                'nama_ujian' => $ba['nama_ujian'] ?? 'Sumatif Akhir Semester (SAS) Ganjil',
                'tahun_ajaran' => $ba['tahun_ajaran'] ?? '2026/2027',
                'semester' => $ba['semester'] ?? 'Ganjil',
                'class_id' => $ba['class_id'] ?? 0,
                'nama_kelas' => $ba['nama_kelas'] ?? '',
                'tingkat' => $ba['tingkat'] ?? 'XII',
                'mata_pelajaran' => $ba['mata_pelajaran'] ?? '',
                'tanggal_ujian' => $ba['tanggal_ujian'] ?? date('Y-m-d'),
                'jam_mulai' => $ba['jam_mulai'] ?? '07:30',
                'jam_selesai' => $ba['jam_selesai'] ?? '09:30',
                'ruang' => $ba['ruang'] ?? '',
                'peserta_seharusnya' => $ba['peserta_seharusnya'] ?? 0,
                'peserta_hadir' => $ba['peserta_hadir'] ?? 0,
                'peserta_tidak_hadir' => $ba['peserta_tidak_hadir'] ?? 0,
                'blangko_berita_acara' => $ba['blangko_berita_acara'] ?? 1,
                'blangko_daftar_hadir' => $ba['blangko_daftar_hadir'] ?? 1,
                'catatan_pelaksanaan' => $ba['catatan_pelaksanaan'] ?? '',
                'nama_pengawas_1' => $ba['nama_pengawas_1'] ?? '',
                'nip_pengawas_1' => $ba['nip_pengawas_1'] ?? '',
                'nama_pengawas_2' => $ba['nama_pengawas_2'] ?? '',
                'nip_pengawas_2' => $ba['nip_pengawas_2'] ?? '',
                'signature_mode' => $ba['signature_mode'] ?? 'digital',
                'ttd_pengawas_1' => '',
                'ttd_pengawas_2' => '',
                'foto_dokumentasi' => is_string($ba['foto_dokumentasi'] ?? null) ? $ba['foto_dokumentasi'] : json_encode($ba['foto_dokumentasi'] ?? []),
                'pdf_path' => $ba['pdf_path'] ?? '',
                'file_size' => $ba['file_size'] ?? 0,
                'created_at' => $ba['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $ba['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
            $baSeeded++;
        }
    }
    echo "Arsip Absensi seeded: {$dhSeeded}, Arsip Berita Acara seeded: {$baSeeded}.\n";
}

echo "=== Seeding Complete! ===\n";
