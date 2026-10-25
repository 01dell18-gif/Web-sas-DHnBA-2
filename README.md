# Sistem Administrasi Ujian (SAS) - SMK NEGERI FYJ Channel

## Generator Absensi & Berita Acara Ujian Resmi Berformat A4 PDF & Spreadsheet Excel (.xls)

Aplikasi ini merupakan sistem administrasi ujian presisi dan fungsional untuk **SMK NEGERI FYJ Channel**. Aplikasi ini dirancang untuk memfasilitasi administrasi ujian sekolah, pencatatan kehadiran siswa live, pembuatan berita acara, dokumentasi foto pengawasan, tanda tangan digital/manual, audit rekonsiliasi dokumen, menu publik Arsip Dokumen untuk pengawas, rekap matriks kehadiran seluruh mata pelajaran, serta ekspor dokumen ke PDF resmi berformat A4 dan lembar kerja Excel berformat `.xls`.

---

## 🏫 Identitas & Pengaturan Default Sekolah

| Parameter Pengaturan | Nilai Konfigurasi Default |
|---|---|
| **Nama Sekolah** | **SMK NEGERI FYJ Channel** |
| **Alamat Sekolah** | Jl. Merpati No. 1 |
| **Kop Surat Baris 1** | PEMERINTAH PROVINSI FYJ |
| **Kop Surat Baris 2** | DINAS PENDIDIKAN KEPEMUDAAN DAN OLAHRAGA |
| **Kop Surat Baris 3** | SMK NEGERI FYJ Channel |
| **Kontak & Telepon** | Telp. (0300) 2002 \| Email: smkn@yahoo.com |
| **Website Resmi** | Website: smkn.sch.id |
| **Logo Sekolah** | `storage/uploads/logo/logo_1792652650.png` |
| **Folder Google Drive** | `https://drive.google.com/drive/folders/1sHpxyW6DbHSp1D7KNN-_xTu8fG9rY35h?usp=sharing` |
| **Format Template Ujian** | Sumatif Akhir Semester (SAS) Ganjil - Tahun Ajaran 2026/2027 |

---

## 🚀 Panduan Menjalankan Aplikasi

### Cara 1: Menggunakan File Batch (Satu Kali Klik)

Cukup jalankan / double-click file:

```cmd
start-server.bat
```

Script ini akan:
1. Memeriksa ketersediaan PHP di `C:\xampp\php\php.exe`.
2. Mengaktifkan ekstensi yang diperlukan (`gd`, `mbstring`, `pdo_sqlite`, `zip`).
3. Menjalankan web server lokal di `http://localhost:8081`.
4. Otomatis membuka browser default Anda ke halaman beranda.

### Cara 2: Menjalankan via Terminal / CMD / PowerShell

```bash
"C:\xampp\php\php.exe" -d extension_dir="C:\xampp\php\ext" -d browscap="" -d extension=gd -S 127.0.0.1:8081
```
Lalu buka peramban ke: `http://localhost:8081/`

---

## 🔑 Kredensial Akses Pengguna Sistem
Sistem mendukung 2 tingkatan hak akses berbasis peran (RBAC):
* **1. Administrator (Hak Akses Penuh Seluruh Sistem):**
  * **Nama Akun:** `Administrator SAS SMK`
  * **Username:** `admin`
  * **Password:** `admin123`
  * *Hak Akses:* Dashboard, Data Kelas, Data Siswa, Data Guru, Impor Excel (Siswa & Guru), Rekap Kehadiran Siswa (Matriks), Arsip Daftar Hadir, Arsip Berita Acara, Rekonsiliasi Dokumen, Editor Template Ujian WYSIWYG, Pengaturan Kop & Drive, Manajemen User, dan Profil Akun.
* **2. Operator (Hak Akses Operasional Ujian):**
  * **Nama Akun:** `Operator SAS SMK`
  * **Username:** `operator`
  * **Password:** `operator123`
  * *Hak Akses:* Dashboard, Data Kelas, Data Siswa, Data Guru, Impor Excel (Siswa & Guru), Rekap Kehadiran Siswa, Cetak/Unduh Arsip, Rekonsiliasi Dokumen, dan Profil Akun.

---

## 📁 Struktur Navigasi & Modul Aplikasi

