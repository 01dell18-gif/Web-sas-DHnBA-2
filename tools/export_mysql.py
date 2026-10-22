import sqlite3
import re

conn = sqlite3.connect('database/sas.sqlite')
conn.row_factory = sqlite3.Row
cursor = conn.cursor()

tables = ['users', 'pengaturan', 'template_ujian', 'kelas', 'siswa', 'absensi', 'berita_acara', 'activity_logs']

sql_lines = [
    "-- ========================================================",
    "-- Database Schema & Data Clone for MySQL / MariaDB",
    "-- Target: https://sas.doantara.my.id/",
    "-- SMK NEGERI 1 KLUNGKUNG",
    "-- ========================================================\n",
    "SET FOREIGN_KEY_CHECKS = 0;\n"
]

mysql_schemas = {
    'users': """
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `nama_lengkap` VARCHAR(100) DEFAULT '',
    `role` VARCHAR(20) DEFAULT 'admin',
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
""",
    'kelas': """
CREATE TABLE IF NOT EXISTS `kelas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kelas` VARCHAR(50) NOT NULL,
    `tingkat` VARCHAR(10) DEFAULT 'XII',
    `program_keahlian` VARCHAR(100) DEFAULT '',
    `aktif` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
""",
    'siswa': """
CREATE TABLE IF NOT EXISTS `siswa` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `class_id` INT NOT NULL,
    `nis` VARCHAR(30) DEFAULT '',
    `nisn` VARCHAR(30) DEFAULT '',
    `nama` VARCHAR(150) NOT NULL,
    `aktif` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    KEY `idx_class_id` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
""",
    'template_ujian': """
CREATE TABLE IF NOT EXISTS `template_ujian` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_ujian` VARCHAR(150) NOT NULL,
    `tahun_ajaran` VARCHAR(20) NOT NULL,
    `judul_berita_acara` TEXT NULL,
    `redaksi_isi` LONGTEXT NULL,
    `redaksi_pembuka` TEXT NULL,
    `redaksi_poin_1` TEXT NULL,
    `redaksi_poin_2` TEXT NULL,
    `label_catatan` TEXT NULL,
    `aktif` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
""",
    'absensi': """
CREATE TABLE IF NOT EXISTS `absensi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_ujian` VARCHAR(150) NOT NULL,
    `tahun_ajaran` VARCHAR(20) NOT NULL,
    `semester` VARCHAR(20) NOT NULL,
    `class_id` INT NOT NULL,
    `nama_kelas` VARCHAR(50) NOT NULL,
    `tingkat` VARCHAR(10) DEFAULT 'XII',
    `mata_pelajaran` VARCHAR(150) NOT NULL,
    `sesi` VARCHAR(20) DEFAULT '1',
    `ruang` VARCHAR(50) DEFAULT '',
    `tanggal_ujian` DATE NOT NULL,
    `jam_mulai` VARCHAR(10) NOT NULL,
    `jam_selesai` VARCHAR(10) NOT NULL,
    `peserta_hadir` INT DEFAULT 0,
    `peserta_dispensasi` INT DEFAULT 0,
    `peserta_izin` INT DEFAULT 0,
    `peserta_sakit` INT DEFAULT 0,
    `peserta_tidak_hadir` INT DEFAULT 0,
    `total_peserta` INT DEFAULT 0,
    `nama_pengawas_1` VARCHAR(100) NOT NULL,
    `nip_pengawas_1` VARCHAR(50) DEFAULT '',
    `nama_pengawas_2` VARCHAR(100) DEFAULT '',
    `nip_pengawas_2` VARCHAR(50) DEFAULT '',
    `signature_mode` VARCHAR(20) DEFAULT 'digital',
    `ttd_pengawas_1` LONGTEXT NULL,
    `ttd_pengawas_2` LONGTEXT NULL,
    `pdf_path` VARCHAR(255) DEFAULT '',
    `file_size` INT DEFAULT 0,
    `data_siswa` LONGTEXT NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    KEY `idx_class_id` (`class_id`),
    KEY `idx_tanggal` (`tanggal_ujian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
""",
    'berita_acara': """
CREATE TABLE IF NOT EXISTS `berita_acara` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `template_id` INT DEFAULT 1,
    `nama_ujian` VARCHAR(150) NOT NULL,
    `tahun_ajaran` VARCHAR(20) NOT NULL,
    `semester` VARCHAR(20) NOT NULL,
    `class_id` INT NOT NULL,
    `nama_kelas` VARCHAR(50) NOT NULL,
    `tingkat` VARCHAR(10) DEFAULT 'XII',
    `mata_pelajaran` VARCHAR(150) NOT NULL,
    `tanggal_ujian` DATE NOT NULL,
    `jam_mulai` VARCHAR(10) NOT NULL,
    `jam_selesai` VARCHAR(10) NOT NULL,
    `ruang` VARCHAR(50) NOT NULL,
    `peserta_seharusnya` INT DEFAULT 0,
    `peserta_hadir` INT DEFAULT 0,
    `peserta_tidak_hadir` INT DEFAULT 0,
    `blangko_berita_acara` INT DEFAULT 1,
    `blangko_daftar_hadir` INT DEFAULT 1,
    `catatan_pelaksanaan` TEXT NULL,
    `nama_pengawas_1` VARCHAR(100) NOT NULL,
    `nip_pengawas_1` VARCHAR(50) DEFAULT '',
    `nama_pengawas_2` VARCHAR(100) DEFAULT '',
    `nip_pengawas_2` VARCHAR(50) DEFAULT '',
    `signature_mode` VARCHAR(20) DEFAULT 'digital',
    `ttd_pengawas_1` LONGTEXT NULL,
    `ttd_pengawas_2` LONGTEXT NULL,
    `foto_dokumentasi` LONGTEXT NULL,
    `pdf_path` VARCHAR(255) DEFAULT '',
    `file_size` INT DEFAULT 0,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    KEY `idx_class_id` (`class_id`),
    KEY `idx_tanggal` (`tanggal_ujian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
""",
    'pengaturan': """
CREATE TABLE IF NOT EXISTS `pengaturan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) UNIQUE NOT NULL,
    `setting_value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
""",
    'activity_logs': """
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `waktu` DATETIME NOT NULL,
    `aktivitas` VARCHAR(50) NOT NULL,
    `keterangan` TEXT NOT NULL,
    `ip_address` VARCHAR(50) DEFAULT '127.0.0.1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"""
}

