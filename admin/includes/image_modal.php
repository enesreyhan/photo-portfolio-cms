<?php
/**
 * admin/includes/image_modal.php
 * Klasör Destekli Görsel Seçici ve Yükleyici Modal Bileşeni (Editoryal Fotoğraf Galerisi Tasarımı)
 */
?>
<style>
#mediaModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.88);
    z-index: 999999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}
#mediaModalBox {
    background: var(--bg-surface) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 10px !important;
    width: 95% !important;
    max-width: 1120px !important;
    height: 90vh !important;
    max-height: 90vh !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5) !important;
}
#modalGalleryGrid {
    flex: 1 1 0% !important;
    min-height: 0 !important;
    overflow-y: auto !important;
    padding: 20px !important;
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)) !important;
    grid-auto-rows: 200px !important;
    gap: 16px !important;
    background: var(--bg-canvas-subtle) !important;
}
.modal-photo-card {
    background: var(--bg-surface) !important;
    border: 1px solid var(--border-subtle) !important;
    border-radius: 10px !important;
    overflow: hidden !important;
    cursor: pointer !important;
    display: flex !important;
    flex-direction: column !important;
    height: 200px !important;
    min-height: 200px !important;
    max-height: 200px !important;
    transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
}
.modal-photo-card:hover {
    border-color: #2563eb !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 20px rgba(37,99,235,0.18) !important;
}
.modal-photo-thumb-wrap {
    width: 100% !important;
    height: 135px !important;
    min-height: 135px !important;
    max-height: 135px !important;
    background: #0f172a !important;
    position: relative !important;
    overflow: hidden !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.modal-photo-img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    display: block !important;
}
.modal-photo-info {
    height: 65px !important;
    min-height: 65px !important;
    padding: 8px 10px !important;
    background: var(--bg-surface) !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    gap: 3px !important;
    border-top: 1px solid var(--border-subtle) !important;
}
.modal-photo-name {
    font-size: 12px !important;
    font-weight: 600 !important;
    color: var(--text-main) !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    line-height: 1.3 !important;
}
.modal-photo-meta {
    font-size: 12px !important;
    color: var(--text-muted) !important;
    font-family: monospace !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
}
</style>

<div id="mediaModal">
    <div id="mediaModalBox">
        <!-- Başlık -->
        <div style="padding:16px 24px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; background:var(--bg-surface-2);">
            <h3 style="font-size:16px; font-weight:700; color:var(--text-main); margin:0; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                <span><?= e(__a('media_modal_title')) ?></span>
            </h3>
            <button type="button" onclick="closeMediaModal()" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:24px; line-height:1; padding:4px 8px; border-radius:6px;" title="Kapat (ESC)">&times;</button>
        </div>

        <!-- Filtreleme & Arama Çubuğu -->
        <div style="padding:12px 24px; border-bottom:1px solid var(--border-subtle); display:flex; flex-wrap:wrap; gap:12px; align-items:center; background:var(--bg-surface);">
            <!-- Klasör -->
            <div style="display:flex; align-items:center; gap:8px;">
                <label style="font-size:12px; font-weight:600; color:var(--text-muted); white-space:nowrap;"><?= e(__a('media_modal_folder')) ?></label>
                <select id="modalFolderSelect" class="form-control" style="width:230px; font-size:13px; padding:6px 10px;" onchange="modalFolderChanged()">
                    <option value="all"><?= e(__a('media_modal_all_folders')) ?></option>
                </select>
            </div>

            <!-- Sıralama Kriteri -->
            <div style="display:flex; align-items:center; gap:8px;">
                <label style="font-size:12px; font-weight:600; color:var(--text-muted); white-space:nowrap;"><?= e(__a('media_modal_sort')) ?></label>
                <select id="modalSortSelect" class="form-control" style="width:230px; font-size:13px; padding:6px 10px;" onchange="modalSortChanged()">
                    <option value="shot_date_desc"><?= e(__a('sort_date_desc')) ?></option>
                    <option value="shot_date_asc"><?= e(__a('sort_date_asc')) ?></option>
                    <option value="newest" selected><?= e(__a('sort_newest')) ?></option>
                    <option value="oldest"><?= e(__a('sort_oldest')) ?></option>
                    <option value="name"><?= e(__a('sort_name_asc')) ?></option>
                    <option value="name_desc"><?= e(__a('sort_name_desc')) ?></option>
                    <option value="size"><?= e(__a('sort_size')) ?></option>
                </select>
            </div>

            <!-- Arama -->
            <div style="display:flex; align-items:center; gap:8px;">
                <input type="text" id="modalSearchInput" placeholder="<?= e(__a('media_modal_search_ph')) ?>" class="form-control" style="width:210px; padding:6px 12px; font-size:13px;" onkeyup="filterModalImages()">
            </div>
            
            <!-- Görsel Yükle -->
            <label class="btn btn-primary btn-sm" style="margin-left:auto; cursor:pointer; display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:6px; font-weight:600;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                <span><?= e(__a('media_modal_upload_btn')) ?></span>
                <input type="file" id="modalFileInput" accept="image/*" style="display:none;" onchange="uploadFromModal(this)">
            </label>
        </div>

        <!-- Görsel Izgarası -->
        <div id="modalGalleryGrid">
            <!-- JS ile doldurulacak -->
        </div>

        <!-- Alt Butonlar -->
        <div style="padding:12px 24px; border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; background:var(--bg-surface-2);">
            <div style="font-size:13px; color:var(--text-muted); font-family:var(--font-mono); font-weight:600;" id="modalCountText"><?= e(__a('media_modal_scanning')) ?></div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeMediaModal()" style="padding:6px 16px; border-radius:6px;"><?= e(__a('media_modal_cancel')) ?></button>
        </div>
    </div>
