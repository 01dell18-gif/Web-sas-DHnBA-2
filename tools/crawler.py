import urllib.request
import urllib.parse
import http.cookiejar
import re
import os
import json

BASE_URL = "https://sas.doantara.my.id"

# Setup cookie jar & opener
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
opener.addheaders = [('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)')]

def get(url):
    req = urllib.request.Request(url)
    with opener.open(req) as resp:
        return resp.read().decode('utf-8', errors='ignore')

def post(url, data):
    encoded_data = urllib.parse.urlencode(data).encode('utf-8')
    req = urllib.request.Request(url, data=encoded_data)
    with opener.open(req) as resp:
        return resp.read().decode('utf-8', errors='ignore'), resp.geturl()

print("1. Fetching login page to extract CSRF token...")
login_html = get(BASE_URL + "/admin/login.php")
csrf_match = re.search(r'name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']', login_html)
csrf_token = csrf_match.group(1) if csrf_match else ""
print(f"CSRF Token: {csrf_token}")

print("2. Logging in as admin...")
login_data = {
    'csrf_token': csrf_token,
    'username': 'admin',
    'password': 'admin123'
}
resp_html, final_url = post(BASE_URL + "/admin/login.php", login_data)
print(f"Post login final URL: {final_url}")

# Let's save the dashboard HTML
os.makedirs("analysis_data", exist_ok=True)
with open("analysis_data/dashboard.html", "w", encoding="utf-8") as f:
    f.write(resp_html)

# Extract all links from the dashboard
links = set(re.findall(r'href=["\']([^"\'#]+)["\']', resp_html))
admin_links = set()
for link in links:
    if link.startswith('/') or link.startswith(BASE_URL):
        admin_links.add(link)

print("Found admin links:", sorted(list(admin_links)))

# Let's crawl all discovered admin and public pages
pages_to_crawl = [
    '/',
    '/absensi.php',
    '/berita-acara.php',
    '/admin/',
    '/admin/index.php',
]
for l in admin_links:
    if not l.startswith('http') and not l.endswith('.css') and not l.endswith('.png') and not l.endswith('.jpg') and not l.endswith('.ico') and 'logout' not in l:
        pages_to_crawl.append(l)

pages_to_crawl = sorted(list(set(pages_to_crawl)))
print(f"Total pages to crawl: {len(pages_to_crawl)}")

visited = set()
for path in pages_to_crawl:
    if path in visited:
        continue
    visited.add(path)
    url = BASE_URL + path if path.startswith('/') else path
    filename = path.strip('/').replace('/', '_') or 'root'
    if not filename.endswith('.html'):
        filename += '.html'
    
    print(f"Crawling {url} -> {filename}")
    try:
        content = get(url)
        with open(f"analysis_data/{filename}", "w", encoding="utf-8") as f:
            f.write(content)
        
        # Check for any new links
        page_links = re.findall(r'href=["\']([^"\'#]+)["\']', content)
        for pl in page_links:
            if (pl.startswith('/admin') or pl.startswith('/')) and not pl.endswith(('.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.pdf')) and 'logout' not in pl:
                if pl not in visited and pl not in pages_to_crawl:
                    pages_to_crawl.append(pl)
    except Exception as e:
        print(f"Error crawling {url}: {e}")

print("Crawling complete. Files saved in analysis_data/")
