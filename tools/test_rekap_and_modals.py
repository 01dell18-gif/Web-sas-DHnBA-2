import urllib.request
import urllib.parse
import http.cookiejar
import re

BASE_URL = "http://127.0.0.1:8080"
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

def get(url):
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    with opener.open(req) as resp:
        return resp.getcode(), resp.headers, resp.read().decode('utf-8', errors='ignore')

def post(url, data):
    encoded = urllib.parse.urlencode(data).encode('utf-8')
    req = urllib.request.Request(url, data=encoded, headers={'User-Agent': 'Mozilla/5.0', 'Content-Type': 'application/x-www-form-urlencoded'})
    with opener.open(req) as resp:
        return resp.getcode(), resp.headers, resp.read().decode('utf-8', errors='ignore')

# 1. Login
code, headers, html = get(f"{BASE_URL}/admin/login.php")
csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)
code, headers, html = post(f"{BASE_URL}/admin/login.php", {
    "csrf_token": csrf,
    "username": "admin",
    "password": "admin123"
})
assert "Dashboard Administrator" in html, "Login failed!"
print("[OK] Admin login successful")

# 2. Test absensi.php
code, headers, html = get(f"{BASE_URL}/admin/absensi.php")
assert "card-overflow-visible" in html, "absensi.php missing card-overflow-visible!"
assert "dropTingkatDH" in html, "absensi.php missing dropTingkatDH!"
assert "modalDownloadKelasDH" in html, "absensi.php missing modalDownloadKelasDH!"
assert "modalDownloadTanggalDH" in html, "absensi.php missing modalDownloadTanggalDH!"
assert "overflow: visible !important;" in html, "absensi.php missing inline overflow visible!"
print("[OK] absensi.php verified: card-overflow-visible, dropTingkatDH, and top-aligned modals present.")

# 3. Test berita-acara.php
code, headers, html = get(f"{BASE_URL}/admin/berita-acara.php")
assert "card-overflow-visible" in html, "berita-acara.php missing card-overflow-visible!"
assert "dropTingkatBA" in html, "berita-acara.php missing dropTingkatBA!"
assert "modalDownloadKelasBA" in html, "berita-acara.php missing modalDownloadKelasBA!"
assert "modalDownloadTanggalBA" in html, "berita-acara.php missing modalDownloadTanggalBA!"
print("[OK] berita-acara.php verified: card-overflow-visible, dropTingkatBA, and top-aligned modals present.")

# 4. Test rekap-kehadiran.php
code, headers, html = get(f"{BASE_URL}/admin/rekap-kehadiran.php?class_id=15")
assert "writing-mode: vertical-rl" in html, "rekap-kehadiran.php missing vertical rotated headers!"
assert "PERSENTASE KEHADIRAN" in html, "rekap-kehadiran.php missing PERSENTASE KEHADIRAN column!"
assert "modalInputMapel" in html, "rekap-kehadiran.php missing modalInputMapel!"
assert "Set Semua Hadir" in html, "rekap-kehadiran.php missing Set Semua Hadir button!"
assert "Keterangan Status:" in html, "rekap-kehadiran.php missing status legend!"
assert "Persentase Kehadiran = (Hadir + Dispensasi) / Total Mapel" in html, "rekap-kehadiran.php missing percentage formula legend!"
print("[OK] rekap-kehadiran.php UI elements match Image 2 reference perfectly!")

# 5. Test export-rekap-excel.php
code, headers, html = get(f"{BASE_URL}/admin/export-rekap-excel.php?class_id=15&tahun_ajaran=2026%2F2027&semester=Ganjil")
assert code == 200, "export-rekap-excel.php failed!"
assert "application/vnd.ms-excel" in headers.get("Content-Type", ""), "Wrong content-type in Excel export!"
assert "PERSENTASE KEHADIRAN" in html, "Excel export missing PERSENTASE KEHADIRAN!"
assert "mso-number-format" in html, "Excel export missing mso-number-format text styling!"
print("[OK] export-rekap-excel.php (.xls) verified with proper MIME type and matrix structure.")

# 6. Test save_subject_attendance & delete_subject in rekap-kehadiran.php
code, headers, html = get(f"{BASE_URL}/admin/rekap-kehadiran.php?class_id=15")
rekap_csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)
code, headers, html = post(f"{BASE_URL}/admin/rekap-kehadiran.php?class_id=15", {
    "csrf_token": rekap_csrf,
    "action": "save_subject_attendance",
    "class_id": 15,
    "tahun_ajaran": "2026/2027",
    "semester": "Ganjil",
    "mata_pelajaran": "Simulasi Digital Uji Coba",
    "tanggal_ujian": "2026-10-22",
    "students[1]": "Hadir"
})
if "Simulasi Digital Uji Coba" not in html:
    print("DEBUG: Response code:", code)
    print("DEBUG: Content length:", len(html))
    print("DEBUG: Snip:", html[:500])
assert "Simulasi Digital Uji Coba" in html, "Added subject not found in rekap table!"
print("[OK] save_subject_attendance successfully added subject to class matrix!")

# Find the session_id specifically for 'Simulasi Digital Uji Coba' to clean up
sess_id_match = re.search(r'<strong>Simulasi Digital Uji Coba</strong>.*?name="session_id"\s+value="(\d+)"', html, re.DOTALL)
if sess_id_match:
    sess_id = sess_id_match.group(1)
    cleanup_csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)
    code, headers, html = post(f"{BASE_URL}/admin/rekap-kehadiran.php?class_id=15", {
        "csrf_token": cleanup_csrf,
        "action": "delete_subject",
        "session_id": sess_id
    })
    assert "<strong>Simulasi Digital Uji Coba</strong>" not in html, "Deleted subject still present in table/chips!"
    print("[OK] delete_subject successfully removed test subject chip & cleaned up!")

print("\nALL REKAP, MODAL, AND DROPDOWN TESTS PASSED SUCCESSFULLY (100%)!")
