// Question Add/Edit modals.
//
// Depends on window.ExamShow:
//   - media:                  examMediaMap injected by the blade
//   - routes.questionsUpdate: route template with literal "__QID__" placeholder
//
// Globals exposed (used by inline onclick handlers and other feature files):
//   escHtml, updateReuseArea/toggleReuseGrid/buildReuseGrid/selectReuseItem/
//   clearReuseItem, selectType, previewMediaFile, onMediaTypeChange,
//   setSCCorrect, addChoiceRow/addMatchingRow/addOrderingRow/addIdentRow/
//   removeRow, openEditModal, onEqMediaChange, buildEqOptions,
//   eq* row helpers, cp* completion helpers (add + edit).

const EXAM_MEDIA = (window.ExamShow && window.ExamShow.media) || {};
const EQ_UPDATE_URL = (window.ExamShow && window.ExamShow.routes && window.ExamShow.routes.questionsUpdate) || '';

// ── escHtml (needed by reuse picker, Quill helpers, share.js, question-bank.js) ─
function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Media reuse picker ────────────────────────────────────────────────────────
// mode = 'add' (create modal) | 'eq' (edit modal)
// IDs pattern: add → mediaReuseArea/Count/…  |  eq → eqMediaReuseArea/Count/…
function _reuseId(mode, suffix) {
    return mode === 'add' ? 'mediaReuse' + suffix : 'eqMediaReuse' + suffix;
}

function updateReuseArea(mode, type) {
    const area   = document.getElementById(_reuseId(mode, 'Area'));
    const count  = document.getElementById(_reuseId(mode, 'Count'));
    const plural = document.getElementById(_reuseId(mode, 'Plural'));
    const grid   = document.getElementById(_reuseId(mode, 'Grid'));
    const sel    = document.getElementById(_reuseId(mode, 'Selected'));
    const path   = document.getElementById(_reuseId(mode, 'Path'));
    if (!area) return;

    if (type === 'none') {
        area.style.display = 'none';
        if (grid) grid.style.display = 'none';
        if (sel)  sel.classList.add('js-hidden');
        if (path) path.value = '';
        return;
    }

    const items = (EXAM_MEDIA[type] || []);
    if (items.length === 0) { area.style.display = 'none'; return; }

    const one = items.length === 1;
    count.textContent  = items.length;
    plural.textContent = one ? '' : 's';
    const pluralS = document.getElementById(_reuseId(mode, 'PluralS'));
    if (pluralS) pluralS.textContent = one ? '' : 's';
    area.style.display = '';
    if (grid) grid.style.display = 'none';
    if (sel)  sel.classList.add('js-hidden');
    if (path) path.value = '';
}

function toggleReuseGrid(mode) {
    const grid = document.getElementById(_reuseId(mode, 'Grid'));
    if (!grid) return;
    if (grid.style.display !== 'none') { grid.style.display = 'none'; return; }
    buildReuseGrid(mode);
    grid.style.display = '';
}

function buildReuseGrid(mode) {
    const grid    = document.getElementById(_reuseId(mode, 'Grid'));
    const selType = mode === 'add'
        ? document.getElementById('mediaTypeSelect')?.value
        : document.getElementById('eqMediaType')?.value;
    const curPath = document.getElementById(_reuseId(mode, 'Path'))?.value || '';
    const items   = (EXAM_MEDIA[selType] || []);

    let html = '<div style="display:flex;flex-wrap:wrap;gap:10px;padding:4px;">';
    items.forEach((item, idx) => {
        const sel = item.path === curPath
            ? 'border-color:#4F46E5 !important;box-shadow:0 0 0 3px rgba(79,70,229,.2);'
            : '';
        const dataAttrs = `class="reuse-grid-item" data-rmode="${mode}" data-idx="${idx}"`;

        if (selType === 'image') {
            html += `<div ${dataAttrs} title="${escHtml(item.label)}"
                         style="cursor:pointer;border:2px solid #e2e8f0;${sel}border-radius:8px;
                                overflow:hidden;width:110px;flex-shrink:0;text-align:center;">
                        <img src="${escHtml(item.url)}" style="width:110px;height:72px;object-fit:cover;display:block;">
                        <div style="font-size:.63rem;color:#475569;padding:3px 4px;white-space:nowrap;
                                    overflow:hidden;text-overflow:ellipsis;">${escHtml(item.label)}</div>
                     </div>`;
        } else if (selType === 'audio') {
            html += `<div ${dataAttrs}
                         style="cursor:pointer;border:2px solid #e2e8f0;${sel}border-radius:8px;
                                padding:8px 10px;background:#F8FAFC;min-width:220px;max-width:320px;flex:1;">
                        <div style="font-size:.75rem;font-weight:600;color:#1E293B;margin-bottom:4px;">
                            <i class="bi bi-music-note-beamed me-1" style="color:#4F46E5;"></i>${escHtml(item.label)}
                        </div>
                        <audio src="${escHtml(item.url)}" controls style="width:100%;height:30px;"
                               onclick="event.stopPropagation()"></audio>
                     </div>`;
        } else {
            html += `<div ${dataAttrs}
                         style="cursor:pointer;border:2px solid #e2e8f0;${sel}border-radius:8px;
                                overflow:hidden;width:180px;flex-shrink:0;">
                        <video src="${escHtml(item.url)}" style="width:180px;height:100px;object-fit:cover;display:block;"
                               muted onclick="event.stopPropagation()"></video>
                        <div style="font-size:.63rem;color:#475569;padding:3px 6px;white-space:nowrap;
                                    overflow:hidden;text-overflow:ellipsis;">${escHtml(item.label)}</div>
                     </div>`;
        }
    });
    html += '</div>';
    grid.innerHTML = html;

    grid.querySelectorAll('.reuse-grid-item').forEach(el => {
        el.addEventListener('click', function(e) {
            if (e.target.closest('audio,video')) return;
            const m   = this.dataset.rmode;
            const itm = (EXAM_MEDIA[selType] || [])[parseInt(this.dataset.idx, 10)];
            if (itm) selectReuseItem(m, itm.path, itm.label);
        });
    });
}

