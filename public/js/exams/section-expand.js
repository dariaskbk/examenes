// Section expand / student AJAX. Requires window.ExamShow.routes.sectionStudents
// (template with literal "__SEC__" placeholder).
(function() {
    const cfg = window.ExamShow || {};
    const sectionStudentsUrlTpl = cfg.routes && cfg.routes.sectionStudents;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    document.querySelectorAll('.btn-expand-sec').forEach(btn => {
        btn.addEventListener('click', function() {
            const secId  = this.dataset.sec;
            const panel  = document.querySelector(`.student-panel[data-sec="${secId}"]`);
            const icon   = this.querySelector('i');
            const open   = panel.style.display !== 'none';

            if (open) { panel.style.display = 'none'; icon.className = 'bi bi-chevron-down'; return; }
            panel.style.display = 'block';
            icon.className = 'bi bi-chevron-up';

            if (panel.dataset.loaded) return;
            panel.dataset.loaded = '1';

            if (!sectionStudentsUrlTpl) return;
            fetch(sectionStudentsUrlTpl.replace('__SEC__', secId), {
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                const body = panel.querySelector('.student-panel-body');
                if (!data.students || data.students.length === 0) {
                    body.innerHTML = '<span class="text-muted">Sin estudiantes.</span>'; return;
                }
                body.innerHTML = data.students.map(s => `
                    <label class="d-flex align-items-center gap-2 mb-1 py-1 px-1 rounded" style="cursor:pointer;${s.has_code ? 'opacity:.5;' : ''}">
                        <input type="checkbox" name="student_ids[]" value="${s.id}"
                               class="form-check-input mt-0 student-chk" data-sec="${secId}"
                               ${s.has_code ? 'disabled' : ''}>
                        <span style="font-size:.75rem;flex-grow:1;">${s.name}</span>
                        ${s.has_code ? '<i class="bi bi-check-circle-fill text-success flex-shrink-0" style="font-size:.75rem;" title="Ya tiene código"></i>' : ''}
                    </label>
                `).join('');
                body.querySelectorAll('.student-chk').forEach(chk => chk.addEventListener('change', syncSectionCheckbox));
            })
            .catch(() => {
                panel.querySelector('.student-panel-body').innerHTML = '<span class="text-danger">Error al cargar.</span>';
            });
        });
    });

    document.querySelectorAll('.section-chk').forEach(chk => {
        chk.addEventListener('change', function() {
            const secId = this.value;
            const panel = document.querySelector(`.student-panel[data-sec="${secId}"]`);
            if (panel && panel.dataset.loaded) {
                panel.querySelectorAll('.student-chk:not([disabled])').forEach(s => s.checked = this.checked);
            }
        });
    });

    function syncSectionCheckbox(e) {
        const secId   = e.target.dataset.sec;
        const panel   = document.querySelector(`.student-panel[data-sec="${secId}"]`);
        const allChks = [...panel.querySelectorAll('.student-chk:not([disabled])')];
        const secChk  = document.querySelector(`.section-chk[value="${secId}"]`);
        if (secChk) secChk.checked = allChks.every(c => c.checked);
    }
})();
