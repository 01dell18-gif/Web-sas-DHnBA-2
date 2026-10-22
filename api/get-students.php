<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/functions.php';

$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

if ($classId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter class_id tidak valid.'
    ]);
    exit;
}

try {
    $classInfo = dbFetch("SELECT id, nama_kelas, tingkat, program_keahlian FROM kelas WHERE id = :id", ['id' => $classId]);
    if (!$classInfo) {
        echo json_encode([
            'success' => false,
            'message' => 'Kelas tidak ditemukan.'
        ]);
        exit;
    }

    $students = dbFetchAll("SELECT id, nis, nisn, nama FROM siswa WHERE class_id = :cid AND aktif = 1 ORDER BY nama ASC", ['cid' => $classId]);

    echo json_encode([
        'success' => true,
        'class' => $classInfo,
        'students' => $students,
        'count' => count($students)
    ], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}
