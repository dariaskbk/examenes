// Accessibility panel for the student exam runtime.
// Font size, high contrast, dyslexic font, with localStorage persistence.

const A11Y_LS_FS  = 'exam_a11y_fs';
const A11Y_LS_HC  = 'exam_a11y_hc';
const A11Y_LS_DYS = 'exam_a11y_dys';

function toggleA11yPanel() {
    const panel = document.getElementById('a11yPanel');
    panel.classList.toggle('open');
}
document.addEventListener('click', e => {
    const panel = document.getElementById('a11yPanel');
    const btn   = document.getElementById('a11yBtn');
    if (panel && panel.classList.contains('open') && !panel.contains(e.target) && !btn.contains(e.target)) {
        panel.classList.remove('open');
    }
});

// ── Tamaño de letra ───────────────────────────────────────────────────────────
const FS_STEPS = [16, 19, 22, 25];

function setFontSize(px) {
    document.documentElement.style.fontSize = px + 'px';
    document.querySelectorAll('.a11y-fs-btn').forEach(b => {
        b.classList.toggle('active', parseInt(b.dataset.fs, 10) === px);
    });
    try { localStorage.setItem(A11Y_LS_FS, px); } catch(e) {}
}

function stepFontSize(delta) {
    const current = parseInt(document.documentElement.style.fontSize || '16', 10);
    const idx = FS_STEPS.indexOf(current);
    const newIdx = Math.max(0, Math.min(FS_STEPS.length - 1, (idx === -1 ? 0 : idx) + delta));
    setFontSize(FS_STEPS[newIdx]);
}

// ── Alto contraste ────────────────────────────────────────────────────────────
function toggleHighContrast() {
    const on = document.body.classList.toggle('hc');
    document.getElementById('hcSwitch').classList.toggle('on', on);
    try { localStorage.setItem(A11Y_LS_HC, on ? '1' : '0'); } catch(e) {}
}

// ── Fuente dislexia ───────────────────────────────────────────────────────────
function toggleDyslexicFont() {
    const on = document.body.classList.toggle('dyslexic');
    document.getElementById('dyslexicSwitch').classList.toggle('on', on);
    try { localStorage.setItem(A11Y_LS_DYS, on ? '1' : '0'); } catch(e) {}
}

// ── Teclado: Alt+Plus / Alt+Minus ─────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (!e.altKey) return;
    if (e.key === '+' || e.key === '=') { e.preventDefault(); stepFontSize(+1); }
    if (e.key === '-' || e.key === '_') { e.preventDefault(); stepFontSize(-1); }
});

// ── Restore preferences on page load ──────────────────────────────────────────
(function applyA11yPrefs() {
    try {
        const fs  = localStorage.getItem(A11Y_LS_FS);
        const hc  = localStorage.getItem(A11Y_LS_HC);
        const dys = localStorage.getItem(A11Y_LS_DYS);

        if (fs && FS_STEPS.includes(parseInt(fs, 10))) setFontSize(parseInt(fs, 10));
        if (hc === '1') {
            document.body.classList.add('hc');
            document.getElementById('hcSwitch')?.classList.add('on');
        }
        if (dys === '1') {
            document.body.classList.add('dyslexic');
            document.getElementById('dyslexicSwitch')?.classList.add('on');
        }
    } catch(e) {}
})();
