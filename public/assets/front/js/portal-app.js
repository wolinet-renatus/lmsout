/**
 * OUT LMS - Portal Navigation, Drawer & Live Notifications Interactions (v3.0.0)
 * The Open University of Tanzania (OUT)
 */

(function () {
    'use strict';

    function escapeHtml(text) {
        return String(text ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function typeColor(type) {
        if (type === 'success') return '#059669';
        if (type === 'warning') return '#d97706';
        if (type === 'danger') return '#dc2626';
        return '#013ba6';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('outPortalSidebarToggle');
        const sidebar = document.getElementById('outPortalSidebar');
        const backdrop = document.getElementById('outPortalBackdrop');

        if (toggleBtn && sidebar) {
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

            const navLinks = sidebar.querySelectorAll('a.out-nav-link');
            navLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.innerWidth < 992) {
                        closeSidebar();
                    }
                });
            });
        }

        // Initialize WOW animations if available
        if (typeof WOW !== 'undefined') {
            try {
                new WOW().init();
            } catch (e) {}
        }

        // Portal live header notification bell + chat unread count synchronization
        const headerEl = document.querySelector('.out-portal-header');
        if (!headerEl) return;

        const feedUrl = headerEl.dataset.feedUrl || '';
        const chatSelector = headerEl.dataset.chatIndicator || '';
        const bellBadge = document.getElementById('portalBellBadge');
        const bellList = document.getElementById('portalBellList');
        const markAllBtn = document.getElementById('portalBellMarkAll');
        const chatBadges = chatSelector ? document.querySelectorAll(chatSelector) : [];

        function updateBadge(el, count) {
            if (!el) return;
            const n = parseInt(count || 0, 10);
            if (n > 0) {
                el.textContent = n > 99 ? '99+' : String(n);
                el.classList.remove('d-none');
            } else {
                el.textContent = '0';
                el.classList.add('d-none');
            }
        }

        function renderBellFeed(data) {
            if (!data) return;
            updateBadge(bellBadge, data.unread_count);
            chatBadges.forEach(function (b) { updateBadge(b, data.unread_messages); });

            if (markAllBtn) {
                if ((data.unread_count || 0) > 0) {
                    markAllBtn.classList.remove('d-none');
                } else {
                    markAllBtn.classList.add('d-none');
                }
            }

            if (bellList && Array.isArray(data.notifications)) {
                if (data.notifications.length === 0) {
                    bellList.innerHTML = '<div class="text-center py-4 px-3 text-muted" style="font-size: 12.5px;"><i class="fa-regular fa-bell-slash d-block mb-1 fs-5"></i>No notifications yet.</div>';
                    return;
                }

                bellList.innerHTML = data.notifications.map(function (item) {
                    return '<a href="' + escapeHtml(item.read_url || '#') + '" class="dropdown-item out-portal-bell-item ' + (item.is_unread ? 'unread' : '') + '">'
                        + '<div class="d-flex align-items-start gap-2">'
                        + '<span class="mt-1 flex-shrink-0" style="color: ' + typeColor(item.type) + '; font-size: 13px;"><i class="fa-solid fa-circle-dot"></i></span>'
                        + '<div class="flex-grow-1" style="min-width: 0;">'
                        + '<div class="d-flex justify-content-between align-items-center gap-2">'
                        + '<span class="fw-semibold text-dark text-truncate" style="font-size: 12.5px;">' + escapeHtml(item.title) + '</span>'
                        + '<span class="text-muted flex-shrink-0" style="font-size: 10px;">' + escapeHtml(item.time_ago) + '</span>'
                        + '</div>'
                        + '<p class="text-muted mb-0 text-truncate" style="font-size: 11.5px;">' + escapeHtml(item.message) + '</p>'
                        + '</div></div></a>';
                }).join('');
            }
        }

        function syncPortalHeader() {
            if (!feedUrl) return;
            fetch(feedUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) { if (data) renderBellFeed(data); })
                .catch(function () {});
        }

        if (markAllBtn) {
            markAllBtn.addEventListener('click', function (e) {
                const url = markAllBtn.dataset.readAllUrl || markAllBtn.getAttribute('href');
                if (!url || url === '#') return;
                e.preventDefault();
                fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                    .then(function () { syncPortalHeader(); })
                    .catch(function () { window.location.href = url; });
            });
        }

        if (feedUrl) {
            setInterval(syncPortalHeader, 15000);
        }
    });
})();
