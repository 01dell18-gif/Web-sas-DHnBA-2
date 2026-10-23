# Sistem Administrasi Ujian (SAS) - SMK Negeri 1 Klungkung

## Generator Absensi & Berita Acara Ujian Resmi Berformat A4 PDF & Spreadsheet Excel (.xls)

Aplikasi ini merupakan sistem administrasi ujian presisi dan fungsional untuk SMK Negeri 1 Klungkung. Aplikasi ini dirancang untuk memfasilitasi administrasi ujian sekolah, pencatatan kehadiran siswa live, pembuatan berita acara, dokumentasi foto pengawasan, tanda tangan digital/manual, audit rekonsiliasi dokumen, rekap matriks kehadiran seluruh mata pelajaran, serta ekspor dokumen ke PDF resmi berformat A4 dan lembar kerja Excel berformat `.xls`.

---

## 🚀 Panduan Menjalankan Aplikasi

### Cara 1: Menggunakan File Batch (Satu Kali Klik)

Cukup jalankan / double-click file:

```cmd
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
| `/admin/siswa.php` | Admin & Operator | **Kelola Data Siswa:** Tambah, edit, hapus (ikon aksi), toggle aktif siswa, filter siswa per kelas, **Ekspor Excel (.xls)** per kelas/semua kelas, dan tombol Reset Hapus Semua Data Siswa. |
| `/admin/guru.php` | Admin & Operator | **Kelola Data Guru:** Manajemen pendidik (Nama, NIP/NIPPPK), toggle akses cepat (ceklis), **Ekspor Excel (.xls)**, **Impor Excel (.xls)**, dan tombol Reset Hapus Semua Data Guru. |
| `/admin/mapel.php` | Admin & Operator | **Kelola Data Mapel:** Master data mata pelajaran (Kode, Nama, Kelompok: Umum/Kejuruan/Muatan Lokal), toggle akses cepat (ceklis), **Ekspor Excel (.xls)**, **Impor Excel (.xls)**, template unduhan, dan tombol Reset Hapus Semua Data Mapel. Terhubung dinamis sebagai pilihan otomatis di formulir Daftar Hadir dan Berita Acara. |
| `/admin/import-siswa.php` | Admin & Operator | **Impor Data Excel:** Tab navigasi terpadu 3-in-1 untuk Unggah Berkas Siswa, Unggah Berkas Guru, dan Unggah Berkas Mapel berformat `.xls` lengkap dengan template unduhan masing-masing modul. |
| `/admin/rekap-kehadiran.php` | Admin & Operator | **Laporan Rekap Kehadiran Siswa (Matriks Resmi):** Tampilan tabel matriks kehadiran dengan judul mapel vertikal (*rotated headers*), baris tanggal sesi, kalkulasi otomatis persentase kehadiran `(Hadir + Dispensasi) / Total Mapel × 100%`, legenda kode status warna-warni, kelola daftar mapel terdaftar (*chips* dengan tombol hapus ❌), modal input/perbarui absensi mapel dengan tombol *Set Semua Hadir*, dan tombol Hapus Presensi Kelas Ini. |
| `/admin/export-rekap-excel.php` | Admin & Operator | **Ekspor Excel (.xls):** Menghasilkan spreadsheet resmi rekap kehadiran siswa per kelas berformat `.xls` dengan format matriks sel, pewarnaan status, dan persentase kehadiran. |
| `/admin/absensi.php` | Admin & Operator | **Arsip Daftar Hadir:** Bilah unduh cepat ZIP dengan dropdown *Per Tingkat* yang tidak terpotong (overflow visible), modal unduh ZIP *Per Kelas* & *Per Tanggal* yang tampil langsung di bagian atas (*top-aligned*), aksi ikon terstandarisasi (Unduh, Pratinjau Tab Baru 👁️, Detail 🔍, Hapus 🗑️), dan tombol Hapus Semua Arsip Daftar Hadir. |
| `/admin/berita-acara.php` | Admin & Operator | **Arsip Berita Acara:** Bilah unduh cepat ZIP *Per Tingkat* tanpa terpotong, modal unduh ZIP *Per Kelas* & *Per Tanggal* tampil di atas, pratinjau tab baru 👁️, galeri dokumentasi foto interaktif, dan tombol Hapus Semua Arsip Berita Acara. |
| `/admin/preview.php` | Admin & Operator | **Pratinjau Dokumen Tab Baru:** Viewer dokumen A4 mandiri dengan toolbar cetak, unduh PDF, info metadata sesi, dan panel drawer foto lampiran. |
| `/admin/rekonsiliasi.php` | Admin & Operator | **Audit & Rekonsiliasi Dokumen:** Pencocokan silang otomatis antara Arsip Daftar Hadir dan Berita Acara untuk melacak sesi yang belum dibuat atau terinput ganda, serta tombol Hapus Seluruh Dokumen Audit. |
| `/admin/template-ujian.php` | **Khusus Admin** | **Template Ujian & Editor WYSIWYG:** Konfigurasi template ujian, nama ujian, tahun ajaran, dan editor WYSIWYG berita acara dengan palet tag placeholder interaktif serta live simulasi pratinjau teks. |
| `/admin/pengaturan.php` | **Khusus Admin** | **Pengaturan Identitas & Kop Surat:** Unggah kop surat resmi, ganti logo, nama sekolah, alamat, kontak, dan URL Google Drive. |
| `/admin/users.php` | **Khusus Admin** | **Manajemen Pengguna & Hak Akses:** Tambah, edit, dan hapus pengguna dengan pembagian peran Admin vs Operator. Dilengkapi kolom **Password** pada tabel *Daftar Pengguna Terdaftar* dengan ikon mata (`👁️`) untuk toggle melihat password secara interaktif, tombol **Ekspor Excel (.xls)** seluruh akun pengguna, serta modal **Impor Excel (.xls)** untuk unggah data akun massal lengkap dengan template unduhan. |
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
10. **Modul Master Data Mapel & Integrasi Form Publik:**
    - Dibuatkan menu mandiri **Data Mapel** (`/admin/mapel.php`) di bawah bagian *DATA MASTER* sidebar untuk mengelola seluruh kurikulum ujian sekolah (Kode Mapel, Nama Mata Pelajaran, Kelompok Kurikulum: Umum, Kejuruan, Muatan Lokal).
    - Dilengkapi statistik (Total Mapel, Aktif, Umum, Kejuruan), toggle switch aktif/nonaktif akses cepat (ceklis), tombol **Ekspor Excel (.xls)**, modal **Impor Excel (.xls)**, unduh template, dan tombol konfirmasi Hapus Semua Data Mapel.
    - Terhubung secara langsung dan dinamis ke input Mata Pelajaran pada formulir publik **Daftar Hadir** (`/absensi.php`) dan **Berita Acara** (`/berita-acara.php`) melalui searchable datalist (`list="datalist_mapel"`). Pengguna dapat mengetik pencarian cepat atau memilih langsung dari daftar terstandarisasi, dengan sinkronisasi langsung ke live preview A4.
11. **Ekspor & Impor Excel (.xls) Menyeluruh (Siswa, Guru, User, & Mapel):**
    - **Data Siswa (`/admin/siswa.php`):** Ditambahkan tombol **Ekspor Excel (.xls)** di header toolbar yang mengekspor data siswa sesuai kelas yang sedang difilter atau seluruh kelas.
    - **Data Guru (`/admin/guru.php`):** Ditambahkan tombol **Ekspor Excel (.xls)** serta integrasi modal **Impor Excel (.xls)**.
    - **Manajemen User (`/admin/users.php`):** Ditambahkan tombol **Ekspor Excel (.xls)** untuk mencadangkan seluruh akun pengguna (Username, Nama Lengkap, Role, Password Plain, Tanggal Dibuat) serta modal **Impor Excel (.xls)** dengan template unduhan untuk penambahan dan pembaruan akun secara massal.
    - **Impor Terpusat (`/admin/import-siswa.php`):** Dilengkapi sistem tab 3-in-1 untuk mengunggah berkas Siswa, berkas Guru, dan berkas Mapel secara terpadu.
12. **Sistem Notifikasi & Konfirmasi Berupa Modal Popup Menyeluruh:**
    - **Notifikasi Popup (`#flashNotificationModal`):** Pesan flash notifikasi (sukses, peringatan, error/bahaya, info) dari sistem PHP kini tampil sebagai pop-up modal elegan di tengah atas layar dengan badge icon, judul, teks keterangan lengkap, tombol "OK, Mengerti", dan tombol silang tutup (serta auto-dismiss setelah beberapa detik).
    - **Konfirmasi Modal Dialog ("Lanjut / Tidak"):** Semua aksi konfirmasi (penghapusan data satu per satu di tabel siswa, kelas, guru, mapel, user, template ujian, rekap mapel; pengosongan/reset formulir publik; tombol logout admin) beralih dari alert/confirm browser ke dialog popup interaktif (`#modalGlobalConfirmDialog`) dengan dua opsi pilihan: **"Lanjut"** (merah) dan **"Tidak"** (abu-abu).
    - **Override Native Alert & Form Interceptor:** `window.alert()` dialihkan otomatis membuka popup modal pemberitahuan (`showNotificationModal`), dan seluruh form dengan `onsubmit="return confirm(...)"` diintersepsi secara otomatis pada tingkat DOM oleh `app.js`.
