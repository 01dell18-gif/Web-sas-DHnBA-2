import urllib.request
import urllib.parse
import http.cookiejar
import os
import json
import concurrent.futures

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# Login
import re
login_html = opener.open('https://sas.doantara.my.id/admin/login.php').read().decode()
csrf = re.search(r'name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']', login_html).group(1)
login_data = urllib.parse.urlencode({'csrf_token': csrf, 'username': 'admin', 'password': 'admin123'}).encode()
opener.open('https://sas.doantara.my.id/admin/login.php', data=login_data)

with open('seed_records.json') as f:
    d = json.load(f)

photo_paths = set()
for r in d['berita_acara']:
    raw_photos = r.get('foto_dokumentasi')
    if raw_photos:
        try:
            arr = json.loads(raw_photos) if isinstance(raw_photos, str) else raw_photos
            for p in arr:
                if p: photo_paths.add(p)
        except:
            pass

print(f"Downloading {len(photo_paths)} photos...")

def download_file(path):
    url = 'https://sas.doantara.my.id/' + urllib.parse.quote(path)
    local_path = os.path.join(*path.split('/'))
    os.makedirs(os.path.dirname(local_path), exist_ok=True)
    if os.path.exists(local_path) and os.path.getsize(local_path) > 0:
        return
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with opener.open(req) as resp:
            with open(local_path, 'wb') as out:
                out.write(resp.read())
    except Exception as e:
        print(f"Error {path}: {e}")

with concurrent.futures.ThreadPoolExecutor(max_workers=8) as ex:
    list(ex.map(download_file, photo_paths))

print("All photos downloaded!")
