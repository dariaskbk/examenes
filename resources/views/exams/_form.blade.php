@php
    $pf          = $prefill ?? [];
    $actTypes    = \App\Models\Exam::ACTIVITY_TYPES;
    $currentType = old('activity_type', $exam?->activity_type ?? ($pf['activity_type'] ?? 'exam'));
    $actHints = [
        'exam'         => 'Evaluacion formal con tiempo limite y calificacion automatica por preguntas.',
        'quiz'         => 'Prueba corta o formativa. Ideal para repasos rapidos.',
        'assignment'   => 'Tarea para entregar. El estudiante sube un archivo o escribe una respuesta.',
        'project'      => 'Proyecto con multiples entregas o etapas.',
        'lab'          => 'Practica de laboratorio con preguntas y/o entrega de informe.',
        'presentation' => 'Presentacion oral o expositiva. El docente registra la nota manualmente.',
    ];
@endphp

{{-- ── Tipo de actividad ─────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-head"><h6><i class="bi bi-grid-1x2 me-2" style="color:#4F46E5;"></i>Tipo de Actividad</h6></div>
    <div class="p-3">
        <input type="hidden" name="activity_type" id="activityTypeInput" value="{{ $currentType }}">
        <div class="d-flex flex-wrap gap-2" id="activityTypeGrid">
            @foreach($actTypes as $key => $meta)
            <button type="button"
                    class="activity-type-btn {{ $currentType === $key ? 'active' : '' }}"
                    data-type="{{ $key }}"
                    style="
                        border: 2px solid {{ $currentType === $key ? $meta['color'] : '#E2E8F0' }};
                        background: {{ $currentType === $key ? $meta['bg'] : '#F8FAFC' }};
                        color: {{ $currentType === $key ? $meta['color'] : '#64748B' }};
                        border-radius: 12px; padding: .5rem 1rem;
                        font-size: .8rem; font-weight: 600; cursor: pointer;
                        display: inline-flex; align-items: center; gap: .4rem;
                        transition: all .15s;
                    "
                    data-color="{{ $meta['color'] }}"
                    data-bg="{{ $meta['bg'] }}">
                <i class="bi {{ $meta['icon'] }}" style="font-size:.95rem;"></i>
                {{ $meta['label'] }}
            </button>
            @endforeach
        </div>
        <div class="form-text mt-2" id="activityTypeHint" style="font-size:.72rem;">
            <span id="actTypeHintText">{{ $actHints[$currentType] ?? '' }}</span>
        </div>
    </div>
</div>

{{-- ── Vínculo con SICORE (calificación) ─────────────────────────────────────── --}}
@php
    $components    = $evaluationComponents ?? collect();
    $linked        = $linkedComponentIds ?? [];
    $listIds       = $components->pluck('id')->map(fn($i) => (int) $i)->all();
    $missingLinked = $exam ? $exam->linked_components_info->filter(fn($c) => !in_array((int) $c->id, $listIds)) : collect();
    $sicoreType    = \App\Models\Exam::sicoreTypeFor($currentType);
@endphp
<div class="card mb-3">
    <div class="card-head"><h6><i class="bi bi-link-45deg me-2" style="color:#0891B2;"></i>Calificación en SICORE</h6></div>
    <div class="p-3">
        <label class="form-label mb-1">Componentes a calificar (uno por sección/grupo)</label>
        <div class="form-text mb-2" style="margin-top:0;">
            <i class="bi bi-info-circle me-1"></i>
            Lista filtrada por el <strong>tipo de actividad</strong> seleccionado arriba.
            Al cambiar el tipo, los componentes se actualizan automáticamente. Marca los que este examen debe calificar.
            Cada estudiante recibe su nota en el componente de <strong>su sección</strong>. Sin marcar nada = práctica formativa.
        </div>

        <div id="componentSelectorWrap">
            @include('exams.partials.components-selector', [
                'components'         => $components,
                'linkedComponentIds' => $linked,
                'missingLinked'      => $missingLinked,
                'sicoreType'         => $sicoreType,
                'noSubject'          => empty(old('subject_id', $exam?->subject_id ?? ($pf['subject_id'] ?? null))),
            ])
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Left column --}}
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-head"><h6><i class="bi bi-info-circle me-2" style="color:#4F46E5;"></i>Información General</h6></div>
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title', $exam?->title ?? ($pf['title'] ?? '')) }}"
                           placeholder="Ej: Examen Parcial I — Matemáticas" required autofocus>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                @php
                    $selSubject = old('subject_id', $exam?->subject_id ?? ($pf['subject_id'] ?? ''));
                    $selLevel   = old('level_id',   $exam?->level_id   ?? '');
                    $defaultYear = old('year_id', $exam?->year_id ?? ($pf['year_id'] ?? $activeYear?->id));
                    $hasCiclos  = count($subjectsByCiclo ?? []) > 1
                                  || (count($subjectsByCiclo ?? []) === 1 && !array_key_exists('General', $subjectsByCiclo ?? []));
                @endphp
                <div class="row g-3 mb-3">
                    {{-- ── Materia ─────────────────────────────────────────── --}}
                    <div class="col-md-6">
                        <label class="form-label">Materia</label>
                        <select name="subject_id" id="subjectSelect" class="form-select">
                            <option value="">Sin materia específica</option>
                            @if($hasCiclos)
                                @foreach($subjectsByCiclo as $ciclo => $group)
                                <optgroup label="{{ $ciclo }}">
                                    @foreach($group as $subject)
                                    <option value="{{ $subject->id }}" {{ $selSubject == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            @else
                                @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ $selSubject == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                                @endforeach
                            @endif
                        </select>
                        @if($subjectsFiltered ?? false)
                        <div class="form-text"><i class="bi bi-funnel me-1"></i>Solo tus materias asignadas.</div>
                        @else
                        <div class="form-text text-muted"><i class="bi bi-info-circle me-1"></i>Todas las materias disponibles.</div>
                        @endif

                        {{-- Ciclo / Nivel — visible solo si no hay materia seleccionada --}}
                        <div id="levelSelectWrap" class="mt-2" style="{{ $selSubject ? 'display:none' : '' }}">
                            <label class="form-label mb-1" style="font-size:.82rem;color:#64748B;">
                                <i class="bi bi-diagram-3 me-1"></i>Ciclo / Nivel educativo
                            </label>
                            <select name="level_id" id="levelSelect" class="form-select form-select-sm">
                                <option value="">— Sin ciclo específico —</option>
                                @foreach($levels as $level)
                                <option value="{{ $level->id }}" {{ $selLevel == $level->id ? 'selected' : '' }}>
                                    {{ $level->full_label }}
                                </option>
                                @endforeach
                            </select>
                            <div class="form-text" style="font-size:.72rem;">
                                Asocia la actividad a un ciclo o nivel cuando no tiene materia.
                            </div>
                        </div>
                    </div>

                    {{-- ── Año Lectivo ──────────────────────────────────────── --}}
                    <div class="col-md-6">
                        <label class="form-label">Año Lectivo</label>
                        <select name="year_id" class="form-select">
                            <option value="">Seleccionar año</option>
                            @foreach($years as $year)
                            <option value="{{ $year->id }}" {{ $defaultYear == $year->id ? 'selected' : '' }}>
                                {{ $year->year }}{{ $year->status ? ' ★' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" rows="2"
                              placeholder="Breve descripción del examen (opcional)">{{ old('description', $exam?->description) }}</textarea>
                </div>

                <div class="mb-0">
                    <label class="form-label">Instrucciones para el estudiante</label>
                    <textarea name="instructions" class="form-control" rows="3"
                              placeholder="El estudiante leerá esto antes de comenzar...">{{ old('instructions', $exam?->instructions) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Availability --}}
        <div class="card">
            <div class="card-head"><h6><i class="bi bi-calendar-range me-2" style="color:#059669;"></i>Disponibilidad</h6></div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Disponible desde</label>
                        <input type="datetime-local" name="available_from" class="form-control"
                               value="{{ old('available_from', $exam?->available_from?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Disponible hasta</label>
                        <input type="datetime-local" name="available_until" class="form-control"
                               value="{{ old('available_until', $exam?->available_until?->format('Y-m-d\TH:i')) }}">
                    </div>
                </div>
                <div class="form-text mt-2">Deja vacío para que el examen esté siempre disponible mientras el estado sea "Activo".</div>
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-lg-4">
        {{-- Status --}}
        <div class="card mb-3">
            <div class="card-head"><h6><i class="bi bi-toggles me-2" style="color:#D97706;"></i>Publicación</h6></div>
            <div class="p-3">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select mb-2">
                    <option value="draft"  {{ old('status', $exam?->status ?? 'draft') === 'draft'  ? 'selected' : '' }}>📝 Borrador</option>
                    <option value="active" {{ old('status', $exam?->status) === 'active' ? 'selected' : '' }}>✅ Activo</option>
                    <option value="closed" {{ old('status', $exam?->status) === 'closed' ? 'selected' : '' }}>🔒 Cerrado</option>
                </select>
                <div class="form-text">En "Activo" los estudiantes pueden acceder con su código.</div>
            </div>
        </div>

        {{-- Timing & attempts --}}
        <div class="card mb-3">
            <div class="card-head"><h6><i class="bi bi-clock me-2" style="color:#4F46E5;"></i>Tiempo y Límites</h6></div>
            <div class="p-3">
                <div class="mb-3">
                    <label class="form-label">Duración (minutos) <span class="text-danger">*</span></label>
                    <input type="number" name="duration_minutes" class="form-control"
                           min="1" max="480" value="{{ old('duration_minutes', $exam?->duration_minutes ?? 60) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Intentos máximos por estudiante</label>
                    <input type="number" name="max_attempts" class="form-control"
                           min="1" max="10" value="{{ old('max_attempts', $exam?->max_attempts ?? 1) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nota mínima aprobatoria (%)</label>
                    <div class="input-group">
                        <input type="number" name="passing_score" class="form-control"
                               min="0" max="100" step="0.1"
                               value="{{ old('passing_score', $exam?->passing_score ?? 70) }}" required>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div>
                    <label class="form-label">Preguntas a mostrar por intento</label>
                    <input type="number" name="questions_per_exam" class="form-control"
                           min="1" placeholder="Todas"
                           value="{{ old('questions_per_exam', $exam?->questions_per_exam) }}">
                    <div class="form-text">Vacío = mostrar todas las preguntas.</div>
                </div>
            </div>
        </div>

        {{-- Options --}}
        <div class="card">
            <div class="card-head"><h6><i class="bi bi-sliders me-2" style="color:#7C3AED;"></i>Opciones</h6></div>
            <div class="p-3">
                @php
                    $switches = [
                        ['name' => 'shuffle_questions',   'label' => 'Orden aleatorio de preguntas', 'icon' => 'shuffle'],
                        ['name' => 'shuffle_answers',     'label' => 'Orden aleatorio de respuestas','icon' => 'arrow-left-right'],
                        ['name' => 'show_results',        'label' => 'Mostrar resultado al finalizar','icon' => 'eye'],
                        ['name' => 'show_correct_answers','label' => 'Mostrar respuestas correctas',  'icon' => 'check-circle'],
                        ['name' => 'proctoring',          'label' => 'Control anti-trampa (detección de salidas)', 'icon' => 'shield-lock'],
                        ['name' => 'proctoring_strict',   'label' => 'Modo estricto: pausar al detectar salida (requiere autorización del docente)', 'icon' => 'pause-circle'],
                    ];
                    // Switches checked by default when creating a NEW exam
                    $defaultOn = ['show_results', 'proctoring'];
                @endphp
                @foreach($switches as $sw)
                <div class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-{{ $sw['icon'] }}" style="color:#94A3B8;font-size:.9rem;"></i>
                        <span style="font-size:.8rem;color:#374151;">{{ $sw['label'] }}</span>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="{{ $sw['name'] }}"
                               id="{{ $sw['name'] }}" role="switch"
                               {{ old($sw['name'], $exam ? $exam->{$sw['name']} : in_array($sw['name'], $defaultOn)) ? 'checked' : '' }}>
                    </div>
                </div>
                @endforeach

                {{-- Umbral del modo estricto (solo visible si está activo) --}}
                @php
                    $strictOn = (bool) old('proctoring_strict', $exam?->proctoring_strict ?? false);
                    $threshold = (int) old('proctoring_threshold', $exam?->proctoring_threshold ?? 2);
                @endphp
                <div id="proctoringThresholdWrap" class="mt-2 ps-4 pe-1" style="{{ $strictOn ? '' : 'display:none;' }}border-top:1px dashed #E2E8F0;padding-top:.5rem;">
                    <label class="form-label mb-1" style="font-size:.74rem;color:#475569;">
                        <i class="bi bi-eye-slash me-1"></i>Salidas permitidas antes de pausar
                    </label>
                    <input type="number" name="proctoring_threshold" id="proctoring_threshold"
                           class="form-control form-control-sm" min="1" max="10" value="{{ $threshold }}" style="max-width:90px;">
                    <div class="form-text" style="font-size:.7rem;">
                        El estudiante recibe aviso en las primeras N salidas. A partir de N+1, el examen se <strong>pausa</strong> hasta que tú autorices.
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function(){
            const sw = document.getElementById('proctoring_strict');
            const wrap = document.getElementById('proctoringThresholdWrap');
            if(sw && wrap){
                sw.addEventListener('change', () => { wrap.style.display = sw.checked ? '' : 'none'; });
            }
        })();
        </script>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger mt-3">
    <ul class="mb-0 ps-3" style="font-size:.8rem;">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

@push('scripts')
<script>
(function () {
    const hints = @json($actHints);

    document.querySelectorAll('.activity-type-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const type  = this.dataset.type;
            const color = this.dataset.color;
            const bg    = this.dataset.bg;

            // Deselect all
            document.querySelectorAll('.activity-type-btn').forEach(b => {
                b.style.border     = '2px solid #E2E8F0';
                b.style.background = '#F8FAFC';
                b.style.color      = '#64748B';
            });

            // Select this
            this.style.border     = `2px solid ${color}`;
            this.style.background = bg;
            this.style.color      = color;

            document.getElementById('activityTypeInput').value = type;
            document.getElementById('actTypeHintText').textContent = hints[type] || '';
        });
    });
})();