13. **Tata Letak Filter Horizontal Mendatar (Arsip Daftar Hadir & Arsip Berita Acara):**
    - Formulir filter pada menu **Arsip Daftar Hadir** (`/admin/absensi.php`) dan **Arsip Berita Acara** (`/admin/berita-acara.php`) kini berjejer mendatar ke samping (*horizontal row*):
      `[Tingkat Kelas] [Kelas] [Tanggal Ujian] [Cari Mapel / Pengawas] [🔍 Terapkan Filter]`
    - Menggunakan class flexbox `.filter-horizontal-form` dengan `align-items: flex-end`, sehingga kolom pilihan, input tanggal, kotak pencarian, dan tombol submit tersusun sejajar rapi dalam satu baris mendatar dan responsif melipat di perangkat seluler.
14. **Pratinjau & Cetak Laporan PDF (Preview PDF Cetak) di Tab Baru pada Rekap Kehadiran Siswa:**
    - Pada menu **Laporan Rekap Kehadiran Siswa** (`/admin/rekap-kehadiran.php`), ditambahkan tombol **"Preview PDF (Cetak)"** dengan ikon PDF merah di sebelah tombol *Ekspor Excel (.xls)*.
    - Membuka pratinjau dokumen di tab baru (`target="_blank"`) yang dihasilkan oleh backend generator PDF resmi (`/admin/export-rekap-pdf.php`) menggunakan Dompdf A4 Landscape:
      - Kop Surat resmi sekolah dan identitas dokumen.
      - Tabel metadata kelas, tahun ajaran, semester, dan tanggal cetak.
      - Matriks kehadiran seluruh siswa untuk setiap mata pelajaran dengan kode status (H, D, I, S, A).
      - Kalkulasi total kehadiran dan persentase kehadiran `(Hadir + Dispensasi) / Total Mapel × 100%`.
      - Baris total kehadiran siswa per mapel di bagian bawah tabel.
      - Legenda kode status dan 3 kolom blok tanda tangan resmi (Kepala Sekolah, Ketua Panitia Ujian SAS, dan Wali Kelas).
      - Disajikan via header `application/pdf; inline` siap cetak langsung atau disimpan.
