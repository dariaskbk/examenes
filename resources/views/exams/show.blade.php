@extends('layouts.app')
@section('title', $exam->title)
@section('breadcrumb')
    <a href="{{ route('exams.index') }}">Mis Exámenes</a>
    <span class="mx-1 text-muted">/</span>
    <span class="fw-600 text-dark">{{ Str::limit($exam->title, 40) }}</span>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/exams/show.css') }}?v={{ filemtime(public_path('css/exams/show.css')) }}">
<link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
@endpush


@section('content')
{{-- Header toolbar --}}
@php $actMeta = $exam->activityMeta(); @endphp
<div class="d-flex flex-wrap gap-2 align-items-center mb-4">
    {{-- Activity type badge --}}
    <span style="font-size:.72rem;font-weight:700;border-radius:20px;padding:3px 10px;
                 background:{{ $actMeta['bg'] }};color:{{ $actMeta['color'] }};border:1px solid {{ $actMeta['color'] }}33;">
        <i class="bi {{ $actMeta['icon'] }} me-1"></i>{{ $actMeta['label'] }}
    </span>
    <span class="status-badge badge-{{ $exam->status }}">
        {{ ['draft'=>'Borrador','active'=>'Activo','closed'=>'Cerrado'][$exam->status] }}
    </span>
    @if($exam->canBeEdited())
    <a href="{{ route('exams.edit', $exam) }}" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-pencil me-1"></i>Editar
    </a>
    @endif
    <a href="{{ route('exams.preview', $exam) }}" target="_blank" class="btn btn-sm btn-outline-warning">
        <i class="bi bi-eye me-1"></i>Vista Previa
    </a>
    <a href="{{ route('exams.results', $exam) }}" class="btn btn-sm btn-outline-success">
        <i class="bi bi-bar-chart-line me-1"></i>Resultados <span class="badge bg-success ms-1">{{ $submittedCount }}</span>
    </a>
    <a href="{{ route('exams.monitor', $exam) }}" class="btn btn-sm btn-outline-info">
        <i class="bi bi-broadcast me-1"></i>Monitoreo en vivo
    </a>
    @if($exam->user_id === Auth::id())
    <button type="button" class="btn btn-sm" style="border:1px solid #7C3AED;color:#7C3AED;background:#fff;"
            data-bs-toggle="modal" data-bs-target="#shareExamModal">
        <i class="bi bi-share me-1"></i>Compartir
    </button>
    @endif
    {{-- Clone --}}
    <form method="POST" action="{{ route('exams.clone', $exam) }}"
          onsubmit="return confirmAndLoad('¿Duplicar este examen? Se creará una copia editable en borrador.', this, 'Duplicando examen…', 'Copiando preguntas y opciones...')">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-copy me-1"></i>Duplicar
        </button>
    </form>
    @if($exam->user_id === Auth::id())
        @if($exam->isArchived())
        <form method="POST" action="{{ route('exams.unarchive', $exam) }}"
              onsubmit="return confirmAndLoad('¿Restaurar esta actividad a la lista activa?', this, 'Restaurando…', 'Sacando del archivo.')">
            @csrf
            <button type="submit" class="btn btn-sm" style="background:#FEF3C7;color:#92400E;border:1px solid #FCD34D;">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Desarchivar
            </button>
        </form>
        @else
        <form method="POST" action="{{ route('exams.archive', $exam) }}"
              onsubmit="return confirmAndLoad('¿Archivar esta actividad? Se ocultará de la lista principal pero seguirá disponible en la pestaña Archivadas.', this, 'Archivando…', 'Moviendo al archivo.')">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-archive me-1"></i>Archivar
            </button>
        </form>
        @endif
    @endif
    @if($exam->canBeDeleted())
    <form method="POST" action="{{ route('exams.destroy', $exam) }}"
          onsubmit="return confirmAndLoad('¿Eliminar este examen? Esta acción es permanente y no se puede deshacer.', this, 'Eliminando examen…', 'Por favor espera.')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-trash me-1"></i>Eliminar
        </button>
    </form>
    @endif
</div>

@if($exam->isArchived())
<div class="alert d-flex align-items-center gap-2 mb-3" style="background:#FEF3C7;border:1px solid #FDE68A;color:#92400E;font-size:.85rem;border-radius:10px;">
    <i class="bi bi-archive-fill"></i>
    <div>
        <strong>Actividad archivada</strong> el {{ $exam->archived_at?->format('d/m/Y H:i') }}.
        Está oculta de la lista principal pero sigue disponible. Usa <strong>Desarchivar</strong> para restaurarla.
    </div>
</div>
@endif

