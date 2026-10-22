import urllib.request
import urllib.parse
import http.cookiejar
import re

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

BASE = "http://127.0.0.1:8080"

def get(path):
    req = urllib.request.Request(f"{BASE}{path}")
    res = opener.open(req)
    return res.status, res.headers, res.read()

def post(path, data):
    body = urllib.parse.urlencode(data).encode("utf-8")
    req = urllib.request.Request(f"{BASE}{path}", data=body)
    res = opener.open(req)
    return res.status, res.headers, res.read()

print("1. Logging in as admin...")
status, headers, body = get("/admin/login.php")
csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', body.decode('utf-8')).group(1)
login_s, login_h, login_b = post("/admin/login.php", {
    "csrf_token": csrf,
    "username": "admin",
    "password": "admin123"
})
print("Login status:", login_s)
assert "login.php" not in login_h.get("Location", ""), "Login redirect failed"
print("Logged in successfully!")

print("\n2. Testing Export Siswa .xls...")
s, h, b = get("/admin/siswa.php?action=export_excel")
assert s == 200, f"Expected 200, got {s}"
assert "vnd.ms-excel" in h.get("Content-Type", ""), f"Wrong content type: {h.get('Content-Type')}"
assert "Data_Siswa" in h.get("Content-Disposition", ""), f"Wrong disposition: {h.get('Content-Disposition')}"
assert b"<table" in b, "Missing table in xls"
print(f"Export Siswa OK! Size: {len(b)} bytes")

print("\n3. Testing Export Guru .xls...")
s, h, b = get("/admin/guru.php?action=export_excel")
assert s == 200, f"Expected 200, got {s}"
assert "vnd.ms-excel" in h.get("Content-Type", ""), f"Wrong content type: {h.get('Content-Type')}"
assert "Data_Guru" in h.get("Content-Disposition", ""), f"Wrong disposition: {h.get('Content-Disposition')}"
assert b"<table" in b, "Missing table in xls"
print(f"Export Guru OK! Size: {len(b)} bytes")

print("\n4. Testing Export Users .xls...")
s, h, b = get("/admin/users.php?action=export_excel")
assert s == 200, f"Expected 200, got {s}"
assert "vnd.ms-excel" in h.get("Content-Type", ""), f"Wrong content type: {h.get('Content-Type')}"
assert "Data_Pengguna" in h.get("Content-Disposition", ""), f"Wrong disposition: {h.get('Content-Disposition')}"
assert b"<table" in b, "Missing table in xls"
print(f"Export Users OK! Size: {len(b)} bytes")

print("\n5. Testing Users Template .xls...")
s, h, b = get("/admin/users.php?action=template_excel")
assert s == 200
assert "template_impor_pengguna" in h.get("Content-Disposition", "")
print("Users Template OK!")

print("\n6. Testing Mapel page...")
s, h, b = get("/admin/mapel.php")
assert s == 200
body_str = b.decode("utf-8")
assert "Data Mata Pelajaran Ujian" in body_str
assert "Ekspor Excel (.xls)" in body_str
assert "Impor Excel (.xls)" in body_str
assert "Hapus Semua Data Mapel" in body_str
print("Mapel Page OK!")

print("\n7. Testing Export Mapel .xls...")
s, h, b = get("/admin/mapel.php?action=export_excel")
assert s == 200
assert "Data_Mata_Pelajaran" in h.get("Content-Disposition", "")
assert b"<table" in b
print(f"Export Mapel OK! Size: {len(b)} bytes")

print("\n8. Testing Mapel Template .xls...")
s, h, b = get("/admin/mapel.php?action=template_excel")
assert s == 200
assert "template_impor_mapel" in h.get("Content-Disposition", "")
print("Mapel Template OK!")

print("\n9. Testing absensi.php mapel datalist...")
s, h, b = get("/absensi.php")
assert s == 200
abs_str = b.decode("utf-8")
assert 'list="datalist_mapel"' in abs_str
assert '<datalist id="datalist_mapel">' in abs_str
assert 'Pemrograman Web' in abs_str or 'Matematika' in abs_str
print("absensi.php datalist OK!")

print("\n10. Testing berita-acara.php mapel datalist...")
s, h, b = get("/berita-acara.php")
assert s == 200
ba_str = b.decode("utf-8")
assert 'list="datalist_mapel"' in ba_str
assert '<datalist id="datalist_mapel">' in ba_str
assert 'Pemrograman Web' in ba_str or 'Matematika' in ba_str
print("berita-acara.php datalist OK!")

print("\n11. Testing import-siswa.php Mapel tab...")
s, h, b = get("/admin/import-siswa.php?type=mapel")
assert s == 200
imp_str = b.decode("utf-8")
assert 'cardImportMapel' in imp_str
assert 'template_impor_mapel.xls' in imp_str or 'template=mapel' in imp_str
print("import-siswa.php Mapel tab OK!")

print("\nALL ROUND 4 TESTS PASSED SUCCESSFULLY!")
