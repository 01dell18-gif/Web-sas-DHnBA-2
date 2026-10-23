import urllib.request, urllib.parse, http.cookiejar, re

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# 1. Login as admin
res = opener.open('http://127.0.0.1:8081/admin/login.php')
html = res.read().decode('utf-8')
token = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)

login_data = urllib.parse.urlencode({
    'username': 'admin',
    'password': 'admin123',
    'csrf_token': token
}).encode()
opener.open('http://127.0.0.1:8081/admin/login.php', data=login_data)
print("[OK] Admin login successful")

# 2. Test users.php: Password column and eye icon
users_html = opener.open('http://127.0.0.1:8081/admin/users.php').read().decode('utf-8')
assert '<th>PASSWORD</th>' in users_html.upper() or '<th>Password</th>' in users_html, "Password table header not found in users.php"
assert 'togglePasswordVisibility' in users_html, "togglePasswordVisibility not found in users.php"
assert 'bi-eye' in users_html, "Eye toggle icon not found in users.php"
assert '••••••••' in users_html, "Masked password dots not found in users.php"
print("[OK] admin/users.php verified: Password column with eye toggle and masked password present!")

# 3. Test modalConfirmResetGlobal in layout_footer.php across pages
for page in ['guru.php', 'kelas.php', 'siswa.php', 'absensi.php', 'berita-acara.php', 'rekonsiliasi.php', 'rekap-kehadiran.php']:
    page_html = opener.open(f'http://127.0.0.1:8081/admin/{page}').read().decode('utf-8')
    assert 'modalConfirmResetGlobal' in page_html, f"modalConfirmResetGlobal missing from {page}"
    assert 'showConfirmResetModal' in page_html, f"showConfirmResetModal call missing from {page}"
    assert 'prompt(' not in page_html, f"Old prompt() still found in {page}"
    assert 'Tidak' in page_html and 'Lanjut' in page_html, f"Lanjut/Tidak buttons missing from {page}"
    print(f"[OK] {page} verified: Uses showConfirmResetModal with Lanjut/Tidak popup!")

# 4. Test PDF direct streaming for Absensi and Berita Acara
res_ba = opener.open('http://127.0.0.1:8081/admin/berita-acara.php?action=view_single&id=109')
assert res_ba.status == 200, "Berita Acara direct stream failed"
assert 'application/pdf' in res_ba.headers.get('Content-Type', ''), "Berita Acara content type not application/pdf"
ba_bytes = res_ba.read()
assert ba_bytes.startswith(b'%PDF-'), "Berita Acara stream does not start with %PDF-"
print(f"[OK] admin/berita-acara.php view_single: direct PDF stream verified ({len(ba_bytes)} bytes)!")

# 5. Verify eye icon links in table rows
ba_html = opener.open('http://127.0.0.1:8081/admin/berita-acara.php').read().decode('utf-8')
assert 'berita-acara.php?action=view_single&id=' in ba_html, "Eye button does not link to direct view_single in berita-acara.php"
assert 'target="_blank"' in ba_html, "Eye button missing target=_blank"

dh_html = opener.open('http://127.0.0.1:8081/admin/absensi.php').read().decode('utf-8')
assert 'absensi.php?action=view_single&id=' in dh_html, "Eye button does not link to direct view_single in absensi.php"
print("[OK] Eye icon links in tables verified: Open direct PDF view in new tab!")

print("\n>>> ALL NEW FEATURES VALIDATED SUCCESSFULLY (100%)! <<<")
