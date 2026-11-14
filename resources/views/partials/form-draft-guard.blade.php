@auth
<script>
(function () {
    const TTL_MS = 15 * 60 * 1000;
    const pathKey = 'outlms_draft_' + window.location.pathname;

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function (form, idx) {
            if (form.dataset.noDraft === '1' || form.querySelector('input[type="password"]')) {
                return;
            }
            const storageKey = pathKey + '_f' + idx;
            try {
                const raw = localStorage.getItem(storageKey);
                if (raw) {
                    const parsed = JSON.parse(raw);
                    if (parsed && parsed.expiresAt > Date.now() && parsed.fields) {
                        Object.keys(parsed.fields).forEach(function (name) {
                            const el = form.querySelector('[name="' + CSS.escape(name) + '"]');
                            if (el && !el.value && el.type !== 'hidden' && el.type !== 'file') {
                                el.value = parsed.fields[name];
                            }
                        });
                    } else {
                        localStorage.removeItem(storageKey);
                    }
                }
            } catch (e) {}

            form.addEventListener('input', function () {
                const fields = {};
                Array.from(form.elements).forEach(function (el) {
                    if (!el.name || el.name === '_token' || el.name === '_method' || el.type === 'password' || el.type === 'file') {
                        return;
                    }
                    if (el.type === 'checkbox' || el.type === 'radio') {
                        if (el.checked) fields[el.name] = el.value;
                    } else {
                        fields[el.name] = el.value;
                    }
                });
                try {
                    localStorage.setItem(storageKey, JSON.stringify({
                        expiresAt: Date.now() + TTL_MS,
                        fields: fields
                    }));
                } catch (e) {}
            });

            form.addEventListener('submit', function () {
                try { localStorage.removeItem(storageKey); } catch (e) {}
            });
        });
    });
})();
</script>
@endauth
