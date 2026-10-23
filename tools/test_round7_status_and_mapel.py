import urllib.request
import urllib.parse
import http.cookiejar
import re
import sys
import os

BASE_URL = "http://127.0.0.1:8080"
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

print("=== START TESTING ROUND 7: STATUS CEKLIS & MAPEL IMPORT ===")

# 1. Login
res = opener.open(f"{BASE_URL}/admin/login.php")
html = res.read().decode('utf-8')
csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)
data = urllib.parse.urlencode({'username': 'admin', 'password': 'admin123', 'csrf_token': csrf}).encode('utf-8')
res = opener.open(f"{BASE_URL}/admin/login.php", data=data)
print("✓ Login successful")

# 2. Test Status (Ceklis) on Data Kelas
res = opener.open(f"{BASE_URL}/admin/kelas.php")
html = res.read().decode('utf-8')
csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)
m_k = re.search(r'check_kelas_(\d+)', html)
assert m_k, "No kelas checkbox found"
kid = m_k.group(1)

# Toggle to 0
post_data = urllib.parse.urlencode({'action': 'toggle_active', 'id': kid, 'status': '0', 'csrf_token': csrf, 'ajax': '1'}).encode('utf-8')
req = urllib.request.Request(f"{BASE_URL}/admin/kelas.php", data=post_data, headers={'X-Requested-With': 'XMLHttpRequest'})
res = opener.open(req)
import json
resp = json.loads(res.read().decode('utf-8'))
assert resp['success'] == True and resp['status'] == 0, f"Failed toggle kelas {kid} to 0"
print(f"✓ Data Kelas ID {kid} quick toggle to 0 (Nonaktif) verified")

# Toggle back to 1
post_data = urllib.parse.urlencode({'action': 'toggle_active', 'id': kid, 'status': '1', 'csrf_token': csrf, 'ajax': '1'}).encode('utf-8')
req = urllib.request.Request(f"{BASE_URL}/admin/kelas.php", data=post_data, headers={'X-Requested-With': 'XMLHttpRequest'})
res = opener.open(req)
resp = json.loads(res.read().decode('utf-8'))
assert resp['success'] == True and resp['status'] == 1, f"Failed toggle kelas {kid} to 1"
print(f"✓ Data Kelas ID {kid} quick toggle back to 1 (Aktif) verified")

# 3. Test Status (Ceklis) on Kelola Data Siswa
res = opener.open(f"{BASE_URL}/admin/siswa.php")
html = res.read().decode('utf-8')
csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)
m_s = re.search(r'check_siswa_(\d+)', html)
assert m_s, "No siswa checkbox found"
sid = m_s.group(1)

# Toggle to 0
post_data = urllib.parse.urlencode({'action': 'toggle_active', 'id': sid, 'status': '0', 'csrf_token': csrf, 'ajax': '1'}).encode('utf-8')
req = urllib.request.Request(f"{BASE_URL}/admin/siswa.php", data=post_data, headers={'X-Requested-With': 'XMLHttpRequest'})
res = opener.open(req)
resp = json.loads(res.read().decode('utf-8'))
assert resp['success'] == True and resp['status'] == 0, f"Failed toggle siswa {sid} to 0"
print(f"✓ Kelola Data Siswa ID {sid} quick toggle to 0 (Nonaktif) verified")

# Toggle back to 1
post_data = urllib.parse.urlencode({'action': 'toggle_active', 'id': sid, 'status': '1', 'csrf_token': csrf, 'ajax': '1'}).encode('utf-8')
req = urllib.request.Request(f"{BASE_URL}/admin/siswa.php", data=post_data, headers={'X-Requested-With': 'XMLHttpRequest'})
res = opener.open(req)
resp = json.loads(res.read().decode('utf-8'))
assert resp['success'] == True and resp['status'] == 1, f"Failed toggle siswa {sid} to 1"
print(f"✓ Kelola Data Siswa ID {sid} quick toggle back to 1 (Aktif) verified")

# 4. Test Status (Ceklis) on Data Mapel
res = opener.open(f"{BASE_URL}/admin/mapel.php")
html = res.read().decode('utf-8')
csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)
m_m = re.search(r'check_mapel_(\d+)', html)
assert m_m, "No mapel checkbox found"
mid = m_m.group(1)

# Toggle to 0
post_data = urllib.parse.urlencode({'action': 'toggle_active', 'id': mid, 'status': '0', 'csrf_token': csrf, 'ajax': '1'}).encode('utf-8')
req = urllib.request.Request(f"{BASE_URL}/admin/mapel.php", data=post_data, headers={'X-Requested-With': 'XMLHttpRequest'})
res = opener.open(req)
resp = json.loads(res.read().decode('utf-8'))
assert resp['success'] == True and resp['status'] == 0, f"Failed toggle mapel {mid} to 0"
print(f"✓ Data Mapel ID {mid} quick toggle to 0 (Nonaktif) verified")

