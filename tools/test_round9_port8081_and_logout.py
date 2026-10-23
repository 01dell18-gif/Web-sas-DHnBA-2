import urllib.request
import urllib.parse
import http.cookiejar
import re
import sys

BASE_URL = "http://127.0.0.1:8081"
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

print("=== 1. TEST PUBLIC HOME PAGE (PORT 8081) ===")
res = opener.open(f"{BASE_URL}/")
assert res.status == 200, f"Public page failed with status {res.status}"
print("   [OK] Public page loaded on port 8081")

print("=== 2. TEST LOGIN ADMIN ===")
res_login = opener.open(f"{BASE_URL}/admin/login.php")
html = res_login.read().decode('utf-8')
csrf_match = re.search(r'name="csrf_token"\s+value="([a-f0-9]+)"', html)
assert csrf_match, "CSRF token not found on login page"
csrf = csrf_match.group(1)

login_data = urllib.parse.urlencode({
    'csrf_token': csrf,
    'username': 'admin',
    'password': 'admin123'
}).encode('utf-8')

res_post = opener.open(f"{BASE_URL}/admin/login.php", data=login_data)
# Check dashboard access
res_dash = opener.open(f"{BASE_URL}/admin/index.php")
dash_html = res_dash.read().decode('utf-8')
assert "Dashboard" in dash_html, "Login failed: cannot access dashboard"
assert "Keluar" in dash_html, "Logout button not present on dashboard"
print("   [OK] Successfully logged in as admin on port 8081")

print("=== 3. TEST DATA MASTER PAGES (8081) ===")
for page in ['kelas.php', 'siswa.php', 'guru.php', 'mapel.php', 'import-siswa.php']:
    res_p = opener.open(f"{BASE_URL}/admin/{page}")
    assert res_p.status == 200, f"Page {page} returned {res_p.status}"
    print(f"   [OK] /admin/{page} loaded 200 OK")

print("=== 4. TEST EXCEL TEMPLATES & EXPORT ===")
res_tpl_siswa = opener.open(f"{BASE_URL}/admin/import-siswa.php?template=siswa")
content_siswa = res_tpl_siswa.read().decode('utf-8')
assert 'text-cell' in content_siswa, "text-cell class missing in template siswa"
assert 'mso-number-format' in content_siswa, "mso-number-format missing in template siswa"
print("   [OK] Template Siswa Excel outputs valid mso-number-format")

res_tpl_guru = opener.open(f"{BASE_URL}/admin/import-siswa.php?template=guru")
content_guru = res_tpl_guru.read().decode('utf-8')
assert 'text-cell' in content_guru, "text-cell class missing in template guru"
print("   [OK] Template Guru Excel outputs valid text-cell class")

res_excel_export = opener.open(f"{BASE_URL}/admin/export-rekap-excel.php?class_id=15")
content_export = res_excel_export.read().decode('utf-8')
assert 'mso-number-format' in content_export, "mso-number-format missing in export-rekap-excel"
print("   [OK] Export Rekap Excel contains valid mso-number-format")

print("=== 5. TEST PDF REKAP EXPORT (DOMPDF AUTOLOADER) ===")
res_pdf = opener.open(f"{BASE_URL}/admin/export-rekap-pdf.php?class_id=15")
assert res_pdf.headers.get_content_type() == 'application/pdf', "PDF output not application/pdf"
pdf_data = res_pdf.read()
assert pdf_data.startswith(b'%PDF'), "PDF magic bytes missing"
print(f"   [OK] Dompdf generated valid PDF of size {len(pdf_data)} bytes")

print("=== 6. TEST LOGOUT ENDPOINT ===")
res_logout = opener.open(f"{BASE_URL}/admin/logout.php")
logout_html = res_logout.read().decode('utf-8')
assert "Masuk ke Panel" in logout_html or "login" in res_logout.url.lower(), "Logout did not redirect to login page"

# Verify that session is dead by trying to access index.php
try:
    res_recheck = opener.open(f"{BASE_URL}/admin/index.php")
    recheck_html = res_recheck.read().decode('utf-8')
    assert "Masuk ke Panel" in recheck_html or "login.php" in res_recheck.url, "Session was not destroyed after logout!"
except urllib.error.HTTPError as e:
    pass

print("   [OK] Admin logout successfully destroyed session and blocked access to protected areas")
print("\nALL PORT 8081 & LOGOUT BACKEND TESTS PASSED!")
