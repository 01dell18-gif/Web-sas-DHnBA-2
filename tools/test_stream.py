import urllib.request
import urllib.parse
import http.cookiejar
import re

BASE = "http://127.0.0.1:8081"
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# Login
login_page = opener.open(f"{BASE}/admin/login.php").read().decode()
csrf = re.search(r'name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']', login_page).group(1)
opener.open(f"{BASE}/admin/login.php", data=urllib.parse.urlencode({'csrf_token': csrf, 'username': 'admin', 'password': 'admin123'}).encode())

# Test preview
resp = opener.open(f"{BASE}/admin/preview.php?type=ba&id=109")
print("Preview status:", resp.getcode())
html = resp.read().decode()
print("Has iframe:", "<iframe" in html)
iframe_src = re.search(r'<iframe[^>]+src="([^"]+)"', html).group(1)
print("Iframe src:", iframe_src)

# Test raw stream
resp2 = opener.open(f"{BASE}{iframe_src}")
print("Stream status:", resp2.getcode())
print("Stream Content-Type:", resp2.headers.get('Content-Type'))
print("Stream Content-Disposition:", resp2.headers.get('Content-Disposition'))
data = resp2.read()
print("Stream length:", len(data), "starts with %PDF:", data[:4] == b'%PDF')