### 1. Halaman Publik (Dapat Diakses Guru & Pengawas)
| URL / Berkas | Fungsi & Fitur Utama |
|---|---|
| `/index.php` | **Beranda Portal Publik:** Dilengkapi 3 kartu pilihan utama: **Daftar Hadir Ujian**, **Berita Acara Ujian**, dan **Arsip Dokumen Ujian**. |
| `/absensi.php` | **Generator Formulir Daftar Hadir Ujian:** Live synchronised A4 Preview, pemilihan kelas, daftar siswa interaktif per kelas, status kehadiran (Hadir, Dispensasi, Izin, Sakit, Tidak Hadir), input nama pengawas dengan fitur ketik langsung (searchable datalist dari Data Guru), canvas tanda tangan digital pengawas, mode tanda tangan manual, dan tombol cetak/unduh PDF. Data tersimpan langsung di database. |
| `/berita-acara.php` | **Generator Formulir Berita Acara Ujian:** Format redaksi dinamis dari template, validasi jumlah peserta (Hadir + Tidak Hadir = Seharusnya), input nama pengawas terintegrasi Data Guru, unggah & kompresi foto kamera ruangan ujian (maks 4 foto), tanda tangan digital pengawas, pratinjau 2 halaman A4 (Berita Acara + Lampiran Bukti Pelaksanaan), dan unduh PDF. Data tersimpan langsung di database. |
| `/arsip.php` | **Arsip Dokumen Ujian (Pelihat Pengawas):** Halaman viewer publik untuk pengawas melihat dan memastikan lembar presensi dan berita acara yang telah diisi sudah tersimpan aman di database server. Dilengkapi filter pencarian kata kunci, tanggal ujian, tingkat, kelas, status kelengkapan dokumen, dan tombol lihat/pratinjau PDF langsung di tab baru. |
| `/generate-absensi.php` | Backend generator PDF Daftar Hadir berstandar Dompdf A4 dengan Kop Surat resmi sekolah dan kalkulasi rekapitulasi kehadiran. |
| `/generate-berita-acara.php` | Backend generator PDF 2 halaman Berita Acara + Lampiran Bukti Foto Pelaksanaan. |
| `/api/get-students.php` | Endpoint JSON API untuk mengambil daftar siswa terdaftar berdasarkan `class_id`. |
| `/api/clear-cache.php` | Endpoint API untuk menghapus cache sesi dan file sementara server. |

