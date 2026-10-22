<?php
require_once __DIR__ . '/../config/functions.php';

$pdo = getDb();
$pdo->exec("
CREATE TABLE IF NOT EXISTS guru (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama TEXT NOT NULL,
    nip TEXT,
    jabatan TEXT DEFAULT 'Guru / Pengawas',
    aktif INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
");

$existingCount = (int)dbValue("SELECT COUNT(*) FROM guru");
if ($existingCount === 0) {
    $teachers = [];
    $rows1 = dbFetchAll("SELECT nama_pengawas_1 as nama, nip_pengawas_1 as nip FROM absensi WHERE nama_pengawas_1 != ''");
    $rows2 = dbFetchAll("SELECT nama_pengawas_2 as nama, nip_pengawas_2 as nip FROM absensi WHERE nama_pengawas_2 != ''");
    $rows3 = dbFetchAll("SELECT nama_pengawas_1 as nama, nip_pengawas_1 as nip FROM berita_acara WHERE nama_pengawas_1 != ''");
    $rows4 = dbFetchAll("SELECT nama_pengawas_2 as nama, nip_pengawas_2 as nip FROM berita_acara WHERE nama_pengawas_2 != ''");

    foreach (array_merge($rows1, $rows2, $rows3, $rows4) as $r) {
        $n = trim($r['nama']);
        $nip = trim($r['nip'] ?? '');
        if ($n !== '' && !isset($teachers[$n])) {
            $teachers[$n] = $nip;
        } elseif ($n !== '' && empty($teachers[$n]) && $nip !== '') {
            $teachers[$n] = $nip;
        }
    }

    ksort($teachers);
    $now = date('Y-m-d H:i:s');
    foreach ($teachers as $nama => $nip) {
        dbInsert('guru', [
            'nama' => $nama,
            'nip' => $nip ?: '-',
            'jabatan' => 'Guru / Pengawas',
            'aktif' => 1,
            'created_at' => $now,
            'updated_at' => $now
        ]);
    }
    echo "SUCCESS: Inserted " . count($teachers) . " teachers into guru table.\n";
} else {
    echo "Guru table already has {$existingCount} teachers.\n";
}