15. **Navbar & Sidebar Fleksibel (Collapsible dengan Tombol Hamburger):**
    - Ditambahkan tombol toggle sidebar/navbar berbentuk rounded square dengan ikon 3 garis horizontal (*hamburger menu icon* `≡`) yang identik dengan desain yang diminta pengguna.
    - **Desktop Mode (> 900px):** Menekan tombol toggle menyembunyikan sidebar admin (width: 0), dan area konten utama (`.admin-main`) otomatis melebar penuh 100% (*isi dinamis mengikuti saat navbar disembunyikan atau ditampilkan*). Menekan tombol kembali mengembalikan posisi sidebar ke 240px. Status toggle tersimpan otomatis di `localStorage`.
    - **Mobile Mode (≤ 900px):** Sidebar secara default tersembunyi rapi dan muncul saat tombol toggle ditekan dengan animasi slide down, serta otomatis menutup bila mengklik di luar area menu.
    - **Halaman Publik:** Tombol toggle yang sama mengontrol visibilitas menu navigasi dropdown mobile.
16. **Perbaikan & Optimalisasi Impor Data Siswa Peserta Ujian:**
    - Menyediakan modal popup interaktif **"Impor Data Siswa (.xls)"** langsung pada halaman `/admin/siswa.php`, memungkinkan panitia mengunggah data siswa secara langsung tanpa perlu berpindah modul.
    - Dilengkapi tautan unduh **Template Resmi Siswa (.xls)** dengan format kolom presisi: *NIS*, *NISN*, *Nama Siswa*, dan *Kelas*.
    - **Smart Header & Column Auto-Detection:** Mengatasi masalah impor gagal dengan algoritma pencocokan cerdas yang membaca baris header secara otomatis. Mampu memproses file hasil ekspor sistem sebelumnya, format Dapodik sekolah, maupun template murni secara fleksibel.
    - Pendaftaran kelas otomatis jika nama kelas belum terdaftar di sistem (lengkap dengan deteksi tingkat X, XI, XII), serta kemampuan deteksi pembaruan data (*update existing*) siswa berdasarkan NIS maupun nama lengkap.
