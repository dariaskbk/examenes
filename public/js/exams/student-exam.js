// Student exam runtime: state + timer + navigation + save + per-question handlers
// + submit modal + audio player + lightbox.
//
// All Blade-dependent values come from window.ExamState (set inline by the blade):
//   { attemptId, totalQ, previewMode, paused, lsPrefix, secondsLeft,
//     urls: { save, submit },
//     questionIds: [id, ...],
//     questions:   [{ id, type, answered }, ...] }

const S              = window.ExamState || {};
const ATTEMPT_ID     = S.attemptId;
const TOTAL_Q        = S.totalQ;
const PREVIEW_MODE   = !!S.previewMode;
const SAVE_URL       = (S.urls && S.urls.save)   || '';
const SUBMIT_URL     = (S.urls && S.urls.submit) || '';
const QUESTION_IDS   = S.questionIds || [];
const ATTEMPT_PAUSED = !!S.paused;
const CSRF           = document.querySelector('meta[name="csrf-token"]').content;
const LS_PREFIX      = S.lsPrefix || ('ec_' + ATTEMPT_ID + '_');
let   secondsLeft    = S.secondsLeft || 0;
let   currentIdx     = 0;

const answered = new Array(TOTAL_Q).fill(false);
const flagged  = new Array(TOTAL_Q).fill(false);

// ── localStorage helpers ──────────────────────────────────────────────────────
function lsSave(qid, data) {
    try { localStorage.setItem(LS_PREFIX + qid, JSON.stringify(data)); } catch(e) {}
}
function lsGet(qid) {
    try { const raw = localStorage.getItem(LS_PREFIX + qid); return raw ? JSON.parse(raw) : null; } catch(e) { return null; }
}
function lsClear(qid) {
    try { localStorage.removeItem(LS_PREFIX + qid); } catch(e) {}
}

// Init answered state from server-rendered data.
(S.questions || []).forEach((q, i) => { answered[i] = !!q.answered; });

// ── Restore unsaved answers from localStorage (page reload safety net) ────────
(function restoreFromLS() {
    (S.questions || []).forEach((q, idx) => {
        const qid    = q.id;
        const type   = q.type;
        const cached = lsGet(qid);
        if (!cached) return;

        if (type === 'single_choice' || type === 'true_false') {
            if (!answered[idx] && cached.optionId) {
                const container = document.querySelector('[data-qid="' + qid + '"].options-container');
                if (!container) return;
                const opt = container.querySelector('[data-option-id="' + cached.optionId + '"]');
                if (opt) selectSingle(opt, qid, cached.optionId, idx);
            }
        } else if (type === 'multiple_select') {
            if (!answered[idx] && cached.ids && cached.ids.length) {
                const container = document.querySelector('.options-container-ms[data-qid="' + qid + '"]');
                if (!container) return;
                container.querySelectorAll('.option-label').forEach(el => {
                    if (cached.ids.includes(parseInt(el.dataset.optionId)) && !el.classList.contains('selected')) {
                        toggleMS(el, qid, idx);
                    }
                });
            }
        } else if (type === 'short_answer') {
            if (!answered[idx] && cached.text) {
                const ta = document.querySelector('.short-answer-ta[data-qid="' + qid + '"]');
                if (ta && !ta.value.trim()) { ta.value = cached.text; markAnswered(idx); }
            }
        } else if (type === 'matching') {
            if (!answered[idx] && cached.map) {
                const wrapper = document.querySelector('.matching-wrapper[data-qid="' + qid + '"]');
                if (!wrapper) return;
                wrapper.querySelectorAll('select').forEach(sel => {
                    const val = cached.map[sel.dataset.optionId];
                    if (val && !sel.value) { sel.value = val; onMatch(sel); }
                });
            }
        } else if (type === 'ordering') {
            if (!answered[idx] && cached.ids && cached.ids.length) {
                const list = document.getElementById('order-list-' + qid);
                if (!list) return;
                const items = [...list.querySelectorAll('.ordering-item')];
                const map = {};
                items.forEach(li => map[parseInt(li.dataset.optionId)] = li);
                cached.ids.forEach(id => { const li = map[id]; if (li) list.appendChild(li); });
                list.querySelectorAll('.order-pos').forEach((p, i) => p.textContent = i + 1);
                const json = JSON.stringify(cached.ids);
                const h = document.getElementById('or-' + qid);
                if (h) h.value = json;
                markAnswered(idx);
            }
        }
    });
})();

