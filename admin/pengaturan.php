<?php
$pageTitle = 'Pengaturan Identitas & Kop Surat';
require_once __DIR__ . '/layout_header.php';
require_admin_role();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $kopMode = trim($_POST['kop_mode'] ?? 'image');
    $namaSekolah = trim($_POST['nama_sekolah'] ?? 'SMK NEGERI 1 KLUNGKUNG');
    $alamatSekolah = trim($_POST['alamat_sekolah'] ?? '');
    $kopBaris1 = trim($_POST['kop_baris_1'] ?? '');
    $kopBaris2 = trim($_POST['kop_baris_2'] ?? '');
    $kopBaris3 = trim($_POST['kop_baris_3'] ?? '');
    $kopTelepon = trim($_POST['kop_telepon'] ?? '');
    $kopWebsite = trim($_POST['kop_website'] ?? '');
    $driveUrl = trim($_POST['google_drive_folder_url'] ?? '');

    save_setting('kop_mode', $kopMode);
    save_setting('nama_sekolah', $namaSekolah);
    save_setting('alamat_sekolah', $alamatSekolah);
    save_setting('kop_baris_1', $kopBaris1);
    save_setting('kop_baris_2', $kopBaris2);
    save_setting('kop_baris_3', $kopBaris3);
    save_setting('kop_telepon', $kopTelepon);
    save_setting('kop_website', $kopWebsite);
    save_setting('google_drive_folder_url', $driveUrl);

    // Handle Kop Image upload
    if (!empty($_FILES['kop_image_file']['tmp_name'])) {
        $tmp = $_FILES['kop_image_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['kop_image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
            $dest = BASE_PATH . '/assets/img/kop-header.png';
            @mkdir(dirname($dest), 0777, true);
            move_uploaded_file($tmp, $dest);
            save_setting('kop_image_path', 'assets/img/kop-header.png');
        }
    }

    // Handle Logo upload
    if (!empty($_FILES['logo_file']['tmp_name'])) {
        $tmp = $_FILES['logo_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
            $logoName = 'logo_' . time() . '.' . $ext;
            $dest = BASE_PATH . '/storage/uploads/logo/' . $logoName;
            @mkdir(dirname($dest), 0777, true);
            if (move_uploaded_file($tmp, $dest)) {
                save_setting('logo_path', 'storage/uploads/logo/' . $logoName);
            }
        }
    }

    log_activity('UPDATE_SETTINGS', "Memperbarui pengaturan identitas sekolah, kop surat, dan Google Drive.");
    set_flash('success', 'Semua pengaturan berhasil disimpan.');
    header('Location: /admin/pengaturan.php');
    exit;
}

$kopMode = get_setting('kop_mode', 'image');
$namaSekolah = get_setting('nama_sekolah', 'SMK NEGERI 1 KLUNGKUNG');
$alamatSekolah = get_setting('alamat_sekolah', 'Jl. Merpati No. 1, Semarapura Tengah, Kec. Klungkung, Kab. Klungkung, Bali 80711');
$kopBaris1 = get_setting('kop_baris_1', 'PEMERINTAH PROVINSI BALI');
$kopBaris2 = get_setting('kop_baris_2', 'DINAS PENDIDIKAN KEPEMUDAAN DAN OLAHRAGA');
$kopBaris3 = get_setting('kop_baris_3', 'SMK NEGERI 1 KLUNGKUNG');
$kopTelepon = get_setting('kop_telepon', 'Telp. (0366) 21152 | Email: smkn1klungkung@yahoo.com');
$kopWebsite = get_setting('kop_website', 'Website: smkn1klungkung.sch.id');
$driveUrl = get_setting('google_drive_folder_url', 'https://drive.google.com/drive/folders/1wExampleDriveFolderSMKN1Klungkung');
$logoPath = get_setting('logo_path', 'storage/uploads/logo/logo_default.png');
$kopPath = get_setting('kop_image_path', 'assets/img/kop-header.png');
?>

