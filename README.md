# Sistem Administrasi Ujian (SAS) - SMK Negeri 1 Klungkung
### Generator Absensi & Berita Acara Ujian Resmi Berformat A4 PDF & Spreadsheet Excel (.xls)

Aplikasi ini merupakan sistem administrasi ujian presisi dan fungsional untuk SMK Negeri 1 Klungkung. Aplikasi ini dirancang untuk memfasilitasi administrasi ujian sekolah, pencatatan kehadiran siswa live, pembuatan berita acara, dokumentasi foto pengawasan, tanda tangan digital/manual, audit rekonsiliasi dokumen, rekap matriks kehadiran seluruh mata pelajaran, serta ekspor dokumen ke PDF resmi berformat A4 dan lembar kerja Excel berformat `.xls`.

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
  * *Hak Akses:* Dashboard, Data Kelas, Data Siswa, Data Guru, Impor Excel (Siswa & Guru), Rekap Kehadiran Siswa (Matriks), Arsip Daftar Hadir, Arsip Berita Acara, Rekonsiliasi Dokumen, Editor Template Ujian WYSIWYG, Pengaturan Kop & Drive, dan Manajemen User.
* **2. Operator (Hak Akses Operasional Ujian):**
  * **Username:** `operator`
  * **Password:** `operator123`
  * *Hak Akses:* Dashboard, Data Kelas, Data Siswa, Data Guru, Impor Excel (Siswa & Guru), Rekap Kehadiran Siswa, Cetak/Unduh Arsip, Rekonsiliasi Dokumen, dan Ganti Password Akun.

---

## 📁 Struktur Navigasi & Modul Aplikasi

### 1. Halaman Publik
| URL / Berkas | Fungsi & Fitur Utama |
|---|---|
| `/index.php` | Beranda Portal Publik dengan kartu pilihan: **Daftar Hadir Ujian** dan **Berita Acara Ujian**. |
| `/absensi.php` | Generator Formulir Daftar Hadir Ujian: Live synchronised A4 Preview, pemilihan kelas, daftar siswa interaktif per kelas, status kehadiran (Hadir, Dispensasi, Izin, Sakit, Tidak Hadir), input nama pengawas dengan fitur ketik langsung (searchable datalist dari Data Guru), canvas tanda tangan digital pengawas, mode tanda tangan manual, dan tombol cetak/unduh PDF. |
| `/berita-acara.php` | Generator Formulir Berita Acara Pelaksanaan Ujian: Format redaksi dinamis dari template, validasi jumlah peserta (Hadir + Tidak Hadir = Seharusnya), input nama pengawas dengan fitur ketik langsung terintegrasi Data Guru, unggah & kompresi foto kamera ruangan ujian (maks 4 foto), tanda tangan digital pengawas, pratinjau 2 halaman A4 (Halaman 1 Berita Acara, Halaman 2 Lampiran Bukti Pelaksanaan), dan unduh PDF. |
| `/generate-absensi.php` | Backend generator PDF Daftar Hadir berstandar Dompdf A4 dengan Kop Surat resmi sekolah dan kalkulasi rekapitulasi kehadiran. |
| `/generate-berita-acara.php` | Backend generator PDF 2 halaman Berita Acara + Lampiran Bukti Foto Pelaksanaan. |
| `/api/get-students.php` | Endpoint JSON API untuk mengambil daftar siswa terdaftar berdasarkan `class_id`. |
| `/api/toggle-status.php` | Endpoint AJAX toggle cepat status aktif siswa, kelas, guru, dan user. |

