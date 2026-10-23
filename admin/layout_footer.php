    </main>
</div>

<!-- MODAL POPUP KONFIRMASI HAPUS SEMUA DATA (LANJUT / TIDAK) -->
<div id="modalConfirmResetGlobal" class="custom-modal" style="display: none;">
    <div style="background: #ffffff; width: 90%; max-width: 460px; border-radius: 14px; padding: 1.5rem; margin-top: 20px; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.25); border-top: 4px solid var(--danger);">
        <div style="display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem;">
            <div style="width: 44px; height: 44px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <h3 id="confirmResetTitle" style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0.35rem;">
                    Konfirmasi Hapus Semua Data
                </h3>
                <p id="confirmResetMessage" style="font-size: 0.875rem; color: #475569; line-height: 1.5; margin: 0;">
                    Tindakan ini akan menghapus seluruh data secara permanen dari sistem untuk memulai dari awal.
                </p>
            </div>
        </div>
        <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.825rem; color: #9f1239; display: flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-info-circle-fill"></i>
            <span>Apakah Anda yakin ingin melanjutkan tindakan ini?</span>
        </div>
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeConfirmResetModal()" style="min-width: 100px;">
                <i class="bi bi-x-lg"></i> Tidak
            </button>
            <button type="button" class="btn btn-danger" id="btnConfirmResetProceed" onclick="proceedConfirmReset()" style="min-width: 110px;">
                <i class="bi bi-arrow-right-circle-fill"></i> Lanjut
            </button>
        </div>
    </div>
</div>

<!-- UNIVERSAL MODAL POPUP KONFIRMASI TINDAKAN (LANJUT / TIDAK) -->
<div id="modalGlobalConfirmDialog" class="custom-modal" style="display: none; z-index: 999999;">
    <div style="background: #ffffff; width: 90%; max-width: 470px; border-radius: 14px; padding: 1.5rem; margin-top: 25px; margin-bottom: 2rem; box-shadow: 0 20px 45px rgba(0,0,0,0.25); border-top: 4px solid var(--danger);">
        <div style="display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem;">
            <div id="globalConfirmIconBox" style="width: 44px; height: 44px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                <i id="globalConfirmIcon" class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <h3 id="globalConfirmTitle" style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0.35rem;">
                    Konfirmasi Tindakan
                </h3>
                <p id="globalConfirmMessage" style="font-size: 0.885rem; color: #475569; line-height: 1.5; margin: 0;">
                    Apakah Anda yakin ingin melanjutkan tindakan ini?
                </p>
            </div>
        </div>
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" id="btnGlobalConfirmCancel" onclick="closeGlobalConfirmDialog()" style="min-width: 100px;">
                <i class="bi bi-x-lg"></i> Tidak
            </button>
            <button type="button" class="btn btn-danger" id="btnGlobalConfirmProceed" style="min-width: 110px;">
                <i class="bi bi-check-circle-fill"></i> Lanjut
            </button>
        </div>
    </div>
</div>

<!-- UNIVERSAL MODAL POPUP NOTIFIKASI / PERINGATAN (OK, MENGERTI) -->
<div id="modalGlobalNotificationDialog" class="custom-modal" style="display: none; z-index: 999999;">
    <div class="notification-popup-card" id="globalNotificationCard" style="border-top: 4px solid #3b82f6;">
        <div style="display: flex; align-items: flex-start; gap: 0.9rem;">
            <div id="globalNotificationIconBox" style="width: 44px; height: 44px; border-radius: 50%; background: #eff6ff; border: 1px solid #bfdbfe; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                <i id="globalNotificationIcon" class="bi bi-info-circle-fill"></i>
            </div>
            <div style="flex: 1;">
                <h3 id="globalNotificationTitle" style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0 0 0.35rem 0;">Pemberitahuan</h3>
                <p id="globalNotificationMessage" style="font-size: 0.885rem; color: #475569; line-height: 1.5; margin: 0; white-space: pre-line;"></p>
            </div>
            <button type="button" onclick="closeGlobalNotificationDialog()" style="background: none; border: none; font-size: 1.35rem; color: #94a3b8; cursor: pointer; padding: 0 4px; line-height: 1; border-radius: 6px;" title="Tutup">&times;</button>
        </div>
        <div style="display: flex; justify-content: flex-end; margin-top: 1.25rem;">
            <button type="button" class="btn btn-primary btn-sm" onclick="closeGlobalNotificationDialog()" style="min-width: 90px;">
                OK, Mengerti
            </button>
        </div>
    </div>
</div>

