import urllib.request
import urllib.parse
import http.cookiejar
import re

BASE = "http://127.0.0.1:8081"
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

print("1. Testing homepage...")
home = opener.open(f"{BASE}/").read().decode()
assert "Generator Absensi" in home
print("   [OK] Homepage loaded!")

print("2. Testing api/get-students.php?class_id=15...")
st_res = opener.open(f"{BASE}/api/get-students.php?class_id=15").read().decode()
assert "XII AK" in st_res
assert "students" in st_res
print("   [OK] API returned student roster!")

print("3. Testing admin login...")
login_page = opener.open(f"{BASE}/admin/login.php").read().decode()
csrf = re.search(r'name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']', login_page).group(1)
login_data = urllib.parse.urlencode({'csrf_token': csrf, 'username': 'admin', 'password': 'admin123'}).encode()
post_resp = opener.open(f"{BASE}/admin/login.php", data=login_data)
dashboard = post_resp.read().decode()
assert "Dashboard Administrator" in dashboard
print("   [OK] Admin login successful!")

print("4. Testing admin pages...")
pages = [
    '/admin/kelas.php',
    '/admin/siswa.php',
    '/admin/guru.php',
    '/admin/absensi.php',
    '/admin/berita-acara.php',
    '/admin/rekap-kehadiran.php',
    '/admin/rekonsiliasi.php',
    '/admin/template-ujian.php',
    '/admin/pengaturan.php',
    '/admin/users.php',
    '/admin/password.php'
]
for p in pages:
    content = opener.open(f"{BASE}{p}").read().decode()
    assert "navbar" in content
    print(f"   [OK] {p} loaded ({len(content)} bytes)")

print("5. Testing Guru pickers in Absensi & Berita Acara...")
abs_content = opener.open(f"{BASE}/absensi.php").read().decode()
assert "input-guru-search" in abs_content
assert "list_guru_pengawas" in abs_content
assert "handleGuruSearch" in abs_content
print("   [OK] absensi.php has unified typable Nama Lengkap with datalist & auto-search!")

ba_content = opener.open(f"{BASE}/berita-acara.php").read().decode()
assert "input-guru-search" in ba_content
assert "list_guru_pengawas" in ba_content
assert "handleGuruSearch" in ba_content
print("   [OK] berita-acara.php has unified typable Nama Lengkap with datalist & auto-search!")

print("6. Testing Guru Status Quick Access (Ceklis)...")
guru_content = opener.open(f"{BASE}/admin/guru.php").read().decode()
assert "ceklis-status-guru" in guru_content
assert "quickToggleGuru" in guru_content
print("   [OK] admin/guru.php has fast-access status checkbox (ceklis)!")

print("7. Testing Reset Buttons presence...")
kelas_content = opener.open(f"{BASE}/admin/kelas.php").read().decode()
assert "confirmResetAllKelas" in kelas_content
print("   [OK] admin/kelas.php has confirmResetAllKelas button!")

siswa_content = opener.open(f"{BASE}/admin/siswa.php").read().decode()
assert "confirmResetAllSiswa" in siswa_content
print("   [OK] admin/siswa.php has confirmResetAllSiswa button!")

guru_content = opener.open(f"{BASE}/admin/guru.php").read().decode()
assert "confirmResetAllGuru" in guru_content
print("   [OK] admin/guru.php has confirmResetAllGuru button!")

abs_admin = opener.open(f"{BASE}/admin/absensi.php").read().decode()
assert "confirmResetAllAbsensi" in abs_admin
print("   [OK] admin/absensi.php has confirmResetAllAbsensi button!")

ba_admin = opener.open(f"{BASE}/admin/berita-acara.php").read().decode()
assert "confirmResetAllBA" in ba_admin
print("   [OK] admin/berita-acara.php has confirmResetAllBA button!")

rekap_admin = opener.open(f"{BASE}/admin/rekap-kehadiran.php").read().decode()
assert "confirmResetClassAbsensi" in rekap_admin
print("   [OK] admin/rekap-kehadiran.php has confirmResetClassAbsensi button!")

rekon_admin = opener.open(f"{BASE}/admin/rekonsiliasi.php").read().decode()
assert "confirmResetAllDocuments" in rekon_admin
print("   [OK] admin/rekonsiliasi.php has confirmResetAllDocuments button!")

print("\nALL TESTS PASSED WITH 100% SUCCESS!")

