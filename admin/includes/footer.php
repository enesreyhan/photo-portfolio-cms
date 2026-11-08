<?php
/**
 * admin/includes/footer.php
 * Yönetim Paneli Alt Bilgi ve Ortak JS Betikleri
 */
?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
// Tab Geçişleri
function setupTabs() {
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tabId = this.getAttribute('data-tab');
            var container = this.closest('.tabs-container') || document;
            container.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
            container.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('active'); });
            this.classList.add('active');
            var target = document.getElementById(tabId);
            if (target) target.classList.add('active');
            
            // Eğer EasyMDE varsa yeniden boyutlandır
            if (window.easyMDE_en) window.easyMDE_en.codemirror.refresh();
            if (window.easyMDE_tr) window.easyMDE_tr.codemirror.refresh();
        });
    });
}
document.addEventListener('DOMContentLoaded', setupTabs);

// Sol Sidebar Aç / Kapat (html + body tam senkronize)
function setAdminSidebarCollapsed(collapse) {
    document.body.classList.add('sidebar-toggling');
    if (collapse) {
        document.body.classList.add('sidebar-collapsed');
        document.documentElement.classList.add('sidebar-collapsed');
    } else {
        document.body.classList.remove('sidebar-collapsed');
        document.documentElement.classList.remove('sidebar-collapsed');
        document.documentElement.classList.remove('sidebar-collapsed-init');
    }
    try {
        localStorage.setItem('admin_sidebar_collapsed', collapse ? 'true' : 'false');
    } catch (e) {}
    updateSidebarToggleBtn();
    setTimeout(function() {
        document.body.classList.remove('sidebar-toggling');
    }, 250);
}

// Dar ekranda yana kayan alt sekme çubuğunda etkin sekmeyi görünür alana getir
document.addEventListener('DOMContentLoaded', function () {
    var act = document.querySelector('.subnav-tabs .subnav-tab.is-active');
    var bar = act && act.parentElement;
    if (bar && bar.scrollWidth > bar.clientWidth) {
        bar.scrollLeft = act.offsetLeft - (bar.clientWidth - act.offsetWidth) / 2;
    }
});

// Telefonda (≤ 900px) sol menü soldan açılan çekmecedir
function isPhoneLayout() {
    return window.matchMedia && window.matchMedia('(max-width: 900px)').matches;
}
function closeMobileNav() {
    document.documentElement.classList.remove('mobile-nav-open');
}
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeMobileNav();
});
document.addEventListener('click', function (e) {
    // Menüden bir sayfaya geçince çekmeceyi kapat
    if (e.target.closest && e.target.closest('.admin-sidebar a.nav-item')) closeMobileNav();
});
window.addEventListener('resize', function () {
    if (!isPhoneLayout()) closeMobileNav();
});

function toggleAdminSidebar() {
    if (isPhoneLayout()) {
        document.documentElement.classList.toggle('mobile-nav-open');
        return;
    }
    var currentlyCollapsed = document.body.classList.contains('sidebar-collapsed') || document.documentElement.classList.contains('sidebar-collapsed');
    setAdminSidebarCollapsed(!currentlyCollapsed);
}

// ── Aydınlık / Karanlık Tema Değiştirme (Zero-Flicker & Persist) ──
function toggleAdminTheme() {
    document.documentElement.classList.add('theme-toggling');
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var nextTheme = isDark ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', nextTheme);
    try {
        localStorage.setItem('admin_theme', nextTheme);
        document.cookie = 'admin_theme=' + nextTheme + '; path=/; max-age=31536000; SameSite=Lax';
    } catch (e) {}

    // Eğer sayfada EasyMDE / CodeMirror varsa yenile
    if (window.easyMDE_en && window.easyMDE_en.codemirror) window.easyMDE_en.codemirror.refresh();
    if (window.easyMDE_tr && window.easyMDE_tr.codemirror) window.easyMDE_tr.codemirror.refresh();

    setTimeout(function() {
        document.documentElement.classList.remove('theme-toggling');
    }, 240);
}