<div class="row g-3">
    {{-- Left sidebar --}}
    <div class="col-lg-4 col-xl-3">

        {{-- Info card --}}
        <div class="card mb-3">
            <div class="card-head"><h6><i class="bi bi-info-circle me-2" style="color:#4F46E5;"></i>Detalles</h6></div>
            <div class="p-3 info-grid">
                <div class="info-row">
                    <label>Materia</label>
                    @if($subject)
                        <span>
                            {{ $subject->name }}
                            @if($subject->ciclo)
                                <small class="text-muted ms-1">— {{ $subject->ciclo }}</small>
                            @endif
                        </span>
                    @elseif($exam->level_id)
                        @php $lvl = \App\Models\Level::find($exam->level_id); @endphp
                        <span class="text-muted">
                            <i class="bi bi-diagram-3 me-1" style="color:#7C3AED;"></i>{{ $lvl?->full_label ?? '—' }}
                        </span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>
                <div class="info-row">
                    <label>Calificación SICORE</label>
                    @php $linkedComps = $exam->linked_components_info; @endphp
                    @if($linkedComps->isNotEmpty())
                        <span>
                            <i class="bi bi-link-45deg me-1" style="color:#0891B2;"></i>{{ $linkedComps->count() }} sección(es)
                            <div class="mt-1 d-flex flex-column gap-1">
                                @foreach($linkedComps as $c)
                                <span class="badge align-self-start" style="background:#CFFAFE;color:#155E75;font-size:.62rem;font-weight:600;">
                                    {{ $c->section_name }}{{ $c->group_type ? ' '.$c->group_type : '' }} · {{ $c->evaluation_name }} {{ rtrim(rtrim(number_format($c->value ?? 0,2),'0'),'.') }}%
                                </span>
                                @endforeach
                            </div>
                        </span>
                    @else
                        <span class="text-muted"><i class="bi bi-dash-circle me-1"></i>Práctica formativa</span>
                    @endif
                </div>
                <div class="info-row">
                    <label>Duración</label>
                    <span>{{ $exam->duration_minutes }} min</span>
                </div>
                <div class="info-row">
                    <label>Preguntas</label>
                    <span>{{ $questions->count() }}{{ $exam->questions_per_exam ? ' (muestra '.$exam->questions_per_exam.')' : '' }}</span>
                </div>
                <div class="info-row">
                    <label>Puntos totales</label>
                    <span>{{ number_format($exam->total_points, 1) }}</span>
                </div>
                <div class="info-row">
                    <label>Intentos máx.</label>
                    <span>{{ $exam->max_attempts }}</span>
                </div>
                <div class="info-row">
                    <label>Aprobación</label>
                    <span>{{ $exam->passing_score }}%</span>
                </div>
                <div class="info-row" style="grid-column:1/-1;">
                    <label>Disponibilidad</label>
                    <span style="font-size:.78rem;">
                        @if($exam->available_from)
                            {{ $exam->available_from->format('d/m/Y H:i') }} → {{ $exam->available_until?->format('d/m/Y H:i') ?? '∞' }}
                        @else
                            Sin restricción horaria
                        @endif
                    </span>
                </div>
            </div>
            <div class="px-3 pb-3 d-flex flex-wrap gap-1">
                @if($exam->shuffle_questions)<span class="badge" style="background:#EEF2FF;color:#4F46E5;font-size:.65rem;"><i class="bi bi-shuffle me-1"></i>Preguntas aleatorias</span>@endif
                @if($exam->shuffle_answers)<span class="badge" style="background:#FEF9C3;color:#854D0E;font-size:.65rem;"><i class="bi bi-shuffle me-1"></i>Respuestas aleatorias</span>@endif
                @if($exam->show_results)<span class="badge" style="background:#D1FAE5;color:#065F46;font-size:.65rem;"><i class="bi bi-eye me-1"></i>Muestra resultado</span>@endif
                @if($exam->proctoring)<span class="badge" style="background:#FEE2E2;color:#991B1B;font-size:.65rem;"><i class="bi bi-shield-lock me-1"></i>Anti-trampa</span>@endif
            </div>
        </div>

        {{-- Sync grades to SICORE --}}
        @if($exam->linked_components_info->isNotEmpty())
        <div class="card mb-3" style="border:1px solid #A5F3FC;">
            <div class="card-head" style="background:#ECFEFF;"><h6><i class="bi bi-cloud-upload me-2" style="color:#0891B2;"></i>Notas a SICORE</h6></div>
            <div class="p-3">
                <p style="font-size:.78rem;color:#475569;margin-bottom:.6rem;">
                    Envía la calificación (mejor intento) al componente de cada estudiante según su sección.
                    Cada alumno se califica en el componente de <strong>su</strong> sección.
                </p>
                <form method="POST" action="{{ route('exams.sync-grades', $exam) }}"
                      onsubmit="return confirmAndLoad(
                          'Se enviará la nota (mejor intento) de cada estudiante a su componente en SICORE. Las notas existentes se sobrescriben. ¿Continuar?',
                          this, 'Sincronizando notas…', 'Escribiendo calificaciones en SICORE.')">
                    @csrf
                    <button type="submit" class="btn btn-sm w-100 text-white" style="background:#0891B2;">
                        <i class="bi bi-cloud-upload me-1"></i>Sincronizar notas a SICORE
                    </button>
                </form>
                <div class="form-text mt-2" style="font-size:.7rem;">
                    <i class="bi bi-info-circle me-1"></i>Requiere que no haya respuestas pendientes de calificar.
                </div>
            </div>
        </div>
        @endif

        {{-- Assign / Generate codes --}}
        <div class="card mb-3">
            <div class="card-head">
                <h6><i class="bi bi-key me-2" style="color:#D97706;"></i>Asignar Examen</h6>
                @if($examYear)
                <span style="font-size:.7rem;background:#FEF9C3;color:#854D0E;padding:2px 8px;border-radius:20px;font-weight:700;">
                    {{ $examYear->year }}
                </span>
                @endif
            </div>
            <div class="p-3">
                @if($teacherSections->isEmpty())
                    <div style="font-size:.78rem;color:#94A3B8;text-align:center;padding:.5rem 0;">
                        <i class="bi bi-exclamation-circle d-block mb-1" style="font-size:1.2rem;"></i>
                        @if($sectionsFilteredByComponent)
                            No se encontraron las secciones de los componentes vinculados en {{ $examYear?->year ?? 'este año' }}.
                        @elseif($exam->level_id)
                            No se encontraron secciones para el nivel seleccionado en {{ $examYear?->year ?? 'este año' }}.
                        @else
                            No tienes secciones asignadas para {{ $examYear?->year ?? 'este año' }}.
                        @endif
                    </div>
                @else
                    @if($sectionsFilteredByComponent)
                    <div class="alert alert-info py-1 px-2 mb-2" style="font-size:.72rem;border-radius:8px;">
                        <i class="bi bi-link-45deg me-1"></i>
                        Solo se muestran las secciones de los <strong>componentes vinculados en SICORE</strong>, para que las notas puedan sincronizarse.
                    </div>
                    @elseif($sectionsFilteredByLevel)
                    <div class="alert alert-info py-1 px-2 mb-2" style="font-size:.72rem;border-radius:8px;">
                        <i class="bi bi-funnel-fill me-1"></i>
                        Mostrando todas las secciones del nivel <strong>{{ \App\Models\Level::find($exam->level_id)?->name }}</strong>.
                        Puedes asignar la actividad a cualquiera aunque no sean tus secciones habituales.
                    </div>
                    @endif
                    <p style="font-size:.75rem;color:#64748B;" class="mb-2">
                        Selecciona secciones o estudiantes individuales para generar códigos de acceso.
                    </p>
                    <form method="POST" action="{{ route('exams.generate-codes', $exam) }}" id="assignForm"
                          onsubmit="AppLoader.show('Generando códigos de acceso…', 'Creando accesos para los estudiantes seleccionados.')">
                        @csrf

                        {{-- Section list --}}
                        <div id="sectionList" class="mb-3">
                            @foreach($teacherSections as $sec)
                            @php
                                $coded = $sectionCodedCounts[$sec->id] ?? 0;
                                $full  = $sec->student_count > 0 && $coded >= $sec->student_count;
                            @endphp
                            <div class="section-item border rounded-2 mb-1 overflow-hidden" data-section-id="{{ $sec->id }}">
                                <div class="d-flex align-items-center gap-2 px-2 py-2">
                                    <input type="checkbox" name="section_ids[]" value="{{ $sec->id }}"
                                           class="section-chk form-check-input flex-shrink-0 mt-0"
                                           id="sec-{{ $sec->id }}"
                                           {{ $full ? 'disabled title=Completo' : '' }}>
                                    <label for="sec-{{ $sec->id }}" class="flex-grow-1 mb-0" style="cursor:pointer;font-size:.82rem;font-weight:600;color:#1E293B;">
                                        {{ $sec->name }}
                                        @if($sec->level) <span style="font-size:.68rem;color:#94A3B8;">{{ $sec->level->name }}</span>@endif
                                    </label>
                                    <span style="font-size:.68rem;background:#F1F5F9;border-radius:20px;padding:1px 7px;color:#64748B;white-space:nowrap;">
                                        {{ $coded }}/{{ $sec->student_count }}
                                    </span>
                                    <button type="button" class="btn-expand-sec p-0 border-0 bg-transparent text-muted"
                                            data-sec="{{ $sec->id }}" title="Ver estudiantes" style="font-size:.85rem;">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                                {{-- Student list (lazy) --}}
                                <div class="student-panel px-2 pb-2" data-sec="{{ $sec->id }}" style="display:none;border-top:1px solid #F1F5F9;padding-top:.5rem;">
                                    <div class="student-panel-body text-muted" style="font-size:.75rem;">
                                        <i class="bi bi-hourglass me-1"></i>Cargando...
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <button type="submit" id="assignBtn" class="btn btn-sm w-100 fw-700"
                                style="background:#D97706;color:#fff;border-radius:8px;">
                            <i class="bi bi-lightning-charge me-1"></i>Generar Códigos
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Excel / ZIP import --}}
        @if($exam->canBeEdited())
        <div class="card">
            <div class="card-head"><h6><i class="bi bi-file-earmark-excel me-2" style="color:#059669;"></i>Importar desde Excel</h6></div>
            <div class="p-3">
                <a href="{{ route('templates.questions') }}"
                   class="btn btn-sm btn-outline-success w-100 mb-2">
                    <i class="bi bi-download me-1"></i>Descargar Plantilla
                </a>
                <form method="POST" action="{{ route('exams.import-questions', $exam) }}"
                      enctype="multipart/form-data" id="importForm" onsubmit="startImport(this)">
                    @csrf
                    <div id="importFileArea">
                        <label style="font-size:.75rem;color:#64748B;font-weight:600;display:block;margin-bottom:.3rem;">
                            Archivo Excel <span style="font-weight:400;">o</span> ZIP con multimedia
                        </label>
                        <input type="file" name="excel_file" id="importFileInput"
                               class="form-control form-control-sm mb-2"
                               accept=".xlsx,.xls,.zip" required onchange="updateImportBtn()">
                        <div id="importFileInfo" style="display:none;font-size:.72rem;margin-bottom:.4rem;">
                            <i class="bi bi-file-earmark-check me-1"></i><span id="importFileName"></span>
                        </div>
                        {{-- ZIP hint shown dynamically --}}
                        <div id="importZipHint" style="display:none;font-size:.7rem;background:#ECFDF5;border:1px solid #6EE7B7;border-radius:7px;padding:.45rem .6rem;color:#065F46;line-height:1.6;">
                            <i class="bi bi-archive me-1"></i><strong>ZIP detectado.</strong>
                            Debe contener la plantilla Excel <em>y</em> los archivos de imagen/audio/video.<br>
                            En cada hoja, la columna <code>media_archivo</code> debe tener el nombre exacto del archivo.
                        </div>
                    </div>
                    <button type="submit" id="importBtn" class="btn btn-success btn-sm w-100 mt-2" disabled>
                        <i class="bi bi-upload me-1"></i>Importar Preguntas
                    </button>
                    {{-- Loading state (hidden until submit) --}}
                    <div id="importLoading" style="display:none;text-align:center;padding:.75rem 0;">
                        <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                        <span id="importLoadingMsg" style="font-size:.82rem;color:#059669;font-weight:600;">Procesando…</span>
                        <div id="importProgress" style="margin-top:.5rem;">
                            <div class="progress" style="height:6px;border-radius:3px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                     style="width:100%;"></div>
                            </div>
                            <div style="font-size:.7rem;color:#64748B;margin-top:.3rem;">Esto puede tardar unos segundos...</div>
                        </div>
                    </div>
                </form>
                <details class="mt-2">
                    <summary style="font-size:.72rem;color:#64748B;cursor:pointer;">Ver tipos de hojas soportados</summary>
                    <div style="font-size:.7rem;color:#64748B;margin-top:.5rem;line-height:1.8;">
                        La plantilla tiene <strong>10 hojas</strong>:<br>
                        <code>Seleccion_Unica</code> · <code>Seleccion_Multiple</code> · <code>Verdadero_Falso</code><br>
                        <code>Respuesta_Corta</code> · <code>Emparejamiento</code> · <code>Ordenamiento</code><br>
                        <code>Identificacion</code> · <code>Resp_Restringida</code> · <code>Ejercicio</code> · <code>Prod_Escrita</code>
                    </div>
                </details>
            </div>
        </div>
        @endif
    </div>

    {{-- Right main area --}}
    <div class="col-lg-8 col-xl-9">

        {{-- Access codes table --}}
        @if($accessCodes->count() > 0)
        <div class="card mb-3">
            <div class="card-head">
                <h6><i class="bi bi-person-badge me-2" style="color:#7C3AED;"></i>
                    Códigos de Acceso
                    <span class="badge ms-1" style="background:#EEF2FF;color:#4F46E5;">{{ $accessCodes->count() }}</span>
                </h6>
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    @if(($codeSections ?? collect())->count() > 1)
                    {{-- Section filter for PDF exports --}}
                    <select id="pdfSectionFilter" class="form-select form-select-sm" style="width:auto;max-width:160px;font-size:.78rem;"
                            title="Filtrar PDF por sección">
                        <option value="">Todas las secciones</option>
                        @foreach($codeSections as $sec)
                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                        @endforeach
                    </select>
                    @endif
                    {{-- Export PDF --}}
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-danger dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" title="Exportar PDF">
                            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="font-size:.82rem;min-width:240px;">
                            <li><h6 class="dropdown-header"><span id="pdfHeaderLabel">Códigos de acceso</span></h6></li>
                            <li>
                                <a class="dropdown-item pdf-link" data-format="list"
                                   href="{{ route('exams.codes.pdf', [$exam, 'format' => 'list']) }}" target="_blank">
                                    <i class="bi bi-list-ul me-2 text-primary"></i>Lista completa
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item pdf-link" data-format="slips"
                                   href="{{ route('exams.codes.pdf', [$exam, 'format' => 'slips']) }}" target="_blank">
                                    <i class="bi bi-scissors me-2 text-success"></i>Tiquetes para recortar
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Control de asistencia</h6></li>
                            <li>
                                <a class="dropdown-item pdf-link" data-format="padron"
                                   href="{{ route('exams.codes.pdf', [$exam, 'format' => 'padron']) }}" target="_blank">
                                    <i class="bi bi-pen me-2 text-danger"></i>Padrón de firmas
                                </a>
                            </li>
                        </ul>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" type="button"
                            data-bs-toggle="collapse" data-bs-target="#codesCollapse">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
            </div>
            <div class="collapse" id="codesCollapse">
                {{-- Regeneration toolbar --}}
                <div class="d-flex flex-wrap align-items-center gap-2 px-3 py-2 border-bottom" style="background:#FAFBFF;">
                    <span style="font-size:.72rem;color:#64748B;"><span id="regenSelCount">0</span> seleccionado(s)</span>
                    <button type="button" class="btn btn-sm btn-outline-warning" id="regenSelBtn" disabled
                            onclick="regenerateSelected()" style="font-size:.74rem;">
                        <i class="bi bi-arrow-repeat me-1"></i>Regenerar seleccionados
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger ms-auto"
                            onclick="regenerateAll()" style="font-size:.74rem;">
                        <i class="bi bi-arrow-repeat me-1"></i>Regenerar todos
                    </button>
                </div>
                <div class="table-responsive" style="max-height:280px;overflow-y:auto;">
                    <table class="table table-sm align-middle mb-0" style="font-size:.8rem;">
                        <thead style="background:#F8FAFC;position:sticky;top:0;">
                            <tr>
                                <th class="ps-3 py-2" style="width:28px;">
                                    <input type="checkbox" class="form-check-input" id="regenCheckAll"
                                           title="Seleccionar todos" onchange="toggleAllRegen(this)">
                                </th>
                                <th class="py-2 fw-600 text-muted" style="font-size:.7rem;">Estudiante</th>
                                <th class="py-2 fw-600 text-muted" style="font-size:.7rem;">Código / Enlace</th>
                                <th class="py-2 fw-600 text-muted text-center" style="font-size:.7rem;">Intentos</th>
                                <th class="py-2 fw-600 text-muted text-center" style="font-size:.7rem;">T. Extra</th>
                                <th class="py-2 fw-600 text-muted" style="font-size:.7rem;">Resultado</th>
                                <th class="py-2 pe-3 text-end fw-600 text-muted" style="font-size:.7rem;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accessCodes as $code)
                            @php
                                $student     = $students[$code->student_id] ?? null;
                                $lastAttempt = $lastAttemptsByCode[$code->id] ?? null;
                                $directUrl   = route('student.entry') . '?code=' . $code->code;
                                $hasAttempt  = $codesWithAttempts->has($code->id);
                            @endphp
                            <tr class="border-top">
                                <td class="ps-3 py-2">
                                    <input type="checkbox" class="form-check-input regen-chk" value="{{ $code->id }}"
                                           onchange="updateRegenCount()"
                                           {{ $hasAttempt ? 'disabled' : '' }}
                                           title="{{ $hasAttempt ? 'No se puede regenerar: el estudiante ya inició o realizó el examen' : 'Seleccionar para regenerar' }}">
                                </td>
                                <td class="py-2">{{ $student?->full_name ?? 'ID: '.$code->student_id }}</td>
                                <td class="py-2">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="code-chip">{{ $code->code }}</span>
                                        <button type="button"
                                                class="btn btn-sm p-0 border-0 bg-transparent"
                                                title="Copiar enlace directo"
                                                onclick="copyDirectLink('{{ $directUrl }}')">
                                            <i class="bi bi-link-45deg" style="color:#4F46E5;font-size:.95rem;"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="py-2 text-center">
                                    <span class="{{ $code->attemptsUsed() >= $exam->max_attempts ? 'text-danger' : 'text-success' }} fw-600">
                                        {{ $code->attemptsUsed() }}/{{ $exam->max_attempts }}
                                    </span>
                                </td>
                                <td class="py-2 text-center">
                                    <button type="button" class="btn btn-sm p-0 border-0 bg-transparent d-inline-flex align-items-center gap-1"
                                            title="Asignar tiempo extra (adecuación)"
                                            onclick="setExtraTime({{ $code->id }}, '{{ addslashes($student?->full_name ?? 'estudiante') }}', {{ (int) $code->extra_minutes }})">
                                        @if($code->extra_minutes > 0)
                                            <span class="badge" style="background:#EDE9FE;color:#6D28D9;font-size:.66rem;">+{{ $code->extra_minutes }} min</span>
                                        @else
                                            <i class="bi bi-clock-history" style="color:#94A3B8;font-size:.95rem;"></i>
                                        @endif
                                    </button>
                                </td>
                                <td class="py-2">
                                    @if($lastAttempt)
                                        <span class="fw-600 {{ $lastAttempt->percentage >= $exam->passing_score ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($lastAttempt->percentage, 1) }}%
                                        </span>
                                    @else
                                        <span class="text-muted">Pendiente</span>
                                    @endif
                                </td>
                                <td class="py-2 pe-3 text-end">
                                    @if($hasAttempt)
                                        <span class="text-muted" title="El estudiante ya inició o realizó el examen" style="font-size:.9rem;">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                    @else
                                        <button type="button" class="btn btn-sm p-0 border-0 bg-transparent"
                                                title="Regenerar código" onclick="regenerateOne({{ $code->id }})">
                                            <i class="bi bi-arrow-repeat" style="color:#D97706;font-size:.95rem;"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Hidden form for code regeneration --}}
            <form method="POST" action="{{ route('exams.regenerate-codes', $exam) }}" id="regenForm" class="d-none">
                @csrf
                <input type="hidden" name="scope" id="regenScope" value="selected">
                <div id="regenIdsContainer"></div>
            </form>

            {{-- Hidden form for per-student extra time --}}
            <form method="POST" id="extraTimeForm" class="d-none">
                @csrf
                <input type="hidden" name="extra_minutes" id="extraMinutesInput">
            </form>
        </div>
        @endif

        {{-- Questions --}}
        <div class="card" id="questionsList">
            <div class="card-head">
                <h6>
                    <i class="bi bi-list-ol me-2" style="color:#4F46E5;"></i>
                    Preguntas
                    <span class="badge ms-1" id="qCountBadge" style="background:#EEF2FF;color:#4F46E5;">{{ $questions->count() }}</span>
                </h6>
                @if($exam->canBeEdited())
                <div class="d-flex align-items-center gap-2">
                    @if($questions->count() > 1)
                    <span style="font-size:.68rem;color:#94A3B8;" title="Arrastra para reordenar">
                        <i class="bi bi-grip-vertical me-1"></i>Reordenable
                    </span>
                    @endif
                    @if($questions->count() > 0)
                    <form method="POST" action="{{ route('exams.questions.destroy-all', $exam) }}"
                          onsubmit="return confirmDanger(
                              'Se eliminarán las {{ $questions->count() }} pregunta(s) y sus opciones. Esta acción no se puede deshacer.',
                              this, 'Eliminando preguntas…', 'Borrando todas las preguntas y sus opciones.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar todas las preguntas">
                            <i class="bi bi-trash3 me-1"></i>Limpiar todo
                        </button>
                    </form>
                    @endif
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bankModal"
                            onclick="loadQuestionBank()">
                        <i class="bi bi-collection me-1"></i>Desde el banco
                    </button>
                    <button class="btn btn-indigo btn-sm" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                        <i class="bi bi-plus me-1"></i>Agregar
                    </button>
                </div>
                @endif
            </div>

            @forelse($questions as $i => $question)
            <div class="q-card mx-3 mt-3 {{ $loop->last ? 'mb-3' : '' }}" id="q-{{ $question->id }}"
                 @if($exam->canBeEdited()) draggable="true" data-question-id="{{ $question->id }}" @endif>
                <div class="d-flex align-items-start gap-3">
                    @if($exam->canBeEdited())
                    <div class="q-drag-handle" title="Arrastra para mover">
                        <i class="bi bi-grip-vertical"></i>
                    </div>
                    @endif
                    <div class="flex-shrink-0 rounded-2 d-flex align-items-center justify-content-center fw-700 text-white q-num-badge"
                         style="width:30px;height:30px;background:#4F46E5;font-size:.8rem;">{{ $i+1 }}</div>
                    <div class="flex-grow-1" style="min-width:0;overflow-wrap:break-word;">
                        @php
                            $tMap = [
                                'single_choice'       => ['mc', 'Selección Única'],
                                'multiple_choice'     => ['mc', 'Selección Única'],
                                'multiple_select'     => ['ms', 'Selección Múltiple'],
                                'true_false'          => ['tf', 'Verdadero/Falso'],
                                'short_answer'        => ['sa', 'Respuesta Corta'],
                                'matching'            => ['mt', 'Emparejamiento'],
                                'ordering'            => ['or', 'Ordenamiento'],
                                'identification'      => ['id', 'Identificación'],
                                'completion'          => ['cp', 'Completar'],
                                'restricted_response' => ['rr', 'Resp. Restringida'],
                                'exercise'            => ['ex', 'Ejercicio'],
                                'written_production'  => ['wp', 'Prod. Escrita'],
                            ];
                            [$tClass,$tLabel] = $tMap[$question->type] ?? ['mc', $question->type];
                        @endphp
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="type-pill type-{{ $tClass }}">{{ $tLabel }}</span>
                            @if($question->media_type !== 'none')
                            <span style="font-size:.65rem;background:#F0F9FF;color:#075985;border-radius:20px;padding:2px 7px;">
                                <i class="bi bi-{{ $question->media_type === 'image' ? 'image' : ($question->media_type === 'audio' ? 'music-note' : 'camera-video') }} me-1"></i>{{ ucfirst($question->media_type) }}
                            </span>
                            @endif
                            <span style="font-size:.72rem;color:#94A3B8;">{{ $question->points }} pt{{ $question->points != 1 ? 's' : '' }}</span>
                        </div>
                        <div class="mb-2 fw-semibold exam-prose" style="font-size:.9rem;">{!! $question->question_text !!}</div>

                        @if($question->media_type === 'image' && $question->image)
                        <img src="{{ Storage::url($question->image) }}" class="rounded mb-2" style="max-height:100px;object-fit:contain;">
                        @elseif($question->media_type === 'audio' && $question->audio)
                        <audio controls class="mb-2 w-100" style="height:36px;"><source src="{{ Storage::url($question->audio) }}"></audio>
                        @elseif($question->media_type === 'video' && $question->video)
                        <video controls class="rounded mb-2" style="max-height:80px;"><source src="{{ Storage::url($question->video) }}"></video>
                        @endif

                        @if(in_array($question->type, ['single_choice','multiple_choice','multiple_select','true_false']))
                        <div class="row g-1">
                            @foreach($question->options as $opt)
                            <div class="col-md-6">
                                <div class="p-2 rounded border small {{ $opt->is_correct ? 'opt-correct' : '' }}" style="font-size:.8rem;">
                                    @if($opt->is_correct)<i class="bi bi-check-circle-fill text-success me-1"></i>@endif
                                    {{ $opt->option_text }}
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @elseif($question->type === 'matching')
                        <div class="d-flex flex-column gap-1">
                            @foreach($question->options as $pair)
                            <div class="match-pair">
                                <span class="fw-semibold" style="min-width:30%;">{{ $pair->option_text }}</span>
                                <span class="match-arr"><i class="bi bi-arrow-right"></i></span>
                                <span class="text-muted">{{ $pair->match_text }}</span>
                            </div>
                            @endforeach
                        </div>
                        @elseif($question->type === 'ordering')
                        <div class="d-flex flex-column gap-1">
                            @foreach($question->options->sortBy('order') as $j => $item)
                            <div class="order-item">
                                <div class="order-num">{{ $item->order }}</div>
                                <span>{{ $item->option_text }}</span>
                            </div>
                            @endforeach
                        </div>
                        @elseif($question->type === 'identification')
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($question->options->sortBy('order') as $part)
                            <div class="d-flex align-items-center gap-1 px-2 py-1 rounded-2" style="background:#FFF1F2;border:1px solid #FECDD3;font-size:.78rem;">
                                <span class="ident-label-badge" style="width:22px;height:22px;font-size:.7rem;">{{ $part->option_text }}</span>
                                <span class="text-muted">→</span>
                                <span class="fw-semibold" style="color:#9F1239;">{{ $part->match_text }}</span>
                            </div>
                            @endforeach
                        </div>
                        @elseif($question->type === 'completion')
                        @php
                            $cpCorrects    = $question->options->where('is_correct', true)->sortBy('order');
                            $cpDistractors = $question->options->where('is_correct', false);
                        @endphp
                        <div class="mt-1">
                            <div class="mb-1" style="font-size:.72rem;color:#94A3B8;font-weight:600;">BANCO DE PALABRAS</div>
                            <div>
                                @foreach($cpCorrects as $copt)
                                <span class="cp-blank-chip">✓ {{ $copt->option_text }}</span>
                                @endforeach
                                @foreach($cpDistractors as $dopt)
                                <span class="cp-distractor-chip">✗ {{ $dopt->option_text }}</span>
                                @endforeach
                            </div>
                        </div>
                        @elseif(in_array($question->type, ['restricted_response','exercise','written_production']))
                        @if($question->grading_criteria)
                        <div class="rubric-box mt-1">
                            <i class="bi bi-award me-1"></i><strong>Criterios:</strong> {{ $question->grading_criteria }}
                        </div>
                        @endif
                        @endif
                    </div>
                    @if($exam->canBeEdited())
                    <div class="d-flex flex-column gap-1">
                        @php
                            $qData = [
                                'id'              => $question->id,
                                'type'            => $question->type,
                                'text'            => $question->question_text,
                                'points'          => $question->points,
                                'imageUrl'        => $question->image ? Storage::url($question->image) : null,
                                'audioUrl'        => $question->audio ? Storage::url($question->audio) : null,
                                'videoUrl'        => $question->video ? Storage::url($question->video) : null,
                                // Normalize: treat as 'none' if the actual file column is empty
                                'mediaType'       => (
                                    ($question->media_type === 'image' && $question->image) ||
                                    ($question->media_type === 'audio' && $question->audio) ||
                                    ($question->media_type === 'video' && $question->video)
                                ) ? $question->media_type : 'none',
                                'gradingCriteria' => $question->grading_criteria,
                                'rubric'          => $question->rubric,
                                'options'         => $question->options->map(fn($o) => [
                                    'text'      => $o->option_text,
                                    'isCorrect' => (bool) $o->is_correct,
                                    'matchText' => $o->match_text,
                                    'order'     => $o->order,
                                ])->values()->toArray(),
                            ];
                        @endphp
                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                style="padding:3px 8px;"
                                onclick="openEditModal({{ Js::from($qData) }})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                style="padding:3px 8px;"
                                onclick="deleteQuestion(this)"
                                data-url="{{ route('exams.questions.destroy', [$exam, $question]) }}"
                                data-token="{{ csrf_token() }}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-question-circle"></i>
                <p class="small mb-2">No hay preguntas. Agrega una manualmente o importa desde Excel.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Question Bank Modal (extracted) --}}