function selectReuseItem(mode, path, label) {
    document.getElementById(_reuseId(mode, 'Path')).value = path;
    const sel = document.getElementById(_reuseId(mode, 'Selected'));
    document.getElementById(_reuseId(mode, 'SelectedLabel')).textContent = label;
    sel.classList.remove('js-hidden');
    const fileInput = mode === 'add'
        ? document.getElementById('mediaFileInput')
        : document.getElementById('eqMediaFileInput');
    if (fileInput) fileInput.value = '';
    document.getElementById(_reuseId(mode, 'Grid')).style.display = 'none';
}

function clearReuseItem(mode) {
    document.getElementById(_reuseId(mode, 'Path')).value = '';
    document.getElementById(_reuseId(mode, 'Selected')).classList.add('js-hidden');
}


// ── Add Question Modal ────────────────────────────────────────────────────────
const ALL_SECTIONS = ['sc-section','ms-section','tf-section','sa-section','mt-section','or-section','id-section','cp-section','rr-section','ex-section','wp-section'];

function selectType(type) {
    document.getElementById('selectedType').value = type;
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('active'));
    document.querySelector(`.type-card[data-type="${type}"]`)?.classList.add('active');
    const map = { single_choice:'sc-section', multiple_select:'ms-section', true_false:'tf-section',
                  short_answer:'sa-section', matching:'mt-section', ordering:'or-section',
                  identification:'id-section', completion:'cp-section',
                  restricted_response:'rr-section',
                  exercise:'ex-section', written_production:'wp-section' };
    ALL_SECTIONS.forEach(id => {
        const el = document.getElementById(id);
        el.style.display = 'none';
        el.querySelectorAll('[required]').forEach(i => { i.removeAttribute('required'); i.dataset.wasRequired = '1'; });
    });
    const sec = document.getElementById(map[type]);
    if (sec) {
        sec.style.display = '';
        sec.querySelectorAll('[data-was-required]').forEach(i => i.setAttribute('required', ''));
    }
    syncAutoPoints('addPoints');
}

function previewMediaFile(input, wrapId, imgId) {
    const wrap = document.getElementById(wrapId);
    const img  = document.getElementById(imgId);
    if (!input.files || !input.files[0]) { wrap.style.display = 'none'; return; }
    const file = input.files[0];
    if (!file.type.startsWith('image/')) { wrap.style.display = 'none'; return; }
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; wrap.style.display = ''; };
    reader.readAsDataURL(file);
}

function onMediaTypeChange(val) {
    const area  = document.getElementById('mediaFileArea');
    const input = document.getElementById('mediaFileInput');
    const label = document.getElementById('mediaFileLabel');
    const hint  = document.getElementById('mediaHint');
    const wrap  = document.getElementById('mediaPreviewWrap');

    if (val === 'none') {
        area.style.display = 'none'; hint.style.display = 'none';
        wrap.style.display = 'none'; input.value = '';
        updateReuseArea('add', 'none'); return;
    }
    area.style.display = '';
    hint.style.display = '';
    wrap.style.display = 'none';
    input.value = '';

    input.name = val;
    if (val === 'image') {
        input.accept = 'image/*'; label.textContent = 'Imagen';
        hint.textContent = 'JPG, PNG, WebP. Máx. 8 MB.';
    } else if (val === 'audio') {
        input.accept = '.mp3,.wav,.ogg,.m4a'; label.textContent = 'Audio';
        hint.textContent = 'MP3, WAV, OGG, M4A. Máx. 20 MB.';
    } else {
        input.accept = '.mp4,.webm,.mov'; label.textContent = 'Video';
        hint.textContent = 'MP4, WebM, MOV. Máx. 100 MB.';
    }
    updateReuseArea('add', val);
}

// ── Single Choice correct button ──────────────────────────────────────────────
function setSCCorrect(btn) {
    const container = document.getElementById('sc-options');
    const allBtns   = container.querySelectorAll('.sc-correct-btn');
    const idx       = [...container.querySelectorAll('.sc-opt-row')].indexOf(btn.closest('.sc-opt-row'));
    allBtns.forEach((b, i) => {
        b.className = 'btn sc-correct-btn ' + (i === idx ? 'btn-success' : 'btn-outline-secondary');
        b.querySelector('i').className = 'bi ' + (i === idx ? 'bi-record-circle-fill' : 'bi-circle');
    });
    document.getElementById('correct_mc').value = idx;
}