// Kısayol Tuşları: Ctrl+Shift+D (Tema), Ctrl+[ (Sidebar)
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'D' || e.key === 'd')) {
        e.preventDefault();
        toggleAdminTheme();
    }
});

function updateSidebarToggleBtn() {
    var isCollapsed = document.body.classList.contains('sidebar-collapsed');
    var isEn = window.ADMIN_LANG === 'en';
    var btn = document.getElementById('sidebarToggleBtn');
    var label = document.getElementById('sidebarToggleLabel');
    if (btn) {
        if (isCollapsed) {
            btn.classList.add('btn-primary');
            btn.classList.remove('btn-secondary');
            if (label) label.textContent = isEn ? 'Expand' : 'Genişlet';
            btn.title = isEn ? 'Expand Sidebar (Ctrl+[)' : 'Sol Menüyü Genişlet (Ctrl+[)';
        } else {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
            if (label) label.textContent = isEn ? 'Collapse' : 'Daralt';
            btn.title = isEn ? 'Collapse Sidebar (Ctrl+[)' : 'Sol Menüyü Daralt (Ctrl+[)';
        }
    }
    var collapseBtn = document.getElementById('sidebarCollapseBtn');
    if (collapseBtn) {
        collapseBtn.title = isCollapsed 
            ? (isEn ? 'Expand Sidebar (Ctrl+[)' : 'Sol Menüyü Genişlet (Ctrl+[)')
            : (isEn ? 'Collapse Sidebar (Ctrl+[)' : 'Sol Menüyü Daralt (Ctrl+[)');
    }
}
document.addEventListener('DOMContentLoaded', updateSidebarToggleBtn);

// ── Evrensel Canlı Arama & ⌘+K Dropdown Sistemi ──
var adminSearchTimer = null;
var adminSearchSelectedIndex = -1;