// ── Timer ─────────────────────────────────────────────────────────────────────
function fmtTime(s) {
    const m = Math.floor(s / 60), sec = s % 60;
    return (m < 10 ? '0' : '') + m + ':' + (sec < 10 ? '0' : '') + sec;
}
let _warned5 = false, _warned1 = false;
function showTimeWarning(text, danger) {
    const el = document.getElementById('timeWarning');
    if (!el) return;
    document.getElementById('timeWarningText').textContent = text;
    if (danger) {
        el.style.background  = '#FEE2E2';
        el.style.borderColor = '#DC2626';
        el.style.color       = '#991B1B';
    }
    el.style.display = 'flex';
    clearTimeout(window._twTimer);
    window._twTimer = setTimeout(() => { el.style.display = 'none'; }, danger ? 8000 : 6000);
}

function tickTimer() {
    if (PREVIEW_MODE) {
        const sideEl = document.getElementById('timerSide');
        if (sideEl) sideEl.textContent = fmtTime(secondsLeft);
        return;
    }
    if (secondsLeft <= 0) { doSubmit(); return; }
    const display = fmtTime(secondsLeft);
    const sideEl = document.getElementById('timerSide');
    sideEl.textContent = display;
    sideEl.classList.remove('warning','danger');
    if (secondsLeft <= 60)       sideEl.classList.add('danger');
    else if (secondsLeft <= 300) sideEl.classList.add('warning');

    if (!_warned5 && secondsLeft <= 300 && secondsLeft > 60) {
        _warned5 = true;
        showTimeWarning('⏰ Te quedan 5 minutos para terminar', false);
    }
    if (!_warned1 && secondsLeft <= 60) {
        _warned1 = true; _warned5 = true;
        showTimeWarning('⚠️ ¡Último minuto! Revisa y envía tu examen', true);
    }
    secondsLeft--;
}
tickTimer();
setInterval(tickTimer, 1000);

// ── Navigation ────────────────────────────────────────────────────────────────
function goTo(idx) {
    if (idx < 0 || idx >= TOTAL_Q) return;
    document.getElementById('slide-' + currentIdx).classList.remove('active');
    document.getElementById('qbtn-' + currentIdx).classList.remove('current');
    currentIdx = idx;
    document.getElementById('slide-' + currentIdx).classList.add('active');
    document.getElementById('qbtn-' + currentIdx).classList.add('current');
    document.getElementById('questionArea').scrollTo(0, 0);
    refreshGrid();
    refreshNav();
}

function navNext() {
    if (currentIdx < TOTAL_Q - 1) {
        goTo(currentIdx + 1);
    } else {
        openSubmitModal();
    }
}

function refreshNav() {
    const prev    = document.getElementById('btnPrev');
    const next    = document.getElementById('btnNext');
    const counter = document.getElementById('qCounter');
    prev.disabled = (currentIdx === 0);
    counter.textContent = (currentIdx + 1) + ' de ' + TOTAL_Q;
    const isLast = currentIdx === TOTAL_Q - 1;
    if (isLast) {
        next.innerHTML = '<i class="bi bi-send-fill me-1"></i> Finalizar';
        next.style.cssText = 'background:linear-gradient(135deg,#059669,#10B981);border-color:#059669;color:#fff;';
    } else {
        next.innerHTML = 'Siguiente <i class="bi bi-chevron-right"></i>';
        next.style.cssText = '';
        next.className = 'nav-btn primary';
    }
}

// ── Progress ──────────────────────────────────────────────────────────────────
function refreshProgress() {
    const count = answered.filter(Boolean).length;
    const pct   = Math.round((count / TOTAL_Q) * 100);
    document.getElementById('progressBar').style.width = pct + '%';
    document.getElementById('progressLabel').textContent = count + ' / ' + TOTAL_Q;
}

function refreshGrid() {
    for (let i = 0; i < TOTAL_Q; i++) {
        const btn = document.getElementById('qbtn-' + i);
        btn.className = 'q-btn';
        if (i === currentIdx) btn.classList.add('current');
        else if (answered[i] && flagged[i]) btn.classList.add('answered', 'flagged');
        else if (answered[i])  btn.classList.add('answered');
        else if (flagged[i])   btn.classList.add('flagged');
    }
}

