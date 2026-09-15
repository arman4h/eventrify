document.addEventListener('DOMContentLoaded', function () {
    // ── Sidebar Toggle ──────────────────────
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.querySelector('[data-sidebar]');
    const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('-translate-x-full');
            if (sidebarOverlay) sidebarOverlay.classList.toggle('hidden');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        });
    }

    // ── Mobile Menu (Landing) ───────────────
    const mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle]');
    const mobileMenu = document.querySelector('[data-mobile-menu]');

    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // ── Confirm Dialogs ─────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (event) {
            const message = el.dataset.confirm || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // ── Modal Open / Dismiss ────────────────
    document.querySelectorAll('[data-modal-dismiss]').forEach(function (el) {
        el.addEventListener('click', function () {
            const modal = document.getElementById(el.dataset.modalDismiss);
            if (modal) modal.classList.add('hidden');
        });
    });

    document.querySelectorAll('[data-modal-open]').forEach(function (el) {
        el.addEventListener('click', function () {
            const modal = document.getElementById(el.dataset.modalOpen);
            if (modal) modal.classList.remove('hidden');
        });
    });

    // Close modals on overlay click
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.classList.add('hidden');
        });
    });

    // Close modals on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay:not(.hidden)').forEach(function (m) {
                m.classList.add('hidden');
            });
        }
    });

    // ── Auto-dismiss Alerts ─────────────────
    window.setTimeout(function () {
        document.querySelectorAll('[data-auto-dismiss]').forEach(function (el) {
            el.style.transition = 'opacity 0.3s ease';
            el.style.opacity = '0';
            window.setTimeout(function () {
                el.remove();
            }, 300);
        });
    }, 4000);

    // ── Dropdown Toggle ─────────────────────
    document.querySelectorAll('[data-dropdown-toggle]').forEach(function (trigger) {
        const menu = trigger.nextElementSibling;
        if (!menu) return;

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            // Close all other open dropdowns
            document.querySelectorAll('.dropdown-menu:not(.hidden)').forEach(function (m) {
                if (m !== menu) m.classList.add('hidden');
            });
            menu.classList.toggle('hidden');
        });
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.dropdown-menu:not(.hidden)').forEach(function (m) {
            m.classList.add('hidden');
        });
    });

    // ── Tabs ────────────────────────────────
    document.querySelectorAll('[data-tab]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            const group = tab.dataset.tabGroup || 'default';
            const target = tab.dataset.tab;

            // Deactivate all tabs in group
            document.querySelectorAll('[data-tab-group="' + group + '"]').forEach(function (t) {
                t.classList.remove('tab-active');
                t.classList.remove('text-blue-600');
                t.classList.add('text-gray-500');
            });

            // Activate clicked tab
            tab.classList.add('tab-active');
            tab.classList.remove('text-gray-500');
            tab.classList.add('text-blue-600');

            // Show/hide tab panels
            document.querySelectorAll('[data-tab-panel]').forEach(function (panel) {
                if (panel.dataset.tabPanelGroup === group) {
                    if (panel.dataset.tabPanel === target) {
                        panel.classList.remove('hidden');
                    } else {
                        panel.classList.add('hidden');
                    }
                }
            });
        });
    });

    // ── Form Validation Visual Cues ─────────
    document.querySelectorAll('.input, .select, .textarea').forEach(function (input) {
        input.addEventListener('blur', function () {
            if (input.hasAttribute('required') && !input.value.trim()) {
                input.classList.add('input-error');
            } else {
                input.classList.remove('input-error');
            }
        });
    });

    // ── Copy to Clipboard ───────────────────
    document.querySelectorAll('[data-copy]').forEach(function (el) {
        el.addEventListener('click', function () {
            const text = el.dataset.copy;
            navigator.clipboard.writeText(text).then(function () {
                const original = el.textContent;
                el.textContent = 'Copied!';
                setTimeout(function () { el.textContent = original; }, 1500);
            });
        });
    });
});