@include('exams.partials.question-bank-modal')

{{-- Add Question Modal (extracted) --}}
@include('exams.partials.add-question-modal')

{{-- Edit Question Modal (extracted) --}}
@include('exams.partials.edit-question-modal')

{{-- Share Exam Modal (extracted) --}}
@include('exams.partials.share-modal')

@endsection

@php
/* Build a map of media already used in this exam, grouped by unique file path.
   Used by the "reuse from another question" picker in both modals. */
$examMediaMap = ['image' => [], 'audio' => [], 'video' => []];
$_byPath = [];
foreach ($questions as $_i => $_q) {
    $_type = $_q->media_type ?? 'none';
    $_path = match($_type) { 'image' => $_q->image, 'audio' => $_q->audio, 'video' => $_q->video, default => null };
    if (!$_path || !in_array($_type, ['image','audio','video'])) continue;
    $_byPath[$_path] ??= ['type' => $_type, 'url' => Storage::url($_path), 'nums' => []];
    $_byPath[$_path]['nums'][] = $_i + 1;
}
foreach ($_byPath as $_path => $_d) {
    $examMediaMap[$_d['type']][] = [
        'path'  => $_path,
        'url'   => $_d['url'],
        'label' => (count($_d['nums']) === 1 ? 'Pregunta ' : 'Preguntas ') . implode(', ', $_d['nums']),
    ];
}
unset($_byPath, $_i, $_q, $_type, $_path, $_d);
@endphp