function initGlobalSearch() {
    var input = document.getElementById('adminQuickSearchInput');
    var dropdown = document.getElementById('adminSearchResultsDropdown');
    if (!input || !dropdown) return;

    input.addEventListener('input', function() {
        var q = this.value.trim();
        filterAdminSidebarMenu(q);

        clearTimeout(adminSearchTimer);
        if (q.length === 0) {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
            adminSearchSelectedIndex = -1;
            return;
        }

        adminSearchTimer = setTimeout(function() {
            fetch('api-search.php?q=' + encodeURIComponent(q))
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (!data.success || !data.results) {
                        dropdown.style.display = 'none';
                        return;
                    }
                    renderSearchResults(data.results, q);
                })
                .catch(function(err) {
                    console.error('Search error:', err);
                });
        }, 160);
    });

    input.addEventListener('focus', function() {
        if (this.value.trim().length > 0 && dropdown.children.length > 0) {
            dropdown.style.display = 'block';
        }
    });

    input.addEventListener('keydown', function(e) {
        var items = dropdown.querySelectorAll('.admin-search-item');
        if (dropdown.style.display !== 'block' || items.length === 0) {
            if (e.key === 'Escape') {
                input.blur();
                dropdown.style.display = 'none';
            }
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            adminSearchSelectedIndex = (adminSearchSelectedIndex + 1) % items.length;
            updateSearchSelected(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            adminSearchSelectedIndex = (adminSearchSelectedIndex - 1 + items.length) % items.length;
            updateSearchSelected(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (adminSearchSelectedIndex >= 0 && items[adminSearchSelectedIndex]) {
                items[adminSearchSelectedIndex].click();
            } else if (items.length > 0) {
                items[0].click();
            }
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
            input.blur();
        }
    });

    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}

function updateSearchSelected(items) {
    items.forEach(function(item, idx) {
        if (idx === adminSearchSelectedIndex) {
            item.classList.add('selected');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('selected');
        }
    });
}

function renderSearchResults(results, q) {
    var dropdown = document.getElementById('adminSearchResultsDropdown');
    if (!dropdown) return;

    if (results.length === 0) {
        var emptyMsg = window.ADMIN_LANG === 'en'
            ? '"<strong>' + escapeHtml(q) + '</strong>" - no results found.'
            : '"<strong>' + escapeHtml(q) + '</strong>" ile eşleşen sonuç bulunamadı.';
        dropdown.innerHTML = '<div class="admin-search-empty">' + emptyMsg + '</div>';
        dropdown.style.display = 'block';
        adminSearchSelectedIndex = -1;
        return;
    }

    var html = '';
    results.forEach(function(item) {
        var badgeClass = 'badge-' + (item.itemType || item.type || 'neutral');
        var mediaHtml = '';
        if (item.thumb) {
            mediaHtml = '<img src="' + escapeHtml(item.thumb) + '" class="admin-search-thumb" alt="">';
        } else {
            mediaHtml = '<div class="admin-search-icon-box">' +
                '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>' +
                '</div>';
        }

        var targetAttr = item.external ? ' target="_blank" rel="noopener noreferrer"' : '';
        html += '<a href="' + escapeHtml(item.url) + '" class="admin-search-item"' + targetAttr + '>' +
            mediaHtml +
            '<div class="admin-search-info">' +
                '<div class="admin-search-title">' +
                    '<span>' + escapeHtml(item.title) + '</span>' +
                    '<span class="admin-search-badge ' + badgeClass + '">' + escapeHtml(item.badge || '') + '</span>' +
                '</div>' +
                '<div class="admin-search-sub">' + escapeHtml(item.sub || '') + '</div>' +
            '</div>' +
        '</a>';
    });

    dropdown.innerHTML = html;
    dropdown.style.display = 'block';
    adminSearchSelectedIndex = -1;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function filterAdminSidebarMenu(query) {
    var q = (query || '').toLowerCase().trim();
    if (q === '') {
        document.querySelectorAll('.sidebar-nav .nav-item').forEach(function(item) {
            item.style.display = 'flex';
        });
        document.querySelectorAll('.nav-group').forEach(function(group) {
            group.style.display = '';
            var id = group.getAttribute('data-group-id');
            var savedCollapsed = [];
            try {
                savedCollapsed = JSON.parse(localStorage.getItem('admin_collapsed_nav_groups') || '[]');
            } catch(e) {}
            var hasActive = group.querySelector('.nav-item.active');
            if (savedCollapsed.indexOf(id) !== -1 && !hasActive) {
                group.classList.add('is-collapsed');
                var btn = group.querySelector('.nav-group-toggle');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            } else {
                group.classList.remove('is-collapsed');
                var btn = group.querySelector('.nav-group-toggle');
                if (btn) btn.setAttribute('aria-expanded', 'true');
            }
        });
        return;
    }

    document.querySelectorAll('.nav-group').forEach(function(group) {
        var items = group.querySelectorAll('.nav-item');
        var groupMatchCount = 0;
        items.forEach(function(item) {
            var text = item.textContent.toLowerCase();
            if (text.indexOf(q) !== -1) {
                item.style.display = 'flex';
                groupMatchCount++;
            } else {
                item.style.display = 'none';
            }
        });
        if (groupMatchCount > 0) {
            group.style.display = '';
            group.classList.remove('is-collapsed');
            var btn = group.querySelector('.nav-group-toggle');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        } else {
            group.style.display = 'none';
        }
    });
}
document.addEventListener('DOMContentLoaded', initGlobalSearch);

// Klavye Kısayolları (Ctrl+S, Ctrl+[, Ctrl+], ⌘+K)
window.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        var searchInput = document.getElementById('adminQuickSearchInput');
        if (searchInput) {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    } else if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        var form = document.querySelector('form.save-form');
        if (form) {
            e.preventDefault();
            if (typeof prepareAboutSubmit === 'function') prepareAboutSubmit();
            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }
    } else if ((e.ctrlKey || e.metaKey) && e.key === '[') {
        e.preventDefault();
        toggleAdminSidebar();
    } else if ((e.ctrlKey || e.metaKey) && e.key === ']') {
        if (typeof toggleItemRightPanel === 'function') {
            e.preventDefault();
            toggleItemRightPanel();
        }
    }
});

