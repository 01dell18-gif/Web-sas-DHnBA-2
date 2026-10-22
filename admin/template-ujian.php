<?php
$pageTitle = 'Template Ujian & Redaksi';
require_once __DIR__ . '/layout_header.php';
require_admin_role();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $namaUjian = trim($_POST['nama_ujian'] ?? '');
        $tahunAjaran = trim($_POST['tahun_ajaran'] ?? '');
        $judulBA = trim($_POST['judul_berita_acara'] ?? '');
        $redaksiIsi = trim($_POST['redaksi_isi'] ?? '');
        $aktif = isset($_POST['aktif']) ? 1 : 0;

        if ($namaUjian === '' || $tahunAjaran === '') {
            set_flash('danger', 'Nama ujian dan tahun ajaran wajib diisi.');
        } else {
            if ($id > 0) {
                dbUpdate('template_ujian', [
                    'nama_ujian' => $namaUjian,
                    'tahun_ajaran' => $tahunAjaran,
                    'judul_berita_acara' => $judulBA,
                    'redaksi_isi' => $redaksiIsi,
                    'aktif' => $aktif,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $id]);
                log_activity('UPDATE_TEMPLATE', "Memperbarui template ujian {$namaUjian} (ID #{$id})");
                set_flash('success', "Template ujian {$namaUjian} berhasil diperbarui.");
            } else {
                $newId = dbInsert('template_ujian', [
                    'nama_ujian' => $namaUjian,
                    'tahun_ajaran' => $tahunAjaran,
                    'judul_berita_acara' => $judulBA,
                    'redaksi_isi' => $redaksiIsi,
                    'aktif' => $aktif,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                log_activity('CREATE_TEMPLATE', "Menambahkan template ujian {$namaUjian} (ID #{$newId})");
                set_flash('success', "Template ujian {$namaUjian} berhasil ditambahkan.");
            }
        }
        header('Location: /admin/template-ujian.php');
        exit;
    }

    if ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        dbUpdate('template_ujian', ['aktif' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
        set_flash('success', 'Status template ujian berhasil diperbarui.');
        header('Location: /admin/template-ujian.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $tInfo = dbFetch("SELECT nama_ujian FROM template_ujian WHERE id = :id", ['id' => $id]);
        if ($tInfo) {
            dbDelete('template_ujian', 'id = :id', ['id' => $id]);
            log_activity('DELETE_TEMPLATE', "Menghapus template ujian {$tInfo['nama_ujian']} (ID #{$id})");
            set_flash('success', "Template ujian {$tInfo['nama_ujian']} berhasil dihapus.");
        }
        header('Location: /admin/template-ujian.php');
        exit;
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editTpl = null;
if ($editId > 0) {
    $editTpl = dbFetch("SELECT * FROM template_ujian WHERE id = :id", ['id' => $editId]);
}

$templates = dbFetchAll("SELECT * FROM template_ujian ORDER BY id DESC");
?>

<div style="margin-bottom: 0.75rem;">
    <h2>Kelola Template Ujian & Editor Redaksi Berita Acara</h2>
    <p style="color: var(--text-muted); font-size: 0.9rem;">
        Konfigurasi nama ujian, tahun ajaran, dan teks redaksi berita acara secara fleksibel dengan placeholder dinamis.
    </p>
</div>

<div class="split-layout" style="height: auto; align-items: flex-start; gap: 0.75rem;">
    <!-- KOLOM KIRI: DAFTAR TEMPLATE UJIAN -->
    <div style="flex: 0 0 380px; min-width: 320px;">
        <div class="card" style="position: sticky; top: 1rem;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span class="card-title">📋 Daftar Template (<?= count($templates) ?>)</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive" style="max-height: 650px; overflow-y: auto;">
                    <table class="table" style="font-size: 0.85rem; margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th>Nama Template</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $t): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;">
                                            <?= htmlspecialchars($t['nama_ujian']) ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                            TP: <strong><?= htmlspecialchars($t['tahun_ajaran']) ?></strong>
                                        </div>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <form method="POST" action="/admin/template-ujian.php" style="display: inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="status" value="<?= $t['aktif'] ? 0 : 1 ?>">
                                            <button type="submit" class="badge <?= $t['aktif'] ? 'badge-success' : 'badge-danger' ?>" 
                                                    style="border: none; cursor: pointer; padding: 4px 8px;" title="Klik untuk ubah status aktif">
                                                <?= $t['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td style="text-align: right; vertical-align: middle; white-space: nowrap;">
                                        <a href="/admin/template-ujian.php?edit=<?= $t['id'] ?>" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 0.775rem;">
                                            ✏️ Edit
                                        </a>
                                        <form method="POST" action="/admin/template-ujian.php" style="display: inline;" 
                                              onsubmit="return confirm('Hapus template ujian ini?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 0.775rem;">
                                                🗑️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN: FORM & EDITOR TEMPLATE UJIAN -->
    <div style="flex: 1; min-width: 0;">
        <div class="card" style="border-top: 4px solid var(--primary);">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <span class="card-title" style="font-size: 1.1rem;">
                        <?= $editTpl ? '✏️ Edit Template Ujian' : '➕ Tambah Template Ujian Baru' ?>
                    </span>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                        <?= $editTpl ? 'Perbarui format redaksi berita acara' : 'Buat format redaksi baru untuk berita acara ujian' ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/template-ujian.php" id="formTemplateEditor">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <?php if ($editTpl): ?>
                        <input type="hidden" name="id" value="<?= $editTpl['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="grid-2" style="margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label" for="nama_ujian">Nama Ujian <span class="required">*</span></label>
                            <input type="text" name="nama_ujian" id="nama_ujian" class="form-control" 
                                   value="<?= htmlspecialchars($editTpl['nama_ujian'] ?? '') ?>" placeholder="Misal: Sumatif Akhir Semester (SAS) Ganjil" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="tahun_ajaran">Tahun Ajaran <span class="required">*</span></label>
                            <input type="text" name="tahun_ajaran" id="tahun_ajaran" class="form-control" 
                                   value="<?= htmlspecialchars($editTpl['tahun_ajaran'] ?? '2026/2027') ?>" placeholder="Misal: 2026/2027" required>
                        </div>
                    </div>

                    <!-- 2. PALET PLACEHOLDER UTAMA (Bisa diklik untuk menyisipkan ke textarea/editor aktif) -->
                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.85rem; margin-bottom: 0.7rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span style="font-size: 0.85rem; font-weight: 700; color: var(--slate-800);">
                                💡 Klik Tag Placeholder untuk Menyisipkan ke Editor Aktif:
                            </span>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">
                                Otomatis disubstitusi saat dokumen dicetak
                            </span>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            <button type="button" class="btn-tag" onclick="insertPlaceholder('[nama_ujian]')">🏷️ [nama_ujian]</button>
                            <button type="button" class="btn-tag" onclick="insertPlaceholder('[tahun_ajaran]')">📅 [tahun_ajaran]</button>
                            <button type="button" class="btn-tag" onclick="insertPlaceholder('[hari]')">📆 [hari]</button>
                            <button type="button" class="btn-tag" onclick="insertPlaceholder('[tanggal]')">🔢 [tanggal]</button>
                            <button type="button" class="btn-tag btn-tag-purple" onclick="insertPlaceholder('[tanggal_teks]')">✍️ [tanggal_teks]</button>
                            <button type="button" class="btn-tag" onclick="insertPlaceholder('[bulan]')">🌙 [bulan]</button>
                            <button type="button" class="btn-tag" onclick="insertPlaceholder('[tahun]')">📆 [tahun]</button>
                            <button type="button" class="btn-tag btn-tag-purple" onclick="insertPlaceholder('[tahun_teks]')">✍️ [tahun_teks]</button>
                            <button type="button" class="btn-tag" onclick="insertPlaceholder('[jam_mulai]')">⏰ [jam_mulai]</button>
                            <button type="button" class="btn-tag" onclick="insertPlaceholder('[jam_selesai]')">⏰ [jam_selesai]</button>
                            <button type="button" class="btn-tag" onclick="insertPlaceholder('[mata_pelajaran]')">📚 [mata_pelajaran]</button>
                            <button type="button" class="btn-tag" onclick="insertPlaceholder('[kelas]')">🏫 [kelas]</button>
                            <button type="button" class="btn-tag" onclick="insertPlaceholder('[ruang]')">🚪 [ruang]</button>
                            <button type="button" class="btn-tag" onclick="insertPlaceholder('[sekolah]')">🏫 [sekolah]</button>
                            <button type="button" class="btn-tag btn-tag-green" onclick="insertPlaceholder('[peserta_seharusnya]')">👥 [peserta_seharusnya]</button>
                            <button type="button" class="btn-tag btn-tag-green" onclick="insertPlaceholder('[peserta_hadir]')">✅ [peserta_hadir]</button>
                            <button type="button" class="btn-tag btn-tag-green" onclick="insertPlaceholder('[peserta_tidak_hadir]')">❌ [peserta_tidak_hadir]</button>
                            <button type="button" class="btn-tag btn-tag-orange" onclick="insertPlaceholder('[blangko_berita_acara]')">📄 [blangko_berita_acara]</button>
                            <button type="button" class="btn-tag btn-tag-orange" onclick="insertPlaceholder('[blangko_daftar_hadir]')">📋 [blangko_daftar_hadir]</button>
                            <button type="button" class="btn-tag btn-tag-purple" onclick="insertPlaceholder('[catatan_pelaksanaan]')">📝 [catatan_pelaksanaan]</button>
                        </div>
                    </div>

                    <!-- 3. Judul Dokumen Berita Acara -->
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label class="form-label" for="judul_berita_acara" style="margin-bottom: 0;">
                                Judul Dokumen Berita Acara
                            </label>
                            <div class="editor-mini-tools">
                                <button type="button" class="btn-mini" onclick="insertPlaceholderInto('judul_berita_acara', '[nama_ujian]')">+ [nama_ujian]</button>
                                <button type="button" class="btn-mini" onclick="insertPlaceholderInto('judul_berita_acara', '[tahun_ajaran]')">+ [tahun_ajaran]</button>
                            </div>
                        </div>
                        <textarea name="judul_berita_acara" id="judul_berita_acara" class="form-control tracked-input" rows="3" 
                                  style="font-family: inherit; font-size: 0.95rem; font-weight: 600; line-height: 1.4;" 
                                  placeholder="Judul pada lembar berita acara (tekan Enter untuk memecah menjadi beberapa baris)"><?= htmlspecialchars($editTpl['judul_berita_acara'] ?? "BERITA ACARA \r\nPENYELENGGARAAN SUMATIF AKHIR SEMESTER (SAS) GANJIL\r\nTAHUN AJARAN [tahun_ajaran]") ?></textarea>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                            💡 Tekan <strong>Enter</strong> untuk membuat judul dalam beberapa baris (misal: baris 1 nama berita acara, baris 2 subjudul/tahun).
                        </div>
                    </div>

                    <!-- 4. REDAKSI BERITA ACARA UTUH (WYSIWYG / RICH TEXT EDITOR) -->
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                            <div>
                                <label class="form-label" style="margin-bottom: 0; font-size: 0.95rem; font-weight: 700; color: var(--slate-800);">
                                    📝 Redaksi Berita Acara (Editor Teks / WYSIWYG)
                                </label>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    Format teks secara bebas dan rapi. Tampilan di editor ini akan sama persis ketika diunduh sebagai PDF.
                                </div>
                            </div>
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="resetWysiwygToDefault()" style="font-size: 0.775rem;">
                                    🔄 Standar Resmi
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" id="btnToggleSource" onclick="toggleHtmlSourceMode()" style="font-size: 0.775rem;">
                                    💻 Mode HTML
                                </button>
                            </div>
                        </div>

                        <!-- WYSIWYG Editor Container -->
                        <div class="wysiwyg-box" id="wysiwygBox">
                            <!-- Toolbar Format Lengkap -->
                            <div class="wysiwyg-toolbar" id="wysiwygToolbar">
                                <div class="wysiwyg-toolgroup">
                                    <button type="button" class="wysiwyg-btn" data-command="bold" title="Tebal (Ctrl+B)"><b>B</b></button>
                                    <button type="button" class="wysiwyg-btn" data-command="italic" title="Miring (Ctrl+I)"><i>I</i></button>
                                    <button type="button" class="wysiwyg-btn" data-command="underline" title="Garis Bawah (Ctrl+U)"><u>U</u></button>
                                    <button type="button" class="wysiwyg-btn" data-command="strikeThrough" title="Coret"><s>S</s></button>
                                </div>
                                <div class="wysiwyg-divider"></div>
                                <div class="wysiwyg-toolgroup">
                                    <button type="button" class="wysiwyg-btn" data-command="justifyLeft" title="Rata Kiri">⫷</button>
                                    <button type="button" class="wysiwyg-btn" data-command="justifyCenter" title="Rata Tengah">☰</button>
                                    <button type="button" class="wysiwyg-btn" data-command="justifyRight" title="Rata Kanan">⫸</button>
                                    <button type="button" class="wysiwyg-btn" data-command="justifyFull" title="Rata Kiri-Kanan (Justify)">𝄘</button>
                                </div>
                                <div class="wysiwyg-divider"></div>
                                <div class="wysiwyg-toolgroup">
                                    <button type="button" class="wysiwyg-btn" data-command="insertOrderedList" title="Penomoran Angka (1, 2, 3)">1.</button>
                                    <button type="button" class="wysiwyg-btn" data-command="insertUnorderedList" title="Daftar Bullet (•)">•</button>
                                    <button type="button" class="wysiwyg-btn" data-command="indent" title="Geser ke Dalam (Indent)">⇥</button>
                                    <button type="button" class="wysiwyg-btn" data-command="outdent" title="Geser ke Luar (Outdent)">⇤</button>
                                </div>
                                <div class="wysiwyg-divider"></div>
                                <div class="wysiwyg-toolgroup">
                                    <button type="button" class="wysiwyg-btn" data-command="removeFormat" title="Hapus Format Pemformatan">🧹</button>
                                </div>
                            </div>

                            <?php
                            $defaultRedaksiContent = '<p>Pada hari ini <strong>[hari]</strong>, tanggal <strong>[tanggal]</strong> (<strong>[tanggal_teks]</strong>) bulan <strong>[bulan]</strong> tahun <strong>[tahun]</strong> (<strong>[tahun_teks]</strong>):</p>' . "\n" .
'<ol style="margin: 6px 0 12px 24px; padding: 0;">' . "\n" .
'    <li style="margin-bottom: 8px;">Telah diselenggarakan <strong>[nama_ujian]</strong> Tahun Ajaran <strong>[tahun_ajaran]</strong> dari pukul <strong>[jam_mulai]</strong> sampai dengan pukul <strong>[jam_selesai]</strong> WITA.' . "\n" .
'        <ul style="list-style-type: disc; margin: 4px 0 6px 20px; padding: 0;">' . "\n" .
'            <li>Pada Sekolah: <strong>[sekolah]</strong></li>' . "\n" .
'            <li>Kelas: <strong>[kelas]</strong></li>' . "\n" .
'            <li>Mata Pelajaran: <strong>[mata_pelajaran]</strong></li>' . "\n" .
'            <li>Ruang: <strong>[ruang]</strong></li>' . "\n" .
'            <li>Jumlah Peserta Seharusnya: <strong>[peserta_seharusnya]</strong> orang</li>' . "\n" .
'            <li>Jumlah Peserta yang Tidak Hadir: <strong>[peserta_tidak_hadir]</strong> orang</li>' . "\n" .
'            <li>Jumlah Peserta yang Hadir: <strong>[peserta_hadir]</strong> orang</li>' . "\n" .
'        </ul>' . "\n" .
'    </li>' . "\n" .
'    <li style="margin-bottom: 8px;">Telah dibagikan tautan soal ujian online untuk Mata Pelajaran <strong>[mata_pelajaran]</strong> di ruang <strong>[ruang]</strong> dengan disaksikan oleh para peserta, serta telah dibagikan lembar blangko berita acara sebanyak <strong>[blangko_berita_acara]</strong> eksemplar dan blangko daftar hadir sebanyak <strong>[blangko_daftar_hadir]</strong> eksemplar.</li>' . "\n" .
'    <li style="margin-bottom: 8px;">Catatan selama pelaksanaan ujian (kendala teknis, kejadian khusus, atau keterangan lain):<br>' . "\n" .
'        <em>[catatan_pelaksanaan]</em>' . "\n" .
'    </li>' . "\n" .
'</ol>' . "\n" .
'<p style="margin-top: 15px;">Berita Acara ini dibuat dengan sesungguhnya.</p>';

                            $initRedaksiContent = !empty($editTpl['redaksi_isi']) ? $editTpl['redaksi_isi'] : $defaultRedaksiContent;
                            ?>

                            <!-- Contenteditable Paper Canvas -->
                            <div id="wysiwygCanvas" class="wysiwyg-content tracked-input" contenteditable="true" spellcheck="false"><?= $initRedaksiContent ?></div>
                            <textarea id="wysiwygSource" class="wysiwyg-source tracked-input" style="display: none; width: 100%; min-height: 320px; font-family: monospace; font-size: 0.85rem; padding: 12px; border: none; outline: none; box-sizing: border-box;"></textarea>
                            <input type="hidden" name="redaksi_isi" id="redaksi_isi" value="<?= htmlspecialchars($initRedaksiContent) ?>">
                        </div>
                    </div>

                    <!-- 5. Live Pratinjau Teks Redaksi Berita Acara -->
                    <div style="background: #ffffff; border: 1px solid var(--border); border-left: 4px solid #0284c7; padding: 1.25rem; border-radius: var(--radius-sm); margin: 1.5rem 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <span style="font-size: 0.875rem; font-weight: 700; color: #0369a1;">
                                👁️ Live Simulasi Pratinjau Teks Berita Acara (Sama Persis dengan Hasil Unduhan PDF):
                            </span>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">(Placeholder disimulasikan otomatis)</span>
                        </div>
                        <div style="font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.45; color: #000; background: #fff; padding: 22px 28px; border-radius: 4px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.06); text-align: justify;">
                            <div id="pvLiveJudul" style="text-align: center; font-weight: bold; margin-bottom: 14px; text-transform: uppercase; font-size: 12pt; line-height: 1.35;">
                                BERITA ACARA PENYELENGGARAAN UJIAN
                            </div>
                            <div id="pvLiveKonten"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                            <input type="checkbox" name="aktif" value="1" <?= ($editTpl['aktif'] ?? 1) == 1 ? 'checked' : '' ?>>
                            <strong>Template Aktif</strong> (Muncul di dropdown pilihan pengawas di formulir publik)
                        </label>
                    </div>

                    <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 2; padding: 0.85rem; font-size: 1rem;">
                            <?= $editTpl ? '💾 Simpan Perubahan Template' : '💾 Tambahkan Template Baru' ?>
                        </button>
                        <?php if ($editTpl): ?>
                            <a href="/admin/template-ujian.php" class="btn btn-secondary" style="padding: 0.85rem;">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Tombol Tag Placeholder Interaktif */
.btn-tag {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 4px 8px;
    font-size: 0.75rem;
    font-weight: 600;
    font-family: monospace;
    background: #e2e8f0;
    color: #1e293b;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
}
.btn-tag:hover {
    background: #0f4c81;
    color: #ffffff;
    border-color: #0f4c81;
    transform: translateY(-1px);
}
.btn-tag-green {
    background: #dcfce7;
    color: #15803d;
    border-color: #bbf7d0;
}
.btn-tag-green:hover {
    background: #15803d;
    color: #ffffff;
}
.btn-tag-orange {
    background: #fef3c7;
    color: #b45309;
    border-color: #fde68a;
}
.btn-tag-orange:hover {
    background: #b45309;
    color: #ffffff;
}
.btn-tag-purple {
    background: #f3e8ff;
    color: #7e22ce;
    border-color: #e9d5ff;
}
.btn-tag-purple:hover {
    background: #7e22ce;
    color: #ffffff;
}
.editor-mini-tools {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}
.btn-mini {
    padding: 2px 6px;
    font-size: 0.7rem;
    font-family: monospace;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    cursor: pointer;
}
.btn-mini:hover {
    background: #0f4c81;
    color: #fff;
    border-color: #0f4c81;
}

/* Styling Kotak & Toolbar WYSIWYG */
.wysiwyg-box {
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    overflow: hidden;
}
.wysiwyg-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    padding: 8px 10px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.wysiwyg-toolgroup {
    display: inline-flex;
    align-items: center;
    gap: 2px;
}
.wysiwyg-divider {
    width: 1px;
    height: 20px;
    background: #cbd5e1;
    margin: 0 4px;
}
.wysiwyg-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 30px;
    height: 30px;
    padding: 0 6px;
    font-size: 0.85rem;
    font-family: inherit;
    background: #ffffff;
    color: #334155;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
}
.wysiwyg-btn:hover {
    background: #0f4c81;
    color: #ffffff;
    border-color: #0f4c81;
}
.wysiwyg-btn:active {
    transform: scale(0.96);
}
.wysiwyg-content {
    min-height: 280px;
    max-height: 520px;
    overflow-y: auto;
    padding: 16px 20px;
    font-family: 'Times New Roman', Times, serif;
    font-size: 11pt;
    line-height: 1.45;
    color: #000000;
    text-align: justify;
    outline: none;
    background: #ffffff;
}
.wysiwyg-content p {
    margin: 0 0 8px 0;
}
.wysiwyg-content ol, .wysiwyg-content ul {
    margin: 4px 0 10px 24px;
    padding: 0;
}
.wysiwyg-content li {
    margin-bottom: 4px;
}
</style>

<script>
// Template Redaksi Standar Resmi (Default Fallback)
const DEFAULT_REDAKSI_HTML = `<p>Pada hari ini <strong>[hari]</strong>, tanggal <strong>[tanggal]</strong> (<strong>[tanggal_teks]</strong>) bulan <strong>[bulan]</strong> tahun <strong>[tahun]</strong> (<strong>[tahun_teks]</strong>):</p>
<ol style="margin: 6px 0 12px 24px; padding: 0;">
    <li style="margin-bottom: 8px;">Telah diselenggarakan <strong>[nama_ujian]</strong> Tahun Ajaran <strong>[tahun_ajaran]</strong> dari pukul <strong>[jam_mulai]</strong> sampai dengan pukul <strong>[jam_selesai]</strong> WITA.
        <ul style="list-style-type: disc; margin: 4px 0 6px 20px; padding: 0;">
            <li>Pada Sekolah: <strong>[sekolah]</strong></li>
            <li>Kelas: <strong>[kelas]</strong></li>
            <li>Mata Pelajaran: <strong>[mata_pelajaran]</strong></li>
            <li>Ruang: <strong>[ruang]</strong></li>
            <li>Jumlah Peserta Seharusnya: <strong>[peserta_seharusnya]</strong> orang</li>
            <li>Jumlah Peserta yang Tidak Hadir: <strong>[peserta_tidak_hadir]</strong> orang</li>
            <li>Jumlah Peserta yang Hadir: <strong>[peserta_hadir]</strong> orang</li>
        </ul>
    </li>
    <li style="margin-bottom: 8px;">Telah dibagikan tautan soal ujian online untuk Mata Pelajaran <strong>[mata_pelajaran]</strong> di ruang <strong>[ruang]</strong> dengan disaksikan oleh para peserta, serta telah dibagikan lembar blangko berita acara sebanyak <strong>[blangko_berita_acara]</strong> eksemplar dan blangko daftar hadir sebanyak <strong>[blangko_daftar_hadir]</strong> eksemplar.</li>
    <li style="margin-bottom: 8px;">Catatan selama pelaksanaan ujian (kendala teknis, kejadian khusus, atau keterangan lain):<br>
        <em>[catatan_pelaksanaan]</em>
    </li>
</ol>
<p style="margin-top: 15px;">Berita Acara ini dibuat dengan sesungguhnya.</p>`;

// Lacak elemen input/editor yang terakhir difokuskan
let lastActiveField = document.getElementById('wysiwygCanvas');
let isSourceMode = false;

const wysiwygCanvas = document.getElementById('wysiwygCanvas');
const wysiwygSource = document.getElementById('wysiwygSource');
const hiddenRedaksi = document.getElementById('redaksi_isi');
const elJudul = document.getElementById('judul_berita_acara');

// Event listener focus & input
if (wysiwygCanvas) {
    wysiwygCanvas.addEventListener('focus', () => { lastActiveField = wysiwygCanvas; });
    wysiwygCanvas.addEventListener('input', syncFromCanvas);
    wysiwygCanvas.addEventListener('keyup', syncFromCanvas);
}
if (wysiwygSource) {
    wysiwygSource.addEventListener('focus', () => { lastActiveField = wysiwygSource; });
    wysiwygSource.addEventListener('input', syncFromSource);
}
if (elJudul) {
    elJudul.addEventListener('focus', () => { lastActiveField = elJudul; });
    elJudul.addEventListener('input', updateLiveSimulation);
}

// Tombol format toolbar WYSIWYG
document.querySelectorAll('.wysiwyg-btn[data-command]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const cmd = this.getAttribute('data-command');
        if (isSourceMode) {
            alert('Nonaktifkan Mode HTML terlebih dahulu untuk menggunakan tombol pemformatan visual.');
            return;
        }
        wysiwygCanvas.focus();
        document.execCommand(cmd, false, null);
        syncFromCanvas();
    });
});

// Sinkronisasi dari Canvas Visual ke Hidden Input & Source Code
function syncFromCanvas() {
    if (!wysiwygCanvas) return;
    const html = wysiwygCanvas.innerHTML;
    if (hiddenRedaksi) hiddenRedaksi.value = html;
    if (wysiwygSource && !isSourceMode) wysiwygSource.value = html;
    updateLiveSimulation();
}

// Sinkronisasi dari Source Code ke Canvas Visual & Hidden Input
function syncFromSource() {
    if (!wysiwygSource) return;
    const html = wysiwygSource.value;
    if (hiddenRedaksi) hiddenRedaksi.value = html;
    if (wysiwygCanvas) wysiwygCanvas.innerHTML = html;
    updateLiveSimulation();
}

// Toggle Switch Mode HTML / Visual
function toggleHtmlSourceMode() {
    const btn = document.getElementById('btnToggleSource');
    if (!btn || !wysiwygCanvas || !wysiwygSource) return;

    isSourceMode = !isSourceMode;
    if (isSourceMode) {
        wysiwygSource.value = wysiwygCanvas.innerHTML;
        wysiwygCanvas.style.display = 'none';
        wysiwygSource.style.display = 'block';
        btn.innerHTML = '👁️ Mode Visual';
        btn.classList.replace('btn-secondary', 'btn-primary');
        lastActiveField = wysiwygSource;
        wysiwygSource.focus();
    } else {
        wysiwygCanvas.innerHTML = wysiwygSource.value;
        wysiwygSource.style.display = 'none';
        wysiwygCanvas.style.display = 'block';
        btn.innerHTML = '💻 Mode HTML';
        btn.classList.replace('btn-primary', 'btn-secondary');
        lastActiveField = wysiwygCanvas;
        wysiwygCanvas.focus();
        syncFromCanvas();
    }
}

// Kembalikan ke susunan redaksi standar resmi
function resetWysiwygToDefault() {
    if (confirm('Apakah Anda yakin ingin mengembalikan isi redaksi berita acara ke format standar resmi?')) {
        if (wysiwygCanvas) wysiwygCanvas.innerHTML = DEFAULT_REDAKSI_HTML;
        if (wysiwygSource) wysiwygSource.value = DEFAULT_REDAKSI_HTML;
        syncFromCanvas();
    }
}

// Sisipkan placeholder ke editor yang sedang aktif
function insertPlaceholder(placeholder) {
    if (lastActiveField === elJudul) {
        insertTextAtCursor(elJudul, placeholder);
        updateLiveSimulation();
        return;
    }

    if (isSourceMode && wysiwygSource) {
        insertTextAtCursor(wysiwygSource, placeholder);
        syncFromSource();
        return;
    }

    if (wysiwygCanvas) {
        wysiwygCanvas.focus();
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            const range = sel.getRangeAt(0);
            range.deleteContents();
            const textNode = document.createTextNode(placeholder);
            range.insertNode(textNode);
            range.setStartAfter(textNode);
            range.setEndAfter(textNode);
            sel.removeAllRanges();
            sel.addRange(range);
        } else {
            wysiwygCanvas.innerHTML += placeholder;
        }
        syncFromCanvas();
    }
}

