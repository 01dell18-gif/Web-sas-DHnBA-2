import urllib.request
import urllib.parse
import http.cookiejar
import re

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

login_html = opener.open('https://sas.doantara.my.id/admin/login.php').read().decode()
csrf = re.search(r'name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']', login_html).group(1)
login_data = urllib.parse.urlencode({'csrf_token': csrf, 'username': 'admin', 'password': 'admin123'}).encode()
opener.open('https://sas.doantara.my.id/admin/login.php', data=login_data)

resp = opener.open('https://sas.doantara.my.id/admin/absensi.php?action=view_single&id=103')
print('Final URL:', resp.geturl())
print('Content-Type:', resp.headers.get('Content-Type'))
print('Content-Disposition:', resp.headers.get('Content-Disposition'))
pdf_bytes = resp.read()
print('PDF Size:', len(pdf_bytes))
if b'/Producer' in pdf_bytes or b'/Creator' in pdf_bytes:
    # search for creator or producer in PDF
    for m in re.finditer(rb'/(Producer|Creator)\s*\(([^)]+)\)', pdf_bytes):
        print(m.group(1).decode(), ':', repr(m.group(2)))
