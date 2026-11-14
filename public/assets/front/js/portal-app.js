/**
 * OUT LMS - Portal Navigation & Drawer Interactions (v3.0.0)
 * The Open University of Tanzania (OUT)
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('outPortalSidebarToggle');
        const sidebar = document.getElementById('outPortalSidebar');
        const backdrop = document.getElementById('outPortalBackdrop');

        if (!toggleBtn || !sidebar) return;

        function openSidebar() {
            sidebar.classList.add('portal-sidebar-open');
            if (backdrop) backdrop.classList.add('active');
            document.body.style.overflow = window.innerWidth < 992 ? 'hidden' : '';
        }

        function closeSidebar() {
            sidebar.classList.remove('portal-sidebar-open');
            if (backdrop) backdrop.classList.remove('active');
            document.body.style.overflow = '';
        }

        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (sidebar.classList.contains('portal-sidebar-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                closeSidebar();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('portal-sidebar-open')) {
                closeSidebar();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992) {
                closeSidebar();
            }
        });

        // Auto-close on mobile when a navigation destination link is clicked (skip dropdown toggles)
        const navLinks = sidebar.querySelectorAll('a.out-nav-link');
        navLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
        });

        // Initialize WOW animations if available
        if (typeof WOW !== 'undefined') {
            try {
                new WOW().init();
            } catch (e) {}
        }
    });
})();