# Toggle back to 1
post_data = urllib.parse.urlencode({'action': 'toggle_active', 'id': mid, 'status': '1', 'csrf_token': csrf, 'ajax': '1'}).encode('utf-8')
req = urllib.request.Request(f"{BASE_URL}/admin/mapel.php", data=post_data, headers={'X-Requested-With': 'XMLHttpRequest'})
res = opener.open(req)
resp = json.loads(res.read().decode('utf-8'))
assert resp['success'] == True and resp['status'] == 1, f"Failed toggle mapel {mid} to 1"
print(f"✓ Data Mapel ID {mid} quick toggle back to 1 (Aktif) verified")

# 5. Test Link from Data Mapel to Impor Data Excel (type=mapel)
assert '/admin/import-siswa.php?type=mapel' in html, "Link to import-siswa.php?type=mapel missing on mapel.php"
print("✓ Button 'Impor Excel (.xls)' on Data Mapel links directly to /admin/import-siswa.php?type=mapel")

# 6. Verify /admin/import-siswa.php?type=mapel
res = opener.open(f"{BASE_URL}/admin/import-siswa.php?type=mapel")
html_import = res.read().decode('utf-8')
assert 'id="tabBtnMapel" class="btn btn-primary"' in html_import or 'Unggah Berkas Mapel' in html_import
assert 'id="cardImportMapel"' in html_import
assert '/admin/mapel.php' in html_import, "Link back to /admin/mapel.php missing on import mapel card"
assert '/admin/import-siswa.php?template=mapel' in html_import, "Template download missing on import mapel card"
print("✓ /admin/import-siswa.php?type=mapel correctly activates Unggah Berkas Mapel (.xls) and has back link to Data Mapel")

# 7. Test Mapel Template Download
res_tpl = opener.open(f"{BASE_URL}/admin/import-siswa.php?template=mapel")
assert res_tpl.getcode() == 200
tpl_content = res_tpl.read().decode('utf-8')
assert "Kode Mapel" in tpl_content and "Nama Mata Pelajaran" in tpl_content
print("✓ Mapel template download (.xls) verified working")

# 8. Test Actual Mapel Import via import-siswa.php
boundary = "----WebKitFormBoundaryMapelTest7"
test_excel_content = """<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>
<body>
<table>
<thead><tr><th>Kode Mapel</th><th>Nama Mata Pelajaran</th><th>Kelompok</th></tr></thead>
<tbody>
<tr><td>MP-TEST-01</td><td>Mata Pelajaran Uji Coba SAS</td><td>Kejuruan</td></tr>
</tbody>
</table>
</body>
</html>"""

csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html_import).group(1)
body_lines = [
    f"--{boundary}",
    'Content-Disposition: form-data; name="csrf_token"',
    '',
    csrf,
    f"--{boundary}",
    'Content-Disposition: form-data; name="target"',
    '',
    'mapel',
    f"--{boundary}",
    'Content-Disposition: form-data; name="file_mapel"; filename="test_mapel.xls"',
    'Content-Type: application/vnd.ms-excel',
    '',
    test_excel_content,
    f"--{boundary}--",
    ''
]
body_data = "\r\n".join(body_lines).encode('utf-8')
req = urllib.request.Request(
    f"{BASE_URL}/admin/import-siswa.php",
    data=body_data,
    headers={
        'Content-Type': f'multipart/form-data; boundary={boundary}',
        'Content-Length': str(len(body_data))
    }
)
res = opener.open(req)
final_url = res.geturl()
final_html = res.read().decode('utf-8')
assert final_url.endswith('/admin/mapel.php'), f"Expected redirect to /admin/mapel.php, got {final_url}"
assert "Mata Pelajaran Uji Coba SAS" in final_html, "Imported mapel not found on mapel.php"
print("✓ Full end-to-end import of mapel via /admin/import-siswa.php?type=mapel verified successfully!")

# Clean up test mapel record
m_del = re.search(r'Hapus mata pelajaran Mata Pelajaran Uji Coba SAS.*?"id" value="(\d+)"', final_html, re.DOTALL)
if m_del:
    del_id = m_del.group(1)
    del_csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', final_html).group(1)
    del_data = urllib.parse.urlencode({'action': 'delete', 'id': del_id, 'csrf_token': del_csrf}).encode('utf-8')
    opener.open(f"{BASE_URL}/admin/mapel.php", data=del_data)
    print(f"✓ Cleaned up temporary test mapel ID {del_id}")

print("\n=== ALL ROUND 7 TESTS PASSED 100%! ===")
