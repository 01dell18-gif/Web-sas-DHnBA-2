import urllib.request
import urllib.parse
import http.cookiejar
import re

BASE = "http://127.0.0.1:8081"
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# 1. Test generate-absensi.php
print("Testing generate-absensi.php...")
absensi_page = opener.open(f"{BASE}/absensi.php").read().decode()
csrf = re.search(r'name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']', absensi_page).group(1)

post_data = {
    'csrf_token': csrf,
    'nama_ujian': 'Sumatif Akhir Semester (SAS) Ganjil',
    'tahun_ajaran': '2026/2027',
    'semester': 'Ganjil',
    'tanggal_ujian': '2026-10-22',
    'mata_pelajaran': 'Pemrograman Web dan Perangkat Bergerak',
    'sesi': '1',
    'ruang': 'Lab Komputer RPL',
    'jam_mulai': '07:30',
    'jam_selesai': '09:30',
    'class_id': '15',
    'nama_pengawas_1': 'I Wayan Developer, S.Kom.',
    'nip_pengawas_1': '198501012010011001',
    'nama_pengawas_2': '',
    'nip_pengawas_2': '',
    'signature_mode': 'manual',
    'action': 'download',
    'students[1][id]': '1',
    'students[1][nama]': 'I Desak Ketut Patniki Astuti',
    'students[1][nis]': '13741',
    'students[1][status]': 'Hadir',
    'students[2][id]': '2',
    'students[2][nama]': 'I Dewa Ayu Awidiya Sari',
    'students[2][nis]': '13742',
    'students[2][status]': 'Hadir'
}

encoded = urllib.parse.urlencode(post_data).encode()
resp = opener.open(f"{BASE}/generate-absensi.php", data=encoded)
pdf_bytes = resp.read()
print(f"   [OK] Generated Daftar Hadir PDF: {len(pdf_bytes)} bytes, Content-Type: {resp.headers.get('Content-Type')}")
print("Response text:", pdf_bytes.decode(errors='ignore')[:500])
assert pdf_bytes.startswith(b'%PDF')

# 2. Test generate-berita-acara.php
print("Testing generate-berita-acara.php...")
ba_page = opener.open(f"{BASE}/berita-acara.php").read().decode()
csrf2 = re.search(r'name=["\']csrf_token["\']\s+value=["\']([^"\']+)["\']', ba_page).group(1)

post_data_ba = {
    'csrf_token': csrf2,
    'template_id': '1',
    'nama_ujian': 'Sumatif Akhir Semester (SAS) Ganjil',
    'tahun_ajaran': '2026/2027',
    'semester': 'Ganjil',
    'tanggal_ujian': '2026-10-22',
    'mata_pelajaran': 'Pemrograman Web dan Perangkat Bergerak',
    'ruang': 'Lab Komputer RPL',
    'jam_mulai': '07:30',
    'jam_selesai': '09:30',
    'class_id': '15',
    'nama_kelas': 'XII AK',
    'peserta_seharusnya': '28',
    'peserta_hadir': '28',
    'peserta_tidak_hadir': '0',
    'blangko_berita_acara': '1',
    'blangko_daftar_hadir': '1',
    'catatan_pelaksanaan': 'Ujian berlangsung kondusif dan tertib.',
    'nama_pengawas_1': 'I Wayan Developer, S.Kom.',
    'nip_pengawas_1': '198501012010011001',
    'nama_pengawas_2': '',
    'nip_pengawas_2': '',
    'signature_mode': 'manual',
    'action': 'download'
}

encoded_ba = urllib.parse.urlencode(post_data_ba).encode()
resp_ba = opener.open(f"{BASE}/generate-berita-acara.php", data=encoded_ba)
pdf_bytes_ba = resp_ba.read()
print(f"   [OK] Generated Berita Acara PDF: {len(pdf_bytes_ba)} bytes, Content-Type: {resp_ba.headers.get('Content-Type')}")
assert pdf_bytes_ba.startswith(b'%PDF')

print("\nPDF GENERATION & INTEGRATION 100% OPERATIONAL!")