<div style="margin-bottom: 0.75rem;">
    <h2>Pengaturan Identitas Sekolah & Kop Dokumen Resmi</h2>
    <p style="color: var(--text-muted); font-size: 0.9rem;">
        Kelola gambar kop surat resmi A4, identitas instansi, dan tautan folder Google Drive pengawas.
    </p>
</div>

<form method="POST" action="/admin/pengaturan.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="split-layout" style="height: auto;">
        <!-- Pengaturan Kop Gambar Utama & Identitas -->
        <div style="flex: 1;">
            <!-- 1. KOP SURAT GAMBAR RESMI (UTAMA) -->
            <div class="card" style="border-top: 4px solid var(--primary); margin-bottom: 0.75rem;">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="card-title">📜 Gambar Kop Surat Sekolah Lengkap (Rekomendasi Utama)</span>
                    <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">Full Header Banner</span>
                </div>
                <div class="card-body">
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                        Sesuai standar dokumen resmi, kop surat diunggah dalam bentuk <strong>gambar banner lengkap</strong> yang memuat logo Pemprov Bali di kiri, lambang sekolah di kanan, aksara Bali, nama dinas/sekolah, alamat, dan garis pembatas ganda.
                    </p>

                    <div class="form-group">
                        <label class="form-label">Mode Kop Dokumen:</label>
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-size: 0.9rem;">
                                <input type="radio" name="kop_mode" value="image" <?= $kopMode === 'image' ? 'checked' : '' ?>>
                                <strong>Gunakan Gambar Kop Lengkap</strong> (Rekomendasi)
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-size: 0.9rem;">
                                <input type="radio" name="kop_mode" value="text" <?= $kopMode === 'text' ? 'checked' : '' ?>>
                                Gunakan Susunan Teks & Logo Terpisah
                            </label>
                        </div>
                    </div>

                    <!-- Pratinjau Gambar Kop Saat Ini -->
                    <div style="margin: 1.25rem 0; padding: 12px; background: #f8fafc; border: 1px dashed var(--border); border-radius: 8px;">
                        <div style="font-size: 0.8rem; font-weight: 600; color: var(--slate-700); margin-bottom: 8px;">
                            Pratinjau Gambar Kop Aktif:
                        </div>
                        <div style="background: #ffffff; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; text-align: center;">
                            <img src="/<?= htmlspecialchars($kopPath) ?>?v=<?= time() ?>" alt="Kop Surat Lengkap" style="max-width: 100%; max-height: 120px; object-fit: contain; display: block; margin: 0 auto;">
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px; word-break: break-all;">
                            Lokasi berkas: <code><?= htmlspecialchars($kopPath) ?></code>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="kop_image_file">Unggah Gambar Kop Lengkap Baru</label>
                        <input type="file" name="kop_image_file" id="kop_image_file" class="form-control" accept="image/png,image/jpeg,image/webp">
                        <div class="form-hint">
                            Format: PNG, JPG, JPEG, WebP. Maksimal 4 MB. Disarankan gambar memanjang horizontal dengan resolusi tajam (misal 1200 x 250 px).
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. IDENTITAS SEKOLAH & TEKS KOP CADANGAN -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">🏫 Identitas Sekolah & Cadangan Format Teks</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="nama_sekolah">Nama Sekolah <span class="required">*</span></label>
                        <input type="text" name="nama_sekolah" id="nama_sekolah" class="form-control" 
                               value="<?= htmlspecialchars($namaSekolah) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="alamat_sekolah">Alamat Lengkap</label>
                        <textarea name="alamat_sekolah" id="alamat_sekolah" class="form-control" rows="2"><?= htmlspecialchars($alamatSekolah) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="kop_baris_1">Kop Baris 1 (Pemerintah)</label>
                        <input type="text" name="kop_baris_1" id="kop_baris_1" class="form-control" 
                               value="<?= htmlspecialchars($kopBaris1) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="kop_baris_2">Kop Baris 2 (Dinas)</label>
                        <input type="text" name="kop_baris_2" id="kop_baris_2" class="form-control" 
                               value="<?= htmlspecialchars($kopBaris2) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="kop_baris_3">Kop Baris 3 (Nama Instansi Sekolah)</label>
                        <input type="text" name="kop_baris_3" id="kop_baris_3" class="form-control" 
                               value="<?= htmlspecialchars($kopBaris3) ?>">
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label" for="kop_telepon">Kontak / Telepon / Email</label>
                            <input type="text" name="kop_telepon" id="kop_telepon" class="form-control" 
                                   value="<?= htmlspecialchars($kopTelepon) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="kop_website">Website Sekolah</label>
                            <input type="text" name="kop_website" id="kop_website" class="form-control" 
                                   value="<?= htmlspecialchars($kopWebsite) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pengaturan Samping: Logo & Google Drive -->
        <div style="flex: 0 0 440px;">
            <!-- Link Google Drive -->
            <div class="card" style="margin-bottom: 0.75rem;">
                <div class="card-header">
                    <span class="card-title">📁 Folder Google Drive Pengawas</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="google_drive_folder_url">
                            URL Folder Google Drive <span class="required">*</span>
                        </label>
                        <input type="url" name="google_drive_folder_url" id="google_drive_folder_url" class="form-control" 
                               value="<?= htmlspecialchars($driveUrl) ?>" placeholder="https://drive.google.com/drive/folders/..." required>
                        <div class="form-hint">
                            Hanya menerima domain <code>drive.google.com</code> dengan protokol HTTPS.
                        </div>
                    </div>

                    <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="testDriveLink()">
                            🔍 Uji / Buka Link di Tab Baru
                        </button>
                    </div>

                    <div style="background: var(--info-light); color: var(--slate-800); border: 1px solid #bae6fd; border-radius: var(--radius-sm); padding: 0.75rem; font-size: 0.8rem; margin-top: 1rem;">
                        💡 <strong>Catatan Pengawas:</strong> Tombol pada formulir publik akan membuka folder Google Drive ini agar pengawas dapat mengunggah berkas PDF yang telah selesai diunduh.
                    </div>
                </div>
            </div>

            <!-- Logo Sekolah (Untuk Fallback) -->
            <div class="card" style="margin-bottom: 0.75rem;">
                <div class="card-header">
                    <span class="card-title">🖼️ Logo Instansi (Cadangan Mode Teks)</span>
                </div>
                <div class="card-body">
                    <div style="display: flex; align-items: center; gap: 1.25rem; margin-bottom: 0.7rem;">
                        <div style="width: 75px; height: 75px; border: 1px solid var(--border); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; background: #fff; padding: 4px;">
                            <img src="/<?= htmlspecialchars($logoPath) ?>?v=<?= time() ?>" alt="Logo Saat Ini" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        </div>
                        <div>
                            <div style="font-size: 0.85rem; font-weight: 600;">Logo Tunggal Aktif</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); word-break: break-all;">
                                <?= htmlspecialchars($logoPath) ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="logo_file">Unggah Logo Tunggal Baru (Opsional)</label>
                        <input type="file" name="logo_file" id="logo_file" class="form-control" accept="image/png,image/jpeg,image/webp">
                        <div class="form-hint">Format: PNG, JPG, WebP. Maksimal 2 MB. Digunakan bila mode teks diaktifkan.</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 0.85rem; font-size: 1rem;">
                💾 Simpan Semua Pengaturan
            </button>
        </div>
    </div>
</form>

<script>
function testDriveLink() {
    const input = document.getElementById('google_drive_folder_url');
    const val = input.value.trim();
    if (!val) {
        alert('Silakan masukkan link Google Drive terlebih dahulu.');
        return;
    }
    window.open(val, '_blank', 'noopener,noreferrer');
}
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