</div>

<script>
var targetInputId = null;
var activeEditorInstance = null;
var customImageCallback = null;
var modalActiveFolder = 'all';
var modalSortOrder = localStorage.getItem('admin_media_modal_sort') || 'shot_date_desc';
var currentModalImages = [];

function openMediaModalForInput(inputId) {
    targetInputId = inputId;
    activeEditorInstance = null;
    customImageCallback = null;
    modalActiveFolder = 'all';
    var sel = document.getElementById('modalFolderSelect');
    if (sel) sel.value = 'all';
    openModalCore();
}

function openMediaModalForEditor(editorInstance) {
    targetInputId = null;
    activeEditorInstance = editorInstance;
    customImageCallback = null;
    modalActiveFolder = 'all';
    var sel = document.getElementById('modalFolderSelect');
    if (sel) sel.value = 'all';
    openModalCore();
}

function openMediaModalForCallback(callback, defaultFolder) {
    targetInputId = null;
    activeEditorInstance = null;
    customImageCallback = callback;
    modalActiveFolder = defaultFolder || 'all';
    var sel = document.getElementById('modalFolderSelect');
    if (sel) sel.value = modalActiveFolder;
    openModalCore();
}

function openModalCore() {
    var modal = document.getElementById('mediaModal');
    if (modal) modal.style.display = 'flex';
    var searchInput = document.getElementById('modalSearchInput');
    if (searchInput) searchInput.value = '';
    var sortSelect = document.getElementById('modalSortSelect');
    if (sortSelect) {
        sortSelect.value = modalSortOrder || 'shot_date_desc';
    }
    loadModalMedia();
}

function closeMediaModal() {
    var modal = document.getElementById('mediaModal');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var modal = document.getElementById('mediaModal');
        if (modal && modal.style.display === 'flex') {
            closeMediaModal();
        }
    }
});

var modalEl = document.getElementById('mediaModal');
if (modalEl) {
    modalEl.addEventListener('click', function(e) {
        if (e.target === this) {
            closeMediaModal();
        }
    });
}

function modalFolderChanged() {
    modalActiveFolder = document.getElementById('modalFolderSelect').value;
    loadModalMedia();
}

function modalSortChanged() {
    var sel = document.getElementById('modalSortSelect');
    if (sel) {
        modalSortOrder = sel.value;
        try { localStorage.setItem('admin_media_modal_sort', modalSortOrder); } catch (e) {}
    }
    loadModalMedia();
}

