{{--
    Question Bank Modal — lets the teacher add questions from their other exams.
    Requires: $exam.
    JS hooks (in public/js/exams/question-bank.js): loadQuestionBank, filterBank,
    toggleBank, updateBankCount, submitBank.
--}}
@if($exam->canBeEdited())
<div class="modal fade" id="bankModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('exams.question-bank.import', $exam) }}"
              id="bankForm" style="border-radius:16px;border:none;"
              onsubmit="return submitBank(this)">
            @csrf
            <div class="modal-header border-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold mb-0"><i class="bi bi-collection me-2" style="color:#4F46E5;"></i>Banco de preguntas</h5>
                    <div class="text-muted" style="font-size:.76rem;">Reutiliza preguntas de tus otros exámenes.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- Filters --}}
            <div class="px-3 pb-2 d-flex flex-wrap gap-2 align-items-center">
                <div class="flex-grow-1" style="min-width:180px;">
                    <input type="text" id="bankSearch" class="form-control form-control-sm"
                           placeholder="Buscar por enunciado…" oninput="filterBank()">
                </div>
                <select id="bankType" class="form-select form-select-sm" style="width:auto;" onchange="filterBank()">
                    <option value="">Todos los tipos</option>
                    @foreach(\App\Models\ExamQuestion::TYPES as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @if($exam->subject_id)
                <div class="form-check form-switch mb-0 d-flex align-items-center gap-1">
                    <input class="form-check-input" type="checkbox" id="bankSameSubject" role="switch" onchange="loadQuestionBank()">
                    <label class="form-check-label" for="bankSameSubject" style="font-size:.76rem;color:#475569;">Solo esta materia</label>
                </div>
                @endif
            </div>

            <div class="modal-body pt-1" style="max-height:55vh;overflow-y:auto;">
                {{-- Loading --}}
                <div id="bankLoading" class="text-center py-5" style="display:none;">
                    <div class="spinner-border" role="status" style="color:#4F46E5;"></div>
                    <div class="text-muted mt-2" style="font-size:.82rem;">Cargando preguntas…</div>
                </div>
                {{-- Empty --}}
                <div id="bankEmpty" class="text-center py-5" style="display:none;">
                    <i class="bi bi-inbox" style="font-size:2rem;color:#CBD5E1;"></i>
                    <p class="text-muted mt-2 mb-0" style="font-size:.85rem;">No hay preguntas que coincidan.</p>
                </div>
                {{-- List --}}
                <div id="bankList" class="d-flex flex-column gap-2"></div>
            </div>

            <div class="modal-footer border-0 pt-2 d-flex justify-content-between align-items-center">
                <span class="text-muted" style="font-size:.78rem;"><span id="bankSelCount">0</span> seleccionada(s)</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-indigo" id="bankAddBtn" disabled>
                        <i class="bi bi-plus-lg me-1"></i>Agregar seleccionadas
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
