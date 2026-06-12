// Auto-sync points with item count (Add + Edit modals).
// Types where points = number of items (one point per item by default).
const AUTO_POINT_TYPES = {
    identification: '#id-items .id-item-row',
    matching:       '#mt-pairs .mt-pair-row',
    ordering:       '#or-items .or-item-row',
    completion:     '#cp-answers .cp-ans-row',
};

function syncAutoPoints(inputId) {
    const type = document.getElementById('selectedType')?.value;
    if (!type || !AUTO_POINT_TYPES[type]) return;
    const count = document.querySelectorAll(AUTO_POINT_TYPES[type]).length;
    const el = document.getElementById(inputId || 'addPoints');
    if (el && count > 0) { el.value = count; el.style.background = '#EEF2FF'; setTimeout(() => el.style.background = '', 600); }
}

const EQ_AUTO_POINT_TYPES = {
    identification: '#eq-id-items .eq-id-row',
    matching:       '#eq-mt-pairs .eq-mt-row',
    ordering:       '#eq-or-items .eq-or-row',
    completion:     '#eq-cp-answers .eq-cp-ans-row',
};

function syncEqAutoPoints() {
    const type = document.getElementById('eqTypeBadge')?.dataset.type;
    if (!type || !EQ_AUTO_POINT_TYPES[type]) return;
    const count = document.querySelectorAll(EQ_AUTO_POINT_TYPES[type]).length;
    const el = document.getElementById('eqPoints');
    if (el && count > 0) { el.value = count; el.style.background = '#EEF2FF'; setTimeout(() => el.style.background = '', 600); }
}
