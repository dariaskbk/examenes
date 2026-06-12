// deleteQuestion: AJAX individual question deletion.
function deleteQuestion(btn) {
    Swal.fire({
        title: '¿Eliminar pregunta?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#94A3B8',
        customClass: { popup: 'swal-examcore' },
    }).then(result => {
        if (!result.isConfirmed) return;

        const url   = btn.dataset.url;
        const token = btn.dataset.token;
        const card  = btn.closest('.q-card');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                Swal.fire('Error', data.message || 'No se pudo eliminar.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-trash"></i>';
                return;
            }

            card.style.transition = 'opacity .25s, transform .25s';
            card.style.opacity    = '0';
            card.style.transform  = 'translateX(10px)';
            setTimeout(() => {
                card.remove();

                const badge = document.getElementById('qCountBadge');
                if (badge) badge.textContent = data.count;

                document.querySelectorAll('.q-num-badge').forEach((el, i) => {
                    el.textContent = i + 1;
                });

                if (data.count === 0) {
                    const list = document.getElementById('questionsList');
                    if (list) {
                        const existing = list.querySelector('.empty-state');
                        if (!existing) {
                            const emptyDiv = document.createElement('div');
                            emptyDiv.className = 'empty-state';
                            emptyDiv.innerHTML = '<i class="bi bi-question-circle"></i><p class="small mb-2">No hay preguntas. Agrega una manualmente o importa desde Excel.</p>';
                            list.appendChild(emptyDiv);
                        }
                    }
                }

                AppToast.show('Pregunta eliminada correctamente.', 'success');
            }, 260);
        })
        .catch(() => {
            Swal.fire('Error', 'Ocurrió un error de red. Intenta de nuevo.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-trash"></i>';
        });
    });
}