### 2. Panel Administrator & Operator (`/admin/`)
| Menu / Berkas | Hak Akses | Fungsi & Fitur Utama |
|---|:---:|---|
| `/admin/index.php` | Admin & Operator | **Dashboard:** Statistik total kelas, siswa, guru, arsip absensi, berita acara, Google Drive, alert selisih dokumen, pintasan cepat, dan log aktivitas. |
| `/admin/kelas.php` | Admin & Operator | **Kelola Data Kelas:** Tambah, edit, hapus kelas (ikon aksi terstandarisasi), toggle status aktif/nonaktif, ringkasan siswa per kelas, dan tombol Reset Hapus Semua Data Kelas. |
| `/admin/siswa.php` | Admin & Operator | **Kelola Data Siswa:** Tambah, edit, hapus (ikon aksi), toggle aktif siswa, filter siswa per kelas, dan tombol Reset Hapus Semua Data Siswa. |
| `/admin/guru.php` | Admin & Operator | **Kelola Data Guru:** Manajemen pendidik (Nama, NIP/NIPPPK), toggle akses cepat (ceklis), ekspor & impor Excel `.xls`, dan tombol Reset Hapus Semua Data Guru. |
| `/admin/import-siswa.php` | Admin & Operator | **Impor Excel:** Tab ganda untuk unggah berkas siswa dan berkas guru berformat `.xls` lengkap dengan template unduhan. |
| `/admin/rekap-kehadiran.php` | Admin & Operator | **Laporan Rekap Kehadiran Siswa (Matriks Resmi):** Tampilan tabel matriks kehadiran dengan judul mapel vertikal (*rotated headers*), baris tanggal sesi, kalkulasi otomatis persentase kehadiran `(Hadir + Dispensasi) / Total Mapel × 100%`, legenda kode status warna-warni, kelola daftar mapel terdaftar (*chips* dengan tombol hapus ❌), modal input/perbarui absensi mapel dengan tombol *Set Semua Hadir*, dan tombol Hapus Presensi Kelas Ini. |
| `/admin/export-rekap-excel.php` | Admin & Operator | **Ekspor Excel (.xls):** Menghasilkan spreadsheet resmi rekap kehadiran siswa per kelas berformat `.xls` dengan format matriks sel, pewarnaan status, dan persentase kehadiran. |
| `/admin/absensi.php` | Admin & Operator | **Arsip Daftar Hadir:** Bilah unduh cepat ZIP dengan dropdown *Per Tingkat* yang tidak terpotong (overflow visible), modal unduh ZIP *Per Kelas* & *Per Tanggal* yang tampil langsung di bagian atas (*top-aligned*), aksi ikon terstandarisasi (Unduh, Pratinjau Tab Baru 👁️, Detail 🔍, Hapus 🗑️), dan tombol Hapus Semua Arsip Daftar Hadir. |
| `/admin/berita-acara.php` | Admin & Operator | **Arsip Berita Acara:** Bilah unduh cepat ZIP *Per Tingkat* tanpa terpotong, modal unduh ZIP *Per Kelas* & *Per Tanggal* tampil di atas, pratinjau tab baru 👁️, galeri dokumentasi foto interaktif, dan tombol Hapus Semua Arsip Berita Acara. |
| `/admin/preview.php` | Admin & Operator | **Pratinjau Dokumen Tab Baru:** Viewer dokumen A4 mandiri dengan toolbar cetak, unduh PDF, info metadata sesi, dan panel drawer foto lampiran. |
| `/admin/rekonsiliasi.php` | Admin & Operator | **Audit & Rekonsiliasi Dokumen:** Pencocokan silang otomatis antara Arsip Daftar Hadir dan Berita Acara untuk melacak sesi yang belum dibuat atau terinput ganda, serta tombol Hapus Seluruh Dokumen Audit. |
| `/admin/template-ujian.php` | **Khusus Admin** | **Template Ujian & Editor WYSIWYG:** Konfigurasi template ujian, nama ujian, tahun ajaran, dan editor WYSIWYG berita acara dengan palet tag placeholder interaktif serta live simulasi pratinjau teks. |
| `/admin/pengaturan.php` | **Khusus Admin** | **Pengaturan Identitas & Kop Surat:** Unggah kop surat resmi, ganti logo, nama sekolah, alamat, kontak, dan URL Google Drive. |
| `/admin/users.php` | **Khusus Admin** | **Manajemen Pengguna & Hak Akses:** Tambah, edit, dan hapus pengguna dengan pembagian peran Admin vs Operator. Dilengkapi kolom **Password** pada tabel *Daftar Pengguna Terdaftar* dengan ikon mata (`👁️`) untuk toggle melihat password secara interaktif. |
| `/admin/password.php` | Admin & Operator | **Ganti Password Akun:** Ubah kata sandi akun masing-masing pengguna dengan enkripsi bcrypt. |
| `/admin/logout.php` | Admin & Operator | Mengakhiri sesi pengguna secara aman. |