@push('scripts')
<script>
// ── Bootstrap config shared between the inline blade script and the
//    feature files extracted under public/js/exams/ ────────────────────────
window.ExamShow = {
    examId:   {{ $exam->id }},
    canEdit:  @json($exam->canBeEdited()),
    canShare: @json($exam->user_id === Auth::id()),
    media:    @json($examMediaMap),
    routes: {
        sectionStudents:     "{{ route('exams.section-students', [$exam, '__SEC__']) }}",
        @if($exam->canBeEdited())
        questionsReorder:    "{{ route('exams.questions.reorder', $exam) }}",
        questionBank:        "{{ route('exams.question-bank', $exam) }}",
        questionsUpdate:     "{{ route('exams.questions.update', [$exam, '__QID__']) }}",
        @endif
        extraTimeBase:       "{{ url('exams/'.$exam->id.'/codes') }}",
        @if($exam->user_id === Auth::id())
        shareSearchTeachers: "{{ route('shares.search-teachers') }}",
        @endif
    }
};
</script>
{{-- Feature scripts (extracted from the inline @push('scripts') block above).
     Load order matters:
       1. question-modals.js declares escHtml() + window.selectType — must be first.
       2. Quill CDN must load before quill-init.js (depends on Quill global).
       3. rubric-builder.js wraps window.selectType — must come after question-modals.js.
       4. The rest are independent. --}}
