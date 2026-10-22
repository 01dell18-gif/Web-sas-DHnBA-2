/**
 * App JavaScript Helper
 * Generator Absensi dan Berita Acara Ujian - SMK Negeri 1 Klungkung
 */

// Global Mobile Nav Toggle (Dipanggil langsung dari onclick atau event listener)
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

// Global Admin Menu Toggle
window.toggleAdminMenu = function(e) {
    if (e) {
        if (typeof e.preventDefault === 'function') e.preventDefault();
        if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    const adminSidebar = document.querySelector('.admin-sidebar');
    if (adminSidebar) {
        adminSidebar.classList.toggle('show');
    }
    return false;
};

document.addEventListener('DOMContentLoaded', function() {
    // 1. Auto-dismiss alert messages after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // 2. Tutup menu publik saat mengklik di luar area menu
    document.addEventListener('click', function(e) {
        const navLinks = document.getElementById('navLinks');
        const btnToggleNav = document.getElementById('btnToggleNav');
        if (navLinks && navLinks.classList.contains('show')) {
            if (!navLinks.contains(e.target) && (!btnToggleNav || !btnToggleNav.contains(e.target))) {
                navLinks.classList.remove('show');
            }
        }

        const adminSidebar = document.querySelector('.admin-sidebar');
        const btnToggleAdmin = document.getElementById('btnToggleAdminMenu');
        if (adminSidebar && adminSidebar.classList.contains('show')) {
            if (!adminSidebar.contains(e.target) && (!btnToggleAdmin || !btnToggleAdmin.contains(e.target))) {
                adminSidebar.classList.remove('show');
            }
        }
    });

    // Fallback jika tombol tidak memiliki inline onclick
    const btnToggleNav = document.getElementById('btnToggleNav');
    if (btnToggleNav && !btnToggleNav.getAttribute('onclick')) {
        btnToggleNav.addEventListener('click', function(e) {
            window.toggleMobileNav(e);
        });
    }

    const btnToggleAdmin = document.getElementById('btnToggleAdminMenu');
    if (btnToggleAdmin && !btnToggleAdmin.getAttribute('onclick')) {
        btnToggleAdmin.addEventListener('click', function(e) {
            window.toggleAdminMenu(e);
        });
    }
});