// ── Dynamic row helpers ───────────────────────────────────────────────────────
let scCount = 4, msCount = 4, mtCount = 3, orCount = 4;

function addChoiceRow(isMS) {
    const prefix = isMS ? 'ms' : 'sc';
    const container = document.getElementById(prefix + '-options');
    const count = isMS ? msCount++ : scCount++;
    const letter = String.fromCharCode(65 + count);

    const row = document.createElement('div');
    row.className = 'input-group mb-2 ' + prefix + '-opt-row';
    row.dataset.idx = count;

    if (isMS) {
        row.innerHTML = `
            <div class="input-group-text" style="width:38px;padding:0;justify-content:center;">
                <input type="checkbox" name="correct_ms[]" value="${count}"
                       class="form-check-input mt-0 ms-correct-chk" style="width:16px;height:16px;">
            </div>
            <span class="input-group-text fw-bold text-muted" style="width:36px;padding:0;justify-content:center;">${letter}</span>
            <input type="text" name="options[${count}][text]" class="form-control" placeholder="Opción ${letter}">
            <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)" style="width:36px;padding:0;"><i class="bi bi-x"></i></button>
        `;
    } else {
        row.innerHTML = `
            <button type="button" class="btn btn-outline-secondary sc-correct-btn" onclick="setSCCorrect(this)" style="width:38px;padding:0;">
                <i class="bi bi-circle"></i>
            </button>
            <span class="input-group-text fw-bold text-muted" style="width:36px;padding:0;justify-content:center;">${letter}</span>
            <input type="text" name="options[${count}][text]" class="form-control" placeholder="Opción ${letter}">
            <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)" style="width:36px;padding:0;"><i class="bi bi-x"></i></button>
        `;
    }
    container.appendChild(row);
}

function addMatchingRow() {
    const container = document.getElementById('mt-pairs');
    const i = mtCount++;
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 mt-pair-row';
    row.dataset.idx = i;
    row.innerHTML = `
        <div class="col-5"><input type="text" name="pairs[${i}][concept]" class="form-control form-control-sm" placeholder="Concepto ${i+1}"></div>
        <div class="col-1 d-flex align-items-center justify-content-center text-muted"><i class="bi bi-arrow-right"></i></div>
        <div class="col-5"><input type="text" name="pairs[${i}][definition]" class="form-control form-control-sm" placeholder="Definición ${i+1}"></div>
        <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeRow(this)"><i class="bi bi-x"></i></button></div>
    `;
    container.appendChild(row);
    syncAutoPoints('addPoints');
}

function addOrderingRow() {
    const container = document.getElementById('or-items');
    const i = orCount++;
    const row = document.createElement('div');
    row.className = 'input-group mb-2 or-item-row';
    row.dataset.idx = i;
    row.innerHTML = `
        <span class="input-group-text fw-bold" style="width:36px;background:#EFF6FF;color:#3B82F6;justify-content:center;">${i+1}</span>
        <input type="text" name="ordering_items[${i}]" class="form-control" placeholder="Ítem ${i+1}">
        <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)" style="width:36px;padding:0;"><i class="bi bi-x"></i></button>
    `;
    container.appendChild(row);
    syncAutoPoints('addPoints');
}

let idCount = 3;
function addIdentRow() {
    const container = document.getElementById('id-items');
    if (container.querySelectorAll('.id-item-row').length >= 5) {
        AppToast.show('Máximo 5 etiquetas por pregunta', 'warning', 2500); return;
    }
    const labels = ['A','B','C','D','E'];
    const i = idCount++;
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 id-item-row';
    row.dataset.idx = i;
    row.innerHTML = `
        <div class="col-3"><input type="text" name="ident_items[${i}][label]" class="form-control form-control-sm text-center fw-bold" placeholder="${labels[i] || ''}" maxlength="5"></div>
        <div class="col-8"><input type="text" name="ident_items[${i}][answer]" class="form-control form-control-sm" placeholder="Respuesta correcta"></div>
        <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeRow(this)"><i class="bi bi-x"></i></button></div>
    `;
    container.appendChild(row);
    syncAutoPoints('addPoints');
}

function removeRow(btn) {
    btn.closest('[class*="-row"],[class*="-pair-row"]').remove();
    syncAutoPoints('addPoints');
}

// Reset modal on open
document.getElementById('addQuestionModal')?.addEventListener('show.bs.modal', () => {
    document.getElementById('qForm').reset();
    document.getElementById('mediaFileArea').style.display = 'none';
    document.getElementById('mediaHint').style.display = 'none';
    selectType('single_choice');
    document.getElementById('correct_mc').value = 0;
    const scBtns = document.querySelectorAll('#sc-options .sc-correct-btn');
    scBtns.forEach((b, i) => {
        b.className = 'btn sc-correct-btn ' + (i === 0 ? 'btn-success' : 'btn-outline-secondary');
        b.querySelector('i').className = 'bi ' + (i === 0 ? 'bi-record-circle-fill' : 'bi-circle');
    });
});

