<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

// Handle Export Excel (.xls)
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $searchQ = trim($_GET['q'] ?? '');
    $statusFilter = trim($_GET['status'] ?? '');

    $where = "1=1";
    $params = [];
    if ($searchQ !== '') {
        $where .= " AND (nama LIKE :q OR nip LIKE :q)";
        $params['q'] = "%{$searchQ}%";
    }
    if ($statusFilter !== '') {
        $where .= " AND aktif = :st";
        $params['st'] = (int)$statusFilter;
    }

    $gurus = dbFetchAll("SELECT * FROM guru WHERE {$where} ORDER BY nama ASC", $params);

    $headers = ['No', 'Nama Lengkap Guru', 'NIP / NIPPPK', 'Jabatan / Tugas', 'Status'];
    $rows = [];
    foreach ($gurus as $idx => $g) {
        $rows[] = [
            $idx + 1,
            $g['nama'],
            $g['nip'] ?: '-',
            $g['jabatan'] ?? 'Guru / Pengawas',
            $g['aktif'] == 1 ? 'Aktif' : 'Nonaktif'
        ];
    }
    log_activity('EXPORT_EXCEL', "Mengekspor data guru ke format Excel (.xls)");
    export_excel_xls("Data_Guru_SMKN1_Klungkung_" . date('Ymd_His') . ".xls", $headers, $rows, [2]);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $nip = trim($_POST['nip'] ?? '');
        $jabatan = trim($_POST['jabatan'] ?? 'Guru / Pengawas');
        $aktif = isset($_POST['aktif']) ? 1 : 0;

        if ($nama === '') {
            set_flash('danger', 'Nama guru tidak boleh kosong.');
        } else {
            if ($nip === '') {
                $nip = '-';
            }
            if ($id > 0) {
                dbUpdate('guru', [
                    'nama' => $nama,
                    'nip' => $nip,
                    'jabatan' => $jabatan,
                    'aktif' => $aktif,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $id]);
                log_activity('UPDATE_GURU', "Memperbarui data guru {$nama} (ID #{$id})");
                set_flash('success', "Data guru {$nama} berhasil diperbarui.");
            } else {
                $newId = dbInsert('guru', [
                    'nama' => $nama,
                    'nip' => $nip,
                    'jabatan' => $jabatan,
                    'aktif' => $aktif,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                log_activity('CREATE_GURU', "Menambahkan guru baru {$nama} (ID #{$newId})");
                set_flash('success', "Guru {$nama} berhasil ditambahkan.");
            }
        }
        header('Location: /admin/guru.php');
        exit;
    }

    if ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        dbUpdate('guru', ['aktif' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
        log_activity('TOGGLE_GURU', "Mengubah status aktif guru ID #{$id} menjadi {$status}");

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                  || isset($_POST['ajax']);
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id' => $id,
                'status' => $status,
                'message' => 'Status aktif guru berhasil diperbarui.'
            ]);
            exit;
        }

        set_flash('success', 'Status aktif guru berhasil diperbarui.');
        header('Location: /admin/guru.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $gInfo = dbFetch("SELECT nama FROM guru WHERE id = :id", ['id' => $id]);
        if ($gInfo) {
            dbDelete('guru', 'id = :id', ['id' => $id]);
            log_activity('DELETE_GURU', "Menghapus data guru {$gInfo['nama']}");
            set_flash('success', "Data guru {$gInfo['nama']} berhasil dihapus.");
        }
        header('Location: /admin/guru.php');
        exit;
    }

    // Aksi Kosongkan / Hapus Semua Data Guru
    if ($action === 'delete_all') {
        $count = (int)dbValue("SELECT COUNT(*) FROM guru");
        getDb()->exec("DELETE FROM guru");
        if (DB_TYPE === 'sqlite') {
            getDb()->exec("DELETE FROM sqlite_sequence WHERE name = 'guru'");
        }
        log_activity('CLEAR_ALL_GURU', "Menghapus seluruh data guru ({$count} data) untuk reset.");
        set_flash('success', "Seluruh data guru ({$count} guru) berhasil dikosongkan.");
        header('Location: /admin/guru.php');
        exit;
    }
}

