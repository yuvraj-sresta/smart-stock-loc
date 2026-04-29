/**
 * assets/js/main.js
 * Global JavaScript for Smart Stock.
 * Handles: mobile sidebar toggle, flash auto-dismiss, confirm dialogs.
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── Mobile sidebar toggle ─────────────────────────────────
    const sidebar  = document.querySelector('.sidebar');
    const overlay  = document.querySelector('.sidebar-overlay');
    const hamburger = document.querySelector('.hamburger');

    function openSidebar()  {
        sidebar?.classList.add('open');
        overlay?.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('open');
        document.body.style.overflow = '';
    }

    hamburger?.addEventListener('click', openSidebar);
    overlay?.addEventListener('click', closeSidebar);

    // ── Auto-dismiss flash alerts ─────────────────────────────
    const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
    alerts.forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 4000);
    });

    // ── Delete confirmation ───────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

});
