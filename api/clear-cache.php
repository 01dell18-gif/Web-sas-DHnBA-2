<?php
/**
 * API Endpoint: Clear Web & Server Cache
 */
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Optional: check session or allow clear
$res = [
    'success' => true,
    'message' => 'Cache web dan temporary server berhasil dibersihkan.',
    'cleared' => []
];

// 1. Clear PHP OpCache if available
if (function_exists('opcache_reset')) {
    @opcache_reset();
    $res['cleared'][] = 'opcache';
}

// 2. Clear stat cache
if (function_exists('clearstatcache')) {
    @clearstatcache(true);
    $res['cleared'][] = 'statcache';
}

// 3. Clear temporary files in storage/cache if any
$cacheDir = BASE_PATH . '/storage/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    foreach ($files as $f) {
        if (is_file($f)) @unlink($f);
    }
    $res['cleared'][] = 'storage_cache';
}

$res['timestamp'] = time();

echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