// Check edit mode
$editId = (int)($_GET['edit'] ?? 0);
$editGuru = null;
if ($editId > 0) {
    $editGuru = dbFetch("SELECT * FROM guru WHERE id = :id", ['id' => $editId]);
}

// Search and filter
$searchQ = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = "1=1";
$params = [];
if ($searchQ !== '') {
    $where .= " AND (nama LIKE :q OR nip LIKE :q)";
    $params['q'] = "%{$searchQ}%";
}
if ($statusFilter !== '') {
    $where .= " AND aktif = :st";
    $params['st'] = (int)$statusFilter;
}

$gurus = dbFetchAll("SELECT * FROM guru WHERE {$where} ORDER BY nama ASC", $params);

// Stat counts
$totalGuru = (int)dbValue("SELECT COUNT(*) FROM guru");
$aktifGuru = (int)dbValue("SELECT COUNT(*) FROM guru WHERE aktif = 1");
$nonaktifGuru = (int)dbValue("SELECT COUNT(*) FROM guru WHERE aktif = 0");
$nipGuru = (int)dbValue("SELECT COUNT(*) FROM guru WHERE nip != '' AND nip != '-'");

$pageTitle = 'Kelola Data Guru & Pengawas';
require_once __DIR__ . '/layout_header.php';
?>

<div class="page-header-card">
    <div class="page-header-content">
        <div class="page-header-icon-box">
            <i class="bi bi-person-badge"></i>
        </div>
        <div>
            <h1 class="page-header-title">Data Guru & Pengawas Ujian</h1>
            <p class="page-header-subtitle">
                Master data guru (Nama, NIP/NIPPPK) yang digunakan untuk pilihan otomatis pengawas pada Daftar Hadir dan Berita Acara.
            </p>
        </div>
    </div>
    <div class="page-header-actions" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <a href="/admin/guru.php?action=export_excel<?= !empty($searchQ) ? '&q=' . urlencode($searchQ) : '' ?><?= $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : '' ?>" class="btn btn-secondary btn-sm" title="Ekspor data guru ke format Excel .xls">
            <i class="bi bi-file-earmark-arrow-down"></i> <span>Ekspor Excel (.xls)</span>
        </a>
        <a href="/admin/import-siswa.php?type=guru" class="btn btn-success btn-sm" title="Impor data guru dari format Excel .xls">
            <i class="bi bi-file-earmark-excel"></i> <span>Impor Excel (.xls)</span>
        </a>
        <?php if ($totalGuru > 0): ?>
            <button type="button" class="btn btn-danger-soft btn-sm" onclick="confirmResetAllGuru()" title="Kosongkan seluruh data guru untuk persiapan ujian baru">
                <i class="bi bi-trash3-fill"></i> <span>Hapus Semua Data Guru</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Modal / Form Hidden Delete All -->
<form id="formDeleteAllGuru" method="POST" action="/admin/guru.php" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete_all">
</form>

<!-- Stat Cards -->
<div class="stats-grid">
    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #4f46e5, #6366f1);"></div>
        <div class="stat-icon stat-icon-indigo">
            <i class="bi bi-people-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $totalGuru ?></div>
            <div class="stat-label">Total Data Guru</div>
            <span class="stat-meta-pill" style="background: #eef2ff; color: #4338ca;">
                <i class="bi bi-check-circle"></i> Terdaftar
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #059669, #10b981);"></div>
        <div class="stat-icon" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #059669;">
            <i class="bi bi-person-check-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $aktifGuru ?></div>
            <div class="stat-label">Guru Aktif</div>
            <span class="stat-meta-pill" style="background: #ecfdf5; color: #059669;">
                Dapat Dipilih Publik
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #0284c7, #38bdf8);"></div>
        <div class="stat-icon stat-icon-sky">
            <i class="bi bi-card-text"></i>
        </div>
        <div>
            <div class="stat-value"><?= $nipGuru ?></div>
            <div class="stat-label">Memiliki NIP / NIPPPK</div>
            <span class="stat-meta-pill" style="background: #f0f9ff; color: #0284c7;">
                Identitas Lengkap
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #64748b, #94a3b8);"></div>
        <div class="stat-icon" style="background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #475569;">
            <i class="bi bi-person-slash"></i>
        </div>
        <div>
            <div class="stat-value"><?= $nonaktifGuru ?></div>
            <div class="stat-label">Guru Nonaktif</div>
            <span class="stat-meta-pill" style="background: #f1f5f9; color: #475569;">
                Tidak Ditampilkan
            </span>
        </div>
    </div>
