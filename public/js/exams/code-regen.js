// Code regeneration (per-student / selected / all).
function regenSelectedIds() {
    return [...document.querySelectorAll('.regen-chk:checked')].map(c => c.value);
}
function updateRegenCount() {
    const n = regenSelectedIds().length;
    const lbl = document.getElementById('regenSelCount');
    const btn = document.getElementById('regenSelBtn');
    if (lbl) lbl.textContent = n;
    if (btn) btn.disabled = n === 0;
}
function toggleAllRegen(master) {
    document.querySelectorAll('.regen-chk:not(:disabled)').forEach(c => c.checked = master.checked);
    updateRegenCount();
}
function submitRegen(scope, ids) {
    const form = document.getElementById('regenForm');
    document.getElementById('regenScope').value = scope;
    const cont = document.getElementById('regenIdsContainer');
    cont.innerHTML = '';
    (ids || []).forEach(id => {
        const i = document.createElement('input');
        i.type = 'hidden'; i.name = 'code_ids[]'; i.value = id;
        cont.appendChild(i);
    });
    AppLoader.show('Regenerando códigos…', 'Los códigos anteriores dejarán de funcionar.');
    form.submit();
}
function regenerateOne(id) {
    Swal.fire({
        title: '¿Regenerar este código?',
        text: 'El código anterior dejará de funcionar de inmediato.',
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Sí, regenerar', cancelButtonText: 'Cancelar',
        confirmButtonColor: '#D97706', cancelButtonColor: '#94A3B8',
    }).then(r => { if (r.isConfirmed) submitRegen('one', [id]); });
}
function regenerateSelected() {
    const ids = regenSelectedIds();
    if (!ids.length) return;
    Swal.fire({
        title: `¿Regenerar ${ids.length} código(s)?`,
        text: 'Los códigos anteriores dejarán de funcionar de inmediato.',
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Sí, regenerar', cancelButtonText: 'Cancelar',
        confirmButtonColor: '#D97706', cancelButtonColor: '#94A3B8',
    }).then(r => { if (r.isConfirmed) submitRegen('selected', ids); });
}
function regenerateAll() {
    Swal.fire({
        title: '¿Regenerar TODOS los códigos?',
        html: 'Se regenerarán todos los códigos <strong>sin intentos</strong>. Los anteriores dejarán de funcionar de inmediato.',
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Sí, regenerar todos', cancelButtonText: 'Cancelar',
        confirmButtonColor: '#DC2626', cancelButtonColor: '#94A3B8',
    }).then(r => { if (r.isConfirmed) submitRegen('all', []); });
}