17. **Standardisasi Tampilan Kolom Status (Ceklis) Menyeluruh:**
    - Menyeragamkan seluruh tabel data master pada admin panel (**Kelola Data Siswa**, **Data Kelas**, **Data Guru & Pengawas**, dan **Data Mapel**) ke satu tampilan dan satu desain yang 100% konsisten.
    - Menggunakan header resmi `<th style="text-align: center; width: 140px;"><i class="bi bi-check2-square"></i> Status (Ceklis)</th>`.
    - Mengimplementasikan komponen pill terstandarisasi (`.ceklis-status-pill`):
      - **Status Aktif:** Pill rounded hijau mint (`#ecfdf5`), border hijau (`#a7f3d0`), rounded square checkbox hijau dengan ikon ceklis putih (`✓`), dan teks tebal "Aktif" bernuansa hijau tua (`#065f46`).
      - **Status Nonaktif:** Pill abu-abu lembut (`#f8fafc`), border abu-abu (`#e2e8f0`), rounded square putih, dan teks abu-abu "Nonaktif".
    - Setiap pill status berfungsi interaktif dengan toggle instan via AJAX, pembaruan antarmuka seketika (*optimistic UI*), dan notifikasi melayang (*quick toast notification*) di pojok kanan bawah tanpa memuat ulang halaman.