</div>

<div class="split-layout" style="height: auto; align-items: flex-start; gap: 1rem;">
    <!-- Formulir Tambah / Edit Guru -->
    <div style="flex: 0 0 360px; min-width: 320px;">
        <div class="card" style="border-top: 3px solid var(--primary); position: sticky; top: 75px;">
            <div class="card-header">
                <span class="card-title">
                    <i class="bi <?= $editGuru ? 'bi-pencil-square' : 'bi-person-plus-fill' ?>"></i>
                    <?= $editGuru ? 'Edit Data Guru' : 'Tambah Guru Baru' ?>
                </span>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/guru.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <?php if ($editGuru): ?>
                        <input type="hidden" name="id" value="<?= $editGuru['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="nama">Nama Lengkap & Gelar <span class="required">*</span></label>
                        <input type="text" name="nama" id="nama" class="form-control" 
                               value="<?= htmlspecialchars($editGuru['nama'] ?? '') ?>" placeholder="Contoh: I Wayan Developer, S.Kom." required autofocus>
                        <span class="form-hint">Nama ini akan tercetak pada lembar Daftar Hadir & Berita Acara.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nip">NIP / NIPPPK</label>
                        <input type="text" name="nip" id="nip" class="form-control" 
                               value="<?= htmlspecialchars($editGuru['nip'] ?? '') ?>" placeholder="Contoh: 198412132009022009 atau -">
                        <span class="form-hint">Kosongkan atau beri tanda strip (-) jika belum memiliki NIP.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="jabatan">Jabatan / Keterangan</label>
                        <input type="text" name="jabatan" id="jabatan" class="form-control" 
                               value="<?= htmlspecialchars($editGuru['jabatan'] ?? 'Guru / Pengawas') ?>" placeholder="Guru / Pengawas">
                    </div>

                    <div class="form-group" style="margin-top: 0.85rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.835rem; font-weight: 600; color: var(--slate-700);">
                            <input type="checkbox" name="aktif" value="1" <?= ($editGuru['aktif'] ?? 1) == 1 ? 'checked' : '' ?>>
                            Guru Aktif (Dapat dipilih di form ujian)
                        </label>
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-top: 1.25rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="bi bi-save"></i> <?= $editGuru ? 'Simpan Perubahan' : 'Tambah Guru' ?>
                        </button>
                        <?php if ($editGuru): ?>
                            <a href="/admin/guru.php" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Guru -->
    <div style="flex: 1; min-width: 0;">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                <span class="card-title">
                    <i class="bi bi-person-lines-fill"></i> Daftar Seluruh Guru (<?= count($gurus) ?>)
                </span>
                
                <!-- Filter & Search Bar -->
                <form method="GET" action="/admin/guru.php" style="display: flex; gap: 0.35rem; align-items: center;">
                    <select name="status" class="form-select" style="min-height: 30px; font-size: 0.775rem; padding: 0.2rem 0.5rem; width: auto;" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Hanya Aktif</option>
                        <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Hanya Nonaktif</option>
                    </select>
                    <input type="text" name="q" class="form-control" placeholder="Cari nama / NIP..." value="<?= htmlspecialchars($searchQ) ?>" style="min-height: 30px; font-size: 0.775rem; width: 170px;">
                    <button type="submit" class="btn btn-secondary btn-sm" style="min-height: 30px;">
                        <i class="bi bi-search"></i>
                    </button>
                    <?php if ($searchQ !== '' || $statusFilter !== ''): ?>
                        <a href="/admin/guru.php" class="btn btn-secondary btn-sm" title="Reset Pencarian" style="min-height: 30px;">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table" style="margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">No</th>
                                <th>Nama Lengkap Guru</th>
                                <th>NIP / NIPPPK</th>
                                <th>Jabatan</th>
                                <th style="text-align: center; width: 140px;"><i class="bi bi-check2-square"></i> Status (Ceklis)</th>
                                <th style="text-align: center; width: 105px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($gurus)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                        Tidak ada data guru yang cocok dengan pencarian.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($gurus as $idx => $g): ?>
                                    <tr>
                                        <td style="text-align: center; color: var(--text-subtle);"><?= $idx + 1 ?></td>
                                        <td>
                                            <div style="font-weight: 700; color: var(--text-heading);">
                                                <?= htmlspecialchars($g['nama']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($g['nip'] && $g['nip'] !== '-'): ?>
                                                <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.78rem; color: #1e293b;"><?= htmlspecialchars($g['nip']) ?></code>
                                            <?php else: ?>
                                                <span style="color: var(--text-subtle); font-size: 0.8rem;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                                <?= htmlspecialchars($g['jabatan'] ?? 'Guru / Pengawas') ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <label class="ceklis-status-pill <?= $g['aktif'] ? 'is-active' : 'is-inactive' ?>" 
                                                   id="ceklis_box_guru_<?= $g['id'] ?>" 
                                                   title="Klik ceklis untuk mengubah status aktif/nonaktif guru ini">
                                                <input type="checkbox" 
                                                       class="ceklis-guru-input" 
                                                       id="check_guru_<?= $g['id'] ?>" 
                                                       <?= $g['aktif'] ? 'checked' : '' ?> 
                                                       onchange="quickToggleGuru(<?= $g['id'] ?>, this)">
                                                <span class="ceklis-box">
                                                    <i class="bi bi-check-lg ceklis-check-icon"></i>
                                                </span>
                                                <span class="ceklis-text" id="ceklis_text_guru_<?= $g['id'] ?>">
                                                    <?= $g['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                                </span>
                                            </label>
                                        </td>
                                        <td style="text-align: center; white-space: nowrap;">
                                            <div class="action-btn-group">
                                                <a href="/admin/guru.php?edit=<?= $g['id'] ?>" class="btn-icon-action btn-icon-edit" title="Edit Data Guru">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" action="/admin/guru.php" style="display: inline; margin: 0;" 
                                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus data guru <?= htmlspecialchars(addslashes($g['nama'])) ?>?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                                    <button type="submit" class="btn-icon-action btn-icon-delete" title="Hapus Guru">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function quickToggleGuru(id, checkbox) {
    if (typeof id === 'object' && id !== null && id.nodeType) {
        const tmp = checkbox;
        checkbox = id;
        id = tmp;
    }
    const isChecked = checkbox.checked ? 1 : 0;
    const box = document.getElementById('ceklis_box_guru_' + id);
    const text = document.getElementById('ceklis_text_guru_' + id);

    // Optimistic UI update
    if (isChecked) {
        if (box) {
            box.classList.remove('is-inactive');
            box.classList.add('is-active');
        }
        if (text) text.textContent = 'Aktif';
    } else {
        if (box) {
            box.classList.remove('is-active');
            box.classList.add('is-inactive');
        }
        if (text) text.textContent = 'Nonaktif';
    }

    const csrfToken = '<?= csrf_token() ?>';
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('action', 'toggle_active');
    formData.append('id', id);
    formData.append('status', isChecked);
    formData.append('ajax', '1');

    fetch('/admin/guru.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.success) {
            try {
                if (typeof showQuickStatusToast === 'function') {
                    showQuickStatusToast('Status guru berhasil ' + (isChecked ? 'diaktifkan' : 'dinonaktifkan'));
                }
            } catch (e) {}
        } else {
            revertToggle();
        }
    })
    .catch(err => {
        console.error('Toggle guru error:', err);
        revertToggle();
    });

    function revertToggle() {
        checkbox.checked = !isChecked;
        if (checkbox.checked) {
            if (box) { box.classList.remove('is-inactive'); box.classList.add('is-active'); }
            if (text) text.textContent = 'Aktif';
        } else {
            if (box) { box.classList.remove('is-active'); box.classList.add('is-inactive'); }
            if (text) text.textContent = 'Nonaktif';
        }
        try {
            if (typeof showQuickStatusToast === 'function') {
                showQuickStatusToast('Gagal memperbarui status');
            }
        } catch (e) {}
    }
}
window.quickToggleGuru = quickToggleGuru;

function confirmResetAllGuru() {
    showConfirmResetModal(
        'formDeleteAllGuru',
        'Hapus Semua Data Guru',
        'PERINGATAN: Tindakan ini akan MENGHAPUS SELURUH data guru dari sistem untuk memulai dari awal.'
    );
}
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
