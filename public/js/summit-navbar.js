/* ============================================================
   Summit Station - Interactive Navbar
   Elevasi saat scroll, menu mobile, dropdown user, badge keranjang.
   ============================================================ */
(function () {
    'use strict';

    var header = document.getElementById('siteHeader');

    /* ---------- Elevasi header saat scroll ---------- */
    function onScroll() {
        if (!header) return;
        header.classList.toggle('is-scrolled', window.scrollY > 8);
    }
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    /* ---------- Menu mobile (hamburger) ---------- */
    var navToggle = document.getElementById('navToggle');
    var mainNav = document.getElementById('mainNav');

    function closeMobileMenu() {
        if (!mainNav || !navToggle) return;
        mainNav.classList.remove('menu-open');
        navToggle.classList.remove('is-active');
        navToggle.setAttribute('aria-expanded', 'false');
        navToggle.setAttribute('aria-label', 'Buka menu navigasi');
    }

    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = mainNav.classList.toggle('menu-open');
            navToggle.classList.toggle('is-active', open);
            navToggle.setAttribute('aria-expanded', String(open));
            navToggle.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
        });

        mainNav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeMobileMenu);
        });

        document.addEventListener('click', function (e) {
            if (!mainNav.contains(e.target) && !navToggle.contains(e.target)) {
                closeMobileMenu();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 900) closeMobileMenu();
        });
    }

    /* ---------- Dropdown user: klik untuk buka/tutup ---------- */
    document.querySelectorAll('[data-user-menu]').forEach(function (wrapper) {
        var toggleBtn = wrapper.querySelector('.user-toggle');
        var dropdown = wrapper.querySelector('.user-dropdown');
        if (!toggleBtn || !dropdown) return;

        function closeDropdown() {
            wrapper.classList.remove('open');
            toggleBtn.setAttribute('aria-expanded', 'false');
        }

        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = wrapper.classList.toggle('open');
            toggleBtn.setAttribute('aria-expanded', String(open));
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) closeDropdown();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeDropdown();
        });
    });

    /* ---------- Dropdown notifikasi: klik untuk buka/tutup ---------- */
    document.querySelectorAll('[data-notif-menu]').forEach(function (wrapper) {
        var toggleBtn = wrapper.querySelector('.notif-toggle');
        if (!toggleBtn) return;

        function closeNotifDropdown() {
            wrapper.classList.remove('open');
            toggleBtn.setAttribute('aria-expanded', 'false');
        }

        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = wrapper.classList.toggle('open');
            toggleBtn.setAttribute('aria-expanded', String(open));
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) closeNotifDropdown();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeNotifDropdown();
        });
    });

    /* ---------- Tutup menu mobile dengan Escape ---------- */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMobileMenu();
    });

    /* ---------- Badge notifikasi: pop singkat saat angka benar-benar berubah ---------- */
    document.querySelectorAll('.notif-badge').forEach(function (badge) {
        var last = badge.textContent;
        var observer = new MutationObserver(function () {
            var now = badge.textContent;
            if (now !== last && now.trim() !== '') {
                badge.classList.remove('badge-bounce');
                void badge.offsetWidth;
                badge.classList.add('badge-bounce');
            }
            last = now;
        });
        observer.observe(badge, { characterData: true, childList: true, subtree: true });
    });

    /* ---------- Logout Konfirmasi (global) ----------
       Terpisah PENUH dari navigasi profil:
       - Klik [data-logout-open] (tombol "Keluar")      -> buka modal konfirmasi
       - Klik [data-close-logout] / backdrop            -> tutup modal
       - Klik #confirm-logout                           -> submit #logout-form
       Tombol/avatar PROFILE sama sekali tidak terhubung ke sini. */
    var logoutModal = document.getElementById('logout-modal');
    if (logoutModal) {
        var logoutForm = document.getElementById('logout-form');

        document.querySelectorAll('[data-logout-open]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                logoutModal.hidden = false;
                document.body.classList.add('modal-open');
            });
        });

        document.querySelectorAll('[data-close-logout]').forEach(function (el) {
            el.addEventListener('click', function () {
                logoutModal.hidden = true;
                document.body.classList.remove('modal-open');
            });
        });

        var confirmLogout = document.getElementById('confirm-logout');
        if (confirmLogout && logoutForm) {
            confirmLogout.addEventListener('click', function () {
                logoutForm.submit();
            });
        }
    }
})();