18. **Penyelarasan Navbar Header & Penggabungan Grup Data Master:**
    - **Penyelarasan Presisi Header & Sidebar:** Mengintegrasikan area brand sekolah (`.admin-brand-box` dengan lebar `240px`) di sudut kiri atas bilah navigasi agar sejajar vertikal lurus (*flush alignment*) dengan bilah sidebar menu di bawahnya. Tombol toggle titik tiga / garis tiga (`#btnToggleSidebar`) ditempatkan secara terstandarisasi di samping brand header pada bilah navbar atas, identik dengan tata letak dashboard web modern pada umumnya.
    - **Penggabungan Menu Data Master Terpadu:** Menu **Kelola Data Siswa** dan **Impor Data Excel** kini disatukan ke dalam satu grup utama **Data Master**:
      - 🏫 **Data Kelas**
      - 👨‍🎓 **Kelola Data Siswa**
      - 👨‍🏫 **Data Guru & Pengawas**
      - 📚 **Data Mapel**
      - 📥 **Impor Data Excel**
    - **Struktur Grup Menu Bersih & Efisien:**
      - **Dashboard** (Pintasan Utama)
      - **Data Master:** Data Kelas, Kelola Data Siswa, Data Guru & Pengawas, Data Mapel, Impor Data Excel
      - **Rekap dan Laporan:** Rekap Kehadiran, Arsip Daftar Hadir, Arsip Berita Acara, Rekonsiliasi Dokumen
      - **Pengaturan:** Template Ujian, Pengaturan Kop & Drive, Manajemen User
    - **Kehandalan Interaksi Accordion Tree Menu:**
      - Mengimplementasikan penanganan toggle ganda (*inline synchronous handler* di kepala HTML dan *delegated document click listener*) untuk menjamin klik pada grup menu langsung membuka/menutup daftar submenu tanpa kendala cache peramban.
      - Menambahkan parameter *cache-busting* dinamis (`?v=...`) pada pemanggilan berkas CSS dan JavaScript aplikasi.
      - Menetapkan aturan `display: flex !important;` pada submenu yang terbuka dengan animasi transisi yang mulus dan rotasi indikator panah.
      - Menu aktif secara otomatis terbuka dan disorot (*highlighted*) saat halaman dibuka.
19. **Integrasi Menu Impor Data Mapel & Akses Cepat Status Ceklis:**
    - **Akses Cepat Status (Ceklis) di Data Kelas, Kelola Data Siswa, & Data Mapel:** Seluruh toggle status (Aktif/Nonaktif) pada kolom *Status (Ceklis)* berfungsi secara instan menggunakan selector modern CSS `:has(input:checked)` dan `:not(:has(input:checked))` dipadukan dengan *optimistic UI state update*, background AJAX POST asynchronous, dan *quick toast notification* melayang tanpa refresh halaman. Tampilan pill ceklis dijamin 100% konsisten antar semua tabel master.
    - **Integrasi Langsung Impor Excel Data Mapel:** Tombol **"Impor Excel (.xls)"** pada menu **Data Mapel** (`/admin/mapel.php`) kini terhubung langsung ke menu **Impor Data Excel** pada tab aktif **Unggah Berkas Mapel (.xls)** (`/admin/import-siswa.php?type=mapel`). Dilengkapi juga dengan kartu navigasi dua arah *Kembali ke Data Mapel*, template spreadsheet unduhan `.xls`, dan mesin parsing multi-format (`.xls`, `.xlsx`, `.csv`).

---

## 🗄️ Arsitektur Basis Data
Aplikasi mendukung 2 mode basis data:
1. **SQLite (Bawaan & Siap Pakai):** Tersimpan di `database/sas.sqlite`. Sudah terisi penuh (*pre-seeded*) dengan:
   * 11 Kelas aktif (XII AK, XII BD 1, XII BD 2, XII DKV, XII LPB, XII LPKKK 1, XII LPKKK 2, XII MP 1, XII MP 2, XII TKJ, XII TSM).
   * 258 Siswa lengkap dengan NIS dan nama asli.
   * Data Guru lengkap dengan status ceklis aktif.
   * 31 Mata Pelajaran kurikulum pre-seeded di tabel `mapel` (Umum, Kejuruan, Muatan Lokal).
   * 100 Arsip Daftar Hadir & 94 Berita Acara.
   * 138 Foto dokumentasi pelaksanaan asli di `storage/uploads/dokumentasi/`.
   * Template Ujian SAS Ganjil 2026/2027.
2. **MySQL / MariaDB:** File dump skema dan data lengkap tersedia di:
   ```
   database/database_mysql.sql
   ```
   Untuk beralih ke MySQL, cukup sesuaikan konstanta di `config/database.php`.

