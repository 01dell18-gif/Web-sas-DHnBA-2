<?php
require_once __DIR__ . '/../config/functions.php';

$exists = dbFetch("SELECT id FROM users WHERE username = 'operator'");
if (!$exists) {
    dbInsert('users', [
        'username' => 'operator',
        'password' => password_hash('operator123', PASSWORD_BCRYPT),
        'nama_lengkap' => 'Operator SAS SMKN 1 Klungkung',
        'role' => 'operator',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    echo "Operator user created successfully!\n";
} else {
    echo "Operator user already exists.\n";
}

$all = dbFetchAll("SELECT id, username, nama_lengkap, role FROM users");
print_r($all);
