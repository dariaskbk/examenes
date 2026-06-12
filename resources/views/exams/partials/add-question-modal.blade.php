{{-- Add Question Modal --}}
@if($exam->canBeEdited())
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px;border:none;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Agregar Pregunta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('exams.questions.store', $exam) }}"
                  enctype="multipart/form-data" id="qForm"
                  style="display:flex;flex-direction:column;flex:1 1 auto;overflow:hidden;min-height:0;">
                @csrf
                <div class="modal-body pt-3" style="overflow-y:auto;">

                    {{-- 1. Type selector (6 types) --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de pregunta</label>
                        <input type="hidden" name="type" id="selectedType" value="single_choice">
                        <div class="type-selector-grid">
                            <div class="type-card active" data-type="single_choice" onclick="selectType('single_choice')">
                                <i class="bi bi-ui-radios d-block mb-1" style="font-size:1.1rem;"></i>
                                <div style="font-size:.7rem;font-weight:600;">Selección Única</div>
                            </div>
                            <div class="type-card" data-type="multiple_select" onclick="selectType('multiple_select')">
                                <i class="bi bi-ui-checks d-block mb-1" style="font-size:1.1rem;"></i>
                                <div style="font-size:.7rem;font-weight:600;">Selección Múltiple</div>
                            </div>
                            <div class="type-card" data-type="true_false" onclick="selectType('true_false')">
                                <i class="bi bi-toggles d-block mb-1" style="font-size:1.1rem;"></i>
                                <div style="font-size:.7rem;font-weight:600;">Verdadero / Falso</div>
                            </div>
                            <div class="type-card" data-type="short_answer" onclick="selectType('short_answer')">
                                <i class="bi bi-chat-left-text d-block mb-1" style="font-size:1.1rem;"></i>
                                <div style="font-size:.7rem;font-weight:600;">Respuesta Corta</div>
                            </div>
                            <div class="type-card" data-type="matching" onclick="selectType('matching')">
                                <i class="bi bi-diagram-2 d-block mb-1" style="font-size:1.1rem;"></i>
                                <div style="font-size:.7rem;font-weight:600;">Emparejamiento</div>
                            </div>
                            <div class="type-card" data-type="ordering" onclick="selectType('ordering')">
                                <i class="bi bi-sort-numeric-down d-block mb-1" style="font-size:1.1rem;"></i>
                                <div style="font-size:.7rem;font-weight:600;">Ordenamiento</div>
                            </div>
                            <div class="type-card" data-type="identification" onclick="selectType('identification')">
                                <i class="bi bi-tag d-block mb-1" style="font-size:1.1rem;color:#9F1239;"></i>
                                <div style="font-size:.7rem;font-weight:600;">Identificación</div>
                            </div>
                            <div class="type-card" data-type="completion" onclick="selectType('completion')">
                                <i class="bi bi-input-cursor-text d-block mb-1" style="font-size:1.1rem;color:#065F46;"></i>
                                <div style="font-size:.7rem;font-weight:600;">Completar</div>
                            </div>
                            <div class="type-card" data-type="restricted_response" onclick="selectType('restricted_response')">
                                <i class="bi bi-justify-left d-block mb-1" style="font-size:1.1rem;color:#14532D;"></i>
                                <div style="font-size:.7rem;font-weight:600;">Resp. Restringida</div>
                            </div>
                            <div class="type-card" data-type="exercise" onclick="selectType('exercise')">
                                <i class="bi bi-calculator d-block mb-1" style="font-size:1.1rem;color:#78350F;"></i>
                                <div style="font-size:.7rem;font-weight:600;">Ejercicio</div>
                            </div>
                            <div class="type-card" data-type="written_production" onclick="selectType('written_production')">
                                <i class="bi bi-pencil-square d-block mb-1" style="font-size:1.1rem;color:#4C1D95;"></i>
                                <div style="font-size:.7rem;font-weight:600;">Prod. Escrita</div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Common fields + Media sidebar --}}
                    <div class="row g-3 mb-3">

                        {{-- Left: Quill editor --}}
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Enunciado <span class="text-danger">*</span></label>
                            <div id="addQuillEditor" style="background:#fff;min-height:180px;"></div>
                            <input type="hidden" name="question_text" id="addQuestionText">
                        </div>

                        {{-- Right: Points + Media stacked --}}
                        <div class="col-md-5 d-flex flex-column gap-3">

                            {{-- Points --}}
                            <div>
                                <label class="form-label fw-semibold">Puntos</label>
                                <input type="number" name="points" id="addPoints" class="form-control" value="1" min="0.1" step="0.1" required>
                            </div>

                            {{-- Media --}}
                            <div class="p-3 border rounded-3 flex-grow-1" style="background:#F8FAFC;">
                                <label class="form-label fw-semibold mb-2" style="font-size:.82rem;">
                                    <i class="bi bi-image me-1 text-primary"></i>Multimedia
                                </label>

                                {{-- Type selector --}}
                                <div class="mb-2">
                                    <label class="form-label small text-muted mb-1">Tipo de media</label>
                                    <select name="media_type" class="form-select form-select-sm" id="mediaTypeSelect" onchange="onMediaTypeChange(this.value)">
                                        <option value="none">Sin media</option>
                                        <option value="image">Imagen</option>
                                        <option value="audio">Audio</option>
                                        <option value="video">Video</option>
                                    </select>
                                </div>

                                {{-- File input (shown when a type is selected) --}}
                                <div id="mediaFileArea" style="display:none;" class="mb-1">
                                    <label class="form-label small text-muted mb-1" id="mediaFileLabel">Archivo</label>
                                    <input type="file" name="image" id="mediaFileInput" class="form-control form-control-sm"
                                           onchange="previewMediaFile(this, 'mediaPreviewWrap', 'mediaPreview')">
                                </div>
                                <p class="text-muted mb-0" id="mediaHint" style="font-size:.68rem;display:none;"></p>

                                {{-- Image preview --}}
                                <div id="mediaPreviewWrap" style="display:none;" class="mt-2">
                                    <img id="mediaPreview" src="" alt="Vista previa"
                                         style="max-height:120px;max-width:100%;border-radius:6px;border:1px solid #e2e8f0;object-fit:contain;">
                                </div>

                                {{-- Reuse from another question --}}
                                <div id="mediaReuseArea" style="display:none;" class="mt-2">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <hr class="flex-grow-1 my-0" style="border-color:#E2E8F0;">
                                        <span style="font-size:.65rem;color:#94A3B8;white-space:nowrap;">o también</span>
                                        <hr class="flex-grow-1 my-0" style="border-color:#E2E8F0;">
                                    </div>
                                    <button type="button" onclick="toggleReuseGrid('add')" id="mediaReuseToggleBtn"
                                            class="w-100 d-flex align-items-center gap-2 text-start reuse-btn"
                                            style="border:2px dashed #818CF8;background:#EEF2FF;color:#4338CA;
                                                   border-radius:10px;padding:8px 12px;font-weight:600;
                                                   font-size:.78rem;cursor:pointer;transition:background .15s;">
                                        <i class="bi bi-collection-fill" style="font-size:1.1rem;color:#6366F1;flex-shrink:0;"></i>
                                        <div class="flex-grow-1">
                                            <div>Reusar de otra pregunta</div>
                                            <div style="font-size:.67rem;font-weight:400;color:#6366F1;margin-top:1px;">
                                                <span id="mediaReuseCount">0</span>
                                                archivo<span id="mediaReusePlural">s</span> disponible<span id="mediaReusePluralS">s</span>
                                            </div>
                                        </div>
                                        <i class="bi bi-chevron-down" style="font-size:.75rem;opacity:.5;flex-shrink:0;"></i>
                                    </button>
                                    <div id="mediaReuseGrid" style="display:none;max-height:200px;overflow-y:auto;border:1.5px solid #C7D2FE;background:#F8FAFC;"
                                         class="mt-2 rounded-3 p-2"></div>
                                    <input type="hidden" name="media_reuse_path" id="mediaReusePath" value="">
                                    <div id="mediaReuseSelected" style="background:#F0FDF4;border:1.5px solid #86EFAC;border-radius:8px;"
                                         class="mt-2 px-2 py-2 d-flex align-items-center gap-2 js-hidden">
                                        <i class="bi bi-check-circle-fill text-success" style="font-size:.9rem;"></i>
                                        <span class="fw-semibold text-success flex-grow-1" style="font-size:.78rem;"
                                              id="mediaReuseSelectedLabel"></span>
                                        <button type="button" class="btn btn-link p-0 text-danger"
                                                style="font-size:.7rem;text-decoration:none;" onclick="clearReuseItem('add')">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>{{-- /media card --}}

                        </div>{{-- /right col --}}
                    </div>{{-- /row --}}

                    {{-- 4a. Single Choice options --}}
                    <div id="sc-section">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold mb-0">Opciones <span class="text-danger">*</span> <span class="text-muted" style="font-size:.75rem;">(mín. 3)</span></label>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addChoiceRow(false)" style="font-size:.75rem;">
                                <i class="bi bi-plus me-1"></i>Opción
                            </button>
                        </div>
                        <p class="text-muted mb-2" style="font-size:.78rem;"><i class="bi bi-info-circle me-1"></i>Haz clic en <i class="bi bi-circle"></i> para marcar la respuesta correcta.</p>
                        <div id="sc-options">
                            @for($i = 0; $i < 4; $i++)
                            <div class="input-group mb-2 sc-opt-row" data-idx="{{ $i }}">
                                <button type="button"
                                        class="btn {{ $i === 0 ? 'btn-success' : 'btn-outline-secondary' }} sc-correct-btn"
                                        onclick="setSCCorrect(this)" style="width:38px;padding:0;">
                                    <i class="bi {{ $i === 0 ? 'bi-record-circle-fill' : 'bi-circle' }}"></i>
                                </button>
                                <span class="input-group-text fw-bold text-muted" style="width:36px;padding:0;justify-content:center;">{{ chr(65+$i) }}</span>
                                <input type="text" name="options[{{ $i }}][text]" class="form-control"
                                       placeholder="Opción {{ chr(65+$i) }}" {{ $i < 3 ? 'required' : '' }}>
                                @if($i >= 3)
                                <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)" style="width:36px;padding:0;"><i class="bi bi-x"></i></button>
                                @endif
                            </div>
                            @endfor
                        </div>
                        <input type="hidden" name="correct_mc" id="correct_mc" value="0">
                    </div>

                    {{-- 4b. Multiple Select options --}}
                    <div id="ms-section" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold mb-0">Opciones <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-outline-purple" onclick="addChoiceRow(true)"
                                    style="font-size:.75rem;color:#9333EA;border-color:#9333EA;">
                                <i class="bi bi-plus me-1"></i>Opción
                            </button>
                        </div>
                        <p class="text-muted mb-2" style="font-size:.78rem;"><i class="bi bi-info-circle me-1"></i>Marca todas las opciones correctas.</p>
                        <div id="ms-options">
                            @for($i = 0; $i < 4; $i++)
                            <div class="input-group mb-2 ms-opt-row" data-idx="{{ $i }}">
                                <div class="input-group-text" style="width:38px;padding:0;justify-content:center;">
                                    <input type="checkbox" name="correct_ms[]" value="{{ $i }}"
                                           class="form-check-input mt-0 ms-correct-chk" style="width:16px;height:16px;">
                                </div>
                                <span class="input-group-text fw-bold text-muted" style="width:36px;padding:0;justify-content:center;">{{ chr(65+$i) }}</span>
                                <input type="text" name="options[{{ $i }}][text]" class="form-control"
                                       placeholder="Opción {{ chr(65+$i) }}">
                                @if($i >= 2)
                                <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)" style="width:36px;padding:0;"><i class="bi bi-x"></i></button>
                                @endif
                            </div>
                            @endfor
                        </div>
                    </div>

                    {{-- 4c. True / False --}}
                    <div id="tf-section" style="display:none;">
                        <label class="form-label fw-semibold">Respuesta correcta</label>
                        <div class="d-flex gap-3">
                            <label class="d-flex align-items-center gap-2 p-3 border rounded-3 flex-fill" style="cursor:pointer;">
                                <input type="radio" name="correct_answer" value="true" checked>
                                <i class="bi bi-check-circle text-success fs-5"></i>
                                <span class="fw-semibold">Verdadero</span>
                            </label>
                            <label class="d-flex align-items-center gap-2 p-3 border rounded-3 flex-fill" style="cursor:pointer;">
                                <input type="radio" name="correct_answer" value="false">
                                <i class="bi bi-x-circle text-danger fs-5"></i>
                                <span class="fw-semibold">Falso</span>
                            </label>
                        </div>
                    </div>

                    {{-- 4d. Short Answer --}}
                    <div id="sa-section" style="display:none;">
                        <div class="alert alert-info py-2 mb-0" style="font-size:.8rem;">
                            <i class="bi bi-info-circle me-1"></i>
                            Las respuestas cortas requieren revisión manual del docente para asignar puntaje.
                        </div>
                    </div>

                    {{-- 4e. Matching --}}
                    <div id="mt-section" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold mb-0">Pares concepto → definición <span class="text-danger">*</span> <span class="text-muted" style="font-size:.75rem;">(mín. 2)</span></label>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="addMatchingRow()"
                                    style="font-size:.75rem;color:#9A3412;border-color:#FCA571;">
                                <i class="bi bi-plus me-1"></i>Par
                            </button>
                        </div>
                        <div id="mt-pairs">
                            @for($i = 0; $i < 3; $i++)
                            <div class="row g-2 mb-2 mt-pair-row" data-idx="{{ $i }}">
                                <div class="col-5">
                                    <input type="text" name="pairs[{{ $i }}][concept]" class="form-control form-control-sm"
                                           placeholder="Concepto {{ $i+1 }}" {{ $i < 2 ? 'required' : '' }}>
                                </div>
                                <div class="col-1 d-flex align-items-center justify-content-center text-muted">
                                    <i class="bi bi-arrow-right"></i>
                                </div>
                                <div class="col-5">
                                    <input type="text" name="pairs[{{ $i }}][definition]" class="form-control form-control-sm"
                                           placeholder="Definición {{ $i+1 }}" {{ $i < 2 ? 'required' : '' }}>
                                </div>
                                <div class="col-1">
                                    @if($i >= 2)
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeRow(this)"><i class="bi bi-x"></i></button>
                                    @endif
                                </div>
                            </div>
                            @endfor
                        </div>
                    </div>

                    {{-- 4f. Ordering --}}
                    <div id="or-section" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold mb-0">Ítems en orden correcto <span class="text-danger">*</span> <span class="text-muted" style="font-size:.75rem;">(mín. 2)</span></label>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="addOrderingRow()"
                                    style="font-size:.75rem;color:#075985;border-color:#7DD3FC;">
                                <i class="bi bi-plus me-1"></i>Ítem
                            </button>
                        </div>
                        <p class="text-muted mb-2" style="font-size:.78rem;"><i class="bi bi-info-circle me-1"></i>Escribe los ítems en el orden correcto. El sistema los mezclará aleatoriamente para el estudiante.</p>
                        <div id="or-items">
                            @for($i = 0; $i < 4; $i++)
                            <div class="input-group mb-2 or-item-row" data-idx="{{ $i }}">
                                <span class="input-group-text fw-bold" style="width:36px;background:#EFF6FF;color:#3B82F6;justify-content:center;">{{ $i+1 }}</span>
                                <input type="text" name="ordering_items[{{ $i }}]" class="form-control"
                                       placeholder="Ítem {{ $i+1 }}" {{ $i < 2 ? 'required' : '' }}>
                                @if($i >= 2)
                                <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)" style="width:36px;padding:0;"><i class="bi bi-x"></i></button>
                                @endif
                            </div>
                            @endfor
                        </div>
                    </div>

                    {{-- 4g. Identification --}}
                    <div id="id-section" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold mb-0">Etiquetas a identificar <span class="text-danger">*</span> <span class="text-muted" style="font-size:.75rem;">(máx. 5)</span></label>
                            <button type="button" class="btn btn-sm" onclick="addIdentRow()"
                                    style="font-size:.75rem;color:#9F1239;border:1px solid #FECDD3;background:#FFF1F2;">
                                <i class="bi bi-plus me-1"></i>Etiqueta
                            </button>
                        </div>
                        <p class="text-muted mb-2" style="font-size:.78rem;"><i class="bi bi-info-circle me-1"></i>La etiqueta es la letra/número que aparece en la imagen (A, B, 1, 2…). La respuesta correcta es lo que el estudiante debe escribir.</p>
                        <div id="id-items">
                            @foreach(['A','B','C'] as $li => $lbl)
                            <div class="row g-2 mb-2 id-item-row" data-idx="{{ $li }}">
                                <div class="col-3">
                                    <input type="text" name="ident_items[{{ $li }}][label]" class="form-control form-control-sm text-center fw-bold"
                                           placeholder="A" value="{{ $lbl }}" maxlength="5" {{ $li < 2 ? 'required' : '' }}>
                                </div>
                                <div class="col-8">
                                    <input type="text" name="ident_items[{{ $li }}][answer]" class="form-control form-control-sm"
                                           placeholder="Respuesta correcta" {{ $li < 2 ? 'required' : '' }}>
                                </div>
                                <div class="col-1">
                                    @if($li >= 2)
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeRow(this)"><i class="bi bi-x"></i></button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <p class="text-muted mt-2 mb-0" style="font-size:.75rem;"><i class="bi bi-lightbulb me-1 text-warning"></i>Sube la imagen con las etiquetas usando el campo <strong>Multimedia</strong> de arriba.</p>
                    </div>

                    {{-- 4h. Restricted Response --}}
                    <div id="rr-section" style="display:none;">
                        <div class="alert py-2 mb-3" style="background:#F0FDF4;border:1px solid #BBF7D0;font-size:.8rem;color:#14532D;">
                            <i class="bi bi-justify-left me-1"></i><strong>Respuesta Restringida</strong> — El estudiante debe explicar, justificar o argumentar según los criterios indicados. Requiere revisión manual.
                        </div>
                        <label class="form-label fw-semibold">Criterios de evaluación / Rúbrica <span class="text-muted" style="font-size:.75rem;">(opcional, visible para el estudiante)</span></label>
                        <textarea name="grading_criteria" class="form-control" rows="3"
                                  placeholder="Ej: Se otorga el puntaje completo si el estudiante explica 2 causas con argumentos claros y ejemplos del texto."></textarea>
                    </div>

                    {{-- 4i. Exercise --}}
                    <div id="ex-section" style="display:none;">
                        <div class="alert py-2 mb-3" style="background:#FFFBEB;border:1px solid #FDE68A;font-size:.8rem;color:#78350F;">
                            <i class="bi bi-calculator me-1"></i><strong>Ejercicio</strong> — El estudiante aplica un procedimiento lógico-matemático. Se valora tanto el proceso como el resultado. Requiere revisión manual.
                        </div>
                        <label class="form-label fw-semibold">Criterios de evaluación / Desglose de puntaje <span class="text-muted" style="font-size:.75rem;">(opcional, visible para el estudiante)</span></label>
                        <textarea name="grading_criteria" class="form-control" rows="3"
                                  placeholder="Ej: Planteamiento (1 pt) + Procedimiento correcto (2 pts) + Resultado final (1 pt)"></textarea>
                    </div>

                    {{-- 4j. Written Production --}}
                    <div id="wp-section" style="display:none;">
                        <div class="alert py-2 mb-3" style="background:#F5F3FF;border:1px solid #DDD6FE;font-size:.8rem;color:#4C1D95;">
                            <i class="bi bi-pencil-square me-1"></i><strong>Producción Escrita</strong> — El estudiante redacta un texto (ensayo, carta, interpretación). Requiere rúbrica para calificación.
                        </div>
                        <label class="form-label fw-semibold">Rúbrica de evaluación <span class="text-muted" style="font-size:.75rem;">(se muestra al estudiante durante el examen)</span></label>
                        <textarea name="grading_criteria" class="form-control" rows="4"
                                  placeholder="Ej: Organización del texto (2 pts) | Argumentación y ejemplos (3 pts) | Gramática y vocabulario (2 pts) | Coherencia (1 pt)"></textarea>
                    </div>

                    {{-- Rubric builder — shared across rubric-based types --}}
                    @include('exams.partials.rubric-builder', ['prefix' => 'add'])

                    {{-- 4k. Completion (drag & drop) --}}
                    <div id="cp-section" style="display:none;">
                        <div class="alert py-2 mb-3" style="background:#F0FDF4;border:1px solid #86EFAC;font-size:.8rem;color:#065F46;">
                            <i class="bi bi-input-cursor-text me-1"></i><strong>Completar</strong> — Escriba la oración en el enunciado usando <code>___</code> (tres guiones bajos) para cada espacio en blanco. El estudiante arrastrará palabras del banco a los espacios.
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold mb-0">Respuestas correctas <small class="text-muted">(una por espacio ___)</small></label>
                            <button type="button" class="btn btn-sm" onclick="addCpAnswerRow()"
                                    style="font-size:.75rem;color:#065F46;border:1px solid #86EFAC;background:#F0FDF4;">
                                <i class="bi bi-plus me-1"></i>Agregar espacio ___
                            </button>
                        </div>
                        <p class="text-muted mb-2" style="font-size:.78rem;"><i class="bi bi-info-circle me-1"></i>Agregue una respuesta por cada <code>___</code> en el enunciado, en el mismo orden. Los puntos se asignan automáticamente.</p>
                        <div id="cp-answers">
                            <div class="row g-2 mb-2 cp-ans-row">
                                <div class="col-auto d-flex align-items-center">
                                    <span class="cp-blank-num fw-bold text-success" style="font-size:.8rem;width:70px;">Espacio 1</span>
                                </div>
                                <div class="col"><input type="text" name="cp_answers[]" class="form-control form-control-sm" placeholder="Respuesta correcta" oninput="syncCpPoints()"></div>
                                <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeCpRow(this)"><i class="bi bi-x"></i></button></div>
                            </div>
                        </div>
                        <hr class="my-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold mb-0">Distractores <small class="text-muted fw-normal">(palabras incorrectas opcionales)</small></label>
                            <button type="button" class="btn btn-sm" onclick="addCpDistractorRow()"
                                    style="font-size:.75rem;color:#64748B;border:1px solid #E2E8F0;background:#F8FAFC;">
                                <i class="bi bi-plus me-1"></i>Agregar distractor
                            </button>
                        </div>
                        <p class="text-muted mb-2" style="font-size:.78rem;"><i class="bi bi-shuffle me-1"></i>Palabras adicionales incorrectas para dificultar la actividad. Se mezclan con las correctas en el banco.</p>
                        <div id="cp-distractors"></div>
                    </div>

                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-indigo">
                        <i class="bi bi-plus-circle me-1"></i>Agregar Pregunta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
