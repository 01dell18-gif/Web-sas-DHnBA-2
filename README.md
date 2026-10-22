# Sistem Administrasi Ujian (SAS) - SMK Negeri 1 Klungkung
### Generator Absensi & Berita Acara Ujian Resmi Berformat A4 PDF

Aplikasi ini merupakan klon 100% presisi dan fungsional dari portal ujian **https://sas.doantara.my.id/**. Aplikasi ini dirancang untuk memfasilitasi administrasi ujian sekolah, pencatatan kehadiran siswa live, pembuatan berita acara, dokumentasi foto pengawasan, tanda tangan digital/manual, audit rekonsiliasi dokumen, rekap matriks kehadiran seluruh mata pelajaran, serta ekspor dokumen ke PDF resmi berformat A4 dan lembar kerja Excel.

---

## 🚀 Panduan Menjalankan Aplikasi

### Cara 1: Menggunakan File Batch (Satu Kali Klik)
Cukup jalankan / double-click file:
```
start-server.bat
```
Script ini akan:
1. Memeriksa ketersediaan PHP di `C:\xampp\php\php.exe`.
2. Mengaktifkan ekstensi yang diperlukan (`gd`, `mbstring`, `pdo_sqlite`).
3. Menjalankan web server lokal di `http://localhost:8080`.
4. Otomatis membuka browser default Anda ke halaman beranda.

### Cara 2: Menjalankan via Terminal / CMD / PowerShell
```bash
"C:\xampp\php\php.exe" -d extension_dir="C:\xampp\php\ext" -d browscap="" -d extension=gd -S 127.0.0.1:8080
```
Lalu buka peramban ke: `http://localhost:8080/`

---

## 🔑 Kredensial Akses Pengguna Sistem
Sistem mendukung 2 tingkatan hak akses berbasis peran (RBAC):
* **1. Administrator (Hak Akses Penuh Seluruh Sistem):**
  * **Username:** `admin`
  * **Password:** `admin123`
  * *Hak Akses:* Dashboard, Data Kelas, Data Siswa, Impor Siswa, Rekap Nilai/Kehadiran, Cetak/Unduh Arsip, Rekonsiliasi Dokumen, Editor Template Ujian WYSIWYG, Pengaturan Kop & Drive, dan Manajemen User.
* **2. Operator (Hak Akses Operasional Ujian):**
  * **Username:** `operator`
  * **Password:** `operator123`
  * *Hak Akses:* Dashboard, Data Kelas, Data Siswa, Impor Siswa, Rekap Nilai/Kehadiran, Cetak/Unduh Arsip, Rekonsiliasi Dokumen, dan Ganti Password Pribadi.

---

## 📁 Struktur Navigasi & Modul Aplikasi

### 1. Halaman Publik
| URL / Berkas | Fungsi & Fitur Utama |
|---|---|
| `/index.php` | Beranda Portal Publik dengan kartu pilihan: **Daftar Hadir Ujian** dan **Berita Acara Ujian**. |
| `/absensi.php` | Generator Formulir Daftar Hadir Ujian: Live synchronised A4 Preview, pemilihan kelas, daftar siswa interaktif per kelas, status kehadiran (Hadir, Dispensasi, Izin, Sakit, Tidak Hadir), canvas tanda tangan digital pengawas, mode tanda tangan manual, dan tombol cetak/unduh PDF. |
| `/berita-acara.php` | Generator Formulir Berita Acara Pelaksanaan Ujian: Format redaksi dinamis dari template, validasi jumlah peserta (Hadir + Tidak Hadir = Seharusnya), unggah & kompresi foto kamera ruangan ujian (maks 4 foto), tanda tangan digital pengawas, pratinjau 2 halaman A4 (Halaman 1 Berita Acara, Halaman 2 Lampiran Bukti Pelaksanaan), dan unduh PDF. |
| `/generate-absensi.php` | Backend generator PDF Daftar Hadir berstandar Dompdf A4 dengan Kop Surat resmi sekolah dan kalkulasi rekapitulasi kehadiran. |
| `/generate-berita-acara.php` | Backend generator PDF 2 halaman Berita Acara + Lampiran Bukti Foto Pelaksanaan. |
| `/api/get-students.php` | Endpoint JSON API untuk mengambil daftar siswa terdaftar berdasarkan `class_id`. |