### 2. Panel Administrator & Operator (`/admin/`)
| Menu / Berkas | Hak Akses | Fungsi & Fitur Utama |
|---|:---:|---|
| `/admin/index.php` | Admin & Operator | **Dashboard:** Statistik total kelas, siswa, guru, arsip absensi, berita acara, Google Drive, alert selisih dokumen, pintasan cepat, dan log aktivitas. |
| `/admin/kelas.php` | Admin & Operator | **Kelola Data Kelas:** Tambah, edit, hapus kelas (ikon aksi terstandarisasi), toggle status aktif/nonaktif, ringkasan siswa per kelas, dan tombol Reset Hapus Semua Data Kelas. |
| `/admin/siswa.php` | Admin & Operator | **Kelola Data Siswa:** Tambah, edit, hapus (ikon aksi), toggle aktif siswa, filter siswa per kelas, **Ekspor Excel (.xls)** per kelas/semua kelas, dan tombol Reset Hapus Semua Data Siswa. |
| `/admin/guru.php` | Admin & Operator | **Kelola Data Guru & Pengawas:** Manajemen pendidik (Nama, NIP/NIPPPK), toggle akses cepat (ceklis), **Ekspor Excel (.xls)**, **Impor Excel (.xls)**, dan tombol Reset Hapus Semua Data Guru. |
| `/admin/mapel.php` | Admin & Operator | **Kelola Data Mapel:** Master data mata pelajaran (Kode, Nama, Kelompok: Umum/Kejuruan/Muatan Lokal), toggle akses cepat (ceklis), **Ekspor Excel (.xls)**, **Impor Excel (.xls)**, template unduhan, dan tombol Reset Hapus Semua Data Mapel. Terhubung dinamis sebagai pilihan otomatis di formulir Daftar Hadir dan Berita Acara. |
| `/admin/import-siswa.php` | Admin & Operator | **Impor Data Excel:** Tab navigasi terpadu 3-in-1 untuk Unggah Berkas Siswa, Unggah Berkas Guru, dan Unggah Berkas Mapel berformat `.xls` lengkap dengan template unduhan masing-masing modul. |
| `/admin/rekap-kehadiran.php` | Admin & Operator | **Laporan Rekap Kehadiran Siswa (Matriks Resmi):** Tampilan tabel matriks kehadiran dengan judul mapel vertikal (*rotated headers*), baris tanggal sesi, kalkulasi otomatis persentase kehadiran `(Hadir + Dispensasi) / Total Mapel × 100%`, legenda kode status warna-warni, kelola daftar mapel terdaftar (*chips* dengan tombol hapus ❌), modal input/perbarui absensi mapel dengan tombol *Set Semua Hadir*, dan tombol Hapus Presensi Kelas Ini. |
| `/admin/export-rekap-excel.php` | Admin & Operator | **Ekspor Excel (.xls):** Menghasilkan spreadsheet resmi rekap kehadiran siswa per kelas berformat `.xls` dengan format matriks sel, pewarnaan status, dan persentase kehadiran. |
| `/admin/export-rekap-pdf.php` | Admin & Operator | **Pratinjau & Cetak PDF Matriks:** Menghasilkan dokumen rekap kehadiran resmi per kelas format A4 Landscape lengkap dengan kop surat dan kolom pengesahan tanda tangan. |
| `/admin/absensi.php` | Admin & Operator | **Arsip Daftar Hadir:** Bilah unduh cepat ZIP dengan dropdown *Per Tingkat* tanpa terpotong, modal unduh ZIP *Per Kelas* & *Per Tanggal* tampil di atas (*top-aligned*), aksi ikon terstandarisasi (Unduh, Pratinjau Tab Baru 👁️, Detail 🔍, Hapus 🗑️), dan tombol Hapus Semua Arsip Daftar Hadir. |
| `/admin/berita-acara.php` | Admin & Operator | **Arsip Berita Acara:** Bilah unduh cepat ZIP *Per Tingkat*, modal unduh ZIP *Per Kelas* & *Per Tanggal*, pratinjau tab baru 👁️, galeri dokumentasi foto interaktif, dan tombol Hapus Semua Arsip Berita Acara. |
| `/admin/preview.php` | Admin & Operator | **Pratinjau Dokumen Tab Baru:** Viewer dokumen A4 mandiri dengan toolbar cetak, unduh PDF, info metadata sesi, dan panel drawer foto lampiran. |
| `/admin/rekonsiliasi.php` | Admin & Operator | **Audit & Rekonsiliasi Dokumen:** Pencocokan silang otomatis antara Arsip Daftar Hadir dan Berita Acara untuk melacak sesi yang belum dibuat atau terinput ganda, serta tombol Hapus Seluruh Dokumen Audit. |
| `/admin/template-ujian.php` | **Khusus Admin** | **Template Ujian & Editor WYSIWYG:** Konfigurasi template ujian, nama ujian, tahun ajaran, dan editor WYSIWYG berita acara dengan palet tag placeholder interaktif serta live simulasi pratinjau teks. |
| `/admin/pengaturan.php` | **Khusus Admin** | **Pengaturan Identitas & Kop Surat:** Unggah kop surat resmi, ganti logo, nama sekolah (**SMK NEGERI FYJ Channel**), alamat, kontak, dan URL Google Drive. |
| `/admin/users.php` | **Khusus Admin** | **Manajemen Pengguna & Hak Akses:** Tambah, edit, dan hapus pengguna dengan pembagian peran Admin vs Operator. Dilengkapi kolom **Password** dengan ikon mata (`👁️`) untuk toggle melihat password secara interaktif, tombol **Ekspor Excel (.xls)**, serta modal **Impor Excel (.xls)** massal. |
| `/admin/profil.php` | Admin & Operator | **Profil Akun & Keamanan:** Menu identitas akun (diakses langsung dengan mengklik nama akun di header), mendukung unggah foto profil (avatar), ubah nama lengkap, ubah username, dan pembaruan password aman. |
| `/admin/password.php` | Admin & Operator | *Redirect alias* ke halaman `/admin/profil.php`. |
| `/admin/logout.php` | Admin & Operator | Mengakhiri sesi pengguna secara aman. |

---

## 🎨 Pembaruan Desain & UI/UX Terkini
1. **Menu Publik Arsip Dokumen (Pelihat Pengawas):**
   - Halaman publik `/arsip.php` yang dirancang khusus bagi para pengawas ujian untuk memeriksa apakah berkas presensi dan berita acara yang baru saja diisi sudah tersimpan dengan aman di database sekolah.
   - Menyediakan filter lengkap: pencarian teks bebas (mata pelajaran, nama pengawas, ruang ujian), filter tanggal pelaksanaan ujian, filter tingkat kelas (X, XI, XII), filter kelas spesifik, serta filter status kelengkapan dokumen.
   - Dilengkapi tombol pratinjau PDF langsung (`target="_blank"`) yang men-stream berkas PDF secara instan ke tab browser baru.