// Sisipkan ke target input spesifik (misal judul)
function insertPlaceholderInto(targetId, placeholder) {
    const el = document.getElementById(targetId);
    if (el) {
        insertTextAtCursor(el, placeholder);
        updateLiveSimulation();
    }
}

// Helper penyisipan teks pada kursor input/textarea biasa
function insertTextAtCursor(inputEl, text) {
    inputEl.focus();
    const start = inputEl.selectionStart || 0;
    const end = inputEl.selectionEnd || 0;
    const val = inputEl.value;
    inputEl.value = val.substring(0, start) + text + val.substring(end);
    const newPos = start + text.length;
    inputEl.setSelectionRange(newPos, newPos);
}

// Simulasi Data Pengganti untuk Live Preview
const simVars = {
    nama_ujian: 'Sumatif Akhir Semester (SAS)',
    tahun_ajaran: '2026/2027',
    hari: 'Senin',
    tanggal: '12',
    tanggal_teks: 'dua belas',
    tanggal_terbilang: 'dua belas',
    bulan: 'Oktober',
    tahun: '2026',
    tahun_teks: 'dua ribu dua puluh enam',
    tahun_terbilang: 'dua ribu dua puluh enam',
    jam_mulai: '07:30',
    jam_selesai: '09:30',
    mata_pelajaran: 'Pemrograman Web',
    kelas: 'XII RPL 1',
    Kelas: 'XII RPL 1',
    ruang: 'Lab 1',
    sekolah: 'SMK NEGERI 1 KLUNGKUNG',
    peserta_seharusnya: '32',
    peserta_hadir: '32',
    peserta_tidak_hadir: '0',
    blangko_berita_acara: '1',
    blangko_daftar_hadir: '1',
    catatan_pelaksanaan: 'Pelaksanaan ujian berjalan tertib, aman, dan lancar tanpa kendala teknis.',
    catatan: 'Pelaksanaan ujian berjalan tertib, aman, dan lancar tanpa kendala teknis.'
};