### 2. Panel Administrator & Operator (`/admin/`)
| Menu / Berkas | Hak Akses | Fungsi & Fitur Utama |
|---|:---:|---|
| `/admin/index.php` | Admin & Operator | **Dashboard:** Statistik total kelas, siswa, arsip absensi, berita acara, Google Drive, alert selisih dokumen, pintasan cepat, dan log aktivitas. |
| `/admin/kelas.php` | Admin & Operator | **Kelola Data Kelas:** Tambah, edit, hapus kelas, toggle status aktif/nonaktif, dan ringkasan siswa per kelas. |
| `/admin/siswa.php` | Admin & Operator | **Kelola Data Siswa:** Tambah, edit, hapus, toggle aktif siswa, dan filter siswa per kelas. |
| `/admin/import-siswa.php` | Admin & Operator | **Impor Siswa Excel / CSV:** Impor massal data siswa dari file Excel/CSV dengan pencocokan kelas otomatis. |
| `/admin/rekap-kehadiran.php` | Admin & Operator | **Laporan Rekap Kehadiran:** Matriks kehadiran lengkap seluruh mata pelajaran per siswa pada kelas yang dipilih. |
| `/admin/export-rekap-excel.php` | Admin & Operator | **Ekspor Excel:** Menghasilkan spreadsheet resmi rekap kehadiran siswa per kelas berformat `.xls` / `.xlsx`. |
| `/admin/absensi.php` | Admin & Operator | **Arsip Daftar Hadir:** Filter pencarian multi-kategori, unduh single PDF, preview inline, modal detail, dan **Unduh Sekaligus File .ZIP**. |
| `/admin/berita-acara.php` | Admin & Operator | **Arsip Berita Acara:** Filter pencarian, single download/view, galeri foto dokumentasi interaktif, dan unduh paket arsip .ZIP. |
| `/admin/rekonsiliasi.php` | Admin & Operator | **Audit & Rekonsiliasi Dokumen:** Pencocokan silang otomatis antara Arsip Daftar Hadir dan Berita Acara untuk melacak sesi yang belum dibuat atau terinput ganda. |
| `/admin/template-ujian.php` | **Khusus Admin** | **Template Ujian & Editor WYSIWYG:** Konfigurasi template ujian, nama ujian, tahun ajaran, dan editor WYSIWYG berita acara dengan palet tag placeholder interaktif serta live simulasi pratinjau teks. |
| `/admin/pengaturan.php` | **Khusus Admin** | **Pengaturan Identitas & Kop Surat:** Unggah kop surat resmi, ganti logo, nama sekolah, alamat, kontak, dan URL Google Drive. |
| `/admin/users.php` | **Khusus Admin** | **Manajemen User:** Tambah, edit, ganti password, dan hapus pengguna dengan pembagian peran Admin vs Operator. |
| `/admin/password.php` | Admin & Operator | **Ganti Password:** Ubah kata sandi akun masing-masing pengguna dengan enkripsi bcrypt. |
| `/admin/logout.php` | Admin & Operator | Mengakhiri sesi pengguna secara aman. |

---

## 🗄️ Arsitektur Basis Data
Aplikasi mendukung 2 mode basis data:
1. **SQLite (Bawaan & Siap Pakai):** Tersimpan di `database/sas.sqlite`. Sudah terisi penuh (*pre-seeded*) dengan:
   * 11 Kelas aktif (XII AK, XII BD 1, XII BD 2, XII DKV, XII LPB, XII LPKKK 1, XII LPKKK 2, XII MP 1, XII MP 2, XII TKJ, XII TSM).
   * 258 Siswa lengkap dengan NIS dan nama asli.
   * 100 Arsip Daftar Hadir & 94 Berita Acara.
   * 138 Foto dokumentasi pelaksanaan asli di `storage/uploads/dokumentasi/`.
   * Template Ujian SAS Ganjil 2026/2027.
2. **MySQL / MariaDB:** File dump skema dan data lengkap tersedia di:
   ```
   database/database_mysql.sql
   ```
   Untuk beralih ke MySQL, cukup sesuaikan konstanta di `config/database.php`.