// ── Flag for review ───────────────────────────────────────────────────────────
function toggleFlag(idx) {
    flagged[idx] = !flagged[idx];
    const btn  = document.getElementById('flag-' + idx);
    const icon = btn.querySelector('i');
    const span = btn.querySelector('span');
    if (flagged[idx]) {
        btn.classList.add('flagged');
        icon.className = 'bi bi-flag-fill';
        span.textContent = 'Marcada para revisar';
    } else {
        btn.classList.remove('flagged');
        icon.className = 'bi bi-flag';
        span.textContent = 'Marcar para revisar';
    }
    refreshGrid();
}

// ── Save API ──────────────────────────────────────────────────────────────────
function saveAnswer(questionId, optionId, textAnswer, idx) {
    if (PREVIEW_MODE) return;
    const body = new FormData();
    body.append('_token', CSRF);
    body.append('question_id', questionId);
    if (optionId !== null && optionId !== '') body.append('option_id', optionId);
    if (textAnswer !== null) body.append('text_answer', textAnswer);

    fetch(SAVE_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (data.redirect) { window.location.href = data.redirect; return; }
            lsClear(questionId);
            showToast();
        }).catch(() => {});
}

let toastTimer = null;
function showToast() {
    const t = document.getElementById('saveToast');
    t.style.opacity = '1';
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.style.opacity = '0', 1800);
}

function markAnswered(idx) {
    if (!answered[idx]) { answered[idx] = true; refreshProgress(); refreshGrid(); }
}

// ── Single Choice / True-False ────────────────────────────────────────────────
function selectSingle(el, qid, optId, idx) {
    const container = el.closest('[data-qid]');
    container.querySelectorAll('.option-label').forEach(l => l.classList.remove('selected'));
    el.classList.add('selected');
    const hidden = document.getElementById('sc-' + qid);
    if (hidden) hidden.value = optId;
    markAnswered(idx);
    lsSave(qid, { optionId: optId });
    saveAnswer(qid, optId, null, idx);
}

// ── Multiple Select ───────────────────────────────────────────────────────────
function toggleMS(el, qid, idx) {
    el.classList.toggle('selected');
    const sq = el.querySelector('.opt-square');
    sq.textContent = el.classList.contains('selected') ? '✓' : '';
    const container = el.closest('.options-container-ms');
    const selIds = [...container.querySelectorAll('.option-label.selected')].map(l => parseInt(l.dataset.optionId));
    const json = JSON.stringify(selIds);
    const hidden = document.getElementById('ms-' + qid);
    if (hidden) hidden.value = json;
    if (selIds.length > 0) markAnswered(idx);
    lsSave(qid, { ids: selIds });
    clearTimeout(window._msSave);
    window._msSave = setTimeout(() => saveAnswer(qid, null, json, idx), 400);
}

// ── Short Answer ──────────────────────────────────────────────────────────────
document.querySelectorAll('.short-answer-ta').forEach(ta => {
    ta.addEventListener('input', function () {
        const qid = this.dataset.qid;
        const idx = parseInt(this.dataset.idx);
        const val = this.value;
        lsSave(qid, { text: val });
        clearTimeout(window._taSave);
        window._taSave = setTimeout(() => {
            if (val.trim()) markAnswered(idx);
            saveAnswer(qid, null, val, idx);
        }, 800);
    });
});

// ── Rich text answers (Quill) for writing-type questions ────────────────────
document.querySelectorAll('.rich-answer-wrap').forEach(wrap => {
    const qid = wrap.dataset.qid;
    const idx = parseInt(wrap.dataset.idx);
    const editorEl = wrap.querySelector('.rich-answer-editor');
    const src = document.getElementById('rich-src-' + qid);
    if (!editorEl || !src || typeof Quill === 'undefined') return;

    const quill = new Quill(editorEl, {
        theme: 'snow',
        placeholder: 'Escribe tu respuesta aquí…',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'header': [2, 3, false] }],
                ['blockquote'],
                ['clean'],
            ],
        },
    });

    const initial = wrap.dataset.initial || src.value || '';
    const cached = lsGet(qid);
    const restoreContent = (cached && cached.html) ? cached.html : initial;
    if (restoreContent) {
        quill.clipboard.dangerouslyPasteHTML(restoreContent);
    }

    quill.on('text-change', () => {
        const html = quill.root.innerHTML;
        const isEmpty = quill.getText().trim() === '';
        const value = isEmpty ? '' : html;
        src.value = value;
        lsSave(qid, { html: value });
        clearTimeout(window['_richSave_' + qid]);
        window['_richSave_' + qid] = setTimeout(() => {
            if (!isEmpty) markAnswered(idx);
            saveAnswer(qid, null, value, idx);
        }, 800);
    });
});

