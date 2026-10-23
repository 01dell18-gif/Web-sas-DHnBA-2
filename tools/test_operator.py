import urllib.request
import urllib.parse
import http.cookiejar

BASE = "http://127.0.0.1:8081"
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

print("1. Testing operator login...")
login_page = opener.open(f"{BASE}/admin/login.php").read().decode()
# Extract CSRF token
csrf_match = [line for line in login_page.split("\n") if 'name="csrf_token"' in line]
csrf_token = ""
if csrf_match:
    import re
    m = re.search(r'value="([a-f0-9]+)"', csrf_match[0])
    if m:
        csrf_token = m.group(1)

post_data = urllib.parse.urlencode({
    "csrf_token": csrf_token,
    "username": "operator",
    "password": "operator123"
}).encode()

req = urllib.request.Request(f"{BASE}/admin/login.php", data=post_data)
res = opener.open(req)
dashboard = res.read().decode()
assert "🛡️ Operator" in dashboard or "Operator" in dashboard
print("   [OK] Operator logged in successfully! Badge displayed.")

print("2. Verifying operator CAN access operational modules...")
for p in ['/admin/kelas.php', '/admin/siswa.php', '/admin/absensi.php', '/admin/berita-acara.php', '/admin/password.php']:
    c = opener.open(f"{BASE}{p}").read().decode()
    assert "Akses ditolak" not in c
    print(f"   [OK] Operator accessed {p} smoothly.")

print("3. Verifying operator CANNOT see restricted menus in sidebar...")
assert "/admin/users.php" not in dashboard
assert "/admin/pengaturan.php" not in dashboard
assert "/admin/template-ujian.php" not in dashboard
print("   [OK] Restricted menus successfully hidden from operator sidebar!")

print("4. Verifying operator is BLOCKED if directly visiting restricted URLs...")
for restricted in ['/admin/users.php', '/admin/pengaturan.php', '/admin/template-ujian.php']:
    c = opener.open(f"{BASE}{restricted}").read().decode()
    assert "Akses ditolak" in c
    print(f"   [OK] Direct visit to {restricted} was blocked with 'Akses ditolak'!")

print("\nALL ROLE-BASED ACCESS CONTROL TESTS PASSED (100% OPERATIONAL)!")
