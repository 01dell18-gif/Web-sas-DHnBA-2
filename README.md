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

## 🔑 Kredensial Akses Administrator
* **URL Login:** `http://localhost:8080/admin/login.php`
* **Username:** `admin`
* **Password:** `admin123`

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

### 2. Panel Administrator (`/admin/`)
| Menu / Berkas | Fungsi & Fitur Utama |
|---|---|
| `/admin/index.php` | **Dashboard Administrator:** Statistik total kelas, total siswa, arsip absensi, arsip berita acara, folder Google Drive, alert selisih dokumen ujian, pintasan cepat, dan log aktivitas login/transaksi. |
| `/admin/kelas.php` | **Kelola Data Kelas:** Tambah, edit, hapus kelas, toggle status aktif/nonaktif, dan ringkasan jumlah siswa terdaftar per kelas. |
| `/admin/siswa.php` | **Kelola Data Siswa:** Tambah, edit, hapus, toggle aktif siswa, filter siswa berdasarkan kelas. |
| `/admin/import-siswa.php` | **Impor Siswa Excel / CSV:** Impor massal data siswa dari Excel (.xlsx) atau CSV dengan pencocokan kelas otomatis, serta download template CSV. |
| `/admin/template-ujian.php` | **Template Ujian & Editor Redaksi:** Konfigurasi template ujian, nama ujian, tahun ajaran, dan editor WYSIWYG berita acara dengan palet tag placeholder interaktif (`[nama_ujian]`, `[tahun_ajaran]`, `[hari]`, `[tanggal]`, `[mata_pelajaran]`, `[kelas]`, `[ruang]`, `[peserta_hadir]`, `[sekolah]`, dll). |
| `/admin/absensi.php` | **Arsip Daftar Hadir:** Filter pencarian multi-kategori (tingkat, kelas, tanggal, kata kunci), unduh single PDF, preview inline tab baru, modal detail dokumen, checkbox aksi batch, dan **Unduh Sekaligus File .ZIP** (per tingkat, per kelas, per tanggal, atau pilihan khusus). |
| `/admin/berita-acara.php` | **Arsip Berita Acara:** Filter pencarian, single download/view, galeri foto dokumentasi interaktif, unduh paket arsip .ZIP, dan modal detail dokumen. |
| `/admin/rekap-kehadiran.php` | **Laporan Rekap Kehadiran:** Matriks kehadiran lengkap seluruh mata pelajaran per siswa pada kelas yang dipilih, rekapitulasi total H, D, I, S, A, dan tombol ekspor. |
| `/admin/export-rekap-excel.php` | **Ekspor Excel:** Menghasilkan spreadsheet resmi rekap kehadiran siswa per kelas berformat `.xls` / `.xlsx`. |
| `/admin/rekonsiliasi.php` | **Audit & Rekonsiliasi Dokumen:** Pencocokan silang otomatis antara Arsip Daftar Hadir dan Berita Acara untuk melacak sesi ujian yang belum lengkap atau terinput ganda. |
| `/admin/pengaturan.php` | **Pengaturan Identitas & Kop Surat:** Unggah gambar kop surat resmi (banner full header horizontal), ganti logo tunggal, nama sekolah, alamat, kontak, dan URL Google Drive pengawas. |
| `/admin/password.php` | **Ganti Password:** Ubah password akun administrator dengan enkripsi bcrypt. |
| `/admin/logout.php` | Mengakhiri sesi administrator secara aman. |

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