2. **Penyempurnaan Header Admin: Tombol Hapus Cache & Akses Profil:**
   - Tombol ganti password di header telah dilebur ke nama akun pengguna (`/admin/profil.php`), sehingga mengklik nama akun langsung membuka menu **Profil Akun**.
   - Menambahkan tombol ikon **Hapus Cache (`bi-trash3`)** di header atas untuk membersihkan cache browser/storage lokal dan merefresh kembali halaman sesuai menu terakhir yang dibuka.
3. **Menu Profil Akun Lengkap dengan Foto Profil (Avatar):**
   - Halaman `/admin/profil.php` memfasilitasi identitas akun pengguna, pratinjau avatar interaktif, unggah foto profil baru, serta pembaruan password.
   - Foto avatar otomatis tampil pada pojok kanan atas bilah header admin.
4. **Pratinjau Dokumen Tab Baru (Ikon Mata 👁️ Langsung Tampil Utuh):**
   - Membuka dokumen PDF resmi secara *native* di tab baru (`/admin/absensi.php?action=view_single&id=...` dan `/admin/berita-acara.php?action=view_single&id=...`) dengan header `application/pdf; inline`. Dokumen A4 tampil 100% utuh, jernih, dan responsif.
5. **Popup Konfirmasi "Lanjut / Tidak" untuk Tombol Hapus Semua Data:**
   - Menggantikan prompt native dengan modal dialog konfirmasi modern dan elegan (`#modalConfirmResetGlobal`) dengan dua pilihan tombol tegas: **"Lanjut"** (merah) dan **"Tidak"** (abu-abu).
6. **Fitur Intip/Lihat Password Interaktif (Ikon Mata 👁️):**
   - Diterapkan pada tabel data pengguna, form tambah pengguna, dan formulir login admin.
7. **Unduh Sekaligus Berupa File ZIP (Universal ZIP Generator):**
   - Mendukung native `ZipArchive` serta mesin fallback murni **Pure-PHP PKZIP Packer** berstandar RFC tanpa dependensi modul eksternal.
8. **Format Matriks Rekap Kehadiran Siswa:**
   - Judul kolom mata pelajaran diputar 180° secara vertikal (`writing-mode: vertical-rl; transform: rotate(180deg)`), kalkulasi otomatis persentase kehadiran `(Hadir + Dispensasi) / Total Mapel × 100%`.
9. **Standardisasi Ikon Aksi & Format Excel (.xls):**
   - Seluruh tabel master dan arsip menggunakan kelompok tombol ikon yang seragam (`.action-btn-group`), format ekspor/impor `.xls` berstandar MIME `application/vnd.ms-excel` dan proteksi teks `mso-number-format: "\@"`.
10. **Modul Master Data Mapel & Integrasi Form Publik:**
    - Master data kurikulum ujian sekolah terhubung dinamis ke input formulir publik melalui datalist pencarian cepat.
11. **Tata Letak Filter Horizontal Mendatar:**
    - Formulir filter pada menu Arsip Daftar Hadir dan Berita Acara tersusun sejajar rapi dalam satu baris mendatar dan responsif melipat di perangkat seluler.

---

## 🗄️ Arsitektur Basis Data
Aplikasi mendukung 2 mode basis data yang keduanya menyimpan seluruh perubahan secara terpusat:
1. **SQLite (Bawaan & Siap Pakai):** Tersimpan di `database/sas.sqlite`. Sudah terisi penuh (*pre-seeded*) dengan:
   * **Identitas Sekolah:** SMK NEGERI FYJ Channel (Jl. Merpati No. 1).
   * **11 Kelas aktif:** XII AK, XII BD 1, XII BD 2, XII DKV, XII LPB, XII LPKKK 1, XII LPKKK 2, XII MP 1, XII MP 2, XII TKJ, XII TSM.
   * **258 Siswa:** Lengkap dengan NIS, NISN, dan nama siswa terdaftar.
   * **Data Guru & Pengawas:** Pendidik aktif dan siap dipilih pada formulir ujian.
   * **31 Mata Pelajaran:** Kurikulum pre-seeded di tabel `mapel` (Umum, Kejuruan, Muatan Lokal).
   * **100 Arsip Daftar Hadir & 94 Berita Acara:** Tersimpan lengkap dengan riwayat waktu pelaksanaan.
   * **Template Ujian:** SAS Ganjil 2026/2027 siap pakai.
2. **MySQL / MariaDB:** File dump skema dan data lengkap tersedia di:
   ```
   database/database_mysql.sql
   ```
   Untuk beralih ke MySQL, cukup sesuaikan konstanta di `config/database.php`. Seluruh query telah diabstraksi via PDO sehingga 100% kompatibel antara SQLite dan MySQL.
