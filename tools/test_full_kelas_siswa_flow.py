import urllib.request, urllib.parse, http.cookiejar, re, sqlite3

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# Ensure clean DB initially
_c = sqlite3.connect('database/sas.sqlite')
_c.execute("DELETE FROM siswa WHERE nis = '88001'")
_c.execute("DELETE FROM kelas WHERE nama_kelas LIKE '%REKAYASA%' OR nama_kelas = 'XII RPL 1'")
_c.commit()
_c.close()

# 1. Login as admin
login_html = opener.open('http://127.0.0.1:8081/admin/login.php').read().decode('utf-8')
csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', login_html).group(1)
opener.open('http://127.0.0.1:8081/admin/login.php', data=urllib.parse.urlencode({
    'username': 'admin',
    'password': 'admin123',
    'csrf_token': csrf
}).encode('utf-8'))
print("[OK] Admin login successful")

# 2. Check admin/kelas.php initial state
kelas_html = opener.open('http://127.0.0.1:8081/admin/kelas.php').read().decode('utf-8')
assert 'Kelola Data Kelas' in kelas_html
print("[OK] admin/kelas.php loaded successfully")

# 3. Add student with new class via admin/siswa.php
siswa_page = opener.open('http://127.0.0.1:8081/admin/siswa.php').read().decode('utf-8')
siswa_csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', siswa_page).group(1)

add_student_data = urllib.parse.urlencode({
    'action': 'save',
    'class_id': 0,
    'kelas_baru': 'XII RPL 1',
    'nama': 'I Putu Test Siswa',
    'nis': '88001',
    'nisn': '0088001',
    'aktif': 1,
    'csrf_token': siswa_csrf
}).encode('utf-8')
opener.open('http://127.0.0.1:8081/admin/siswa.php', data=add_student_data)
print("[OK] Created student 'I Putu Test Siswa' with new class 'XII RPL 1'")

# Verify in DB
db = sqlite3.connect('database/sas.sqlite')
st = db.execute("SELECT id, class_id, nama_kelas, nama FROM siswa WHERE nis = '88001'").fetchone()
assert st is not None, "Student not found in DB"
class_id = st[1]
assert st[2] == 'XII RPL 1', f"Expected 'XII RPL 1', got {st[2]}"
print(f"[OK] Student verified in DB with class_id = {class_id} and nama_kelas = '{st[2]}'")

# 4. View admin/kelas.php: class list must now be derived from student data!
kelas_with_st = opener.open('http://127.0.0.1:8081/admin/kelas.php').read().decode('utf-8')
assert 'Daftar Kelas dari Data Siswa Peserta Ujian' in kelas_with_st, "Heading must indicate source is Data Siswa"
assert 'XII RPL 1' in kelas_with_st, "Class 'XII RPL 1' must appear in class table"
assert '1 Siswa' in kelas_with_st, "Class must show 1 Siswa"
print("[OK] admin/kelas.php verified: list taken from Data Siswa Peserta Ujian!")

# 5. Check edit mode admin/kelas.php?edit=ID
edit_html = opener.open(f'http://127.0.0.1:8081/admin/kelas.php?edit={class_id}').read().decode('utf-8')
assert 'Sinkronisasi Otomatis Siswa' in edit_html, "Sync notice missing"
assert 'I Putu Test Siswa' in edit_html, "Related student preview missing"
print("[OK] admin/kelas.php edit form shows related student preview and auto-sync notice!")

# 6. Perform Edit Data Kelas: rename class from 'XII RPL 1' to 'XII REKAYASA PERANGKAT LUNAK'
edit_csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', edit_html).group(1)
save_data = urllib.parse.urlencode({
    'action': 'save',
    'id': class_id,
    'nama_kelas': 'XII REKAYASA PERANGKAT LUNAK',
    'tingkat': 'XII',
    'program_keahlian': 'Rekayasa Perangkat Lunak',
    'aktif': 1,
    'csrf_token': edit_csrf
}).encode('utf-8')
edit_res = opener.open('http://127.0.0.1:8081/admin/kelas.php', data=save_data).read().decode('utf-8')
print("edit_res length:", len(edit_res))
print("edit_res snippet:", edit_res[:400])
assert 'XII REKAYASA PERANGKAT LUNAK' in edit_res, "New class name not in response"
assert 'berhasil diperbarui' in edit_res, "Flash success message not in response"
print("[OK] Edited class submitted and saved successfully!")

# 7. Check database: student's class MUST HAVE CHANGED AUTOMATICALLY!
st_after = db.execute("SELECT id, class_id, nama_kelas, nama FROM siswa WHERE nis = '88001'").fetchone()
assert st_after[2] == 'XII REKAYASA PERANGKAT LUNAK', f"Student nama_kelas was not automatically updated! Got: {st_after[2]}"
print(f"[OK] In database: student '{st_after[3]}' nama_kelas automatically updated to '{st_after[2]}'!")

# 8. Check admin/siswa.php shows the updated class name for the student!
siswa_view = opener.open('http://127.0.0.1:8081/admin/siswa.php').read().decode('utf-8')
assert 'XII REKAYASA PERANGKAT LUNAK' in siswa_view, "Updated class name not visible in admin/siswa.php"
assert 'I Putu Test Siswa' in siswa_view, "Student name not found in siswa.php"
print("[OK] admin/siswa.php displays the updated class name for all students in that class!")

# 9. Clean up test data to preserve clean current DB state
db.execute("DELETE FROM siswa WHERE nis = '88001'")
db.execute("DELETE FROM kelas WHERE id = ?", (class_id,))
db.commit()
db.close()
print("[OK] Cleaned up test student and test class cleanly!")

# 10. Verify clean DB state
final_kelas = opener.open('http://127.0.0.1:8081/admin/kelas.php').read().decode('utf-8')
assert 'Belum ada data siswa di database' in final_kelas
print("[SUCCESS] All requirements verified 100%! Flow is rock-solid.")