---

## 🎨 Pembaruan Desain & UI/UX Terkini
1. **Pratinjau Dokumen Tab Baru (Ikon Mata 👁️ Langsung Tampil Utuh):**
   - Memperbaiki masalah halaman pratinjau yang sebelumnya tampil kosong/blank akibat pembatasan rendering PDF di dalam `<iframe>` pada peramban berbasis Chromium (Google Chrome / Microsoft Edge).
   - Ikon mata (`👁️`) kini langsung membuka dokumen PDF resmi secara *native* di tab baru (`/admin/absensi.php?action=view_single&id=...` dan `/admin/berita-acara.php?action=view_single&id=...`) dengan header `application/pdf; inline` dan pembersihan cache limiter. Dokumen A4 tampil 100% utuh, jernih, dan responsif dengan fitur bawaan peramban (zoom, navigasi halaman, cetak, pencarian teks, dan unduh).
   - Halaman `/admin/preview.php` juga disempurnakan dengan fallback tag `<object>` dan `<embed>` serta tombol cepat *Buka Tab Baru*.
2. **Popup Konfirmasi "Lanjut / Tidak" untuk Tombol Hapus Semua Data:**
   - Menghilangkan dialog `prompt()` bawaan peramban yang mengharuskan mengetik teks kata sandi/kata kunci secara manual.
   - Menggantikannya dengan modal dialog konfirmasi modern dan elegan (`#modalConfirmResetGlobal`) dengan ikon peringatan tingkat tinggi dan dua pilihan tombol tegas: **"Lanjut"** (berwarna merah untuk melanjutkan penghapusan) dan **"Tidak"** (berwarna abu-abu untuk membatalkan).
   - Diterapkan secara menyeluruh di semua modul:
     - 👨‍🏫 **Data Guru:** Reset Hapus Semua Guru
     - 🏫 **Data Kelas:** Reset Hapus Semua Kelas
     - 👨‍🎓 **Data Siswa:** Reset Hapus Semua Siswa
     - 📋 **Arsip Daftar Hadir:** Reset Hapus Semua Arsip Daftar Hadir
     - 📑 **Arsip Berita Acara:** Reset Hapus Semua Arsip Berita Acara
     - ⚖️ **Rekonsiliasi Dokumen:** Reset Hapus Seluruh Dokumen Audit
     - 📊 **Rekap Kehadiran:** Reset Hapus Presensi Kelas Tertentu
3. **Fitur Intip/Lihat Password Interaktif (Ikon Mata 👁️):**
   - **Tabel Pengguna:** Tabel *Daftar Pengguna Terdaftar* di menu **Manajemen Pengguna & Hak Akses** menyertakan kolom **Password** dengan titik-titik tersamarkan (`••••••••`) dan tombol ikon mata (`👁️` / `bi-eye`) untuk toggle melihat password pengguna secara langsung.
   - **Formulir Tambah/Edit Pengguna:** Input kolom password pada form kartu *Tambah Pengguna Baru* kini dilengkapi tombol mata (`👁️`) interaktif di dalam field untuk melihat teks password yang sedang diketik sebelum disimpan.
   - **Formulir Login Admin:** Input password pada halaman login juga dilengkapi tombol mata (`👁️`) untuk kenyamanan saat mengetik kata sandi.
   - Sinkronisasi kolom `password_plain` otomatis tersimpan pada basis data SQLite (`database/sas.sqlite`) dan skema MySQL (`database/database_mysql.sql`) saat penambahan maupun pembaruan data pengguna.