<script src="{{ asset('js/exams/question-modals.js') }}?v={{ filemtime(public_path('js/exams/question-modals.js')) }}"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script src="{{ asset('js/exams/quill-init.js') }}?v={{ filemtime(public_path('js/exams/quill-init.js')) }}"></script>
<script src="{{ asset('js/exams/rubric-builder.js') }}?v={{ filemtime(public_path('js/exams/rubric-builder.js')) }}"></script>
<script src="{{ asset('js/exams/delete-question.js') }}?v={{ filemtime(public_path('js/exams/delete-question.js')) }}"></script>
<script src="{{ asset('js/exams/copy-link.js') }}?v={{ filemtime(public_path('js/exams/copy-link.js')) }}"></script>
<script src="{{ asset('js/exams/section-expand.js') }}?v={{ filemtime(public_path('js/exams/section-expand.js')) }}"></script>
<script src="{{ asset('js/exams/import-ui.js') }}?v={{ filemtime(public_path('js/exams/import-ui.js')) }}"></script>
<script src="{{ asset('js/exams/sync-points.js') }}?v={{ filemtime(public_path('js/exams/sync-points.js')) }}"></script>
<script src="{{ asset('js/exams/drag-reorder.js') }}?v={{ filemtime(public_path('js/exams/drag-reorder.js')) }}"></script>
<script src="{{ asset('js/exams/question-bank.js') }}?v={{ filemtime(public_path('js/exams/question-bank.js')) }}"></script>
<script src="{{ asset('js/exams/code-regen.js') }}?v={{ filemtime(public_path('js/exams/code-regen.js')) }}"></script>
<script src="{{ asset('js/exams/extra-time.js') }}?v={{ filemtime(public_path('js/exams/extra-time.js')) }}"></script>
<script src="{{ asset('js/exams/share.js') }}?v={{ filemtime(public_path('js/exams/share.js')) }}"></script>
<script src="{{ asset('js/exams/pdf-filter.js') }}?v={{ filemtime(public_path('js/exams/pdf-filter.js')) }}"></script>
<script src="{{ asset('js/exams/scroll-edited.js') }}?v={{ filemtime(public_path('js/exams/scroll-edited.js')) }}"></script>
@endpush
