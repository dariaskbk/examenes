{{--
    Reusable rubric builder.
    Usage: @include('exams.partials.rubric-builder', ['prefix' => 'add' | 'eq'])
    The container element has id="{prefix}RubricRoot". A hidden input named
    "rubric_json" carries the serialized JSON to the server.
--}}
@php $prefix = $prefix ?? 'add'; @endphp

<div id="{{ $prefix }}RubricSection" class="rubric-section mt-3" style="display:none;">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <label class="form-label fw-semibold mb-0">
            <i class="bi bi-table me-1" style="color:#D97706;"></i>Rúbrica de evaluación
        </label>
        <div class="d-flex align-items-center gap-2">
            <label style="font-size:.78rem;color:#475569;">Niveles:</label>
            <select id="{{ $prefix }}RubricLevels" class="form-select form-select-sm" style="width:auto;font-size:.8rem;">
                <option value="3">3</option>
                <option value="4" selected>4</option>
                <option value="5">5</option>
            </select>
            <span class="text-muted" style="font-size:.74rem;">Total máx: <strong id="{{ $prefix }}RubricMax">0</strong> pts</span>
        </div>
    </div>

    <div class="rubric-help text-muted mb-2" style="font-size:.74rem;">
        Define criterios (filas) y niveles (columnas). El docente seleccionará un nivel por criterio al calificar; los puntos suman automáticamente.
    </div>

    <div class="table-responsive" style="border:1px solid #E2E8F0;border-radius:8px;">
        <table class="table table-sm mb-0 align-middle" style="font-size:.82rem;">
            <thead style="background:#FAFAFB;">
                <tr id="{{ $prefix }}RubricHeader">
                    <th style="width:160px;font-size:.7rem;color:#64748B;">CRITERIO</th>
                    {{-- Level headers injected by JS --}}
                    <th style="width:34px;"></th>
                </tr>
            </thead>
            <tbody id="{{ $prefix }}RubricBody"></tbody>
        </table>
    </div>

    <div class="mt-2">
        <button type="button" class="btn btn-sm btn-outline-warning" onclick="rubricAddCriterion('{{ $prefix }}')" style="font-size:.78rem;">
            <i class="bi bi-plus-lg me-1"></i>Agregar criterio
        </button>
        <button type="button" class="btn btn-sm btn-link text-muted" onclick="rubricClear('{{ $prefix }}')" style="font-size:.74rem;">
            Limpiar rúbrica
        </button>
    </div>

    {{-- Hidden field that travels with the form --}}
    <input type="hidden" name="rubric_json" id="{{ $prefix }}RubricJson">
</div>
