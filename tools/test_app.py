import urllib.request
import urllib.parse
import http.cookiejar
import re

BASE = "http://127.0.0.1:8080"
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

print("1. Testing homepage...")
home = opener.open(f"{BASE}/").read().decode()
assert "SMK NEGERI 1 KLUNGKUNG" in home
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
    '/admin/absensi.php',
    '/admin/berita-acara.php',
    '/admin/rekap-kehadiran.php',
    '/admin/rekonsiliasi.php',
    '/admin/template-ujian.php',
    '/admin/pengaturan.php',
    '/admin/password.php'
]
for p in pages:
    content = opener.open(f"{BASE}{p}").read().decode()
    assert "navbar" in content
    print(f"   [OK] {p} loaded ({len(content)} bytes)")

print("\nALL TESTS PASSED WITH 100% SUCCESS!")
