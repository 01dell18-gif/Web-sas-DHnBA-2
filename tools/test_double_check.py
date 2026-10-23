import urllib.request
import urllib.parse
import http.cookiejar
import re
import json

BASE_URL = "http://127.0.0.1:8080"
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# 1. Login
res = opener.open(f"{BASE_URL}/admin/login.php")
html = res.read().decode('utf-8')
csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)
data = urllib.parse.urlencode({'username': 'admin', 'password': 'admin123', 'csrf_token': csrf}).encode('utf-8')
opener.open(f"{BASE_URL}/admin/login.php", data=data)

# 2. Test AJAX toggle on Siswa
res = opener.open(f"{BASE_URL}/admin/siswa.php")
html = res.read().decode('utf-8')
csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)
m_s = re.search(r'check_siswa_(\d+)', html)
if m_s:
    sid = m_s.group(1)
    # Toggle to 0
    post_data = urllib.parse.urlencode({'action': 'toggle_active', 'id': sid, 'status': '0', 'csrf_token': csrf, 'ajax': '1'}).encode('utf-8')
    req = urllib.request.Request(f"{BASE_URL}/admin/siswa.php", data=post_data, headers={'X-Requested-With': 'XMLHttpRequest'})
    res = opener.open(req)
    data_resp = json.loads(res.read().decode('utf-8'))
    assert data_resp['success'] == True
    print(f"✓ Siswa ID {sid} toggle to 0 via AJAX successful")

    # Toggle back to 1
    post_data = urllib.parse.urlencode({'action': 'toggle_active', 'id': sid, 'status': '1', 'csrf_token': csrf, 'ajax': '1'}).encode('utf-8')
    req = urllib.request.Request(f"{BASE_URL}/admin/siswa.php", data=post_data, headers={'X-Requested-With': 'XMLHttpRequest'})
    res = opener.open(req)
    data_resp = json.loads(res.read().decode('utf-8'))
    assert data_resp['success'] == True
    print(f"✓ Siswa ID {sid} toggle back to 1 via AJAX successful")

# 3. Test AJAX toggle on Kelas
res = opener.open(f"{BASE_URL}/admin/kelas.php")
html = res.read().decode('utf-8')
csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)
m_k = re.search(r'check_kelas_(\d+)', html)
if m_k:
    kid = m_k.group(1)
    post_data = urllib.parse.urlencode({'action': 'toggle_active', 'id': kid, 'status': '0', 'csrf_token': csrf, 'ajax': '1'}).encode('utf-8')
    req = urllib.request.Request(f"{BASE_URL}/admin/kelas.php", data=post_data, headers={'X-Requested-With': 'XMLHttpRequest'})
    res = opener.open(req)
    data_resp = json.loads(res.read().decode('utf-8'))
    assert data_resp['success'] == True
    print(f"✓ Kelas ID {kid} toggle to 0 via AJAX successful")

    post_data = urllib.parse.urlencode({'action': 'toggle_active', 'id': kid, 'status': '1', 'csrf_token': csrf, 'ajax': '1'}).encode('utf-8')
    req = urllib.request.Request(f"{BASE_URL}/admin/kelas.php", data=post_data, headers={'X-Requested-With': 'XMLHttpRequest'})
    res = opener.open(req)
    data_resp = json.loads(res.read().decode('utf-8'))
    assert data_resp['success'] == True
    print(f"✓ Kelas ID {kid} toggle back to 1 via AJAX successful")

# 4. Test AJAX toggle on Mapel
res = opener.open(f"{BASE_URL}/admin/mapel.php")
html = res.read().decode('utf-8')
csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)
m_m = re.search(r'check_mapel_(\d+)', html)
if m_m:
    mid = m_m.group(1)
    post_data = urllib.parse.urlencode({'action': 'toggle_active', 'id': mid, 'status': '0', 'csrf_token': csrf}).encode('utf-8')
    req = urllib.request.Request(f"{BASE_URL}/admin/mapel.php", data=post_data, headers={'X-Requested-With': 'XMLHttpRequest'})
    res = opener.open(req)
    data_resp = json.loads(res.read().decode('utf-8'))
    assert data_resp['success'] == True
    print(f"✓ Mapel ID {mid} toggle to 0 via AJAX successful")

    post_data = urllib.parse.urlencode({'action': 'toggle_active', 'id': mid, 'status': '1', 'csrf_token': csrf}).encode('utf-8')
    req = urllib.request.Request(f"{BASE_URL}/admin/mapel.php", data=post_data, headers={'X-Requested-With': 'XMLHttpRequest'})
    res = opener.open(req)
    data_resp = json.loads(res.read().decode('utf-8'))
    assert data_resp['success'] == True
    print(f"✓ Mapel ID {mid} toggle back to 1 via AJAX successful")

print("\nDOUBLE CHECK: ALL AJAX TOGGLE ACTIONS VERIFIED PERFECTLY!")
