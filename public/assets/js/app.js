document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.querySelector('[data-sidebar]');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('-translate-x-full');
        });
    }

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (event) {
            const message = el.dataset.confirm || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-modal-dismiss]').forEach(function (el) {
        el.addEventListener('click', function () {
            const modal = document.getElementById(el.dataset.modalDismiss);
            if (modal) {
                modal.classList.add('hidden');
            }
        });
    });

    document.querySelectorAll('[data-modal-open]').forEach(function (el) {
        el.addEventListener('click', function () {
            const modal = document.getElementById(el.dataset.modalOpen);
            if (modal) {
                modal.classList.remove('hidden');
            }
        });
    });

    window.setTimeout(function () {
        document.querySelectorAll('[data-auto-dismiss]').forEach(function (el) {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            window.setTimeout(function () {
                el.remove();
            }, 500);
        });
    }, 3000);
});
