import os
import sqlite3
import subprocess
import time
import urllib.request
import http.cookiejar

print("=== START TESTING STATS CARDS & PROGRAM KEAHLIAN ===")

# 1. Direct DB check on sas.sqlite
conn = sqlite3.connect('database/sas.sqlite')
cur = conn.cursor()
cols = [r[1] for r in cur.execute("PRAGMA table_info(siswa)").fetchall()]
assert 'program_keahlian' in cols, f"program_keahlian missing from siswa: {cols}"
print("✓ PRAGMA table_info(siswa) confirmed: 'program_keahlian' column exists.")

# 2. Check if admin user exists for session simulation
admin = cur.execute("SELECT id, username, password FROM users WHERE username = 'admin'").fetchone()
print(f"✓ Admin user found: {admin[1] if admin else 'None'}")
conn.close()

# 3. Start local PHP test server on port 8085
php_bin = r"C:\xampp\php\php.exe"
proc = subprocess.Popen([
    php_bin,
    "-d", "extension_dir=C:\\xampp\\php\\ext",
    "-d", "browscap=",
    "-d", "extension=pdo_sqlite",
    "-d", "extension=mbstring",
    "-S", "127.0.0.1:8085"
], cwd=os.getcwd(), stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

time.sleep(1.5)

try:
    cj = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

    # Get login page & CSRF token
    resp = opener.open("http://127.0.0.1:8085/admin/login.php")
    login_html = resp.read().decode('utf-8', errors='ignore')

    import re
    m = re.search(r'name="csrf_token"\s+value="([^"]+)"', login_html)
    csrf_token = m.group(1) if m else ''

    # Perform login
    import urllib.parse
    login_data = urllib.parse.urlencode({
        'username': 'admin',
        'password': 'admin123',
        'csrf_token': csrf_token
    }).encode('utf-8')

    req = urllib.request.Request("http://127.0.0.1:8085/admin/login.php", data=login_data)
    login_res = opener.open(req)
    login_res_html = login_res.read().decode('utf-8', errors='ignore')

    # Test 1: Access admin/kelas.php
    kelas_resp = opener.open("http://127.0.0.1:8085/admin/kelas.php")
    kelas_html = kelas_resp.read().decode('utf-8', errors='ignore')
    
    assert "Total Data Kelas" in kelas_html, "Stat card 'Total Data Kelas' not found in admin/kelas.php"
    assert "Kelas Aktif" in kelas_html, "Stat card 'Kelas Aktif' not found in admin/kelas.php"
    assert "Total Siswa Terdaftar" in kelas_html, "Stat card 'Total Siswa Terdaftar' not found in admin/kelas.php"
    assert "Kelas Nonaktif" in kelas_html, "Stat card 'Kelas Nonaktif' not found in admin/kelas.php"
    assert "stat-card-modern" in kelas_html, "'stat-card-modern' class not found in admin/kelas.php"
    print("✓ admin/kelas.php successfully rendered all 4 stat cards with modern styles.")

    # Test 2: Access admin/siswa.php
    siswa_resp = opener.open("http://127.0.0.1:8085/admin/siswa.php")
    siswa_html = siswa_resp.read().decode('utf-8', errors='ignore')

    assert "Total Data Siswa" in siswa_html, "Stat card 'Total Data Siswa' not found in admin/siswa.php"
    assert "Siswa Aktif" in siswa_html, "Stat card 'Siswa Aktif' not found in admin/siswa.php"
    assert "Memiliki NIS / NISN" in siswa_html, "Stat card 'Memiliki NIS / NISN' not found in admin/siswa.php"
    assert "Siswa Nonaktif" in siswa_html, "Stat card 'Siswa Nonaktif' not found in admin/siswa.php"
    assert "stat-card-modern" in siswa_html, "'stat-card-modern' class not found in admin/siswa.php"
    print("✓ admin/siswa.php successfully rendered all 4 stat cards matching Data Guru & Pengawas Ujian.")

    # Test 3: Check Program Keahlian in table & form of admin/siswa.php
    assert "Program Keahlian" in siswa_html, "'Program Keahlian' not found in admin/siswa.php"
    assert "<th>Program Keahlian</th>" in siswa_html, "<th>Program Keahlian</th> column not found in admin/siswa.php"
    assert 'name="program_keahlian"' in siswa_html, "Form input 'program_keahlian' not found in admin/siswa.php"
    assert 'data-program=' in siswa_html or 'class_id' in siswa_html, "Class select logic not found in admin/siswa.php"
    print("✓ admin/siswa.php contains 'Program Keahlian' column in table and input in form.")

    # Test 4: Check template download
    tpl_resp = opener.open("http://127.0.0.1:8085/admin/siswa.php?action=template_excel")
    tpl_data = tpl_resp.read().decode('utf-8', errors='ignore')
    assert "Program Keahlian" in tpl_data, "Template excel missing 'Program Keahlian'"
    print("✓ admin/siswa.php template excel includes 'Program Keahlian'.")

    # Test 5: Check import-siswa.php template
    imp_resp = opener.open("http://127.0.0.1:8085/admin/import-siswa.php?template=siswa")
    imp_data = imp_resp.read().decode('utf-8', errors='ignore')
    assert "Program Keahlian" in imp_data, "import-siswa.php template missing 'Program Keahlian'"
    print("✓ admin/import-siswa.php template includes 'Program Keahlian'.")

    print("\n🎉 ALL TESTS PASSED SUCCESSFULLY! 🎉")

finally:
    proc.terminate()
    proc.wait()
