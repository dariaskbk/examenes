{{-- Edit Question Modal --}}
@if($exam->canBeEdited())
<div class="modal fade" id="editQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px;border:none;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-1">Editar Pregunta</h5>
                    <span id="eqTypeBadge" class="type-pill type-mc">—</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editQForm" action="" enctype="multipart/form-data"
                  style="display:flex;flex-direction:column;flex:1 1 auto;overflow:hidden;min-height:0;">
                @csrf
                @method('PUT')
                <div class="modal-body pt-3" style="overflow-y:auto;">

                    {{-- Two-column layout: editor left, controls right --}}
                    <div class="row g-3 mb-3">

                        {{-- Left: Quill editor --}}
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Enunciado <span class="text-danger">*</span></label>
                            <div id="editQuillEditor" style="background:#fff;min-height:180px;"></div>
                            <input type="hidden" name="question_text" id="eqText">
                        </div>

                        {{-- Right: Points + Media stacked --}}
                        <div class="col-md-5 d-flex flex-column gap-3">

                            {{-- Points --}}
                            <div>
                                <label class="form-label fw-semibold">Puntos</label>
                                <input type="number" name="points" id="eqPoints" class="form-control" min="0.1" step="0.1" required>
                            </div>

                            {{-- Media --}}
                            <div class="p-3 border rounded-3 flex-grow-1" style="background:#F8FAFC;">
                                <label class="form-label fw-semibold mb-2" style="font-size:.82rem;">
                                    <i class="bi bi-image me-1 text-primary"></i>Multimedia
                                </label>

                                {{-- Current media banner --}}
                                <div id="eqCurrentMedia" class="mb-2 d-flex align-items-center gap-2 p-2 bg-white border rounded-2 js-hidden">
                                    <span id="eqCurrentMediaIcon" class="fs-5"></span>
                                    <span id="eqCurrentMediaLabel" style="font-size:.8rem;font-weight:600;"></span>
                                    <label class="ms-auto d-flex align-items-center gap-1 text-danger" style="font-size:.72rem;cursor:pointer;">
                                        <input type="checkbox" name="remove_media" id="eqRemoveMedia" class="form-check-input mt-0">
                                        Quitar
                                    </label>
                                </div>

                                {{-- Type selector --}}
                                <div class="mb-2">
                                    <label class="form-label small text-muted mb-1">Reemplazar con</label>
                                    <select name="media_type" id="eqMediaType" class="form-select form-select-sm" onchange="onEqMediaChange(this.value)">
                                        <option value="none">Sin media</option>
                                        <option value="image">Imagen</option>
                                        <option value="audio">Audio</option>
                                        <option value="video">Video</option>
                                    </select>
                                </div>

                                {{-- File input (shown when a type is selected) --}}
                                <div id="eqMediaFileArea" style="display:none;" class="mb-1">
                                    <label class="form-label small text-muted mb-1" id="eqMediaFileLabel">Archivo</label>
                                    <input type="file" id="eqMediaFileInput" class="form-control form-control-sm"
                                           onchange="previewMediaFile(this, 'eqMediaPreviewWrap', 'eqMediaPreview')">
                                </div>
                                <p class="text-muted mb-0" id="eqMediaHint" style="font-size:.68rem;display:none;"></p>

                                {{-- Image preview --}}
                                <div id="eqMediaPreviewWrap" style="display:none;" class="mt-2">
                                    <img id="eqMediaPreview" src="" alt="Vista previa"
                                         style="max-height:120px;max-width:100%;border-radius:6px;border:1px solid #e2e8f0;object-fit:contain;">
                                </div>

                                {{-- Reuse from another question --}}
                                <div id="eqMediaReuseArea" style="display:none;" class="mt-2">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <hr class="flex-grow-1 my-0" style="border-color:#E2E8F0;">
                                        <span style="font-size:.65rem;color:#94A3B8;white-space:nowrap;">o también</span>
                                        <hr class="flex-grow-1 my-0" style="border-color:#E2E8F0;">
                                    </div>
                                    <button type="button" onclick="toggleReuseGrid('eq')" id="eqMediaReuseToggleBtn"
                                            class="w-100 d-flex align-items-center gap-2 text-start reuse-btn"
                                            style="border:2px dashed #818CF8;background:#EEF2FF;color:#4338CA;
                                                   border-radius:10px;padding:8px 12px;font-weight:600;
                                                   font-size:.78rem;cursor:pointer;transition:background .15s;">
                                        <i class="bi bi-collection-fill" style="font-size:1.1rem;color:#6366F1;flex-shrink:0;"></i>
                                        <div class="flex-grow-1">
                                            <div>Reusar de otra pregunta</div>
                                            <div style="font-size:.67rem;font-weight:400;color:#6366F1;margin-top:1px;">
                                                <span id="eqMediaReuseCount">0</span>
                                                archivo<span id="eqMediaReusePlural">s</span> disponible<span id="eqMediaReusePluralS">s</span>
                                            </div>
                                        </div>
                                        <i class="bi bi-chevron-down" style="font-size:.75rem;opacity:.5;flex-shrink:0;"></i>
                                    </button>
                                    <div id="eqMediaReuseGrid" style="display:none;max-height:200px;overflow-y:auto;border:1.5px solid #C7D2FE;background:#F8FAFC;"
                                         class="mt-2 rounded-3 p-2"></div>
                                    <input type="hidden" name="media_reuse_path" id="eqMediaReusePath" value="">
                                    <div id="eqMediaReuseSelected" style="background:#F0FDF4;border:1.5px solid #86EFAC;border-radius:8px;"
                                         class="mt-2 px-2 py-2 d-flex align-items-center gap-2 js-hidden">
                                        <i class="bi bi-check-circle-fill text-success" style="font-size:.9rem;"></i>
                                        <span class="fw-semibold text-success flex-grow-1" style="font-size:.78rem;"
                                              id="eqMediaReuseSelectedLabel"></span>
                                        <button type="button" class="btn btn-link p-0 text-danger"
                                                style="font-size:.7rem;text-decoration:none;" onclick="clearReuseItem('eq')">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>{{-- /media card --}}

                        </div>{{-- /right col --}}
                    </div>{{-- /row --}}

                    {{-- Options area (rebuilt per type by JS, full width) --}}
                    <div id="eqOptionsArea"></div>

                    {{-- Rubric builder for the edit modal --}}
                    @include('exams.partials.rubric-builder', ['prefix' => 'eq'])

                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-indigo">
                        <i class="bi bi-check-circle me-1"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