4. **Unduh Sekaligus Berupa File ZIP (Universal ZIP Generator):**
   - Mengatasi masalah `Fatal error: Class "ZipArchive" not found` yang muncul saat mengunduh ZIP (Per Tingkat, Per Kelas, atau Per Tanggal) pada sistem PHP tanpa ekstensi zip.
   - Dibuat fungsi universal `create_zip_archive()` di `config/functions.php`:
     - Secara otomatis mendeteksi dan menggunakan ekstensi native `ZipArchive` bila tersedia.
     - Menyediakan mesin fallback murni **Pure-PHP PKZIP Packer** berstandar RFC tanpa dependensi modul eksternal, sehingga pembuatan file `.zip` dijamin 100% berhasil di seluruh komputer/server lokal.
     - Script `start-server.bat` juga telah diperbarui dengan argumen `-d extension=zip` untuk mengaktifkan dukungan native zip bawaan XAMPP.
5. **Perbaikan Redirection Header pada Pengaturan Kop & Drive:**
   - Memperbaiki peringatan `Warning: Cannot modify header information - headers already sent` pada saat menyimpan perubahan di menu **Pengaturan Kop & Drive** (`/admin/pengaturan.php`) dan **Template Ujian** (`/admin/template-ujian.php`).
   - Struktur file disesuaikan agar pemrosesan formulir POST dan pengalihan URL (`header('Location: ...')`) dieksekusi sebelum output HTML `layout_header.php` dimuat.
6. **Dropdown Unduh ZIP Per Tingkat:**
   - Masalah clipping / terpotong akibat `overflow: hidden` pada `.card` telah diperbaiki menggunakan class `.card-overflow-visible` dan `z-index: 1050;` sehingga seluruh opsi tingkat (X, XI, XII) tampil utuh dan jelas.
7. **Modal Pop-up Unduh ZIP Top-Aligned:**
   - Modal unduh *Per Kelas* dan *Per Tanggal* kini tampil langsung di bagian atas layar (`align-items: flex-start; padding-top: 50px;`) dengan animasi *modalTopSlide*, memudahkan pengguna tanpa harus mencari ke bagian tengah/bawah dokumen yang panjang.
8. **Format Matriks Rekap Kehadiran Siswa:**
   - Judul kolom mata pelajaran diputar 180° secara vertikal (`writing-mode: vertical-rl; transform: rotate(180deg)`), dilengkapi baris sub-header tanggal pelaksanaan (`dd/mm/yyyy`).
   - Kolom terakhir menampilkan **PERSENTASE KEHADIRAN** dengan formula resmi `(Hadir + Dispensasi) / Total Mapel × 100%`.
   - Kode sel presensi menggunakan palet warna lembut (`H` hijau `#15803d`/`#dcfce7`, `D` biru, `I` kuning, `S` ungu, `A` merah, `-` abu-abu).
   - Dilengkapi kartu kelola mata pelajaran terdaftar (*chips* dengan tombol hapus ❌) serta modal input/perbarui mapel dengan pintasan `✅ Set Semua Hadir`.
9. **Standardisasi Ikon Aksi & Format Excel (.xls):**
   - Seluruh tabel master dan arsip menggunakan kelompok tombol ikon yang seragam (`.action-btn-group`).
   - Seluruh fitur ekspor dan impor distandarisasikan ke format `.xls` dengan tipe MIME `application/vnd.ms-excel` dan styling `mso-number-format: "\@"` untuk melindungi angka NIS/NIP agar nol di depan tidak hilang.

---

## 🗄️ Arsitektur Basis Data
Aplikasi mendukung 2 mode basis data:
1. **SQLite (Bawaan & Siap Pakai):** Tersimpan di `database/sas.sqlite`. Sudah terisi penuh (*pre-seeded*) dengan:
   * 11 Kelas aktif (XII AK, XII BD 1, XII BD 2, XII DKV, XII LPB, XII LPKKK 1, XII LPKKK 2, XII MP 1, XII MP 2, XII TKJ, XII TSM).
   * 258 Siswa lengkap dengan NIS dan nama asli.
   * Data Guru lengkap dengan status ceklis aktif.
   * 100 Arsip Daftar Hadir & 94 Berita Acara.
   * 138 Foto dokumentasi pelaksanaan asli di `storage/uploads/dokumentasi/`.
   * Template Ujian SAS Ganjil 2026/2027.
2. **MySQL / MariaDB:** File dump skema dan data lengkap tersedia di:
   ```
   database/database_mysql.sql
   ```
   Untuk beralih ke MySQL, cukup sesuaikan konstanta di `config/database.php`.
