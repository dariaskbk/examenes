// Real-time exam monitor for teachers.
// Depends on window.ExamMonitor = { urls: { data, attemptsBase }, csrf }.
(function () {
    const cfg = window.ExamMonitor || {};
    const MONITOR_URL     = (cfg.urls && cfg.urls.data) || '';
    const RESUME_URL_BASE = (cfg.urls && cfg.urls.attemptsBase) || '';
    const CSRF            = cfg.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';
    let rowsState = [];

    function fmtTime(s) {
        if (s <= 0) return '00:00';
        const m = Math.floor(s / 60), sec = s % 60;
        return (m < 10 ? '0' : '') + m + ':' + (sec < 10 ? '0' : '') + sec;
    }
    function escHtml(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function renderMonitor() {
        const body = document.getElementById('monitorBody');
        if (rowsState.length === 0) {
            body.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">' +
                '<i class="bi bi-cup-hot" style="font-size:1.6rem;display:block;margin-bottom:.4rem;color:#CBD5E1;"></i>' +
                'Nadie está rindiendo el examen en este momento.</td></tr>';
            return;
        }
        body.innerHTML = rowsState.map(r => {
            const pct  = r.total > 0 ? Math.round(r.answered / r.total * 100) : 0;
            const rem  = r.remaining;
            let tColor = '#059669';
            if (rem <= 60) tColor = '#DC2626';
            else if (rem <= 300) tColor = '#D97706';
            const timeTxt = rem <= 0 ? 'Tiempo agotado' : fmtTime(rem);

            const flag = r.focus_loss > 0
                ? '<span class="badge" style="background:#FEE2E2;color:#991B1B;font-size:.68rem;"><i class="bi bi-shield-exclamation me-1"></i>' + r.focus_loss + '</span>'
                : '<span class="text-muted" style="font-size:.75rem;">—</span>';

            const pausedRow = r.paused
                ? '<tr class="border-top" data-id="' + r.id + '" style="background:#FEF2F2;">' +
                  '<td colspan="5" class="px-3 py-2">' +
                    '<div class="d-flex align-items-center gap-2">' +
                      '<span class="badge" style="background:#DC2626;color:#fff;animation:pulse 1.4s infinite;">' +
                        '<i class="bi bi-pause-circle-fill me-1"></i>PAUSADO' +
                      '</span>' +
                      '<strong>' + escHtml(r.student) + '</strong>' +
                      '<span class="text-muted" style="font-size:.78rem;">— última salida en pregunta #' + (r.last_q_index || '?') + '</span>' +
                      '<button class="btn btn-sm btn-warning ms-auto" style="font-size:.78rem;" onclick=\'openVerifyModal(' + JSON.stringify({id:r.id, student:r.student, q_id:r.last_q_id, q_index:r.last_q_index, focus_loss:r.focus_loss}).replace(/\'/g,"&#39;") + ')\'>' +
                        '<i class="bi bi-check2-square me-1"></i>Verificar' +
                      '</button>' +
                    '</div>' +
                  '</td></tr>'
                : null;
            if (pausedRow) return pausedRow;

            return '<tr class="border-top" data-id="' + r.id + '">' +
                '<td class="px-3 py-2 fw-500">' + escHtml(r.student) + '</td>' +
                '<td class="py-2" style="min-width:160px;">' +
                    '<div class="d-flex align-items-center gap-2">' +
                        '<div class="flex-grow-1" style="max-width:130px;"><div class="progress" style="height:6px;border-radius:4px;">' +
                            '<div class="progress-bar bg-info" style="width:' + pct + '%"></div></div></div>' +
                        '<span class="text-muted" style="font-size:.76rem;white-space:nowrap;">' + r.answered + '/' + r.total + '</span>' +
                    '</div></td>' +
                '<td class="py-2 text-center"><span class="fw-700 monitor-time" style="font-variant-numeric:tabular-nums;color:' + tColor + ';">' + timeTxt + '</span></td>' +
                '<td class="py-2 text-center">' + flag + '</td>' +
                '<td class="py-2 text-center text-muted" style="font-size:.78rem;">' + (r.started_at || '—') + '</td>' +
            '</tr>';
        }).join('');
    }

    let isFetching = false;
    function fetchMonitor() {
        if (isFetching) return;
        isFetching = true;

        const dot   = document.getElementById('liveDot');
        const btn   = document.getElementById('refreshBtn');
        const icon  = document.getElementById('refreshIcon');
        const label = document.getElementById('refreshLabel');
        const body  = document.getElementById('monitorBody');

        btn.disabled = true;
        icon.className = 'spinner-border spinner-border-sm me-1';
        label.textContent = 'Actualizando…';
        dot.style.background = '#D97706';
        body.style.transition = 'opacity .15s';
        body.style.opacity = '.4';
        const startedAt = Date.now();

        fetch(MONITOR_URL, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                rowsState = data.in_progress || [];
                document.getElementById('stat-in_progress_count').textContent = data.in_progress_count;
                document.getElementById('stat-submitted_count').textContent   = data.submitted_count;
                document.getElementById('stat-total_codes').textContent       = data.total_codes;
                document.getElementById('serverTime').textContent = data.server_time;
                document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('es-CR');
                dot.style.background = '#10B981';
                renderMonitor();
            })
            .catch(() => {
                dot.style.background = '#DC2626';
            })
            .finally(() => {
                const wait = Math.max(0, 450 - (Date.now() - startedAt));
                setTimeout(() => {
                    btn.disabled = false;
                    icon.className = 'bi bi-arrow-clockwise me-1';
                    label.textContent = 'Actualizar';
                    body.style.opacity = '1';
                    isFetching = false;
                }, wait);
            });
    }
    window.fetchMonitor = fetchMonitor;

    // Local 1s tick: decrement timers between fetches so it feels live.
    function tickLocal() {
        let changed = false;
        rowsState.forEach(r => { if (r.remaining > 0) { r.remaining--; changed = true; } });
        if (changed) {
            document.querySelectorAll('#monitorBody tr[data-id]').forEach(tr => {
                const id = parseInt(tr.dataset.id, 10);
                const r = rowsState.find(x => x.id === id);
                if (!r) return;
                const el = tr.querySelector('.monitor-time');
                if (!el) return;
                let tColor = '#059669';
                if (r.remaining <= 60) tColor = '#DC2626';
                else if (r.remaining <= 300) tColor = '#D97706';
                el.style.color = tColor;
                el.textContent = r.remaining <= 0 ? 'Tiempo agotado' : fmtTime(r.remaining);
            });
        }
    }

    fetchMonitor();
    setInterval(tickLocal, 1000);

    // ── Verify paused attempt ────────────────────────────────────────────────
    window.openVerifyModal = function (info) {
        Swal.fire({
            title: 'Verificar pausa: ' + info.student,
            html: '<div style="text-align:left;font-size:.86rem;color:#475569;">' +
                  '<p>El estudiante salió de la pantalla <strong>' + info.focus_loss + ' veces</strong>. La última salida fue en la <strong>pregunta #' + (info.q_index || '?') + '</strong>.</p>' +
                  '<p>Antes de reanudar, verifica con el estudiante el motivo de la salida.</p>' +
                  '<div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:.6rem .75rem;margin-top:.6rem;">' +
                  '<label style="display:flex;align-items:flex-start;gap:.5rem;font-size:.82rem;cursor:pointer;">' +
                  '<input type="checkbox" id="swalVoidQ" style="margin-top:3px;">' +
                  '<span>Anular la pregunta #' + (info.q_index || '?') + ' (valdrá <strong>0 puntos</strong>, el máximo del examen no cambia).</span>' +
                  '</label></div></div>',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: 'Reanudar examen',
            denyButtonText: 'Cerrar intento',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#059669',
            denyButtonColor: '#DC2626',
            cancelButtonColor: '#94A3B8',
        }).then(result => {
            if (result.isConfirmed) {
                const voidIt = document.getElementById('swalVoidQ')?.checked;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = RESUME_URL_BASE + '/' + info.id + '/resume';
                form.innerHTML = '<input type="hidden" name="_token" value="' + CSRF + '">' +
                                 (voidIt && info.q_id ? '<input type="hidden" name="void_question_id" value="' + info.q_id + '">' : '');
                document.body.appendChild(form);
                AppLoader.show('Reanudando examen…', voidIt ? 'Anulando la pregunta seleccionada.' : 'Liberando al estudiante.');
                form.submit();
            } else if (result.isDenied) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = RESUME_URL_BASE + '/' + info.id + '/close';
                form.innerHTML = '<input type="hidden" name="_token" value="' + CSRF + '">';
                document.body.appendChild(form);
                AppLoader.show('Cerrando intento…', 'Calificando lo guardado.');
                form.submit();
            }
        });
    };

    // Subtle pulse animation
    const _style = document.createElement('style');
    _style.textContent = '@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .55; } }';
    document.head.appendChild(_style);
})();