// Tablo Satırlarını Sürükle-Bırak Yeniden Sıralama (Sortable Admin Tables)
function initSortableTables() {
    if (typeof Sortable === 'undefined') return;
    
    document.querySelectorAll('table[data-reorder="true"]').forEach(function(table) {
        var tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        new Sortable(tbody, {
            handle: '.drag-handle',
            animation: 160,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function() {
                var rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
                var ids = [];
                rows.forEach(function(row, idx) {
                    var id = row.getAttribute('data-id');
                    if (id) ids.push(id);
                    var weightEl = row.querySelector('.weight-label');
                    if (weightEl) weightEl.textContent = idx + 1;
                });
                
                if (ids.length === 0) return;
                
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                var csrfInput = document.querySelector('input[name="csrf_token"]');
                var csrf = csrfMeta ? csrfMeta.getAttribute('content') : (csrfInput ? csrfInput.value : '');
                
                var target = table.getAttribute('data-target') || 'items';
                var formData = new FormData();
                formData.append('csrf_token', csrf);
                formData.append('target', target);
                ids.forEach(function(id) {
                    formData.append('ids[]', id);
                });
                
                fetch('api-reorder.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        var msg = (window.ADMIN_LANG === 'en') ? <?= json_encode(__a('reorder_success', 'Order saved successfully.')) ?> : <?= json_encode(__a('reorder_success', 'Sıralama başarıyla kaydedildi.')) ?>;
                        showAdminToast(msg);
                    } else {
                        var errTitle = (window.ADMIN_LANG === 'en') ? 'Could not save order: ' : 'Sıralama kaydedilemedi: ';
                        alert(errTitle + (data.error || 'Error'));
                    }
                })
                .catch(function(err) {
                    console.error('Reorder error:', err);
                });
            }
        });
    });
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSortableTables);
} else {
    initSortableTables();
}

</script>

<!-- ══ Ortak geri bildirim: onay penceresi, metin isteme ve bildirim şeridi ══
     JS: adminConfirm(mesaj, {title, ok, danger}) → Promise<bool>
         adminPrompt(mesaj, varsayılan)            → Promise<string|null>
         adminToast(mesaj, 'success'|'error'|'info')
     HTML: <form data-confirm="…"> ya da <a data-confirm="…"> onaydan sonra gönderilir / açılır.
     alert() bildirim şeridine yönlendirilir; sayfa hemen yenilense de mesaj kaybolmaz. -->
<dialog id="adminDialog" class="admin-dialog" aria-labelledby="adminDialogTitle" aria-describedby="adminDialogMsg">
    <form method="dialog" class="admin-dialog-inner">
        <h2 id="adminDialogTitle" class="admin-dialog-title"></h2>
        <p id="adminDialogMsg" class="admin-dialog-msg"></p>
        <input type="text" id="adminDialogInput" class="form-control" hidden>
        <div class="admin-dialog-actions">
            <button type="button" class="btn btn-secondary" id="adminDialogCancel"><?= e(__a('Vazgeç', 'Cancel')) ?></button>
            <button type="submit" class="btn btn-primary" value="ok" id="adminDialogOk"></button>
        </div>
    </form>