function loadModalMedia() {
    var isEn = (window.ADMIN_LANG === 'en');
    var grid = document.getElementById('modalGalleryGrid');
    grid.innerHTML = '<div style="color:var(--text-muted); grid-column:1/-1; text-align:center; padding:50px; font-size:13px;">' + (isEn ? 'Loading photos...' : 'Görseller yükleniyor...') + '</div>';

    var sortVal = modalSortOrder || 'shot_date_desc';
    var url = '<?= url("admin/api-media.php") ?>?folder=' + encodeURIComponent(modalActiveFolder) + '&sort=' + encodeURIComponent(sortVal);

    fetch(url)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                populateModalFolders(data.categories);
                var images = data.images || [];

                if (images.length === 0 && modalActiveFolder !== 'all') {
                    grid.innerHTML = '<div style="color:var(--text-muted); grid-column:1/-1; text-align:center; padding:50px;">' +
                        '<p style="font-size:14px; font-weight:600; color:var(--text-main); margin-bottom:12px;">' + (isEn ? 'No photos found in this folder yet.' : 'Bu klasörde henüz görsel bulunamadı.') + '</p>' +
                        '<button type="button" class="btn btn-secondary btn-sm" onclick="switchToAllFolders()" style="padding:6px 16px;">' + (isEn ? 'Show All Photos' : 'Tüm Fotoğrafları Göster') + '</button>' +
                        '</div>';
                    document.getElementById('modalCountText').innerText = isEn ? '0 photos' : '0 görsel';
                    return;
                }

                renderModalImages(images);
                document.getElementById('modalCountText').innerText = images.length + (isEn ? ' photos listed' : ' görsel listelendi');
            } else {
                grid.innerHTML = '<div style="color:var(--danger-text); grid-column:1/-1; text-align:center; padding:40px;">' + (isEn ? 'Error: ' : 'Hata: ') + (data.error || (isEn ? 'Photos could not be loaded.' : 'Görseller alınamadı.')) + '</div>';
            }
        })
        .catch(function(err) {
            grid.innerHTML = '<div style="color:var(--danger-text); grid-column:1/-1; text-align:center; padding:40px;">' + (isEn ? 'Connection error: Photos could not be loaded from server.' : 'Bağlantı hatası: Görseller sunucudan yüklenemedi.') + '</div>';
        });
}

function switchToAllFolders() {
    modalActiveFolder = 'all';
    var sel = document.getElementById('modalFolderSelect');
    if (sel) sel.value = 'all';
    loadModalMedia();
}

function populateModalFolders(cats) {
    if (!cats) return;
    var isEn = (window.ADMIN_LANG === 'en');
    var sel = document.getElementById('modalFolderSelect');
    sel.innerHTML = '<option value="all">' + (isEn ? 'All Folders (Whole Library)' : 'Tüm Klasörler (Tüm Kütüphane)') + '</option>';

    function addOptGroup(label, list) {
        if (!list || !list.length) return;
        var grp = document.createElement('optgroup');
        grp.label = label;
        list.forEach(function(f) {
            var opt = document.createElement('option');
            opt.value = f.path;
            opt.innerText = f.name + ' (' + f.count + ')';
            grp.appendChild(opt);
        });
        sel.appendChild(grp);
    }

    Object.keys(cats).forEach(function(key) {
        if (cats[key] && cats[key].folders) {
            addOptGroup(cats[key].label || key, cats[key].folders);
        }
    });

    sel.value = modalActiveFolder || 'all';
}