// ── Matching ──────────────────────────────────────────────────────────────────
function onMatch(sel) {
    sel.classList.toggle('selected', sel.value !== '');
    const wrapper = sel.closest('.matching-wrapper');
    const qid = wrapper.dataset.qid;
    const idx = parseInt(wrapper.dataset.idx);
    const map = {};
    wrapper.querySelectorAll('select').forEach(s => {
        if (s.value !== '') map[s.dataset.optionId] = s.value;
    });
    const allDone = [...wrapper.querySelectorAll('select')].every(s => s.value !== '');
    if (allDone) markAnswered(idx);
    const json = JSON.stringify(map);
    const hidden = document.getElementById('mt-' + qid);
    if (hidden) hidden.value = json;
    lsSave(qid, { map });
    clearTimeout(window._mtSave);
    window._mtSave = setTimeout(() => saveAnswer(qid, null, json, idx), 400);
}

// ── Identification ────────────────────────────────────────────────────────────
function onIdent(input) {
    const qid = input.dataset.qid;
    const idx = parseInt(input.dataset.idx);
    const wrapper = input.closest('.ident-wrapper');
    const map = {};
    wrapper.querySelectorAll('.ident-input').forEach(inp => {
        if (inp.value.trim() !== '') map[inp.dataset.label] = inp.value.trim();
    });
    const allFilled = [...wrapper.querySelectorAll('.ident-input')].every(inp => inp.value.trim() !== '');
    if (allFilled) markAnswered(idx);
    const json = JSON.stringify(map);
    const hidden = document.getElementById('id-' + qid);
    if (hidden) hidden.value = json;
    lsSave(qid, { text: json });
    clearTimeout(window._idSave);
    window._idSave = setTimeout(() => saveAnswer(qid, null, json, idx), 400);
}

// ── Ordering drag-and-drop ────────────────────────────────────────────────────
(function initOrderingDnD() {
    document.querySelectorAll('.ordering-list').forEach(list => {
        let draggingEl = null;

        list.addEventListener('dragstart', e => {
            const item = e.target.closest('.ordering-item');
            if (!item) return;
            draggingEl = item;
            setTimeout(() => item.classList.add('dragging'), 0);
            e.dataTransfer.effectAllowed = 'move';
        });

        list.addEventListener('dragover', e => {
            e.preventDefault();
            const item = e.target.closest('.ordering-item');
            if (!item || item === draggingEl) return;
            list.querySelectorAll('.ordering-item').forEach(li => li.classList.remove('drag-over'));
            item.classList.add('drag-over');
            const rect = item.getBoundingClientRect();
            if (e.clientY < rect.top + rect.height / 2) item.insertAdjacentElement('beforebegin', draggingEl);
            else item.insertAdjacentElement('afterend', draggingEl);
        });

        list.addEventListener('dragleave', e => {
            const item = e.target.closest('.ordering-item');
            if (item && !item.contains(e.relatedTarget)) item.classList.remove('drag-over');
        });

        list.addEventListener('drop', e => {
            e.preventDefault();
            list.querySelectorAll('.ordering-item').forEach(li => li.classList.remove('drag-over'));
        });

        list.addEventListener('dragend', () => {
            if (draggingEl) draggingEl.classList.remove('dragging');
            list.querySelectorAll('.ordering-item').forEach(li => li.classList.remove('drag-over'));
            draggingEl = null;
            list.querySelectorAll('.order-pos').forEach((p, i) => p.textContent = i + 1);
            const qid  = list.dataset.qid;
            const qIdx = parseInt(list.dataset.idx);
            const ids  = [...list.querySelectorAll('.ordering-item')].map(li => parseInt(li.dataset.optionId));
            const json = JSON.stringify(ids);
            const hidden = document.getElementById('or-' + qid);
            if (hidden) hidden.value = json;
            markAnswered(qIdx);
            lsSave(qid, { ids });
            clearTimeout(window._orSave);
            window._orSave = setTimeout(() => saveAnswer(qid, null, json, qIdx), 400);
        });
    });
})();