<script>
let _pendingResetFormId = null;

function showConfirmResetModal(formId, title, message) {
    _pendingResetFormId = formId;
    if (title) document.getElementById('confirmResetTitle').textContent = title;
    if (message) document.getElementById('confirmResetMessage').textContent = message;
    document.getElementById('modalConfirmResetGlobal').style.display = 'flex';
}

function closeConfirmResetModal() {
    _pendingResetFormId = null;
    document.getElementById('modalConfirmResetGlobal').style.display = 'none';
}

function proceedConfirmReset() {
    if (_pendingResetFormId) {
        const f = document.getElementById(_pendingResetFormId);
        if (f) f.submit();
    }
    closeConfirmResetModal();
}

// Universal Global Confirm System
let _globalConfirmCallback = null;

window.showConfirmDialog = function(message, onConfirm, title = 'Konfirmasi Tindakan', isDanger = true) {
    _globalConfirmCallback = onConfirm;
    const modal = document.getElementById('modalGlobalConfirmDialog');
    if (!modal) {
        if (onConfirm) onConfirm();
        return;
    }
    document.getElementById('globalConfirmTitle').textContent = title || 'Konfirmasi Tindakan';
    document.getElementById('globalConfirmMessage').textContent = message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
    
    const iconBox = document.getElementById('globalConfirmIconBox');
    const icon = document.getElementById('globalConfirmIcon');
    const btnProceed = document.getElementById('btnGlobalConfirmProceed');

    if (isDanger) {
        iconBox.style.background = '#fee2e2';
        iconBox.style.color = '#dc2626';
        icon.className = 'bi bi-exclamation-triangle-fill';
        btnProceed.className = 'btn btn-danger';
    } else {
        iconBox.style.background = '#eff6ff';
        iconBox.style.color = '#2563eb';
        icon.className = 'bi bi-question-circle-fill';
        btnProceed.className = 'btn btn-primary';
    }

    btnProceed.onclick = function() {
        closeGlobalConfirmDialog();
        if (typeof _globalConfirmCallback === 'function') {
            _globalConfirmCallback();
        }
    };

    modal.style.display = 'flex';
};

window.closeGlobalConfirmDialog = function() {
    _globalConfirmCallback = null;
    const modal = document.getElementById('modalGlobalConfirmDialog');
    if (modal) modal.style.display = 'none';
};

// Universal Global Notification Modal
window.showNotificationModal = function(message, type = 'info', title = null) {
    const modal = document.getElementById('modalGlobalNotificationDialog');
    if (!modal) return;

    const card = document.getElementById('globalNotificationCard');
    const iconBox = document.getElementById('globalNotificationIconBox');
    const icon = document.getElementById('globalNotificationIcon');
    const titleEl = document.getElementById('globalNotificationTitle');
    const msgEl = document.getElementById('globalNotificationMessage');

    let t = title || 'Pemberitahuan';
    let ic = 'bi-info-circle-fill';
    let color = '#3b82f6';
    let bg = '#eff6ff';
    let border = '#bfdbfe';

    if (type === 'success') {
        t = title || 'Berhasil!';
        ic = 'bi-check-circle-fill';
        color = '#10b981';
        bg = '#ecfdf5';
        border = '#a7f3d0';
    } else if (type === 'danger' || type === 'error') {
        t = title || 'Peringatan / Terjadi Kesalahan';
        ic = 'bi-exclamation-triangle-fill';
        color = '#ef4444';
        bg = '#fef2f2';
        border = '#fecaca';
    } else if (type === 'warning') {
        t = title || 'Perhatian';
        ic = 'bi-exclamation-circle-fill';
        color = '#f59e0b';
        bg = '#fffbeb';
        border = '#fde68a';
    }

    card.style.borderTopColor = color;
    iconBox.style.background = bg;
    iconBox.style.borderColor = border;
    iconBox.style.color = color;
    icon.className = 'bi ' + ic;
    titleEl.textContent = t;
    msgEl.textContent = message || '';

    modal.style.display = 'flex';
};

window.closeGlobalNotificationDialog = function() {
    const modal = document.getElementById('modalGlobalNotificationDialog');
    if (modal) modal.style.display = 'none';
};

// Override native alert to popup modal
window.alert = function(msg) {
    window.showNotificationModal(String(msg), 'warning', 'Pemberitahuan');
};
</script>

<script src="/assets/js/app.js?v=<?= file_exists(__DIR__ . '/../assets/js/app.js') ? filemtime(__DIR__ . '/../assets/js/app.js') : time() ?>"></script>
</body>
</html>
