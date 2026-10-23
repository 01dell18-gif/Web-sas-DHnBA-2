/**
 * App JavaScript Helper
 * Generator Absensi dan Berita Acara Ujian - SMK Negeri 1 Klungkung
 */

// =========================================================
// 1. Modal Dialog & Notification Helpers (Popup System)
// =========================================================

function ensurePopupModalsExist() {
    if (!document.getElementById('modalGlobalConfirmDialog')) {
        const confirmModal = document.createElement('div');
        confirmModal.id = 'modalGlobalConfirmDialog';
        confirmModal.className = 'custom-modal';
        confirmModal.style.cssText = 'display: none; z-index: 999999;';
        confirmModal.innerHTML = `
            <div style="background: #ffffff; width: 90%; max-width: 470px; border-radius: 14px; padding: 1.5rem; margin-top: 25px; margin-bottom: 2rem; box-shadow: 0 20px 45px rgba(0,0,0,0.25); border-top: 4px solid var(--danger);">
                <div style="display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem;">
                    <div id="globalConfirmIconBox" style="width: 44px; height: 44px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                        <i id="globalConfirmIcon" class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <h3 id="globalConfirmTitle" style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 0.35rem;">
                            Konfirmasi Tindakan
                        </h3>
                        <p id="globalConfirmMessage" style="font-size: 0.885rem; color: #475569; line-height: 1.5; margin: 0; white-space: pre-line;">
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
        `;
        document.body.appendChild(confirmModal);
    }

    if (!document.getElementById('modalGlobalNotificationDialog')) {
        const notifModal = document.createElement('div');
        notifModal.id = 'modalGlobalNotificationDialog';
        notifModal.className = 'custom-modal';
        notifModal.style.cssText = 'display: none; z-index: 999999;';
        notifModal.innerHTML = `
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
        `;
        document.body.appendChild(notifModal);
    }
}

var _globalConfirmCallback = window._globalConfirmCallback || null;