// ── Ordering (up/down buttons) ────────────────────────────────────────────────
function moveOrder(btn, dir) {
    const item  = btn.closest('.ordering-item');
    const list  = item.closest('.ordering-list');
    const items = [...list.children];
    const idx   = items.indexOf(item);
    const target = idx + dir;
    if (target < 0 || target >= items.length) return;
    if (dir === -1) list.insertBefore(item, items[target]);
    else items[target].insertAdjacentElement('afterend', item);
    list.querySelectorAll('.order-pos').forEach((p, i) => p.textContent = i + 1);
    const qid  = list.dataset.qid;
    const qIdx = parseInt(list.dataset.idx);
    const ids  = [...list.querySelectorAll('.ordering-item')].map(li => parseInt(li.dataset.optionId));
    const json = JSON.stringify(ids);
    const hidden = document.getElementById('or-' + qid);
    if (hidden) hidden.value = json;
    markAnswered(qIdx);
    lsSave(qid, { ids });
    clearTimeout(window._orSave);
    window._orSave = setTimeout(() => saveAnswer(qid, null, json, qIdx), 400);
}

// ── Completion drag & drop ────────────────────────────────────────────────────
let cpSelectedWord = null;
let cpDragSrc      = null;

function cpClickWord(wordEl) {
    if (wordEl.classList.contains('cp-word-used')) return;
    if (cpSelectedWord === wordEl) {
        wordEl.classList.remove('cp-word-selected');
        cpSelectedWord = null;
        return;
    }
    if (cpSelectedWord) cpSelectedWord.classList.remove('cp-word-selected');
    cpSelectedWord = wordEl;
    wordEl.classList.add('cp-word-selected');
}

function cpClickZone(zoneEl) {
    const qid  = zoneEl.dataset.qid;
    const prev = zoneEl.dataset.word || '';

    if (cpSelectedWord) {
        if (prev) cpReturnToBank(qid, prev);
        cpFillZone(zoneEl, cpSelectedWord.dataset.word);
        cpSelectedWord.classList.add('cp-word-used');
        cpSelectedWord.classList.remove('cp-word-selected');
        cpSelectedWord = null;
        cpSave(qid);
    } else if (prev) {
        cpReturnToBank(qid, prev);
        zoneEl.dataset.word = '';
        zoneEl.textContent  = '';
        zoneEl.classList.add('empty');
        cpSave(qid);
    }
}

function cpFillZone(zoneEl, word) {
    zoneEl.dataset.word = word;
    zoneEl.textContent  = word;
    zoneEl.classList.remove('empty');
}

function cpReturnToBank(qid, word) {
    const bank = document.getElementById('cp-bank-' + qid);
    if (!bank) return;
    bank.querySelectorAll('.cp-word').forEach(w => {
        if (w.dataset.word === word) { w.classList.remove('cp-word-used', 'cp-word-selected'); }
    });
}

function cpDragStart(e, wordEl) {
    cpDragSrc = { word: wordEl.dataset.word, fromZone: null };
    e.dataTransfer.setData('text/plain', wordEl.dataset.word);
    e.dataTransfer.effectAllowed = 'move';
    if (cpSelectedWord) { cpSelectedWord.classList.remove('cp-word-selected'); cpSelectedWord = null; }
}

function cpDragOver(e) { e.preventDefault(); e.currentTarget.classList.add('drag-over'); }
function cpDragLeave(e) { e.currentTarget.classList.remove('drag-over'); }
function cpDragEnd(e) { cpDragSrc = null; }

function cpDrop(e) {
    e.preventDefault();
    const zone = e.currentTarget;
    zone.classList.remove('drag-over');
    if (!cpDragSrc) return;

    const word    = cpDragSrc.word;
    const qid     = zone.dataset.qid;
    const oldWord = zone.dataset.word || '';

    if (oldWord === word) { cpDragSrc = null; return; }

    if (oldWord) cpReturnToBank(qid, oldWord);

    const bank = document.getElementById('cp-bank-' + qid);
    bank?.querySelectorAll('.cp-word').forEach(w => {
        if (w.dataset.word === word) w.classList.add('cp-word-used');
    });

    cpFillZone(zone, word);
    cpDragSrc = null;
    cpSave(qid);
}

