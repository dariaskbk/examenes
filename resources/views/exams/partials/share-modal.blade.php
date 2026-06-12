{{--
    Share Exam Modal — lets the owner share an exam with another teacher.
    Requires: $exam.
    JS hooks (in public/js/exams/share.js): searchTeachersAjax, addTeacher,
    removeTeacher, renderSelected, submitShare.
--}}
@if($exam->user_id === Auth::id())
<div class="modal fade" id="shareExamModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="{{ route('exams.share', $exam) }}"
              id="shareExamForm" style="border-radius:16px;border:none;"
              onsubmit="return submitShare(this)">
            @csrf
            <div class="modal-header border-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold mb-0"><i class="bi bi-share me-2" style="color:#7C3AED;"></i>Compartir actividad</h5>
                    <div class="text-muted" style="font-size:.76rem;">El docente recibirá una invitación. Si acepta, se creará una copia en sus actividades como borrador.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1">
                <label class="form-label" style="font-size:.82rem;">Buscar docente</label>
                <input type="text" id="teacherSearch" class="form-control form-control-sm mb-2"
                       placeholder="Escribe nombre, apellido o email (2 letras mín.)…"
                       oninput="searchTeachersAjax(this.value)" autocomplete="off">
                <div id="teacherResults" style="max-height:200px;overflow-y:auto;border:1px solid #E2E8F0;border-radius:8px;background:#fff;font-size:.82rem;"></div>

                <div class="mt-3">
                    <label class="form-label mb-1" style="font-size:.82rem;">Seleccionados <span class="text-muted">(<span id="selCount">0</span>)</span></label>
                    <div id="selectedTeachers" class="d-flex flex-wrap gap-1" style="min-height:32px;padding:6px;border:1px dashed #CBD5E1;border-radius:8px;background:#F8FAFC;">
                        <span class="text-muted" id="selPlaceholder" style="font-size:.74rem;">Aún no has seleccionado docentes…</span>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label mb-1" style="font-size:.82rem;">Mensaje (opcional)</label>
                    <textarea name="message" class="form-control form-control-sm" rows="2"
                              placeholder="Ej. Te paso el examen del II parcial por si lo quieres usar."
                              maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm text-white" id="shareSubmitBtn" disabled style="background:#7C3AED;">
                    <i class="bi bi-share me-1"></i>Compartir
                </button>
            </div>
        </form>
    </div>
</div>
@endif