for tbl in tables:
    sql_lines.append(f"DROP TABLE IF EXISTS `{tbl}`;")
    sql_lines.append(mysql_schemas[tbl].strip())
    
    rows = cursor.execute(f"SELECT * FROM {tbl}").fetchall()
    if rows:
        cols = [d[0] for d in cursor.description]
        col_list = ", ".join([f"`{c}`" for c in cols])
        
        insert_values = []
        for r in rows:
            vals = []
            for v in r:
                if v is None:
                    vals.append("NULL")
                elif isinstance(v, (int, float)):
                    vals.append(str(v))
                else:
                    escaped = str(v).replace("\\", "\\\\").replace("'", "\\'")
                    vals.append(f"'{escaped}'")
            insert_values.append("(" + ", ".join(vals) + ")")
        
        # Batch into chunks of 50
        for i in range(0, len(insert_values), 50):
            chunk = insert_values[i:i+50]
            sql_lines.append(f"INSERT INTO `{tbl}` ({col_list}) VALUES\n" + ",\n".join(chunk) + ";")
    sql_lines.append("\n")

sql_lines.append("SET FOREIGN_KEY_CHECKS = 1;")

with open('database/database_mysql.sql', 'w', encoding='utf-8') as f:
    f.write("\n".join(sql_lines))

print("Exported database/database_mysql.sql successfully!")
