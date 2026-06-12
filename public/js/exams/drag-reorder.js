// Question drag-and-drop reorder.
// Requires window.ExamShow.canEdit + window.ExamShow.routes.questionsReorder.
(function initQuestionReorder() {
    const cfg = window.ExamShow || {};
    if (!cfg.canEdit) return;
    const REORDER_URL = cfg.routes && cfg.routes.questionsReorder;
    if (!REORDER_URL) return;
    const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content;
    const container = document.getElementById('questionsList');
    if (!container) return;

    let draggingEl = null;

    container.addEventListener('dragstart', e => {
        const card = e.target.closest('.q-card[draggable="true"]');
        if (!card) return;
        draggingEl = card;
        setTimeout(() => card.classList.add('dragging'), 0);
        e.dataTransfer.effectAllowed = 'move';
    });

    container.addEventListener('dragover', e => {
        e.preventDefault();
        const card = e.target.closest('.q-card[draggable="true"]');
        if (!card || card === draggingEl) return;
        container.querySelectorAll('.q-card').forEach(c => c.classList.remove('drag-over'));
        card.classList.add('drag-over');
        const rect = card.getBoundingClientRect();
        if (e.clientY < rect.top + rect.height / 2) card.insertAdjacentElement('beforebegin', draggingEl);
        else card.insertAdjacentElement('afterend', draggingEl);
    });

    container.addEventListener('dragleave', e => {
        const card = e.target.closest('.q-card');
        if (card && !card.contains(e.relatedTarget)) card.classList.remove('drag-over');
    });

    container.addEventListener('drop', e => {
        e.preventDefault();
        container.querySelectorAll('.q-card').forEach(c => c.classList.remove('drag-over'));
    });

    container.addEventListener('dragend', () => {
        if (draggingEl) draggingEl.classList.remove('dragging');
        container.querySelectorAll('.q-card').forEach(c => c.classList.remove('drag-over'));
        draggingEl = null;

        const cards = [...container.querySelectorAll('.q-card[data-question-id]')];
        cards.forEach((c, i) => {
            const badge = c.querySelector('.q-num-badge');
            if (badge) badge.textContent = i + 1;
        });

        const order = cards.map(c => parseInt(c.dataset.questionId));
        fetch(REORDER_URL, {
            method:  'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body:    JSON.stringify({ order })
        }).catch(() => {});
    });
})();
