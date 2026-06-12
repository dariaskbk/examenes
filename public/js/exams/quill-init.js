// Quill rich-text editors for Add/Edit Question modals.
// Depends on the Quill 2 global (CDN must load before this file).
(function () {
    const TOOLBAR = [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['blockquote', 'clean']
    ];

    // Add Question modal editor
    const addEditor = document.getElementById('addQuillEditor');
    let addQuill = null;
    if (addEditor) {
        addQuill = new Quill('#addQuillEditor', {
            theme: 'snow',
            placeholder: 'Escribe aquí la pregunta…',
            modules: { toolbar: TOOLBAR }
        });

        document.getElementById('qForm')?.addEventListener('submit', function (e) {
            const text = addQuill.getText().trim();
            if (!text) {
                e.preventDefault();
                addQuill.focus();
                addQuill.root.style.borderColor = '#DC2626';
                setTimeout(() => addQuill.root.style.borderColor = '', 1500);
                return;
            }
            document.getElementById('addQuestionText').value = addQuill.getSemanticHTML();
        });

        document.getElementById('addQuestionModal')?.addEventListener('hidden.bs.modal', function () {
            addQuill.setText('');
        });
    }

    // Edit Question modal editor (exposed for openEditModal in question-modals.js)
    const editEditor = document.getElementById('editQuillEditor');
    if (editEditor) {
        window.editQuill = new Quill('#editQuillEditor', {
            theme: 'snow',
            placeholder: 'Escribe aquí la pregunta…',
            modules: { toolbar: TOOLBAR }
        });

        document.getElementById('editQForm')?.addEventListener('submit', function (e) {
            const text = window.editQuill.getText().trim();
            if (!text) {
                e.preventDefault();
                window.editQuill.focus();
                window.editQuill.root.style.borderColor = '#DC2626';
                setTimeout(() => window.editQuill.root.style.borderColor = '', 1500);
                return;
            }
            document.getElementById('eqText').value = window.editQuill.getSemanticHTML();
        });
    }
})();