function cpSave(qid) {
    const wrapper = document.querySelector('.completion-wrapper[data-qid="' + qid + '"]');
    if (!wrapper) return;
    const total = parseInt(wrapper.dataset.total);
    const zones = wrapper.querySelectorAll('.cp-zone');
    const map   = {};
    let   filled = 0;
    zones.forEach(z => {
        if (z.dataset.word) { map[z.dataset.blank] = z.dataset.word; filled++; }
    });
    const json   = JSON.stringify(map);
    const hidden = document.getElementById('cp-' + qid);
    if (hidden) hidden.value = json;
    const idx = parseInt(wrapper.dataset.idx);
    if (filled === total) markAnswered(idx);
    lsSave(qid, { text: json });
    clearTimeout(window._cpSave);
    window._cpSave = setTimeout(() => saveAnswer(qid, null, json, idx), 400);
}

(function restoreCompletion() {
    document.querySelectorAll('.completion-wrapper').forEach(wrapper => {
        const qid    = wrapper.dataset.qid;
        const hidden = document.getElementById('cp-' + qid);
        if (!hidden || !hidden.value) return;
        let map;
        try { map = JSON.parse(hidden.value); } catch(e) { return; }
        wrapper.querySelectorAll('.cp-zone').forEach(zone => {
            const word = map[zone.dataset.blank];
            if (!word) return;
            cpFillZone(zone, word);
            const bank = document.getElementById('cp-bank-' + qid);
            bank?.querySelectorAll('.cp-word').forEach(w => {
                if (w.dataset.word === word) w.classList.add('cp-word-used');
            });
        });
    });
})();

// ── Submit modal ──────────────────────────────────────────────────────────────
function serializeAll() {
    document.querySelectorAll('.options-container-ms').forEach(c => {
        const qid  = c.dataset.qid;
        const sel  = [...c.querySelectorAll('.option-label.selected')].map(l => parseInt(l.dataset.optionId));
        const h    = document.getElementById('ms-' + qid);
        if (h) h.value = JSON.stringify(sel);
    });
    document.querySelectorAll('.matching-wrapper').forEach(w => {
        const qid = w.dataset.qid;
        const map = {};
        w.querySelectorAll('select').forEach(s => { if (s.value) map[s.dataset.optionId] = s.value; });
        const h = document.getElementById('mt-' + qid);
        if (h) h.value = JSON.stringify(map);
    });
    document.querySelectorAll('.ordering-list').forEach(l => {
        const qid = l.dataset.qid;
        const ids = [...l.querySelectorAll('.ordering-item')].map(li => parseInt(li.dataset.optionId));
        const h   = document.getElementById('or-' + qid);
        if (h) h.value = JSON.stringify(ids);
    });
}

function openSubmitModal() {
    serializeAll();
    const unanswered = answered.filter(v => !v).length;
    const nFlagged   = flagged.filter((f, i) => f).length;
    const nAnswered  = answered.filter(Boolean).length;

    const icon     = document.getElementById('confirmIcon');
    const title    = document.getElementById('confirmTitle');
    const subtitle = document.getElementById('confirmSubtitle');
    const chips    = document.getElementById('confirmChips');
    const stats    = document.getElementById('confirmStats');

    if (unanswered === 0 && nFlagged === 0) {
        icon.style.background = '#D1FAE5';
        icon.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#059669;font-size:1.5rem;"></i>';
        title.textContent    = '¡Todo listo!';
        subtitle.textContent = 'Respondiste todas las preguntas. ¿Deseas enviar el examen?';
        chips.innerHTML = '';
        stats.innerHTML = `<div style="text-align:center;color:#059669;font-weight:700;">${nAnswered} de ${TOTAL_Q} preguntas respondidas ✓</div>`;
        document.getElementById('confirmNextBtn').className = 'btn btn-success flex-fill fw-semibold';
    } else {
        icon.style.background = '#FEF9C3';
        icon.innerHTML = '<i class="bi bi-exclamation-triangle-fill" style="color:#F59E0B;font-size:1.5rem;"></i>';
        title.textContent    = 'Revisa antes de enviar';
        subtitle.textContent = 'Tienes preguntas pendientes. Puedes regresar o continuar.';

        let chipsHtml = '';
        if (unanswered > 0) chipsHtml += `<span class="confirm-chip chip-unanswered"><i class="bi bi-circle me-1"></i>${unanswered} sin responder</span>`;
        if (nFlagged > 0)   chipsHtml += `<span class="confirm-chip chip-flagged"><i class="bi bi-flag-fill me-1"></i>${nFlagged} marcada${nFlagged > 1 ? 's' : ''} para revisar</span>`;
        if (nAnswered > 0)  chipsHtml += `<span class="confirm-chip chip-answered"><i class="bi bi-check me-1"></i>${nAnswered} respondida${nAnswered > 1 ? 's' : ''}</span>`;
        chips.innerHTML = chipsHtml;

        let rows = '';
        if (unanswered > 0) {
            const nums = answered.map((a, i) => !a ? `<span onclick="closeSubmitModal();goTo(${i})" style="cursor:pointer;text-decoration:underline;">${i+1}</span>` : null).filter(Boolean).join(', ');
            rows += `<div style="margin-bottom:.5rem;"><span style="color:#991B1B;font-weight:700;">Sin responder:</span> ${nums}</div>`;
        }
        if (nFlagged > 0) {
            const nums = flagged.map((f, i) => f ? `<span onclick="closeSubmitModal();goTo(${i})" style="cursor:pointer;text-decoration:underline;">${i+1}</span>` : null).filter(Boolean).join(', ');
            rows += `<div><span style="color:#92400E;font-weight:700;">Para revisar:</span> ${nums}</div>`;
        }
        stats.innerHTML = rows;
        document.getElementById('confirmNextBtn').className = 'btn btn-warning flex-fill fw-semibold text-dark';
    }

    document.getElementById('confirmOverlay').style.display = 'flex';
}