</dialog>
<div id="adminToastStack" class="admin-toast-stack" role="status" aria-live="polite"></div>
<style>
.admin-dialog { border: 1px solid var(--border-subtle); border-radius: var(--r-md); padding: 0; width: calc(100% - 32px); max-width: 440px; background: var(--bg-surface); color: var(--text-main); box-shadow: 0 24px 48px rgba(0, 0, 0, 0.22); margin: auto; }
.admin-dialog::backdrop { background: rgba(15, 23, 42, 0.5); }
.admin-dialog-inner { padding: var(--sp-5); display: grid; gap: var(--sp-3); }
.admin-dialog-title { font-size: var(--fs-lg); font-weight: 700; margin: 0; }
.admin-dialog-msg { font-size: var(--fs-md); line-height: 1.55; color: var(--text-muted); margin: 0; white-space: pre-line; }
.admin-dialog-msg:empty { display: none; }
.admin-dialog-actions { display: flex; justify-content: flex-end; gap: var(--sp-2); padding-top: var(--sp-2); }
.admin-dialog .btn-danger-solid, .admin-dialog .btn-danger-solid:hover { background: var(--danger); border-color: var(--danger); color: #fff; }
.admin-toast-stack { position: fixed; right: max(20px, env(safe-area-inset-right)); bottom: max(20px, env(safe-area-inset-bottom)); z-index: 100000; display: flex; flex-direction: column; align-items: flex-end; gap: var(--sp-2); pointer-events: none; }
.admin-toast { display: flex; align-items: flex-start; gap: var(--sp-2); max-width: min(420px, calc(100vw - 40px)); padding: 10px 14px; border-radius: var(--r-md); background: var(--bg-surface); border: 1px solid var(--border-light); box-shadow: 0 8px 24px rgba(15, 23, 42, 0.16); font-size: var(--fs-sm); font-weight: 600; color: var(--text-main); white-space: pre-line; opacity: 0; transform: translateY(6px); transition: opacity 0.18s ease, transform 0.18s ease; pointer-events: auto; }
.admin-toast.is-visible { opacity: 1; transform: none; }
.admin-toast::before { content: ""; flex-shrink: 0; width: 8px; height: 8px; margin-top: 5px; border-radius: 50%; background: var(--success); }
.admin-toast.is-error::before { background: var(--danger); }
.admin-toast.is-info::before { background: var(--accent-blue); }
</style>
<script>
(function () {
    var EN = window.ADMIN_LANG === 'en';
    var dlg = document.getElementById('adminDialog'), titleEl = document.getElementById('adminDialogTitle'), msgEl = document.getElementById('adminDialogMsg');
    var input = document.getElementById('adminDialogInput'), okBtn = document.getElementById('adminDialogOk'), cancelBtn = document.getElementById('adminDialogCancel');
    var nativeConfirm = window.confirm.bind(window), nativePrompt = window.prompt.bind(window);
    var queue = Promise.resolve();

    // Tek pencere; art arda çağrılar sıraya girer
    function open(o) {
        var run = function () {
            return new Promise(function (resolve) {
                if (!dlg || typeof dlg.showModal !== 'function') {
                    return resolve(o.prompt ? nativePrompt(o.message, o.value || '') : nativeConfirm(o.message));
                }
                titleEl.textContent = o.title || (o.prompt ? '' : (EN ? 'Are you sure?' : 'Emin misiniz?'));
                titleEl.hidden = !titleEl.textContent;
                msgEl.textContent = o.message || '';
                input.hidden = !o.prompt;
                input.value = o.value || '';
                okBtn.textContent = o.ok || (o.danger ? (EN ? 'Delete' : 'Sil') : (EN ? 'OK' : 'Tamam'));
                okBtn.classList.toggle('btn-danger-solid', !!o.danger);
                var onClose = function () {
                    dlg.removeEventListener('close', onClose);
                    var ok = dlg.returnValue === 'ok';
                    resolve(o.prompt ? (ok ? input.value : null) : ok);
                };
                dlg.returnValue = '';
                dlg.addEventListener('close', onClose);
                dlg.showModal();
                (o.prompt ? input : (o.danger ? cancelBtn : okBtn)).focus();
                if (o.prompt) input.select();
            });
        };
        var p = queue.then(run);
        queue = p.catch(function () {});
        return p;
    }
    if (dlg) {
        cancelBtn.addEventListener('click', function () { dlg.close('cancel'); });
        dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close('cancel'); });
    }

    window.adminConfirm = function (message, opts) {
        opts = opts || {};
        // Silme / kaldırma / sıfırlama sözcükleri geçiyorsa onay düğmesi kırmızı olur
        var danger = 'danger' in opts ? opts.danger : /\bsil|kaldır|boşalt|sıfırla|delete|remove|clear|reset|discard/i.test(String(message) + ' ' + (opts.ok || ''));
        return open({ message: String(message || ''), title: opts.title, ok: opts.ok, danger: danger });
    };
    window.adminPrompt = function (message, value) { return open({ prompt: true, title: String(message || ''), value: value }); };
    // Zengin metin editörüne bağlantı: pencere açılınca kaybolan seçim geri yüklenir, güvensiz adresler reddedilir
    window.adminPromptLink = function (message) {
        var sel = window.getSelection(), range = sel && sel.rangeCount ? sel.getRangeAt(0).cloneRange() : null;
        var host = range && (range.commonAncestorContainer.nodeType === 1 ? range.commonAncestorContainer : range.commonAncestorContainer.parentNode);
        var editable = host && host.closest ? host.closest('[contenteditable="true"]') : null;
        return window.adminPrompt(message || (EN ? 'Link address' : 'Bağlantı adresi'), 'https://').then(function (url) {
            url = (url || '').trim();
            if (!url || url === 'https://') return null;
            if (!/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(url)) {
                window.adminToast(EN ? 'The address must start with https://, / or mailto:' : 'Adres https://, / ya da mailto: ile başlamalı.', 'error');
                return null;
            }
            if (editable) editable.focus();
            if (range) { sel.removeAllRanges(); sel.addRange(range); }
            return url;
        });
    };

    // ── Bildirim şeridi ──
    var stack = document.getElementById('adminToastStack');
    window.adminToast = function (message, type) {
        if (type === true) type = 'error';
        var raw = String(message == null || message === '' ? (EN ? 'Saved' : 'Kaydedildi') : message);
        if (!type) type = /hata|başarısız|kaydedilemedi|geçersiz|bulunamadı|error|failed|invalid|could not|⚠|❌/i.test(raw) ? 'error' : 'success';
        var text = raw.replace(/^[\s✓✅⚠️❌]+/u, '');
        if (!stack) return;
        var t = document.createElement('div');
        t.className = 'admin-toast' + (type === 'error' ? ' is-error' : type === 'info' ? ' is-info' : '');
        t.textContent = text;
        stack.appendChild(t);
        // rAF arka plandaki sekmede çalışmaz; yerleşimi zorlayıp sınıfı hemen ekle (geçiş yine oynar)
        void t.offsetWidth;
        t.classList.add('is-visible');
        var life = Math.min(9000, 2600 + text.length * 40);
        t._msg = text; t._type = type; t._until = Date.now() + life;
        setTimeout(function () { t.classList.remove('is-visible'); setTimeout(function () { t.remove(); }, 220); }, life);
    };
    // Sayfa hemen yenilenirse ya da başka sayfaya geçilirse görünen bildirimler bir sonraki sayfada sürer
    window.addEventListener('pagehide', function () {
        var left = [].slice.call(stack ? stack.children : []).filter(function (t) { return t._until - Date.now() > 600; }).map(function (t) { return [t._msg, t._type]; });
        try { if (left.length) sessionStorage.setItem('adminToasts', JSON.stringify(left)); } catch (e) {}
    });
    try {
        var saved = JSON.parse(sessionStorage.getItem('adminToasts') || '[]');
        sessionStorage.removeItem('adminToasts');
        saved.forEach(function (x) { window.adminToast(x[0], x[1]); });
    } catch (e) {}
    window.showAdminToast = function (msg) { window.adminToast(msg, 'success'); };
    window.alert = function (msg) { window.adminToast(msg); };

    // ── data-confirm: formlar ve bağlantılar ──
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f.matches || !f.matches('form[data-confirm]') || f.dataset.confirmed === '1') return;
        e.preventDefault();
        e.stopImmediatePropagation();
        var sub = e.submitter;
        window.adminConfirm(f.getAttribute('data-confirm'), { ok: f.getAttribute('data-confirm-ok') || undefined }).then(function (ok) {
            if (!ok) return;
            f.dataset.confirmed = '1';
            if (typeof f.requestSubmit === 'function') { sub ? f.requestSubmit(sub) : f.requestSubmit(); } else { f.submit(); }
            delete f.dataset.confirmed;
        });
    }, true);
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[data-confirm]');
        if (!a) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        window.adminConfirm(a.getAttribute('data-confirm'), { ok: a.getAttribute('data-confirm-ok') || undefined }).then(function (ok) {
            if (!ok) return;
            if (a.target === '_blank') window.open(a.href, '_blank', 'noopener'); else location.href = a.href;
        });
    }, true);

    // Tasarım "Varsayılana sıfırla" formları onaysız gönderilmesin
    document.querySelectorAll('form').forEach(function (f) {
        if (f.hasAttribute('data-confirm') || !f.querySelector('input[name="action"][value="reset_defaults"]')) return;
        f.removeAttribute('onsubmit');
        f.setAttribute('data-confirm', EN ? 'All design customisations in this template will return to their defaults.' : 'Bu şablondaki bütün tasarım ayarları varsayılana döner.');
        f.setAttribute('data-confirm-ok', EN ? 'Reset to defaults' : 'Varsayılana sıfırla');
    });
})();
</script>

