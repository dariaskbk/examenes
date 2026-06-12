// Question Bank: load + filter + select + submit.
// Requires window.ExamShow.canEdit + window.ExamShow.routes.questionBank
// + the global escHtml() helper (declared in show.blade.php).
(function () {
    const cfg = window.ExamShow || {};
    if (!cfg.canEdit) return;
    const BANK_URL = cfg.routes && cfg.routes.questionBank;
    if (!BANK_URL) return;

    let bankData = [];
    const bankSelected = new Set();

    window.loadQuestionBank = function () {
        const list    = document.getElementById('bankList');
        const loading = document.getElementById('bankLoading');
        const empty   = document.getElementById('bankEmpty');
        list.innerHTML = '';
        empty.style.display   = 'none';
        loading.style.display = '';
        bankSelected.clear();
        updateBankCount();

        const sameSubject = document.getElementById('bankSameSubject')?.checked ? 1 : 0;
        const url = BANK_URL + '?same_subject=' + sameSubject;

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                bankData = data.questions || [];
                loading.style.display = 'none';
                renderBank();
            })
            .catch(() => {
                loading.style.display = 'none';
                empty.style.display = '';
                document.getElementById('bankEmpty').querySelector('p').textContent = 'Error al cargar el banco.';
            });
    };

    function renderBank() {
        const list   = document.getElementById('bankList');
        const empty  = document.getElementById('bankEmpty');
        const search = (document.getElementById('bankSearch').value || '').toLowerCase().trim();
        const type   = document.getElementById('bankType').value;

        const filtered = bankData.filter(q => {
            if (type && q.type !== type) return false;
            if (search && !q.text.toLowerCase().includes(search) && !q.exam_title.toLowerCase().includes(search)) return false;
            return true;
        });

        list.innerHTML = '';
        if (filtered.length === 0) {
            empty.style.display = '';
            empty.querySelector('p').textContent = 'No hay preguntas que coincidan.';
            return;
        }
        empty.style.display = 'none';

        filtered.forEach(q => {
            const checked = bankSelected.has(q.id);
            const el = document.createElement('label');
            el.className = 'd-flex align-items-start gap-2 p-2';
            el.style.cssText = 'border:1.5px solid ' + (checked ? '#A5B4FC' : '#E2E8F0') + ';border-radius:10px;cursor:pointer;background:' + (checked ? '#EEF2FF' : '#fff') + ';';
            el.innerHTML =
                '<input type="checkbox" class="form-check-input mt-1 flex-shrink-0" ' + (checked ? 'checked' : '') + ' onchange="toggleBank(' + q.id + ', this)">' +
                '<div style="min-width:0;flex:1;">' +
                    '<div style="font-size:.82rem;color:#1E293B;line-height:1.4;">' + escHtml(q.text) + '</div>' +
                    '<div class="d-flex flex-wrap gap-1 mt-1" style="font-size:.66rem;">' +
                        '<span class="badge" style="background:#EEF2FF;color:#4F46E5;">' + escHtml(q.type_label) + '</span>' +
                        '<span class="badge" style="background:#F1F5F9;color:#475569;">' + q.points + ' pt</span>' +
                        (q.options_count > 0 ? '<span class="badge" style="background:#F1F5F9;color:#475569;">' + q.options_count + ' opc.</span>' : '') +
                        '<span class="badge" style="background:#FFF7ED;color:#9A3412;"><i class="bi bi-journal-text me-1"></i>' + escHtml(q.exam_title) + '</span>' +
                    '</div>' +
                '</div>';
            list.appendChild(el);
        });
    }

    window.filterBank = function () { renderBank(); };

    window.toggleBank = function (id, cb) {
        if (cb.checked) bankSelected.add(id); else bankSelected.delete(id);
        const label = cb.closest('label');
        if (label) {
            label.style.borderColor = cb.checked ? '#A5B4FC' : '#E2E8F0';
            label.style.background  = cb.checked ? '#EEF2FF' : '#fff';
        }
        updateBankCount();
    };

    function updateBankCount() {
        const n = bankSelected.size;
        document.getElementById('bankSelCount').textContent = n;
        document.getElementById('bankAddBtn').disabled = n === 0;
    }
    window.updateBankCount = updateBankCount;

    window.submitBank = function (form) {
        if (bankSelected.size === 0) return false;
        form.querySelectorAll('input[name="question_ids[]"]').forEach(e => e.remove());
        bankSelected.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'question_ids[]';
            inp.value = id;
            form.appendChild(inp);
        });
        AppLoader.show('Agregando preguntas…', 'Copiando las preguntas seleccionadas a este examen.');
        return true;
    };
})();