// ── Edit Question Modal ───────────────────────────────────────────────────────
const EQ_TYPE_INFO = {
    single_choice:        ['mc', 'Selección Única'],
    multiple_choice:      ['mc', 'Selección Única'],
    multiple_select:      ['ms', 'Selección Múltiple'],
    true_false:           ['tf', 'Verdadero / Falso'],
    short_answer:         ['sa', 'Respuesta Corta'],
    matching:             ['mt', 'Emparejamiento'],
    ordering:             ['or', 'Ordenamiento'],
    identification:       ['id', 'Identificación'],
    completion:           ['cp', 'Completar'],
    restricted_response:  ['rr', 'Resp. Restringida'],
    exercise:             ['ex', 'Ejercicio'],
    written_production:   ['wp', 'Prod. Escrita'],
};

function openEditModal(q) {
    document.getElementById('editQForm').action = EQ_UPDATE_URL.replace('__QID__', q.id);

    const [tCls, tLabel] = EQ_TYPE_INFO[q.type] || ['mc', q.type];
    const badge = document.getElementById('eqTypeBadge');
    badge.textContent = tLabel;
    badge.className = 'type-pill type-' + tCls;
    badge.dataset.type = q.type;

    document.getElementById('eqText').value   = q.text;
    if (window.editQuill) {
        window.editQuill.clipboard.dangerouslyPasteHTML(q.text || '');
    }
    document.getElementById('eqPoints').value = q.points;

    const currentMedia = document.getElementById('eqCurrentMedia');
    const mediaIcons   = { image: '🖼️', audio: '🎵', video: '🎬' };
    const _mediaUrl = q.mediaType === 'image' ? q.imageUrl
                    : q.mediaType === 'audio' ? q.audioUrl
                    : q.videoUrl;
    if (q.mediaType && q.mediaType !== 'none' && _mediaUrl) {
        currentMedia.classList.remove('js-hidden');
        document.getElementById('eqCurrentMediaIcon').textContent  = mediaIcons[q.mediaType] || '📎';
        document.getElementById('eqCurrentMediaLabel').textContent = q.mediaType === 'image' ? 'Imagen actual' : q.mediaType === 'audio' ? 'Audio actual' : 'Video actual';
        document.getElementById('eqRemoveMedia').checked = false;
    } else {
        currentMedia.classList.add('js-hidden');
    }
    document.getElementById('eqMediaType').value = 'none';
    document.getElementById('eqMediaFileArea').style.display = 'none';
    const eqHint = document.getElementById('eqMediaHint');
    if (eqHint) eqHint.style.display = 'none';
    const eqWrap = document.getElementById('eqMediaPreviewWrap');
    if (eqWrap) eqWrap.style.display = 'none';
    if (document.getElementById('eqMediaFileInput')) {
        document.getElementById('eqMediaFileInput').name = 'image';
        document.getElementById('eqMediaFileInput').value = '';
    }
    updateReuseArea('eq', 'none');

    buildEqOptions(q);

    if (typeof RUBRIC_TYPES !== 'undefined' && RUBRIC_TYPES.includes(q.type)) {
        rubricInit('eq', q.rubric || null);
        document.getElementById('eqRubricSection').style.display = '';
    } else {
        document.getElementById('eqRubricSection').style.display = 'none';
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('editQuestionModal')).show();
}

function onEqMediaChange(val) {
    const area  = document.getElementById('eqMediaFileArea');
    const input = document.getElementById('eqMediaFileInput');
    const label = document.getElementById('eqMediaFileLabel');
    const hint  = document.getElementById('eqMediaHint');
    const wrap  = document.getElementById('eqMediaPreviewWrap');
    if (val === 'none') {
        area.style.display = 'none';
        if (hint) hint.style.display = 'none';
        if (wrap) wrap.style.display = 'none';
        input.value = '';
        updateReuseArea('eq', 'none'); return;
    }
    area.style.display = '';
    if (wrap) wrap.style.display = 'none';
    input.value = '';
    input.name = val;
    if (val === 'image') {
        input.accept = 'image/*'; label.textContent = 'Nueva imagen';
        if (hint) { hint.textContent = 'JPG, PNG, WebP. Máx. 8 MB.'; hint.style.display = ''; }
    } else if (val === 'audio') {
        input.accept = '.mp3,.wav,.ogg,.m4a'; label.textContent = 'Nuevo audio';
        if (hint) { hint.textContent = 'MP3, WAV, OGG, M4A. Máx. 20 MB.'; hint.style.display = ''; }
    } else {
        input.accept = '.mp4,.webm,.mov'; label.textContent = 'Nuevo video';
        if (hint) { hint.textContent = 'MP4, WebM, MOV. Máx. 100 MB.'; hint.style.display = ''; }
    }
    updateReuseArea('eq', val);
}