<!-- ══ Kaydedilmemiş Değişiklik Koruması + Sabit Kaydet Çubuğu ══ -->
<div id="unsavedBar" class="unsaved-bar" role="status" aria-live="polite" hidden>
    <span class="unsaved-bar-dot" aria-hidden="true"></span>
    <span class="unsaved-bar-text"><?= e(__a('Kaydedilmemiş değişiklikler var', 'You have unsaved changes')) ?></span>
    <button type="button" class="btn btn-secondary btn-sm" id="unsavedBarDiscard"><?= e(__a('Geri Al', 'Discard')) ?></button>
    <button type="button" class="btn btn-primary btn-sm" id="unsavedBarSave"><?= e(__a('Kaydet', 'Save')) ?></button>
</div>
<style>
.unsaved-bar {
    position: fixed;
    bottom: 18px;
    left: calc(var(--sidebar-width) + (100vw - var(--sidebar-width)) / 2);
    transform: translateX(-50%);
    z-index: 950;
    display: flex; align-items: center; gap: 10px;
    padding: 9px 10px 9px 16px;
    background: var(--bg-surface);
    border: 1px solid var(--border-light);
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18);
    font-size: 13px; font-weight: 600; color: var(--text-main);
    width: max-content;
    max-width: calc(100vw - 32px);
    white-space: nowrap;
}
.unsaved-bar .btn { white-space: nowrap; }
.unsaved-bar[hidden] { display: none; }
html.sidebar-collapsed .unsaved-bar { left: calc(68px + (100vw - 68px) / 2); }
.unsaved-bar-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--warning, #f59e0b); flex-shrink: 0; }
@media (max-width: 900px) {
    .unsaved-bar, html.sidebar-collapsed .unsaved-bar { left: 50%; bottom: 12px; width: calc(100vw - 24px); white-space: normal; }
    .unsaved-bar-text { flex: 1; line-height: 1.3; }
}
</style>
<script>
(function () {
    // İzlenmeyen formlar: kendi kaydetme mantığı olanlar ve toplu seçim formları
    var SKIP_FORM_IDS = ['bulkForm', 'matrixForm'];
    var dirtyForms = new Set();
    var lastDirty = null;
    var submitting = false;
    var bar = document.getElementById('unsavedBar');

    // form.submit() kodla çağrıldığında submit olayı tetiklenmez; bayrağı burada kaldır
    var nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        submitting = true;
        return nativeSubmit.apply(this, arguments);
    };

    function isTrackedForm(form) {
        if (!form || !form.closest('.admin-content')) return false;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') return false;
        if (form.id && SKIP_FORM_IDS.indexOf(form.id) !== -1) return false;
        if (form.hasAttribute('data-no-dirty')) return false;
        return true;
    }
    function isTrackedField(el) {
        if (!el || (el.hasAttribute && el.hasAttribute('data-no-dirty'))) return false;
        if (el.isContentEditable) return true;
        if (!el.name) return false;                        // isimsiz alanlar yalnızca arayüz içindir
        var oc = el.getAttribute && (el.getAttribute('onchange') || '');
        // Anında (AJAX) kaydeden ya da hemen yükleyen kontroller form kaydı beklemez
        if (/toggleSiteModule|toggleSiteLanguage|uploadFiles|uploadDirectToProject|updateBulkBar|toggleSelectAll/.test(oc)) return false;
        if (el.type === 'hidden' || el.type === 'search') return false;
        return true;
    }
    function render() {
        if (!bar) return;
        bar.hidden = dirtyForms.size === 0;
    }
    function markDirty(e) {
        var el = e.target;
        if (!isTrackedField(el)) return;
        var form = el.form || (el.closest && el.closest('form'));
        if (!isTrackedForm(form)) return;
        dirtyForms.add(form);
        lastDirty = form;
        render();
    }
    document.addEventListener('input', markDirty, true);
    document.addEventListener('change', markDirty, true);

    // Sayfanın kendi doğrulaması gönderimi durdurmadıysa kaydediliyor say
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return;
        submitting = true;
    });

    window.addEventListener('beforeunload', function (e) {
        if (submitting || dirtyForms.size === 0) return;
        e.preventDefault();
        e.returnValue = '';
        return '';
    });

    function saveButtonOf(form) {
        var els = Array.prototype.slice.call(form.elements || []);
        return els.find(function (b) {
            return b.type === 'submit' && /kaydet|save|güncelle/i.test(b.textContent || b.value || '');
        }) || null;
    }
    document.getElementById('unsavedBarSave').addEventListener('click', function () {
        var form = lastDirty && document.body.contains(lastDirty) ? lastDirty : Array.from(dirtyForms)[0];
        if (!form) return;
        var btn = saveButtonOf(form);
        if (typeof form.requestSubmit === 'function') {
            btn ? form.requestSubmit(btn) : form.requestSubmit();
        } else {
            btn ? btn.click() : form.submit();
        }
    });
    document.getElementById('unsavedBarDiscard').addEventListener('click', function () {
        dirtyForms.clear();
        render();
        location.reload();
    });

    // name'siz dinamik editörlerin (ör. hikaye tuvali) formu kirli işaretleyebilmesi için
    window.adminMarkDirty = function (form) {
        if (!isTrackedForm(form)) return;
        dirtyForms.add(form);
        lastDirty = form;
        render();
    };

    // Diğer betiklerin (ör. AJAX kaydetme) bayrağı temizleyebilmesi için
    window.adminClearDirty = function (form) {
        if (form) { dirtyForms.delete(form); } else { dirtyForms.clear(); }
        render();
    };
})();

