// Anti-cheat / proctoring runtime. Loaded conditionally by the blade when
// $exam->proctoring && !$previewMode. Depends on window.ExamState.proctoring:
//   { urls: { log, status }, initialLeaveCount }
// and on globals from student-exam.js (CSRF, ATTEMPT_PAUSED, currentIdx, QUESTION_IDS).

(function () {
    const cfg = (window.ExamState && window.ExamState.proctoring) || null;
    if (!cfg) return;
    const PROCTOR_URL = cfg.urls && cfg.urls.log;
    const STATUS_URL  = cfg.urls && cfg.urls.status;
    if (!PROCTOR_URL) return;

    let leaveCount   = parseInt(cfg.initialLeaveCount, 10) || 0;
    let isAway       = false;
    let examFinished = false;

    window.addEventListener('beforeunload', () => { examFinished = true; });

    function logIncident(type) {
        if (examFinished) return;
        const body = new FormData();
        body.append('_token', CSRF);
        body.append('type', type);
        const idx = (typeof currentIdx === 'number') ? currentIdx : 0;
        const qid = (typeof QUESTION_IDS !== 'undefined' && QUESTION_IDS[idx]) ? QUESTION_IDS[idx] : '';
        body.append('question_index', String(idx + 1));
        if (qid) body.append('question_id', String(qid));
        fetch(PROCTOR_URL, { method: 'POST', body, keepalive: true })
            .then(r => r.json())
            .then(data => {
                if (data && typeof data.count === 'number') {
                    leaveCount = data.count;
                    updateLeaveBadge();
                }
                if (data && data.paused) showPauseOverlay();
            })
            .catch(() => {});
    }

    // ── Blocking overlay shown when strict proctoring pauses the exam ─────────
    function showPauseOverlay() {
        let ov = document.getElementById('strictPauseOverlay');
        if (!ov) {
            ov = document.createElement('div');
            ov.id = 'strictPauseOverlay';
            ov.style.cssText = 'position:fixed;inset:0;z-index:5000;background:rgba(15,23,42,.92);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;';
            ov.innerHTML = `
              <div style="background:#fff;border-radius:18px;max-width:460px;width:90%;padding:2rem 1.8rem;text-align:center;box-shadow:0 24px 64px rgba(0,0,0,.4);">
                <div style="width:72px;height:72px;border-radius:50%;background:#FEE2E2;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;">
                  <i class="bi bi-shield-lock-fill" style="color:#DC2626;font-size:2rem;"></i>
                </div>
                <h5 class="fw-bold mb-2" style="color:#991B1B;">Examen pausado por seguridad</h5>
                <p class="text-muted small mb-2">Se detectaron varias salidas de la pantalla del examen.</p>
                <p style="color:#1E293B;font-size:.9rem;">
                  El examen está <strong>pausado</strong> hasta que el docente autorice tu continuación.
                  <br><span style="color:#DC2626;font-size:.8rem;">Avisa al docente que estás esperando autorización.</span>
                </p>
                <div class="d-flex justify-content-center align-items-center gap-2 mt-3" style="color:#64748B;font-size:.78rem;">
                    <div class="spinner-border spinner-border-sm" style="width:.9rem;height:.9rem;"></div>
                    Esperando autorización…
                </div>
              </div>`;
            document.body.appendChild(ov);
        }
        ov.style.display = 'flex';
        startPausePolling();
    }
    function hidePauseOverlay() {
        const ov = document.getElementById('strictPauseOverlay');
        if (ov) ov.style.display = 'none';
    }
    let _pauseTimer = null;
    function startPausePolling() {
        if (_pauseTimer || !STATUS_URL) return;
        _pauseTimer = setInterval(() => {
            fetch(STATUS_URL, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    if (!data || !data.ok) return;
                    if (!data.paused) {
                        clearInterval(_pauseTimer); _pauseTimer = null;
                        hidePauseOverlay();
                    }
                })
                .catch(() => {});
        }, 3000);
    }

    function updateLeaveBadge() {
        const badge = document.getElementById('leaveBadge');
        if (badge && leaveCount > 0) {
            badge.style.display = '';
            badge.textContent = leaveCount;
        }
    }
    updateLeaveBadge();

    // ── Screen-leave detection ────────────────────────────────────────────────
    function handleAway() {
        if (isAway || examFinished) return;
        isAway = true;
        leaveCount++;
        updateLeaveBadge();
        showProctorWarn(leaveCount);
        logIncident('screen_leave');
    }
    function handleBack() { isAway = false; }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) handleAway(); else handleBack();
    });
    window.addEventListener('blur',  handleAway);
    window.addEventListener('focus', handleBack);

    // ── Warning overlay ───────────────────────────────────────────────────────
    window.showProctorWarn = function (count) {
        const ov = document.getElementById('proctorWarn');
        const c  = document.getElementById('proctorWarnCount');
        if (c)  c.textContent = count;
        if (ov) ov.style.display = 'flex';
    };
    window.dismissProctorWarn = function () {
        const ov = document.getElementById('proctorWarn');
        if (ov) ov.style.display = 'none';
    };

    // ── Block copy / paste / right-click ──────────────────────────────────────
    ['copy', 'cut'].forEach(evt =>
        document.addEventListener(evt, e => { e.preventDefault(); logIncident('copy'); })
    );
    document.addEventListener('paste', e => { e.preventDefault(); logIncident('paste'); });
    document.addEventListener('contextmenu', e => { e.preventDefault(); logIncident('contextmenu'); });

    // ── Fullscreen ────────────────────────────────────────────────────────────
    window.toggleFullscreen = function () {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen?.().catch(() => {});
        } else {
            document.exitFullscreen?.();
        }
    };
    document.addEventListener('fullscreenchange', () => {
        const label = document.getElementById('fsLabel');
        const inFs  = !!document.fullscreenElement;
        if (label) label.textContent = inFs ? 'Salir de pantalla completa' : 'Pantalla completa';
        if (!inFs && !examFinished) logIncident('fullscreen_exit');
    });

    // If the page loads with the attempt already paused → show overlay immediately
    if (ATTEMPT_PAUSED) showPauseOverlay();
})();
