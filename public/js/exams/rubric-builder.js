// Rubric builder (shared between Add and Edit modals).
// Depends on globals: escHtml (question-modals.js), window.selectType (idem).
const RUBRIC_DEFAULT_LEVELS = [
    [
        { name: 'Excelente',  points: 4 },
        { name: 'Bueno',      points: 3 },
        { name: 'Aceptable',  points: 2 }
    ],
    [
        { name: 'Excelente',  points: 4 },
        { name: 'Bueno',      points: 3 },
        { name: 'Aceptable',  points: 2 },
        { name: 'Deficiente', points: 1 }
    ],
    [
        { name: 'Excelente',  points: 5 },
        { name: 'Muy bueno',  points: 4 },
        { name: 'Bueno',      points: 3 },
        { name: 'Aceptable',  points: 2 },
        { name: 'Deficiente', points: 1 }
    ],
];
const RUBRIC_TYPES = ['short_answer','restricted_response','exercise','written_production'];
// Live state: {prefix: {levels:[], criteria:[]}}
const rubricState = { add: null, eq: null };

function rubricInit(prefix, existing) {
    // existing: null OR {levels:[...], criteria:[...]}
    let state = existing && existing.levels && existing.criteria
        ? JSON.parse(JSON.stringify(existing))
        : { levels: RUBRIC_DEFAULT_LEVELS[1].slice(), criteria: [] };
    if (state.criteria.length === 0) {
        state.criteria.push({ name: '', descriptors: Array(state.levels.length).fill('') });
    }
    rubricState[prefix] = state;
    const lvlSel = document.getElementById(prefix + 'RubricLevels');
    if (lvlSel) lvlSel.value = String(state.levels.length);
    if (lvlSel) lvlSel.onchange = () => rubricChangeLevels(prefix, parseInt(lvlSel.value, 10));
    rubricRender(prefix);
}

function rubricChangeLevels(prefix, n) {
    const state = rubricState[prefix];
    const presetIndex = ({3:0, 4:1, 5:2})[n] ?? 1;
    const preset = RUBRIC_DEFAULT_LEVELS[presetIndex];
    const newLevels = preset.slice();
    state.levels.forEach((l, i) => { if (i < newLevels.length) newLevels[i] = { name: l.name, points: l.points }; });
    state.levels = newLevels;
    state.criteria.forEach(c => {
        if (c.descriptors.length < n) c.descriptors = c.descriptors.concat(Array(n - c.descriptors.length).fill(''));
        else c.descriptors = c.descriptors.slice(0, n);
    });
    rubricRender(prefix);
}

function rubricAddCriterion(prefix) {
    const state = rubricState[prefix];
    if (!state) return;
    state.criteria.push({ name: '', descriptors: Array(state.levels.length).fill('') });
    rubricRender(prefix);
}

function rubricRemoveCriterion(prefix, idx) {
    const state = rubricState[prefix];
    state.criteria.splice(idx, 1);
    if (state.criteria.length === 0) {
        state.criteria.push({ name: '', descriptors: Array(state.levels.length).fill('') });
    }
    rubricRender(prefix);
}

function rubricClear(prefix) {
    rubricInit(prefix, null);
}

function rubricRender(prefix) {
    const state = rubricState[prefix];
    if (!state) return;
    const header = document.getElementById(prefix + 'RubricHeader');
    const body   = document.getElementById(prefix + 'RubricBody');
    const maxEl  = document.getElementById(prefix + 'RubricMax');
    if (!header || !body) return;

    header.innerHTML = '<th style="width:160px;font-size:.7rem;color:#64748B;">CRITERIO</th>' +
        state.levels.map((l, j) =>
            '<th style="font-size:.7rem;color:#64748B;">' +
              '<input type="text" class="form-control form-control-sm mb-1" value="' + escHtml(l.name) + '" style="font-size:.74rem;" oninput="rubricEditLevelName(\'' + prefix + '\',' + j + ',this.value)">' +
              '<input type="number" class="form-control form-control-sm" value="' + l.points + '" min="0" step="0.5" style="font-size:.72rem;" oninput="rubricEditLevelPoints(\'' + prefix + '\',' + j + ',this.value)">' +
            '</th>'
        ).join('') +
        '<th style="width:34px;"></th>';

    body.innerHTML = state.criteria.map((c, i) =>
        '<tr>' +
          '<td><input type="text" class="form-control form-control-sm" value="' + escHtml(c.name) + '" placeholder="Ej. Coherencia" style="font-size:.78rem;" oninput="rubricEditCritName(\'' + prefix + '\',' + i + ',this.value)"></td>' +
          c.descriptors.map((d, j) =>
            '<td><textarea class="form-control form-control-sm" rows="2" placeholder="Descriptor…" style="font-size:.76rem;" oninput="rubricEditDescriptor(\'' + prefix + '\',' + i + ',' + j + ',this.value)">' + escHtml(d) + '</textarea></td>'
          ).join('') +
          '<td><button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="rubricRemoveCriterion(\'' + prefix + '\',' + i + ')" title="Eliminar criterio"><i class="bi bi-x"></i></button></td>' +
        '</tr>'
    ).join('');

    const top = state.levels.reduce((m, l) => Math.max(m, parseFloat(l.points) || 0), 0);
    if (maxEl) maxEl.textContent = (top * state.criteria.length).toFixed(2).replace(/\.00$/, '');
}

function rubricEditLevelName(prefix, j, val) { rubricState[prefix].levels[j].name = val; }
function rubricEditLevelPoints(prefix, j, val) { rubricState[prefix].levels[j].points = parseFloat(val) || 0; rubricRender(prefix); }
function rubricEditCritName(prefix, i, val) { rubricState[prefix].criteria[i].name = val; }
function rubricEditDescriptor(prefix, i, j, val) { rubricState[prefix].criteria[i].descriptors[j] = val; }

// Serialize the rubric state into the hidden field before form submit.
function rubricSerialize(prefix) {
    const hidden = document.getElementById(prefix + 'RubricJson');
    if (!hidden) return;
    const state = rubricState[prefix];
    const section = document.getElementById(prefix + 'RubricSection');
    if (!state || !section || section.style.display === 'none') {
        hidden.value = '';
        return;
    }
    const cleanCriteria = state.criteria.filter(c => (c.name || '').trim() !== '');
    if (cleanCriteria.length === 0) { hidden.value = ''; return; }
    hidden.value = JSON.stringify({ levels: state.levels, criteria: cleanCriteria });
}

// Show/hide the rubric section based on the current type. Called by selectType.
function rubricToggleVisibility(prefix, type) {
    const section = document.getElementById(prefix + 'RubricSection');
    if (!section) return;
    const isRubric = RUBRIC_TYPES.includes(type);
    section.style.display = isRubric ? '' : 'none';
    if (isRubric && !rubricState[prefix]) {
        rubricInit(prefix, null);
    }
}

// Hook into existing selectType (add modal): show rubric section for rubric types.
(function () {
    const orig = window.selectType;
    if (typeof orig !== 'function') return;
    window.selectType = function (type) {
        orig(type);
        rubricToggleVisibility('add', type);
    };
})();

// Form submit hooks (serialize rubric before submit).
document.getElementById('qForm')?.addEventListener('submit', () => rubricSerialize('add'));
document.getElementById('editQForm')?.addEventListener('submit', () => rubricSerialize('eq'));