function closeSubmitModal() {
    document.getElementById('confirmOverlay').style.display  = 'none';
    document.getElementById('confirmOverlay2').style.display = 'none';
}

function confirmStep2() {
    document.getElementById('confirmOverlay').style.display  = 'none';
    document.getElementById('confirmOverlay2').style.display = 'flex';
}

function backToStep1() {
    document.getElementById('confirmOverlay2').style.display = 'none';
    document.getElementById('confirmOverlay').style.display  = 'flex';
}

function doSubmit() {
    serializeAll();
    try {
        Object.keys(localStorage).forEach(k => {
            if (k.startsWith(LS_PREFIX)) localStorage.removeItem(k);
        });
    } catch(e) {}
    const form = document.getElementById('examForm');
    form.onsubmit = null;
    form.action = SUBMIT_URL;
    form.submit();
}

// Prevent accidental back navigation
history.pushState(null, null, location.href);
window.addEventListener('popstate', () => history.pushState(null, null, location.href));

// Init
refreshProgress();
refreshGrid();
refreshNav();

// ── Custom audio player ───────────────────────────────────────────────────────
function toggleAudio(btn) {
    const player = btn.closest('.audio-player');
    const audio  = player.querySelector('audio');
    const icon   = btn.querySelector('i');
    const time   = player.querySelector('.audio-time');

    if (audio.paused) {
        document.querySelectorAll('.audio-player audio').forEach(a => {
            if (a !== audio && !a.paused) {
                a.pause();
                const ob = a.closest('.audio-player').querySelector('.audio-play-btn i');
                if (ob) ob.className = 'bi bi-play-fill';
            }
        });
        audio.play();
        icon.className = 'bi bi-pause-fill';
    } else {
        audio.pause();
        icon.className = 'bi bi-play-fill';
    }

    audio.ontimeupdate = () => {
        const fmt = s => Math.floor(s/60) + ':' + String(Math.floor(s%60)).padStart(2,'0');
        time.textContent = fmt(audio.currentTime) + ' / ' + (isNaN(audio.duration) ? '--:--' : fmt(audio.duration));
    };
    audio.onloadedmetadata = () => {
        const fmt = s => Math.floor(s/60) + ':' + String(Math.floor(s%60)).padStart(2,'0');
        time.textContent = '0:00 / ' + fmt(audio.duration);
    };
    audio.onended = () => { icon.className = 'bi bi-play-fill'; };
}

// ── Lightbox ──────────────────────────────────────────────────────────────────
function openLightbox(src) {
    document.getElementById('lbImg').src = src;
    document.getElementById('lbOverlay').classList.add('open');
}
function closeLightbox(e) {
    if (e && e.target !== e.currentTarget && !e.target.closest('.lb-close')) return;
    document.getElementById('lbOverlay').classList.remove('open');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') document.getElementById('lbOverlay').classList.remove('open'); });