// Render simulasi untuk judul (plain text dengan newline ke <br>)
function renderSimJudul(text, vars) {
    if (!text) return '';
    let esc = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    for (const [k, v] of Object.entries(vars)) {
        esc = esc.split('[' + k + ']').join(v);
    }
    return esc.replace(/\r\n|\r|\n/g, '<br>');
}

// Render simulasi untuk konten WYSIWYG
function renderSimWysiwyg(htmlContent, vars) {
    if (!htmlContent) return '';
    let result = htmlContent;
    for (const [k, v] of Object.entries(vars)) {
        const regex = new RegExp('\\[' + k + '\\]', 'gi');
        result = result.replace(regex, v);
    }
    return result;
}

function updateLiveSimulation() {
    const pvJudul = document.getElementById('pvLiveJudul');
    const pvKonten = document.getElementById('pvLiveKonten');

    if (pvJudul && elJudul) {
        pvJudul.innerHTML = renderSimJudul(elJudul.value || 'BERITA ACARA PENYELENGGARAAN UJIAN', simVars);
    }

    if (pvKonten && wysiwygCanvas) {
        pvKonten.innerHTML = renderSimWysiwyg(wysiwygCanvas.innerHTML, simVars);
    }
}

// Inisialisasi awal saat halaman dimuat
syncFromCanvas();
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
