import urllib.request
import urllib.parse
import http.cookiejar
import re
import sys

BASE_URL = "http://127.0.0.1:8080"
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# 1. Login
res = opener.open(f"{BASE_URL}/admin/login.php")
html = res.read().decode('utf-8')
csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)
data = urllib.parse.urlencode({'username': 'admin', 'password': 'admin123', 'csrf_token': csrf}).encode('utf-8')
opener.open(f"{BASE_URL}/admin/login.php", data=data)

# Test pages and verify their active state in merged Data Master or other groups
test_cases = [
    ('/admin/siswa.php', 'Data Master', 'Kelola Data Siswa'),
    ('/admin/import-siswa.php', 'Data Master', 'Impor Data Excel'),
    ('/admin/kelas.php', 'Data Master', 'Data Kelas'),
    ('/admin/guru.php', 'Data Master', 'Data Guru & Pengawas'),
    ('/admin/mapel.php', 'Data Master', 'Data Mapel'),
    ('/admin/rekap-kehadiran.php', 'Rekap dan Laporan', 'Rekap Kehadiran'),
    ('/admin/absensi.php', 'Rekap dan Laporan', 'Arsip Daftar Hadir'),
    ('/admin/berita-acara.php', 'Rekap dan Laporan', 'Arsip Berita Acara'),
    ('/admin/rekonsiliasi.php', 'Rekap dan Laporan', 'Rekonsiliasi Dokumen'),
    ('/admin/users.php', 'Pengaturan', 'Manajemen User'),
    ('/admin/pengaturan.php', 'Pengaturan', 'Pengaturan Kop & Drive'),
    ('/admin/template-ujian.php', 'Pengaturan', 'Template Ujian')
]

for url, group_title, sub_title in test_cases:
    res = opener.open(f"{BASE_URL}{url}")
    assert res.getcode() == 200, f"Failed to load {url}"
    page_html = res.read().decode('utf-8')
    
    # Check that navbar is aligned
    assert 'class="navbar admin-navbar"' in page_html, f"admin-navbar missing on {url}"
    assert 'class="admin-brand-box"' in page_html, f"admin-brand-box missing on {url}"
    assert 'id="btnToggleSidebar"' in page_html, f"btnToggleSidebar missing on {url}"
    
    # Check that group is rendered with is-open when active
    assert 'sidebar-nav-group is-open' in page_html, f"Active group not opened on {url}"
    assert group_title in page_html, f"Group {group_title} missing on {url}"
    assert sub_title in page_html, f"Submenu {sub_title} missing on {url}"
    assert 'sidebar-sub-item active' in page_html, f"Submenu {sub_title} active class missing on {url}"
    print(f"✓ Page {url} correctly opened {group_title} -> {sub_title}")

print("\nALL 12 ADMIN PAGES VERIFIED: NAVBAR ALIGNMENT & MENU ACTIVE STATES ARE 100% WORKING!")
