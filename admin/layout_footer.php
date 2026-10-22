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
</script>

<script src="/assets/js/app.js"></script>
</body>
</html>
