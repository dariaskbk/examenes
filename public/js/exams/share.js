// Compartir examen con otro docente.
// Requires window.ExamShow.canShare + window.ExamShow.routes.shareSearchTeachers
// + the global escHtml() helper (declared in show.blade.php).
(function () {
    const cfg = window.ExamShow || {};
    if (!cfg.canShare) return;
    const SHARE_SEARCH_URL = cfg.routes && cfg.routes.shareSearchTeachers;
    if (!SHARE_SEARCH_URL) return;

    const sharedTeachers = new Map();
    let _searchTimer = null;

    window.searchTeachersAjax = function (q) {
        clearTimeout(_searchTimer);
        const results = document.getElementById('teacherResults');
        if ((q || '').trim().length < 2) {
            results.innerHTML = '<div class="text-muted px-2 py-2" style="font-size:.74rem;">Escribe al menos 2 letras…</div>';
            return;
        }
        _searchTimer = setTimeout(() => {
            fetch(SHARE_SEARCH_URL + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    const list = data.teachers || [];
                    if (!list.length) {
                        results.innerHTML = '<div class="text-muted px-2 py-2" style="font-size:.74rem;">Sin resultados.</div>';
                        return;
                    }
                    results.innerHTML = list.map(t => {
                        const taken = sharedTeachers.has(t.id) ? ' style="opacity:.5;pointer-events:none;"' : '';
                        return '<div class="d-flex align-items-center justify-content-between px-2 py-1 border-bottom"' + taken + '>' +
                            '<div><div class="fw-500">' + escHtml(t.name) + '</div>' +
                            '<div class="text-muted" style="font-size:.7rem;">' + escHtml(t.email) + '</div></div>' +
                            '<button type="button" class="btn btn-sm btn-outline-success" style="font-size:.7rem;" onclick="addTeacher(' + t.id + ', \'' + escHtml(t.name).replace(/'/g, "\\'") + '\')">' +
                            '<i class="bi bi-plus"></i></button></div>';
                    }).join('');
                })
                .catch(() => { results.innerHTML = '<div class="text-danger px-2 py-2" style="font-size:.74rem;">Error de red.</div>'; });
        }, 250);
    };

    window.addTeacher = function (id, name) {
        if (sharedTeachers.has(id)) return;
        sharedTeachers.set(id, name);
        renderSelected();
    };
    window.removeTeacher = function (id) {
        sharedTeachers.delete(id);
        renderSelected();
    };
    function renderSelected() {
        const wrap = document.getElementById('selectedTeachers');
        document.getElementById('selCount').textContent = sharedTeachers.size;
        document.getElementById('shareSubmitBtn').disabled = sharedTeachers.size === 0;
        if (sharedTeachers.size === 0) {
            wrap.innerHTML = '<span class="text-muted" id="selPlaceholder" style="font-size:.74rem;">Aún no has seleccionado docentes…</span>';
            return;
        }
        wrap.innerHTML = [...sharedTeachers.entries()].map(([id, name]) =>
            '<span class="badge d-inline-flex align-items-center gap-1" style="background:#EDE9FE;color:#5B21B6;font-size:.74rem;padding:5px 8px;">' +
            escHtml(name) +
            '<button type="button" class="btn p-0 border-0 bg-transparent" style="color:#5B21B6;font-size:.9rem;line-height:1;" onclick="removeTeacher(' + id + ')">×</button>' +
            '</span>'
        ).join('');
    }
    window.renderSelected = renderSelected;

    window.submitShare = function (form) {
        if (sharedTeachers.size === 0) return false;
        form.querySelectorAll('input[name="teacher_ids[]"]').forEach(e => e.remove());
        sharedTeachers.forEach((_, id) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'teacher_ids[]'; inp.value = id;
            form.appendChild(inp);
        });
        AppLoader.show('Compartiendo…', 'Enviando invitaciones a los docentes seleccionados.');
        return true;
    };
})();