// ── Materia / Ciclo toggle ────────────────────────────────────────────────
(function () {
    const subjectSel  = document.getElementById('subjectSelect');
    const levelWrap   = document.getElementById('levelSelectWrap');
    const levelSel    = document.getElementById('levelSelect');

    if (!subjectSel || !levelWrap) return;

    function toggleLevel() {
        const noSubject = subjectSel.value === '';
        levelWrap.style.display = noSubject ? '' : 'none';
        if (!noSubject) levelSel.value = '';   // limpiar nivel si elige materia
    }

    subjectSel.addEventListener('change', toggleLevel);
    toggleLevel(); // aplicar al cargar
})();

// ── AJAX: recargar componentes al cambiar tipo de actividad o materia ────────
(function () {
    const COMP_URL = "{{ route('exams.components-by-type') }}";
    const examId   = {!! $exam?->id ? (int) $exam->id : 'null' !!};

    function currentActivityType() {
        const inp = document.getElementById('activityTypeInput');
        return inp ? inp.value : 'exam';
    }
    function currentSubjectId() {
        const sel = document.getElementById('subjectSelect');
        return sel ? sel.value : '';
    }
    function currentLevelId() {
        const sel = document.getElementById('levelSelect');
        return sel ? sel.value : '';
    }

    function reloadComponents() {
        const wrap = document.getElementById('componentSelectorWrap');
        if (!wrap) return;
        wrap.style.opacity = '.4';
        const params = new URLSearchParams({
            activity_type: currentActivityType(),
            subject_id:    currentSubjectId(),
            level_id:      currentLevelId(),
        });
        if (examId) params.append('exam_id', examId);

        fetch(COMP_URL + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => { wrap.innerHTML = html; wrap.style.opacity = '1'; })
            .catch(() => { wrap.style.opacity = '1'; });
    }

    // Trigger on activity type buttons
    document.querySelectorAll('.activity-type-btn').forEach(btn => {
        btn.addEventListener('click', reloadComponents);
    });
    // Trigger on subject change
    const subjectSel = document.getElementById('subjectSelect');
    if (subjectSel) subjectSel.addEventListener('change', reloadComponents);
    // Trigger on level change (shown when no subject is picked)
    const levelSel = document.getElementById('levelSelect');
    if (levelSel) levelSel.addEventListener('change', reloadComponents);
})();

// ── Filtro de búsqueda en la lista de componentes ────────────────────────────
function filterComponentList(query) {
    const q = (query || '').toLowerCase().trim();
    document.querySelectorAll('#compList .comp-row').forEach(row => {
        const key = row.dataset.search || '';
        row.style.display = (!q || key.includes(q)) ? '' : 'none';
    });
    // Hide groups with no visible rows
    document.querySelectorAll('#compList .comp-group').forEach(g => {
        const anyVisible = Array.from(g.querySelectorAll('.comp-row')).some(r => r.style.display !== 'none');
        g.style.display = anyVisible ? '' : 'none';
    });
}

// ── Exclusividad por sección/grupo (un componente máximo por sección) ────────
// Se enlaza por delegación porque el contenido del selector se recarga vía AJAX.
document.addEventListener('change', function (e) {
    const cb = e.target;
    if (!cb.classList || !cb.classList.contains('comp-checkbox')) return;
    if (!cb.checked) return;
    const group = cb.dataset.sectionGroup;
    if (!group) return;
    document.querySelectorAll('.comp-checkbox[data-section-group="' + CSS.escape(group) + '"]').forEach(other => {
        if (other !== cb && other.checked) {
            other.checked = false;
        }
    });
});
</script>
@endpush
