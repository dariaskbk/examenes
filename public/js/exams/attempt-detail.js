// Teacher grading widget: per-answer manual scoring with optional rubric grid.
// Reads dataset.url/max/answerId from each .grade-widget, calls the URL with
// {points_earned|rubric_choices, feedback}, updates the page totals on success.
document.querySelectorAll('.grade-widget').forEach(widget => {
    const btn          = widget.querySelector('.grade-save-btn');
    const input        = widget.querySelector('.grade-input');
    const feedback     = widget.querySelector('.grade-feedback');
    const status       = widget.querySelector('.grade-status');
    const url          = widget.dataset.url;
    const max          = parseFloat(widget.dataset.max);
    const answerId     = widget.dataset.answerId;
    const rubricGrader = widget.querySelector('.rubric-grader');

    // ── Rubric: click a cell → select level for that criterion ───────────────
    if (rubricGrader) {
        rubricGrader.addEventListener('click', e => {
            const cell = e.target.closest('.rubric-cell');
            if (!cell) return;
            const crit = cell.dataset.crit;
            rubricGrader.querySelectorAll('.rubric-cell[data-crit="' + crit + '"]').forEach(c => {
                c.style.background = '#fff';
                c.style.border = '1px solid #E2E8F0';
                c.dataset.selected = '';
            });
            cell.style.background = '#D1FAE5';
            cell.style.border = '2px solid #10B981';
            cell.dataset.selected = '1';
            recomputeRubricTotal();
        });
    }

    function recomputeRubricTotal() {
        if (!rubricGrader) return 0;
        const headerPts = [];
        rubricGrader.querySelectorAll('thead th').forEach((th, idx) => {
            if (idx === 0) return;
            const m = th.textContent.match(/([\d.]+)\s*pts/);
            headerPts.push(m ? parseFloat(m[1]) : 0);
        });
        let total = 0;
        rubricGrader.querySelectorAll('.rubric-cell[data-selected="1"]').forEach(cell => {
            const lvl = parseInt(cell.dataset.lvl, 10);
            total += headerPts[lvl] || 0;
        });
        if (total > max) total = max;
        const totalEl = rubricGrader.querySelector('.rubric-total');
        if (totalEl) totalEl.textContent = total.toFixed(2).replace(/\.00$/, '');
        return total;
    }

    btn.addEventListener('click', async () => {
        let body;
        if (rubricGrader) {
            const choices = {};
            rubricGrader.querySelectorAll('.rubric-cell[data-selected="1"]').forEach(cell => {
                choices[cell.dataset.crit] = parseInt(cell.dataset.lvl, 10);
            });
            body = JSON.stringify({
                rubric_choices: choices,
                feedback: feedback?.value?.trim() || null,
            });
        } else {
            const val = parseFloat(input.value);
            if (isNaN(val) || val < 0 || val > max) {
                showStatus(status, `Puntaje debe estar entre 0 y ${max}`, '#DC2626');
                return;
            }
            body = JSON.stringify({
                points_earned: val,
                feedback: feedback?.value?.trim() || null,
            });
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass me-1"></i>Guardando…';

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    'Accept': 'application/json',
                },
                body,
            });

            if (!res.ok) throw new Error('Error ' + res.status);
            const data = await res.json();

            const ptsEl = document.querySelector(`#pts-${answerId} .pts-val`);
            if (ptsEl) {
                ptsEl.textContent = data.points_earned.toFixed(1);
                ptsEl.style.color = data.points_earned > 0 ? '#059669' : '#DC2626';
            }

            const hdrScore = document.getElementById('hdr-score');
            const hdrPct   = document.getElementById('hdr-pct');
            if (hdrScore) hdrScore.textContent = data.attempt_score.toFixed(1);
            if (hdrPct)   hdrPct.textContent   = data.percentage.toFixed(1);

            const pending = widget.querySelector('.grade-pending-badge');
            if (pending) {
                pending.outerHTML = `<span class="grade-saved-badge" style="font-size:.72rem;color:#065F46;background:#D1FAE5;padding:2px 8px;border-radius:20px;border:1px solid #A7F3D0;"><i class="bi bi-check-circle me-1"></i>Revisado</span>`;
            }

            showStatus(status, '✓ Guardado', '#059669');
            AppToast.show('Calificación guardada correctamente', 'success');
        } catch (e) {
            showStatus(status, '✗ Error al guardar', '#DC2626');
            AppToast.show('Error al guardar la calificación', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Guardar calificación';
        }
    });
});

function showStatus(el, msg, color) {
    el.textContent = msg;
    el.style.color = color;
    el.style.display = 'inline';
    setTimeout(() => el.style.display = 'none', 2500);
}
