// Per-student extra time (accommodations).
// Requires window.ExamShow.routes.extraTimeBase ("…/exams/{id}/codes").
function setExtraTime(codeId, studentName, current) {
    const base = (window.ExamShow && window.ExamShow.routes && window.ExamShow.routes.extraTimeBase) || '';
    Swal.fire({
        title: 'Tiempo extra',
        html: '<p style="font-size:.84rem;color:#475569;margin-bottom:.6rem;">Minutos adicionales para <strong>' + studentName + '</strong> (adecuación). El temporizador será la duración del examen + estos minutos.</p>',
        input: 'number',
        inputValue: current || 0,
        inputAttributes: { min: 0, max: 240, step: 5 },
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#4F46E5',
        cancelButtonColor: '#94A3B8',
        inputValidator: (value) => {
            const n = parseInt(value, 10);
            if (isNaN(n) || n < 0)  return 'Ingresa un número válido (0 o más).';
            if (n > 240)            return 'Máximo 240 minutos.';
            return null;
        }
    }).then(r => {
        if (!r.isConfirmed) return;
        const form = document.getElementById('extraTimeForm');
        form.action = base + '/' + codeId + '/extra-time';
        document.getElementById('extraMinutesInput').value = parseInt(r.value, 10);
        AppLoader.show('Guardando tiempo extra…', 'Actualizando la adecuación del estudiante.');
        form.submit();
    });
}
