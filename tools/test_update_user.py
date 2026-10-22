import urllib.request
import urllib.parse
import http.cookiejar
import re

BASE = "http://127.0.0.1:8080"
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# 1. Login
login_page = opener.open(f"{BASE}/admin/login.php").read().decode()
csrf = re.search(r'name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']', login_page).group(1)
login_data = urllib.parse.urlencode({'csrf_token': csrf, 'username': 'admin', 'password': 'password123'}).encode()
opener.open(f"{BASE}/admin/login.php", data=login_data)

# 2. Get users page
users_page = opener.open(f"{BASE}/admin/users.php?edit=2").read().decode()
csrf = re.search(r'name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']', users_page).group(1)

# 3. Post user update
post_data = urllib.parse.urlencode({
    'csrf_token': csrf,
    'action': 'save',
    'id': '2',
    'nama': 'Operator Ujian SAS',
    'username': 'operator',
    'role': 'operator',
    'aktif': '1'
}).encode()

res = opener.open(f"{BASE}/admin/users.php", data=post_data).read().decode()

has_warn = 'Warning:' in res or 'headers already sent' in res
print("Has Warning?", has_warn)
if has_warn:
    for line in res.splitlines():
        if 'Warning:' in line:
            print(line)
else:
    print("SUCCESS: 0 warnings! Header already sent issue is COMPLETELY RESOLVED!")
