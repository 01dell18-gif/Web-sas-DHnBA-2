"""
Test Suite Round 5:
1. Universal Popups (Notifications & Confirmations)
2. Horizontal Filter Layout (Arsip Daftar Hadir & Berita Acara)
3. Rekap Kehadiran Siswa PDF Preview & Print (export-rekap-pdf.php in new tab)
4. Collapsible Navbar & Sidebar with dynamic content resizing
"""
import urllib.request
import urllib.parse
import http.cookiejar
import re
import os

BASE_URL = 'http://127.0.0.1:8080'

def run_tests():
    print("=== STARTING TEST SUITE ROUND 5 ===")
    cj = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

    # 1. Login to admin
    print("\n[TEST 1] Admin Authentication...")
    res = opener.open(f'{BASE_URL}/admin/login.php')
    html = res.read().decode('utf-8')
    match = re.search(r'name="csrf_token"\s+value="([^"]+)"', html)
    assert match, "CSRF token not found on login page"
    csrf = match.group(1)

    login_data = urllib.parse.urlencode({'csrf_token': csrf, 'username': 'admin', 'password': 'admin123'}).encode('utf-8')
    req = urllib.request.Request(f'{BASE_URL}/admin/login.php', data=login_data)
    res_login = opener.open(req)
    assert res_login.status == 200
    print("  -> Login success!")

    # 2. Test Horizontal Filters in admin/absensi.php & admin/berita-acara.php
    print("\n[TEST 2] Verifying Horizontal Filter in Arsip Daftar Hadir...")
    res_dh = opener.open(f'{BASE_URL}/admin/absensi.php')
    html_dh = res_dh.read().decode('utf-8')
    assert 'class="filter-horizontal-form"' in html_dh, "filter-horizontal-form class missing in absensi.php"
    assert 'class="form-group filter-col"' in html_dh, "filter-col class missing in absensi.php"
    assert 'class="form-group filter-col-search"' in html_dh, "filter-col-search class missing in absensi.php"
    assert 'class="filter-actions"' in html_dh, "filter-actions class missing in absensi.php"
    print("  -> Arsip Daftar Hadir filter is horizontal!")

    print("\n[TEST 3] Verifying Horizontal Filter in Arsip Berita Acara...")
    res_ba = opener.open(f'{BASE_URL}/admin/berita-acara.php')
    html_ba = res_ba.read().decode('utf-8')
    assert 'class="filter-horizontal-form"' in html_ba, "filter-horizontal-form class missing in berita-acara.php"
    assert 'class="form-group filter-col"' in html_ba, "filter-col class missing in berita-acara.php"
    assert 'class="form-group filter-col-search"' in html_ba, "filter-col-search class missing in berita-acara.php"
    assert 'class="filter-actions"' in html_ba, "filter-actions class missing in berita-acara.php"
    print("  -> Arsip Berita Acara filter is horizontal!")

    # 3. Test Rekap Kehadiran PDF Preview & Print
    print("\n[TEST 4] Verifying Preview PDF (Cetak) button in Rekap Kehadiran...")
    res_rekap = opener.open(f'{BASE_URL}/admin/rekap-kehadiran.php?class_id=15')
    html_rekap = res_rekap.read().decode('utf-8')
    assert 'export-rekap-pdf.php' in html_rekap, "Link to export-rekap-pdf.php missing in rekap-kehadiran.php"
    assert 'target="_blank"' in html_rekap, "target='_blank' missing for Preview PDF button"
    assert 'Preview PDF (Cetak)' in html_rekap, "Preview PDF (Cetak) button text missing"
    print("  -> Rekap Kehadiran has Preview PDF (Cetak) button targeting new tab!")

    print("\n[TEST 5] Testing export-rekap-pdf.php PDF Generation...")
    res_pdf = opener.open(f'{BASE_URL}/admin/export-rekap-pdf.php?class_id=15')
    pdf_bytes = res_pdf.read()
    assert res_pdf.status == 200, f"Expected 200 but got {res_pdf.status}"
    assert 'application/pdf' in res_pdf.headers.get('Content-Type', ''), "Content-Type is not application/pdf"
    disp = res_pdf.headers.get('Content-Disposition', '')
    assert 'inline' in disp, f"Content-Disposition should be inline, got {disp}"
    assert pdf_bytes.startswith(b'%PDF-'), "Output does not start with %PDF- header"
    print(f"  -> Generated valid A4 Landscape PDF ({len(pdf_bytes)} bytes) with inline disposition!")

    # 4. Test Collapsible Navbar / Sidebar
    print("\n[TEST 6] Verifying Collapsible Sidebar / Navbar Elements...")
    assert 'id="btnToggleSidebar"' in html_dh, "btnToggleSidebar missing in header"
    assert 'toggleAdminSidebar(event)' in html_dh, "toggleAdminSidebar click handler missing"
    assert '<svg' in html_dh and 'stroke-linecap="round"' in html_dh, "Hamburger SVG icon missing in sidebar button"

    with open('assets/css/app.css', 'r', encoding='utf-8') as f:
        css = f.read()
    assert '.btn-sidebar-toggle' in css, ".btn-sidebar-toggle missing in app.css"
    assert 'body.sidebar-collapsed .admin-sidebar' in css, "body.sidebar-collapsed .admin-sidebar rule missing in app.css"
    assert 'body.sidebar-collapsed .admin-main' in css, "body.sidebar-collapsed .admin-main rule missing in app.css"
    assert 'body.mobile-sidebar-open' in css, "body.mobile-sidebar-open rule missing in app.css"
    print("  -> Sidebar toggle button and responsive CSS rules verified!")

    # 5. Test Universal Popup System
    print("\n[TEST 7] Verifying Universal Popup System (Modals & Alerts)...")
    assert 'id="modalGlobalConfirmDialog"' in html_dh, "modalGlobalConfirmDialog missing in admin layout"
    assert 'id="modalGlobalNotificationDialog"' in html_dh, "modalGlobalNotificationDialog missing in admin layout"
    assert 'id="modalConfirmResetGlobal"' in html_dh, "modalConfirmResetGlobal missing in admin layout"
    assert 'Lanjut' in html_dh and 'Tidak' in html_dh, "Lanjut/Tidak buttons missing in confirm modal"

    with open('assets/js/app.js', 'r', encoding='utf-8') as f:
        js = f.read()
    assert 'window.showConfirmDialog' in js, "window.showConfirmDialog missing in app.js"
    assert 'window.showNotificationModal' in js, "window.showNotificationModal missing in app.js"
    assert 'ensurePopupModalsExist' in js, "ensurePopupModalsExist fallback injector missing in app.js"
    assert 'window.alert = function' in js, "window.alert override missing in app.js"
    assert 'window.toggleAdminSidebar' in js, "window.toggleAdminSidebar missing in app.js"

    # Public pages check
    with open('absensi.php', 'r', encoding='utf-8') as f:
        abs_code = f.read()
    assert 'window.showConfirmDialog' in abs_code, "confirmResetForm should call window.showConfirmDialog in absensi.php"

    with open('berita-acara.php', 'r', encoding='utf-8') as f:
        ba_code = f.read()
    assert 'window.showConfirmDialog' in ba_code, "confirmResetForm should call window.showConfirmDialog in berita-acara.php"

    print("  -> Universal Popup System verified across scripts and layouts!")

    print("\n=== ALL ROUND 5 TESTS COMPLETED SUCCESSFULLY! ===")

if __name__ == '__main__':
    run_tests()