window.showConfirmDialog = function(message, onConfirm, title = 'Konfirmasi Tindakan', isDanger = true) {
    _globalConfirmCallback = onConfirm;
    ensurePopupModalsExist();
    
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

window.showNotificationModal = function(message, type = 'info', title = null) {
    ensurePopupModalsExist();
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

// Override native alert to modern popup modal
window.alert = function(msg) {
    window.showNotificationModal(String(msg), 'warning', 'Pemberitahuan');
};

// =========================================================
// 2. Navigation & Sidebar Toggles
// =========================================================

// Global Mobile Nav Toggle (Publik)
window.toggleMobileNav = function(e) {
    if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    const navLinks = document.getElementById('navLinks');
    if (navLinks) {
        navLinks.classList.toggle('show');
    }
    return false;
};

// Global Admin Sidebar Toggle (Sembunyikan / Tampilkan Navbar & Menu)
window.toggleAdminSidebar = function(e) {
    if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    if (window.innerWidth <= 900) {
        document.body.classList.toggle('mobile-sidebar-open');
    } else {
        document.body.classList.toggle('sidebar-collapsed');
        try {
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem('admin_sidebar_collapsed', isCollapsed ? 'true' : 'false');
        } catch (err) {}
    }
    return false;
};

// Alias legacy untuk kompatibilitas
window.toggleAdminMenu = window.toggleAdminSidebar;

// Global Accordion Sidebar Group Toggle (Membuka / Menutup Grup Menu Navbar)
window.toggleSidebarGroup = function(button, event) {
    if (event) {
        try { event.preventDefault(); event.stopPropagation(); } catch (e) {}
    }
    const btn = button || (event ? event.target.closest('.sidebar-group-header') : null);
    if (!btn) return false;
    const group = btn.closest('.sidebar-nav-group');
    if (!group) return false;
    group.classList.toggle('is-open');
    return false;
};

// Universal Quick Status Toast Helper
window.showQuickStatusToast = function(message) {
    let toast = document.getElementById('globalQuickStatusToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'globalQuickStatusToast';
        toast.className = 'quick-status-toast';
        toast.innerHTML = '<i class="bi bi-check-circle-fill" style="color: #10b981;"></i><span id="globalQuickStatusMsg">Status berhasil diubah</span>';
        document.body.appendChild(toast);
    }
    const msgEl = document.getElementById('globalQuickStatusMsg');
    if (msgEl) msgEl.textContent = message || 'Status berhasil diubah';
    toast.classList.add('show');
    clearTimeout(window._quickToastTimeout);
    window._quickToastTimeout = setTimeout(() => {
        toast.classList.remove('show');
    }, 2400);
};

// Initial state restore on script evaluation
try {
    if (window.innerWidth > 900 && localStorage.getItem('admin_sidebar_collapsed') === 'true') {
        document.body.classList.add('sidebar-collapsed');
    }
} catch (e) {}


// =========================================================
// 3. Document Ready Interceptors
// =========================================================

document.addEventListener('DOMContentLoaded', function() {
    ensurePopupModalsExist();

    // Auto-dismiss inline alerts if any
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.4s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });

    // Tutup menu publik saat mengklik di luar area menu
    document.addEventListener('click', function(e) {
        const navLinks = document.getElementById('navLinks');
        const btnToggleNav = document.getElementById('btnToggleNav');
        if (navLinks && navLinks.classList.contains('show')) {
            if (!navLinks.contains(e.target) && (!btnToggleNav || !btnToggleNav.contains(e.target))) {
                navLinks.classList.remove('show');
            }
        }

        const adminSidebar = document.querySelector('.admin-sidebar');
        const btnToggleSidebar = document.getElementById('btnToggleSidebar') || document.getElementById('btnToggleAdminMenu');
        if (document.body.classList.contains('mobile-sidebar-open')) {
            if (adminSidebar && !adminSidebar.contains(e.target) && (!btnToggleSidebar || !btnToggleSidebar.contains(e.target))) {
                document.body.classList.remove('mobile-sidebar-open');
            }
        }
    });

    // Delegated click handler untuk grup menu accordion sidebar
    document.addEventListener('click', function(e) {
        const groupHeader = e.target.closest('.sidebar-group-header');
        if (groupHeader) {
            e.preventDefault();
            const group = groupHeader.closest('.sidebar-nav-group');
            if (group) {
                group.classList.toggle('is-open');
            }
        }
    });

    // Global Interceptor untuk Konfirmasi Popup (data-confirm & onsubmit confirm)
    document.addEventListener('click', function(e) {
        const confirmEl = e.target.closest('[data-confirm]');
        if (confirmEl) {
            e.preventDefault();
            e.stopPropagation();
            const message = confirmEl.getAttribute('data-confirm');
            const title = confirmEl.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
            const isDanger = confirmEl.getAttribute('data-confirm-danger') !== 'false';

            window.showConfirmDialog(message, function() {
                if (confirmEl.tagName.toLowerCase() === 'a') {
                    window.location.href = confirmEl.href;
                } else if (confirmEl.type === 'submit') {
                    const form = confirmEl.closest('form');
                    if (form) form.submit();
                } else if (typeof confirmEl.onclick === 'function') {
                    confirmEl.removeAttribute('data-confirm');
                    confirmEl.click();
                    confirmEl.setAttribute('data-confirm', message);
                }
            }, title, isDanger);
            return false;
        }
    }, true);

    // Intercept form submissions that have onsubmit="return confirm(...)"
    document.querySelectorAll('form[onsubmit*="confirm("]').forEach(form => {
        const origSubmitAttr = form.getAttribute('onsubmit');
        const match = origSubmitAttr.match(/confirm\(\s*(['"`])(.*?)\1\s*\)/);
        const confirmMsg = match ? match[2] : 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        
        form.removeAttribute('onsubmit');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            window.showConfirmDialog(confirmMsg, () => {
                form.submit();
            }, 'Konfirmasi Tindakan', true);
        });
    });
});