// ── Ortak bileşen: ⋯ satır menüsü (<details class="row-menu">) ──
// Dışarı tıklayınca ya da Esc ile kapanır; kaydırılan bir kutunun altına taşacaksa yukarı açılır.
(function () {
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.row-menu[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.row-menu[open]').forEach(function (d) { d.removeAttribute('open'); var s = d.querySelector('summary'); if (s) s.focus(); });
    });
    document.addEventListener('toggle', function (e) {
        var d = e.target;
        if (!d.classList || !d.classList.contains('row-menu') || !d.open) return;
        d.classList.remove('is-up');
        var list = d.querySelector('.row-menu-list'), box = d.closest('.table-responsive') || document.documentElement;
        if (list && list.getBoundingClientRect().bottom > Math.min(box.getBoundingClientRect().bottom, window.innerHeight)) d.classList.add('is-up');
    }, true);
})();

// ── Erişilebilirlik: yalnızca simgeden oluşan düğme ve bağlantılara, title'ından okunabilir ad ver ──
(function () {
    function name(root) {
        root.querySelectorAll('a[title]:not([aria-label]), button[title]:not([aria-label])').forEach(function (el) {
            if (!el.textContent.trim()) el.setAttribute('aria-label', el.getAttribute('title'));
        });
    }
    name(document);
    // Sonradan JS ile çizilen satırlar ve kartlar için de
    new MutationObserver(function (list) { list.forEach(function (m) { m.addedNodes.forEach(function (n) { if (n.nodeType === 1) name(n); }); }); })
        .observe(document.body, { childList: true, subtree: true });
})();
</script>
<?php
if (ob_get_level() > 0) {
    @ob_end_flush();
}
?>
</body>
</html>