function buildEqOptions(q) {
    const area = document.getElementById('eqOptionsArea');

    if (q.type === 'single_choice' || q.type === 'multiple_choice') {
        let html = `<div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label fw-semibold mb-0">Opciones <span class="text-danger">*</span></label>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="eqAddChoice(false)" style="font-size:.75rem;"><i class="bi bi-plus me-1"></i>Opción</button>
        </div>
        <p class="text-muted mb-2" style="font-size:.78rem;"><i class="bi bi-info-circle me-1"></i>Haz clic en <i class="bi bi-circle"></i> para marcar la correcta.</p>
        <div id="eq-sc-opts">`;
        q.options.forEach((opt, i) => {
            const letter = String.fromCharCode(65 + i);
            const isCorr = opt.isCorrect;
            html += `<div class="input-group mb-2 eq-sc-row">
                <button type="button" class="btn ${isCorr ? 'btn-success' : 'btn-outline-secondary'} eq-sc-btn" onclick="eqSetCorrect(this)" style="width:38px;padding:0;">
                    <i class="bi ${isCorr ? 'bi-record-circle-fill' : 'bi-circle'}"></i>
                </button>
                <span class="input-group-text fw-bold text-muted" style="width:36px;padding:0;justify-content:center;">${letter}</span>
                <input type="text" name="options[${i}][text]" class="form-control" value="${escHtml(opt.text)}" required>
                ${i >= 3 ? `<button type="button" class="btn btn-outline-danger" onclick="this.closest('.eq-sc-row').remove()" style="width:36px;padding:0;"><i class="bi bi-x"></i></button>` : ''}
            </div>`;
        });
        const corrIdx = q.options.findIndex(o => o.isCorrect);
        html += `</div><input type="hidden" name="correct_mc" id="eq-correct-mc" value="${corrIdx < 0 ? 0 : corrIdx}">`;
        area.innerHTML = html;

    } else if (q.type === 'multiple_select') {
        let html = `<div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label fw-semibold mb-0">Opciones <span class="text-danger">*</span></label>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="eqAddChoice(true)" style="font-size:.75rem;color:#9333EA;border-color:#9333EA;"><i class="bi bi-plus me-1"></i>Opción</button>
        </div>
        <p class="text-muted mb-2" style="font-size:.78rem;"><i class="bi bi-info-circle me-1"></i>Marca todas las correctas.</p>
        <div id="eq-ms-opts">`;
        q.options.forEach((opt, i) => {
            const letter = String.fromCharCode(65 + i);
            html += `<div class="input-group mb-2 eq-ms-row">
                <div class="input-group-text" style="width:38px;padding:0;justify-content:center;">
                    <input type="checkbox" name="correct_ms[]" value="${i}" class="form-check-input mt-0" style="width:16px;height:16px;" ${opt.isCorrect ? 'checked' : ''}>
                </div>
                <span class="input-group-text fw-bold text-muted" style="width:36px;padding:0;justify-content:center;">${letter}</span>
                <input type="text" name="options[${i}][text]" class="form-control" value="${escHtml(opt.text)}">
                ${i >= 2 ? `<button type="button" class="btn btn-outline-danger" onclick="this.closest('.eq-ms-row').remove()" style="width:36px;padding:0;"><i class="bi bi-x"></i></button>` : ''}
            </div>`;
        });
        html += `</div>`;
        area.innerHTML = html;

    } else if (q.type === 'true_false') {
        const isTrue = q.options.find(o => o.text === 'Verdadero')?.isCorrect ?? true;
        area.innerHTML = `<label class="form-label fw-semibold">Respuesta correcta</label>
        <div class="d-flex gap-3">
            <label class="d-flex align-items-center gap-2 p-3 border rounded-3 flex-fill" style="cursor:pointer;">
                <input type="radio" name="correct_answer" value="true" ${isTrue ? 'checked' : ''}>
                <i class="bi bi-check-circle text-success fs-5"></i><span class="fw-semibold">Verdadero</span>
            </label>
            <label class="d-flex align-items-center gap-2 p-3 border rounded-3 flex-fill" style="cursor:pointer;">
                <input type="radio" name="correct_answer" value="false" ${!isTrue ? 'checked' : ''}>
                <i class="bi bi-x-circle text-danger fs-5"></i><span class="fw-semibold">Falso</span>
            </label>
        </div>`;

    } else if (q.type === 'short_answer') {
        area.innerHTML = `<div class="alert alert-info py-2 mb-0" style="font-size:.8rem;">
            <i class="bi bi-info-circle me-1"></i>Las respuestas cortas requieren revisión manual del docente.
        </div>`;

    } else if (q.type === 'matching') {
        let html = `<div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label fw-semibold mb-0">Pares concepto → definición <span class="text-danger">*</span></label>
            <button type="button" class="btn btn-sm btn-outline-warning" onclick="eqAddMatchRow()" style="font-size:.75rem;color:#9A3412;border-color:#FCA571;"><i class="bi bi-plus me-1"></i>Par</button>
        </div>
        <div id="eq-mt-pairs">`;
        q.options.forEach((opt, i) => {
            html += `<div class="row g-2 mb-2 eq-mt-row">
                <div class="col-5"><input type="text" name="pairs[${i}][concept]" class="form-control form-control-sm" value="${escHtml(opt.text)}" placeholder="Concepto ${i+1}" required></div>
                <div class="col-1 d-flex align-items-center justify-content-center text-muted"><i class="bi bi-arrow-right"></i></div>
                <div class="col-5"><input type="text" name="pairs[${i}][definition]" class="form-control form-control-sm" value="${escHtml(opt.matchText||'')}" placeholder="Definición ${i+1}" required></div>
                <div class="col-1">${i >= 2 ? `<button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.eq-mt-row').remove()"><i class="bi bi-x"></i></button>` : ''}</div>
            </div>`;
        });
        html += `</div>`;
        area.innerHTML = html;

    } else if (q.type === 'ordering') {
        let html = `<div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label fw-semibold mb-0">Ítems en orden correcto <span class="text-danger">*</span></label>
            <button type="button" class="btn btn-sm btn-outline-info" onclick="eqAddOrderRow()" style="font-size:.75rem;color:#075985;border-color:#7DD3FC;"><i class="bi bi-plus me-1"></i>Ítem</button>
        </div>
        <p class="text-muted mb-2" style="font-size:.78rem;"><i class="bi bi-info-circle me-1"></i>El orden que listes aquí es el correcto.</p>
        <div id="eq-or-items">`;
        const sorted = [...q.options].sort((a,b) => a.order - b.order);
        sorted.forEach((opt, i) => {
            html += `<div class="input-group mb-2 eq-or-row">
                <span class="input-group-text fw-bold" style="width:36px;background:#EFF6FF;color:#3B82F6;justify-content:center;">${i+1}</span>
                <input type="text" name="ordering_items[${i}]" class="form-control" value="${escHtml(opt.text)}" required>
                ${i >= 2 ? `<button type="button" class="btn btn-outline-danger" onclick="this.closest('.eq-or-row').remove()" style="width:36px;padding:0;"><i class="bi bi-x"></i></button>` : ''}
            </div>`;
        });
        html += `</div>`;
        area.innerHTML = html;

    } else if (q.type === 'identification') {
        let html = `<div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label fw-semibold mb-0">Etiquetas a identificar <span class="text-danger">*</span></label>
            <button type="button" class="btn btn-sm" onclick="eqAddIdentRow()" style="font-size:.75rem;color:#9F1239;border:1px solid #FECDD3;background:#FFF1F2;"><i class="bi bi-plus me-1"></i>Etiqueta</button>
        </div>
        <p class="text-muted mb-2" style="font-size:.78rem;"><i class="bi bi-info-circle me-1"></i>Etiqueta = letra/número en la imagen. Respuesta = lo que el estudiante debe escribir.</p>
        <div id="eq-id-items">`;
        const sortedId = [...q.options].sort((a,b) => a.order - b.order);
        sortedId.forEach((opt, i) => {
            html += `<div class="row g-2 mb-2 eq-id-row">
                <div class="col-3"><input type="text" name="ident_items[${i}][label]" class="form-control form-control-sm text-center fw-bold" value="${escHtml(opt.text)}" maxlength="5" required></div>
                <div class="col-8"><input type="text" name="ident_items[${i}][answer]" class="form-control form-control-sm" value="${escHtml(opt.matchText||'')}" placeholder="Respuesta correcta" required></div>
                <div class="col-1">${i >= 2 ? `<button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.eq-id-row').remove()"><i class="bi bi-x"></i></button>` : ''}</div>
            </div>`;
        });
        html += `</div>`;
        area.innerHTML = html;

    } else if (q.type === 'completion') {
        eqBuildCompletion(q);

    } else if (['restricted_response','exercise','written_production'].includes(q.type)) {
        const labels = {
            restricted_response: ['#14532D','#BBF7D0','#F0FDF4','bi-justify-left','Respuesta Restringida','El estudiante explica, justifica o argumenta. Requiere revisión manual.'],
            exercise:            ['#78350F','#FDE68A','#FFFBEB','bi-calculator','Ejercicio','Se valora el proceso y el resultado. Requiere revisión manual.'],
            written_production:  ['#4C1D95','#DDD6FE','#F5F3FF','bi-pencil-square','Producción Escrita','El estudiante redacta un texto completo. Requiere rúbrica.'],
        };
        const [color, bcolor, bg, icon, title, desc] = labels[q.type];
        area.innerHTML = `
        <div class="alert py-2 mb-3" style="background:${bg};border:1px solid ${bcolor};font-size:.8rem;color:${color};">
            <i class="bi ${icon} me-1"></i><strong>${title}</strong> — ${desc}
        </div>
        <label class="form-label fw-semibold">Criterios de evaluación / Rúbrica <span class="text-muted" style="font-size:.75rem;">(opcional, visible para el estudiante)</span></label>
        <textarea name="grading_criteria" class="form-control" rows="3" placeholder="Describe los criterios de calificación…">${escHtml(q.gradingCriteria||'')}</textarea>`;
    }
    setTimeout(syncEqAutoPoints, 0);
}

// ── Edit modal helper row adders ──────────────────────────────────────────────
let eqScCount = 0, eqMsCount = 0, eqMtCount = 0, eqOrCount = 0;

function eqAddChoice(isMS) {
    const cont = document.getElementById(isMS ? 'eq-ms-opts' : 'eq-sc-opts');
    const rows = cont.querySelectorAll(isMS ? '.eq-ms-row' : '.eq-sc-row');
    const i = rows.length;
    const letter = String.fromCharCode(65 + i);
    const row = document.createElement('div');
    row.className = 'input-group mb-2 ' + (isMS ? 'eq-ms-row' : 'eq-sc-row');
    if (isMS) {
        row.innerHTML = `<div class="input-group-text" style="width:38px;padding:0;justify-content:center;"><input type="checkbox" name="correct_ms[]" value="${i}" class="form-check-input mt-0" style="width:16px;height:16px;"></div>
            <span class="input-group-text fw-bold text-muted" style="width:36px;padding:0;justify-content:center;">${letter}</span>
            <input type="text" name="options[${i}][text]" class="form-control" placeholder="Opción ${letter}">
            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.eq-ms-row').remove()" style="width:36px;padding:0;"><i class="bi bi-x"></i></button>`;
    } else {
        row.innerHTML = `<button type="button" class="btn btn-outline-secondary eq-sc-btn" onclick="eqSetCorrect(this)" style="width:38px;padding:0;"><i class="bi bi-circle"></i></button>
            <span class="input-group-text fw-bold text-muted" style="width:36px;padding:0;justify-content:center;">${letter}</span>
            <input type="text" name="options[${i}][text]" class="form-control" placeholder="Opción ${letter}">
            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.eq-sc-row').remove()" style="width:36px;padding:0;"><i class="bi bi-x"></i></button>`;
    }
    cont.appendChild(row);
}

function eqSetCorrect(btn) {
    const cont = btn.closest('#eq-sc-opts');
    cont.querySelectorAll('.eq-sc-btn').forEach((b, i) => {
        const isThis = b === btn;
        b.className = 'btn eq-sc-btn ' + (isThis ? 'btn-success' : 'btn-outline-secondary');
        b.querySelector('i').className = 'bi ' + (isThis ? 'bi-record-circle-fill' : 'bi-circle');
    });
    const idx = [...cont.querySelectorAll('.eq-sc-row')].indexOf(btn.closest('.eq-sc-row'));
    const hidden = document.getElementById('eq-correct-mc');
    if (hidden) hidden.value = idx;
}

function eqAddMatchRow() {
    const cont = document.getElementById('eq-mt-pairs');
    const i = cont.querySelectorAll('.eq-mt-row').length;
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 eq-mt-row';
    row.innerHTML = `<div class="col-5"><input type="text" name="pairs[${i}][concept]" class="form-control form-control-sm" placeholder="Concepto ${i+1}"></div>
        <div class="col-1 d-flex align-items-center justify-content-center text-muted"><i class="bi bi-arrow-right"></i></div>
        <div class="col-5"><input type="text" name="pairs[${i}][definition]" class="form-control form-control-sm" placeholder="Definición ${i+1}"></div>
        <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.eq-mt-row').remove();syncEqAutoPoints()"><i class="bi bi-x"></i></button></div>`;
    cont.appendChild(row);
    syncEqAutoPoints();
}

function eqAddOrderRow() {
    const cont = document.getElementById('eq-or-items');
    const i = cont.querySelectorAll('.eq-or-row').length;
    const row = document.createElement('div');
    row.className = 'input-group mb-2 eq-or-row';
    row.innerHTML = `<span class="input-group-text fw-bold" style="width:36px;background:#EFF6FF;color:#3B82F6;justify-content:center;">${i+1}</span>
        <input type="text" name="ordering_items[${i}]" class="form-control" placeholder="Ítem ${i+1}">
        <button type="button" class="btn btn-outline-danger" onclick="this.closest('.eq-or-row').remove();syncEqAutoPoints()" style="width:36px;padding:0;"><i class="bi bi-x"></i></button>`;
    cont.appendChild(row);
    syncEqAutoPoints();
}

function eqAddIdentRow() {
    const cont = document.getElementById('eq-id-items');
    const rows = cont.querySelectorAll('.eq-id-row');
    if (rows.length >= 5) { AppToast.show('Máximo 5 etiquetas', 'warning', 2000); return; }
    const i = rows.length;
    const labels = ['A','B','C','D','E'];
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 eq-id-row';
    row.innerHTML = `
        <div class="col-3"><input type="text" name="ident_items[${i}][label]" class="form-control form-control-sm text-center fw-bold" placeholder="${labels[i]||''}" maxlength="5"></div>
        <div class="col-8"><input type="text" name="ident_items[${i}][answer]" class="form-control form-control-sm" placeholder="Respuesta correcta"></div>
        <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.eq-id-row').remove();syncEqAutoPoints()"><i class="bi bi-x"></i></button></div>`;
    cont.appendChild(row);
    syncEqAutoPoints();
}

// ── Completion type (add modal) ───────────────────────────────────────────────
let cpAnsCount = 1;

function addCpAnswerRow() {
    const cont = document.getElementById('cp-answers');
    if (cont.querySelectorAll('.cp-ans-row').length >= 8) {
        AppToast.show('Máximo 8 espacios por pregunta', 'warning', 2000); return;
    }
    cpAnsCount = cont.querySelectorAll('.cp-ans-row').length + 1;
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 cp-ans-row';
    row.innerHTML = `
        <div class="col-auto d-flex align-items-center">
            <span class="fw-bold text-success" style="font-size:.8rem;width:70px;">Espacio ${cpAnsCount}</span>
        </div>
        <div class="col"><input type="text" name="cp_answers[]" class="form-control form-control-sm" placeholder="Respuesta correcta" oninput="syncCpPoints()"></div>
        <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeCpRow(this)"><i class="bi bi-x"></i></button></div>`;
    cont.appendChild(row);
    syncCpPoints();
}

function addCpDistractorRow() {
    const cont = document.getElementById('cp-distractors');
    if (cont.querySelectorAll('.cp-dist-row').length >= 6) {
        AppToast.show('Máximo 6 distractores', 'warning', 2000); return;
    }
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 cp-dist-row';
    row.innerHTML = `
        <div class="col"><input type="text" name="cp_distractors[]" class="form-control form-control-sm" placeholder="Palabra incorrecta"></div>
        <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.cp-dist-row').remove()"><i class="bi bi-x"></i></button></div>`;
    cont.appendChild(row);
}

function removeCpRow(btn) {
    btn.closest('.cp-ans-row').remove();
    document.querySelectorAll('#cp-answers .cp-ans-row').forEach((row, i) => {
        const label = row.querySelector('span');
        if (label) label.textContent = 'Espacio ' + (i + 1);
    });
    syncCpPoints();
}

function syncCpPoints() {
    const count = document.querySelectorAll('#cp-answers .cp-ans-row').length;
    const el = document.getElementById('addPoints');
    if (el && count > 0) { el.value = count; el.style.background = '#D1FAE5'; setTimeout(() => el.style.background = '', 600); }
}

// ── Edit modal completion ─────────────────────────────────────────────────────
function eqBuildCompletion(q) {
    const area = document.getElementById('eqOptionsArea');
    const corrects    = q.options.filter(o => o.isCorrect).sort((a,b) => a.order - b.order);
    const distractors = q.options.filter(o => !o.isCorrect);

    let ansRows = corrects.map((o, i) => `
        <div class="row g-2 mb-2 eq-cp-ans-row">
            <div class="col-auto d-flex align-items-center">
                <span class="fw-bold text-success" style="font-size:.8rem;width:70px;">Espacio ${i+1}</span>
            </div>
            <div class="col"><input type="text" name="cp_answers[]" class="form-control form-control-sm" value="${escHtml(o.text)}" oninput="syncEqCpPoints()"></div>
            <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeEqCpRow(this)"><i class="bi bi-x"></i></button></div>
        </div>`).join('');

    let distRows = distractors.map(o => `
        <div class="row g-2 mb-2 eq-cp-dist-row">
            <div class="col"><input type="text" name="cp_distractors[]" class="form-control form-control-sm" value="${escHtml(o.text)}" placeholder="Palabra incorrecta"></div>
            <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.eq-cp-dist-row').remove()"><i class="bi bi-x"></i></button></div>
        </div>`).join('');

    area.innerHTML = `
        <div class="alert py-2 mb-3" style="background:#F0FDF4;border:1px solid #86EFAC;font-size:.8rem;color:#065F46;">
            <i class="bi bi-input-cursor-text me-1"></i><strong>Completar</strong> — Edite las respuestas correctas y los distractores. El enunciado debe tener el mismo número de <code>___</code> que respuestas correctas.
        </div>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label fw-semibold mb-0">Respuestas correctas</label>
            <button type="button" class="btn btn-sm" onclick="addEqCpAnsRow()" style="font-size:.75rem;color:#065F46;border:1px solid #86EFAC;background:#F0FDF4;">
                <i class="bi bi-plus me-1"></i>Agregar espacio
            </button>
        </div>
        <div id="eq-cp-answers">${ansRows}</div>
        <hr class="my-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label fw-semibold mb-0">Distractores</label>
            <button type="button" class="btn btn-sm" onclick="addEqCpDistRow()" style="font-size:.75rem;color:#64748B;border:1px solid #E2E8F0;background:#F8FAFC;">
                <i class="bi bi-plus me-1"></i>Agregar distractor
            </button>
        </div>
        <div id="eq-cp-distractors">${distRows}</div>`;

    setTimeout(syncEqCpPoints, 0);
}

function addEqCpAnsRow() {
    const cont = document.getElementById('eq-cp-answers');
    if (!cont || cont.querySelectorAll('.eq-cp-ans-row').length >= 8) { AppToast.show('Máximo 8 espacios', 'warning', 2000); return; }
    const n = cont.querySelectorAll('.eq-cp-ans-row').length + 1;
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 eq-cp-ans-row';
    row.innerHTML = `
        <div class="col-auto d-flex align-items-center">
            <span class="fw-bold text-success" style="font-size:.8rem;width:70px;">Espacio ${n}</span>
        </div>
        <div class="col"><input type="text" name="cp_answers[]" class="form-control form-control-sm" placeholder="Respuesta correcta" oninput="syncEqCpPoints()"></div>
        <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeEqCpRow(this)"><i class="bi bi-x"></i></button></div>`;
    cont.appendChild(row);
    syncEqCpPoints();
}

function addEqCpDistRow() {
    const cont = document.getElementById('eq-cp-distractors');
    if (!cont || cont.querySelectorAll('.eq-cp-dist-row').length >= 6) return;
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 eq-cp-dist-row';
    row.innerHTML = `
        <div class="col"><input type="text" name="cp_distractors[]" class="form-control form-control-sm" placeholder="Palabra incorrecta"></div>
        <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.eq-cp-dist-row').remove()"><i class="bi bi-x"></i></button></div>`;
    cont.appendChild(row);
}

function removeEqCpRow(btn) {
    btn.closest('.eq-cp-ans-row').remove();
    document.querySelectorAll('#eq-cp-answers .eq-cp-ans-row').forEach((row, i) => {
        const label = row.querySelector('span');
        if (label) label.textContent = 'Espacio ' + (i + 1);
    });
    syncEqCpPoints();
}

function syncEqCpPoints() {
    const count = document.querySelectorAll('#eq-cp-answers .eq-cp-ans-row').length;
    const el = document.getElementById('eqPoints');
    if (el && count > 0) { el.value = count; el.style.background = '#D1FAE5'; setTimeout(() => el.style.background = '', 600); }
}
