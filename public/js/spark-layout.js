document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.getElementById('sidebar-toggle');
    const desktopToggle = document.getElementById('desktop-sidebar-toggle');

    if (!sidebar) {
        return;
    }

    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    const closeMobileSidebar = function () {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        mobileToggle?.setAttribute('aria-expanded', 'false');
    };

    mobileToggle?.addEventListener('click', function () {
        const isOpen = !sidebar.classList.contains('show');
        sidebar.classList.toggle('show', isOpen);
        overlay.classList.toggle('show', isOpen);
        mobileToggle.setAttribute('aria-expanded', String(isOpen));
    });

    overlay.addEventListener('click', closeMobileSidebar);

    desktopToggle?.addEventListener('click', function () {
        const isMinimized = document.body.classList.toggle('sidebar-minimized');
        desktopToggle.setAttribute('aria-label', isMinimized
            ? 'Mở rộng thanh điều hướng'
            : 'Thu gọn thanh điều hướng');
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1200) {
            closeMobileSidebar();
        }
    });
});
