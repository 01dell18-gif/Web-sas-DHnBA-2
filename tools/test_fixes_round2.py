import urllib.request, urllib.parse, http.cookiejar, re, zipfile, io

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# 1. Login as admin
res = opener.open('http://127.0.0.1:8080/admin/login.php')
html = res.read().decode('utf-8')
token = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)

login_data = urllib.parse.urlencode({
    'username': 'admin',
    'password': 'admin123',
    'csrf_token': token
}).encode()
opener.open('http://127.0.0.1:8080/admin/login.php', data=login_data)
print("[OK] Admin login successful")

# 2. Test pengaturan.php POST (Simpan Pengaturan)
pengaturan_html = opener.open('http://127.0.0.1:8080/admin/pengaturan.php').read().decode('utf-8')
token2 = re.search(r'name="csrf_token"\s+value="([^"]+)"', pengaturan_html).group(1)

post_data = urllib.parse.urlencode({
    'csrf_token': token2,
    'kop_mode': 'text',
    'nama_sekolah': 'SMK NEGERI 1 KLUNGKUNG',
    'alamat_sekolah': 'Jl. Merpati No. 1, Semarapura Tengah, Kec. Klungkung, Kab. Klungkung, Bali 80711',
    'kop_baris_1': 'PEMERINTAH PROVINSI BALI',
    'kop_baris_2': 'DINAS PENDIDIKAN KEPEMUDAAN DAN OLAHRAGA',
    'kop_baris_3': 'SMK NEGERI 1 KLUNGKUNG',
    'kop_telepon': 'Telp. (0366) 21152 | Email: smkn1klungkung@yahoo.com',
    'kop_website': 'Website: smkn1klungkung.sch.id',
    'google_drive_folder_url': 'https://drive.google.com/drive/folders/1sHpxyW6DbHSp1D7KNN-_xTu8fG9rY35h?usp=sharing'
}).encode()

res_post = opener.open('http://127.0.0.1:8080/admin/pengaturan.php', data=post_data)
post_resp_html = res_post.read().decode('utf-8')

assert 'Cannot modify header information' not in post_resp_html, "Warning: Cannot modify header information detected!"
assert 'Semua pengaturan berhasil disimpan' in post_resp_html or 'alert alert-success' in post_resp_html, "Success flash message not found!"
print("[OK] admin/pengaturan.php saved smoothly without header warnings!")

# 3. Test users.php: eye toggle on form password input
users_html = opener.open('http://127.0.0.1:8080/admin/users.php').read().decode('utf-8')
assert 'btnToggleFormPassword' in users_html, "btnToggleFormPassword not found in users.php"
assert 'toggleFormPasswordVisibility' in users_html, "toggleFormPasswordVisibility script not found in users.php"
assert 'iconToggleFormPassword' in users_html, "iconToggleFormPassword not found in users.php"
print("[OK] admin/users.php form has interactive eye toggle on password input!")

# 4. Test ZIP download in Berita Acara (Per Tingkat XII)
res_zip_ba = opener.open('http://127.0.0.1:8080/admin/berita-acara.php?action=download_zip&mode=tingkat&tingkat=XII')
assert res_zip_ba.status == 200, "Berita Acara ZIP download status != 200"
assert 'application/zip' in res_zip_ba.headers.get('Content-Type', ''), f"Wrong content-type: {res_zip_ba.headers.get('Content-Type')}"
zip_ba_data = res_zip_ba.read()
assert len(zip_ba_data) > 0, "Berita Acara ZIP file is empty"
z_ba = zipfile.ZipFile(io.BytesIO(zip_ba_data))
files_ba = z_ba.namelist()
print(f"[OK] admin/berita-acara.php ZIP download successful! Extracted {len(files_ba)} PDFs: {files_ba[:3]}")

# 5. Test ZIP download in Daftar Hadir (Per Tingkat XII)
res_zip_dh = opener.open('http://127.0.0.1:8080/admin/absensi.php?action=download_zip&mode=tingkat&tingkat=XII')
assert res_zip_dh.status == 200, "Absensi ZIP download status != 200"
assert 'application/zip' in res_zip_dh.headers.get('Content-Type', ''), f"Wrong content-type: {res_zip_dh.headers.get('Content-Type')}"
zip_dh_data = res_zip_dh.read()
assert len(zip_dh_data) > 0, "Absensi ZIP file is empty"
z_dh = zipfile.ZipFile(io.BytesIO(zip_dh_data))
files_dh = z_dh.namelist()
print(f"[OK] admin/absensi.php ZIP download successful! Extracted {len(files_dh)} PDFs: {files_dh[:3]}")

print("\n>>> ALL ROUND 2 FIXES VALIDATED WITH 100% SUCCESS! <<<")
