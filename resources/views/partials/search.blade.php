<!-- Real-Time Header Search Styles & Script -->
<style>
    .lmsout-live-search {
        position: relative;
        z-index: 1050;
    }

    .lmsout-live-search__form {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        height: 36px;
        width: 220px;
        padding: 0 8px 0 11px;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        transition: width 0.22s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
    }

    .lmsout-live-search.is-open .lmsout-live-search__form,
    .lmsout-live-search__form:focus-within {
        width: 280px;
        background: #ffffff;
        border-color: #013ba6;
        box-shadow: 0 0 0 3px rgba(1, 59, 166, 0.12);
    }

    .lmsout-live-search__icon {
        font-size: 12.5px;
        color: #64748b;
        flex-shrink: 0;
        transition: color 0.18s ease;
    }

    .lmsout-live-search.is-open .lmsout-live-search__icon,
    .lmsout-live-search__form:focus-within .lmsout-live-search__icon {
        color: #013ba6;
    }

    .lmsout-live-search__input {
        width: 100%;
        border: none !important;
        background: transparent !important;
        outline: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        font-size: 12.5px;
        font-weight: 500;
        color: #0f172a;
        line-height: 1;
    }

    .lmsout-live-search__input::placeholder {
        color: #94a3b8;
        font-weight: 500;
    }

    .lmsout-live-search__clear {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: none;
        background: #e2e8f0;
        color: #475569;
        font-size: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        padding: 0;
        transition: background 0.15s ease, color 0.15s ease;
    }

    .lmsout-live-search__clear:hover {
        background: #cbd5e1;
        color: #0f172a;
    }

    .lmsout-live-search__kbd {
        font-size: 10px;
        font-weight: 700;
        font-family: inherit;
        padding: 2px 5px;
        border-radius: 5px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #64748b;
        line-height: 1.1;
        flex-shrink: 0;
        pointer-events: none;
    }

    /* Live Results Dropdown Panel */
    .lmsout-live-search__dropdown {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: 420px;
        max-width: calc(100vw - 24px);
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.14), 0 4px 12px rgba(15, 23, 42, 0.06);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-6px);
        transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s ease;
        overflow: hidden;
    }

    .lmsout-live-search.is-open .lmsout-live-search__dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .lmsout-live-search__body {
        max-height: 440px;
        overflow-y: auto;
        padding: 10px;
    }

    .lmsout-live-search__loading,
    .lmsout-live-search__empty {
        padding: 26px 16px;
        text-align: center;
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
    }

    .lmsout-live-search__loading i {
        color: #013ba6;
        margin-right: 6px;
    }

    .lmsout-live-search__section {
        margin-bottom: 10px;
    }

    .lmsout-live-search__section:last-child {
        margin-bottom: 0;
    }

    .lmsout-live-search__heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 4px 8px 6px;
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: #64748b;
    }

    .lmsout-live-search__item {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 8px 10px;
        border-radius: 10px;
        text-decoration: none !important;
        color: #0f172a;
        transition: background 0.15s ease;
    }

    .lmsout-live-search__item:hover,
    .lmsout-live-search__item.is-active {
        background: #f0f7ff;
    }

    .lmsout-live-search__thumb {
        width: 48px;
        height: 36px;
        border-radius: 7px;
        object-fit: cover;
        flex-shrink: 0;
        background: #f1f5f9;
    }

    .lmsout-live-search__avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        background: #f1f5f9;
    }

    .lmsout-live-search__info {
        flex: 1;
        min-width: 0;
    }

    .lmsout-live-search__title {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.3;
    }

    .lmsout-live-search__item:hover .lmsout-live-search__title,
    .lmsout-live-search__item.is-active .lmsout-live-search__title {
        color: #013ba6;
    }

    .lmsout-live-search__meta {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11.5px;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .lmsout-live-search__badge {
        display: inline-block;
        padding: 1px 6px;
        border-radius: 4px;
        background: rgba(1, 59, 166, 0.08);
        color: #013ba6;
        font-size: 10.5px;
        font-weight: 700;
    }

    .lmsout-live-search__price {
        font-size: 12px;
        font-weight: 800;
        color: #0f172a;
        flex-shrink: 0;
    }

    .lmsout-live-search__price.is-free {
        color: #16a34a;
    }

    .lmsout-live-search__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 9px 12px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        font-size: 12px;
        font-weight: 700;
    }

    .lmsout-live-search__footer a {
        color: #013ba6;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .lmsout-live-search__footer a:hover {
        color: #228ddd;
    }

    @media (max-width: 991px) {
        .lmsout-live-search__form {
            width: 165px;
        }
        .lmsout-live-search.is-open .lmsout-live-search__form,
        .lmsout-live-search__form:focus-within {
            width: 210px;
        }
        .lmsout-live-search__kbd {
            display: none;
        }
    }

    @media (max-width: 575px) {
        .lmsout-live-search__form {
            width: 140px;
            padding: 0 8px;
        }
        .lmsout-live-search.is-open .lmsout-live-search__form,
        .lmsout-live-search__form:focus-within {
            width: 175px;
        }
        .lmsout-live-search__dropdown {
            position: fixed;
            top: 64px;
            left: 12px;
            right: 12px;
            width: auto;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('lmsout-live-search');
    const input = document.getElementById('lmsout-header-search-input');
    const clearBtn = document.getElementById('lmsout-live-search-clear');
    const kbdBadge = document.getElementById('lmsout-live-search-kbd');
    const dropdown = document.getElementById('lmsout-live-search-dropdown');
    const bodyEl = document.getElementById('lmsout-live-search-body');

    if (!container || !input || !dropdown || !bodyEl) {
        return;
    }

    const endpoint = container.getAttribute('data-endpoint') || '/live-search';
    let debounceTimer = null;
    let currentController = null;
    let activeIndex = -1;
    let hasLoadedInitial = false;

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function syncClearAndKbd() {
        const hasVal = input.value.trim().length > 0;
        if (clearBtn) {
            clearBtn.classList.toggle('d-none', !hasVal);
        }
        if (kbdBadge) {
            kbdBadge.classList.toggle('d-none', hasVal);
        }
    }

    function openDropdown() {
        container.classList.add('is-open');
        input.setAttribute('aria-expanded', 'true');
    }

    function closeDropdown() {
        container.classList.remove('is-open');
        input.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
    }

    function renderResults(data) {
        const courses = Array.isArray(data.courses) ? data.courses : [];
        const instructors = Array.isArray(data.instructors) ? data.instructors : [];
        const query = (data.query || '').trim();

        if (courses.length === 0 && instructors.length === 0) {
            bodyEl.innerHTML = `
                <div class="lmsout-live-search__empty">
                    <i class="fa-solid fa-magnifying-glass mb-2 d-block" style="font-size:18px;color:#94a3b8;"></i>
                    No results found for <strong>"${escapeHtml(query)}"</strong>
                </div>
            `;
            return;
        }

        let html = '';

        if (courses.length > 0) {
            html += `<div class="lmsout-live-search__section">
                <div class="lmsout-live-search__heading">
                    <span>${query ? 'Matching Courses' : 'Popular Courses'}</span>
                    <span>${courses.length}</span>
                </div>`;
            courses.forEach(function (c) {
                html += `
                    <a href="${escapeHtml(c.url)}" class="lmsout-live-search__item" role="option">
                        <img src="${escapeHtml(c.thumbnail)}" alt="${escapeHtml(c.title)}" class="lmsout-live-search__thumb" loading="lazy">
                        <div class="lmsout-live-search__info">
                            <div class="lmsout-live-search__title">${escapeHtml(c.title)}</div>
                            <div class="lmsout-live-search__meta">
                                <span class="lmsout-live-search__badge">${escapeHtml(c.category)}</span>
                                <span>&middot; ${escapeHtml(c.instructor)}</span>
                            </div>
                        </div>
                        <span class="lmsout-live-search__price ${c.is_free ? 'is-free' : ''}">${escapeHtml(c.price_label)}</span>
                    </a>
                `;
            });
            html += `</div>`;
        }

        if (instructors.length > 0) {
            html += `<div class="lmsout-live-search__section">
                <div class="lmsout-live-search__heading">
                    <span>Instructors</span>
                    <span>${instructors.length}</span>
                </div>`;
            instructors.forEach(function (inst) {
                html += `
                    <a href="${escapeHtml(inst.url)}" class="lmsout-live-search__item" role="option">
                        <img src="${escapeHtml(inst.avatar)}" alt="${escapeHtml(inst.name)}" class="lmsout-live-search__avatar" loading="lazy">
                        <div class="lmsout-live-search__info">
                            <div class="lmsout-live-search__title">${escapeHtml(inst.name)}</div>
                            <div class="lmsout-live-search__meta">
                                <span>${escapeHtml(inst.headline)}</span>
                                <span>&middot; ${inst.courses_count} courses</span>
                            </div>
                        </div>
                    </a>
                `;
            });
            html += `</div>`;
        }

        bodyEl.innerHTML = html;

        const existingFooter = dropdown.querySelector('.lmsout-live-search__footer');
        if (existingFooter) {
            existingFooter.remove();
        }

        const footerEl = document.createElement('div');
        footerEl.className = 'lmsout-live-search__footer';
        footerEl.innerHTML = `
            <a href="${escapeHtml(data.all_courses_url || '/courses')}">
                ${query ? `Explore all courses for "${escapeHtml(query)}"` : 'Browse all courses'} <i class="fa-solid fa-arrow-right"></i>
            </a>
        `;
        dropdown.appendChild(footerEl);
        activeIndex = -1;
    }

    function performLiveSearch(query) {
        if (currentController) {
            currentController.abort();
        }
        currentController = new AbortController();

        bodyEl.innerHTML = `
            <div class="lmsout-live-search__loading">
                <i class="fa-solid fa-circle-notch fa-spin"></i> Searching...
            </div>
        `;

        const url = endpoint + '?q=' + encodeURIComponent(query);
        fetch(url, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: currentController.signal
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                hasLoadedInitial = true;
                renderResults(data);
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                bodyEl.innerHTML = `<div class="lmsout-live-search__empty">Unable to load search results right now.</div>`;
            });
    }

    input.addEventListener('focus', function () {
        syncClearAndKbd();
        openDropdown();
        if (!hasLoadedInitial) {
            performLiveSearch(input.value.trim());
        }
    });

    input.addEventListener('input', function () {
        syncClearAndKbd();
        openDropdown();
        clearTimeout(debounceTimer);
        const q = input.value.trim();
        debounceTimer = setTimeout(function () {
            performLiveSearch(q);
        }, 180);
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            input.value = '';
            syncClearAndKbd();
            input.focus();
            performLiveSearch('');
        });
    }

    // Keyboard navigation (Up/Down/Escape & Cmd+K / Ctrl+K)
    input.addEventListener('keydown', function (e) {
        const items = Array.from(bodyEl.querySelectorAll('.lmsout-live-search__item'));
        if (e.key === 'Escape') {
            closeDropdown();
            input.blur();
            return;
        }
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = (activeIndex + 1) % items.length;
            items.forEach((el, idx) => el.classList.toggle('is-active', idx === activeIndex));
            items[activeIndex].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = (activeIndex - 1 + items.length) % items.length;
            items.forEach((el, idx) => el.classList.toggle('is-active', idx === activeIndex));
            items[activeIndex].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && activeIndex >= 0 && items[activeIndex]) {
            e.preventDefault();
            window.location.href = items[activeIndex].getAttribute('href');
        }
    });

    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            input.focus();
            input.select();
        }
    });

    document.addEventListener('click', function (e) {
        if (!container.contains(e.target)) {
            closeDropdown();
        }
    });

    syncClearAndKbd();
});
</script>
