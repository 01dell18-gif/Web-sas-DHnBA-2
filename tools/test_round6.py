import urllib.request
import urllib.parse
import http.cookiejar
import re
import sys
import os

BASE_URL = "http://127.0.0.1:8081"

# Setup cookie opener
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

def login():
    # Fetch login page for CSRF
    res = opener.open(f"{BASE_URL}/admin/login.php")
    html = res.read().decode('utf-8')
    m = re.search(r'name="csrf_token"\s+value="([^"]+)"', html)
    assert m, "CSRF token not found on login"
    csrf = m.group(1)

    # Post login
    data = urllib.parse.urlencode({
        'username': 'admin',
        'password': 'admin123',
        'csrf_token': csrf
    }).encode('utf-8')
    res = opener.open(f"{BASE_URL}/admin/login.php", data=data)
    login_resp = res.read().decode('utf-8')
    assert "Dashboard" in login_resp or res.geturl().endswith('/admin/index.php'), "Login failed"
    print("✓ Login successful as Admin")

def test_sidebar_groups():
    res = opener.open(f"{BASE_URL}/admin/index.php")
    html = res.read().decode('utf-8')
    
    # Check accordion groups
    assert "sidebar-nav-group" in html, "sidebar-nav-group missing"
    assert "sidebar-group-header" in html, "sidebar-group-header missing"
    assert "sidebar-group-sub" in html, "sidebar-group-sub missing"
    assert "sidebar-tree-line" in html, "sidebar-tree-line missing"
    assert "sidebar-sub-item" in html, "sidebar-sub-item missing"
    assert "sidebar-sub-bullet" in html, "sidebar-sub-bullet missing"
    assert "toggleSidebarGroup" in html, "toggleSidebarGroup missing"
    
    # Check all groups present
    assert "admin-navbar" in html, "admin-navbar missing"
    assert "admin-brand-box" in html, "admin-brand-box missing"
    assert "admin-header-actions" in html, "admin-header-actions missing"
    assert "btn-sidebar-toggle" in html, "btn-sidebar-toggle missing"
    assert "Data Master" in html, "Data Master missing"
    assert "Kelola Data Siswa" in html, "Kelola Data Siswa missing from Data Master"
    assert "Impor Data Excel" in html, "Impor Data Excel missing from Data Master"
    assert "Rekap dan Laporan" in html, "Rekap dan Laporan missing"
    assert "Pengaturan" in html, "Pengaturan missing"
    print("✓ Sidebar grouped accordion tree structure & navbar alignment verified")

def test_status_pill_consistency():
    pages = [
        ('/admin/siswa.php', 'siswa'),
        ('/admin/kelas.php', 'kelas'),
        ('/admin/guru.php', 'guru'),
        ('/admin/mapel.php', 'mapel')
    ]
    for url, name in pages:
        res = opener.open(f"{BASE_URL}{url}")
        html = res.read().decode('utf-8')
        assert "Status (Ceklis)" in html or "STATUS (CEKLIS)" in html, f"Status (Ceklis) column header missing on {url}"
        assert ("ceklis-status-pill" in html or "ceklis-status-guru" in html), f"ceklis status pill class missing on {url}"
        assert ("ceklis-box" in html or "ceklis-guru-box" in html), f"ceklis box missing on {url}"
        assert "ceklis-check-icon" in html, f"ceklis-check-icon missing on {url}"
        print(f"✓ Status (Ceklis) design verified identical on {url}")

def test_siswa_import_modal():
    res = opener.open(f"{BASE_URL}/admin/siswa.php")
    html = res.read().decode('utf-8')
    assert "modalImportSiswa" in html, "#modalImportSiswa modal missing in siswa.php"
    assert "openImportSiswaModal()" in html, "openImportSiswaModal() trigger missing"
    assert "action=template_excel" in html, "template_excel link missing"
    assert "file_siswa_excel" in html, "file_siswa_excel input missing"
    assert "quickToggleSiswa" in html, "quickToggleSiswa script missing"
    print("✓ Modal import and template download verified on admin/siswa.php")

def test_template_download():
    res = opener.open(f"{BASE_URL}/admin/siswa.php?action=template_excel")
    content = res.read().decode('utf-8')
    assert "table" in content
    assert "NIS" in content
    assert "Nama Siswa" in content
    assert "Kelas" in content
    print("✓ Template download verified working")

def test_import_execution():
    # Generate a sample HTML Excel table to upload
    sample_excel = """<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>
<body>
<table>
<thead>
<tr>
  <th>NIS</th>
  <th>NISN</th>
  <th>Nama Siswa</th>
  <th>Kelas</th>
</tr>
</thead>
<tbody>
<tr>
  <td>99001</td>
  <td>0099001</td>
  <td>Test Siswa Robotika 1</td>
  <td>XII MEKATRONIKA</td>
</tr>
<tr>
  <td>99002</td>
  <td>0099002</td>
  <td>Test Siswa Robotika 2</td>
  <td>XII MEKATRONIKA</td>
</tr>
</tbody>
</table>
</body>
</html>"""

    # Fetch CSRF from siswa.php
    res = opener.open(f"{BASE_URL}/admin/siswa.php")
    html = res.read().decode('utf-8')
    m = re.search(r'name="csrf_token"\s+value="([^"]+)"', html)
    assert m
    csrf = m.group(1)

    # Build multipart form data
    boundary = "----WebKitFormBoundaryX99001"
    body = []
    body.append(f"--{boundary}".encode('utf-8'))
    body.append(b'Content-Disposition: form-data; name="csrf_token"\r\n')
    body.append(csrf.encode('utf-8'))
    body.append(f"--{boundary}".encode('utf-8'))
    body.append(b'Content-Disposition: form-data; name="action"\r\n')
    body.append(b'import_excel')
    body.append(f"--{boundary}".encode('utf-8'))
    body.append(b'Content-Disposition: form-data; name="file_siswa_excel"; filename="test_siswa.xls"\r\nContent-Type: application/vnd.ms-excel\r\n')
    body.append(sample_excel.encode('utf-8'))
    body.append(f"--{boundary}--\r\n".encode('utf-8'))

    payload = b"\r\n".join(body)
    req = urllib.request.Request(
        f"{BASE_URL}/admin/siswa.php",
        data=payload,
        headers={
            'Content-Type': f'multipart/form-data; boundary={boundary}',
            'Content-Length': str(len(payload))
        }
    )
    res = opener.open(req)
    resp = res.read().decode('utf-8')
    assert "Test Siswa Robotika 1" in resp or "Berhasil memproses" in resp, "Import response did not contain imported student or success message"
    print("✓ Import execution on admin/siswa.php verified successfully")

if __name__ == "__main__":
    try:
        login()
        test_sidebar_groups()
        test_status_pill_consistency()
        test_siswa_import_modal()
        test_template_download()
        test_import_execution()
        print("\nALL 5 TEST SUITES PASSED PERFECTLY!")
    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)