function renderModalImages(images) {
    var isEn = (window.ADMIN_LANG === 'en');
    currentModalImages = images;
    var grid = document.getElementById('modalGalleryGrid');
    if (!images.length) {
        grid.innerHTML = '<div style="color:var(--text-muted); grid-column:1/-1; text-align:center; padding:40px;">' + (isEn ? 'No photos found.' : 'Görsel bulunamadı.') + '</div>';
        return;
    }

    grid.innerHTML = images.map(function(img, idx) {
        var rawName = (img.original_name || img.filename || '');
        var safeName = rawName.replace(/"/g, '&quot;');
        var dims = (img.width && img.height) ? (img.width + '×' + img.height) : (img.width ? img.width + 'px' : '');
        var dateBadge = img.shot_date || (img.created_at ? img.created_at.split(' ')[0] : '');
        var tooltip = safeName + (img.shot_date ? ' | ' + (isEn ? 'Date Taken: ' : 'Çekim Tarihi: ') + img.shot_date : '') + (img.camera ? ' | ' + img.camera : '');

        return '<div class="modal-photo-card" data-idx="' + idx + '" data-filename="' + rawName.toLowerCase() + '" data-date="' + (img.shot_date || '') + '" onclick="selectMediaItemByIdx(' + idx + ')" title="' + tooltip + '">' +
            '<div class="modal-photo-thumb-wrap">' +
                '<img src="' + img.thumb_url + '" class="modal-photo-img" loading="lazy" />' +
                (img.shot_date ? '<div style="position:absolute; bottom:6px; left:6px; background:rgba(15,23,42,0.8); color:#f8fafc; font-size:12px; font-weight:600; padding:2px 6px; border-radius:6px; font-family:monospace; backdrop-filter:blur(3px); letter-spacing:0.3px;">' + img.shot_date + '</div>' : '') +
            '</div>' +
            '<div class="modal-photo-info">' +
                '<div class="modal-photo-name" title="' + safeName + '">' +
                    rawName +
                '</div>' +
                '<div class="modal-photo-meta">' +
                    '<span title="' + (isEn ? 'Capture / Upload Date' : 'Çekim / Yükleme Tarihi') + '">' + (img.shot_date ? '📅 ' + img.shot_date : (dims || '')) + '</span>' +
                    '<span>' + (img.file_size_fmt || '') + '</span>' +
                '</div>' +
            '</div>' +
        '</div>';
    }).join('');
}

function filterModalImages() {
    var isEn = (window.ADMIN_LANG === 'en');
    var q = (document.getElementById('modalSearchInput').value || '').toLowerCase().trim();
    var cards = document.querySelectorAll('.modal-photo-card');
    var visibleCount = 0;
    cards.forEach(function(card) {
        var fn = card.getAttribute('data-filename') || '';
        var dt = card.getAttribute('data-date') || '';
        var match = !q || fn.includes(q) || dt.includes(q);
        card.style.display = match ? 'flex' : 'none';
        if (match) visibleCount++;
    });
    document.getElementById('modalCountText').innerText = visibleCount + (isEn ? ' photos filtered' : ' görsel filtrelendi');
}

function selectMediaItemByIdx(idx) {
    var img = currentModalImages[idx];
    if (!img) return;
    var filePath = img.file_path;
    var altText = img.original_name || img.filename || '';

    if (customImageCallback) {
        customImageCallback(filePath, altText);
    } else if (targetInputId) {
        if (typeof setCoverImage === 'function' && targetInputId === 'input_cover_image') {
            setCoverImage(filePath);
        } else {
            var input = document.getElementById(targetInputId);
            if (input) {
                input.value = filePath;
                var preview = document.getElementById(targetInputId + '_preview');
                if (preview) {
                    var cleanPath = filePath.replace(/^\/+/, '');
                    if (!cleanPath.startsWith('content/') && !cleanPath.startsWith('uploads/') && !cleanPath.startsWith('images/') && !cleanPath.startsWith('processed_images/') && !cleanPath.startsWith('http')) {
                        cleanPath = 'content/' + cleanPath;
                    }
                    preview.src = cleanPath.startsWith('http') ? cleanPath : ('<?= url("") ?>' + cleanPath);
                    preview.style.display = 'block';
                }
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    } else if (activeEditorInstance) {
        var shortcode = '{{ image(path="' + filePath + '", alt="' + altText.replace(/"/g, '') + '") }}';
        var doc = activeEditorInstance.codemirror.getDoc();
        var cursor = doc.getCursor();
        doc.replaceRange(shortcode, cursor);
    }
    closeMediaModal();
}

function uploadFromModal(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    var formData = new FormData();
    formData.append('image', file);
    formData.append('csrf_token', '<?= csrf_token() ?>');
    if (modalActiveFolder && modalActiveFolder !== 'all') {
        formData.append('folder', modalActiveFolder);
    }

    var isEn = (window.ADMIN_LANG === 'en');
    var grid = document.getElementById('modalGalleryGrid');
    var origHtml = grid.innerHTML;
    grid.innerHTML = '<div style="color:var(--accent-blue); grid-column:1/-1; text-align:center; padding:40px; font-weight:600;">' + (isEn ? 'Uploading photo and preparing AVIF/WebP variants...' : 'Görsel yükleniyor ve AVIF/WebP varyantları hazırlanıyor...') + '</div>';

    fetch('<?= url("admin/upload.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            loadModalMedia();
        } else {
            alert((isEn ? 'Error: ' : 'Hata: ') + (data.error || (isEn ? 'Upload failed.' : 'Yükleme başarısız.')));
            grid.innerHTML = origHtml;
        }
    })
    .catch(function() {
        alert(isEn ? 'A server error occurred.' : 'Sunucu hatası oluştu.');
        grid.innerHTML = origHtml;
    });
}
</script>
