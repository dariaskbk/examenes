@extends('layouts.app')
@section('title', 'Resultados — ' . $exam->title)
@section('breadcrumb')
    <a href="{{ route('exams.index') }}">Mis Exámenes</a>
    <span class="mx-1 text-muted">/</span>
    <a href="{{ route('exams.show', $exam) }}">{{ Str::limit($exam->title, 30) }}</a>
    <span class="mx-1 text-muted">/</span>
    <span class="fw-600 text-dark">Resultados</span>
@endsection

@section('content')
@php
    $submitted   = $attempts->whereIn('status', ['submitted','timed_out']);
    $inProgress  = $attempts->where('status', 'in_progress');
    $passedCount = $submitted->filter(fn($a) => $a->percentage >= $exam->passing_score)->count();
    $avgPct      = $submitted->count() ? round($submitted->avg('percentage'), 1) : 0;
    $highestPct  = $submitted->count() ? round($submitted->max('percentage'), 1) : 0;
@endphp

{{-- Pending grading alert --}}
@if($pendingTotal > 0)
<div class="alert d-flex align-items-center gap-3 mb-4"
     style="background:#FEF9C3;border:1.5px solid #FDE68A;border-radius:12px;padding:.85rem 1.1rem;">
    <i class="bi bi-hourglass-split flex-shrink-0" style="font-size:1.3rem;color:#D97706;"></i>
    <div class="flex-grow-1">
        <div style="font-weight:700;font-size:.88rem;color:#92400E;">
            {{ $pendingTotal }} respuesta{{ $pendingTotal != 1 ? 's' : '' }} pendiente{{ $pendingTotal != 1 ? 's' : '' }} de calificar
        </div>
        <div style="font-size:.78rem;color:#B45309;margin-top:2px;">
            Los puntajes y porcentajes mostrados son parciales hasta que todas las respuestas cortas sean evaluadas.
        </div>
    </div>
</div>
@endif

{{-- Toolbar: section filter + export --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div style="font-size:.78rem;color:#64748B;font-weight:500;">
            {{ $submitted->count() }} intento{{ $submitted->count() != 1 ? 's' : '' }} completado{{ $submitted->count() != 1 ? 's' : '' }}
            @if(!empty($sectionFilterLabel))
            <span class="badge ms-1" style="background:#CFFAFE;color:#155E75;font-size:.66rem;">
                <i class="bi bi-funnel-fill me-1"></i>Sección: {{ $sectionFilterLabel }}
            </span>
            @endif
        </div>

        @if(($resultSections ?? collect())->count() > 1)
        <form method="GET" action="{{ route('exams.results', $exam) }}" id="sectionFilterForm" class="d-flex align-items-center gap-1">
            <select name="section_id" class="form-select form-select-sm" style="width:auto;font-size:.78rem;" onchange="document.getElementById('sectionFilterForm').submit()">
                <option value="">— Todas las secciones —</option>
                @foreach($resultSections as $sec)
                <option value="{{ $sec->id }}" {{ (string)($sectionId ?? '') === (string)$sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>
                @endforeach
            </select>
        </form>
        @endif
    </div>

    @if($submitted->count() > 0)
    <a href="{{ route('exams.results.export', array_merge([$exam], $sectionId ? ['section_id' => $sectionId] : [])) }}"
       class="btn btn-sm btn-outline-success d-flex align-items-center gap-1"
       style="font-size:.8rem;"
       onclick="AppLoader.show('Generando Excel…', 'El archivo se descargará en unos segundos.'); AppLoader.autoHide(7000);">
        <i class="bi bi-file-earmark-excel"></i> Exportar Excel
    </a>
    @endif
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    @php
        $statCards = [
            ['label'=>'Completados',     'value'=>$submitted->count(),               'icon'=>'check-circle',   'color'=>'#4F46E5','bg'=>'#EEF2FF'],
            ['label'=>'Aprobados',       'value'=>$passedCount,                      'icon'=>'trophy',         'color'=>'#059669','bg'=>'#D1FAE5'],
            ['label'=>'No aprobados',    'value'=>$submitted->count()-$passedCount,  'icon'=>'x-circle',       'color'=>'#DC2626','bg'=>'#FEE2E2'],
            ['label'=>'Promedio general','value'=>$avgPct.'%',                       'icon'=>'bar-chart-line', 'color'=>'#7C3AED','bg'=>'#F5F3FF'],
        ];
    @endphp
    @foreach($statCards as $s)
    <div class="col-6 col-lg-3">
        <div class="card p-3 d-flex flex-row align-items-center gap-3">
            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:44px;height:44px;background:{{ $s['bg'] }};">
                <i class="bi bi-{{ $s['icon'] }}" style="color:{{ $s['color'] }};font-size:1.1rem;"></i>
            </div>
            <div>
                <div class="fw-800" style="font-size:1.4rem;color:{{ $s['color'] }};line-height:1;">{{ $s['value'] }}</div>
                <div style="font-size:.7rem;color:#64748B;margin-top:2px;">{{ $s['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

@if($submitted->count() > 0)
{{-- ── Analytics: distribution + summary ──────────────────────────────────── --}}
<div class="row g-3 mb-4">
    {{-- Grade distribution histogram --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-head"><h6><i class="bi bi-bar-chart-line me-2" style="color:#7C3AED;"></i>Distribución de notas</h6></div>
            <div class="p-3">
                @php
                    $dist = $analytics['distribution'];
                    $dmax = max(1, $analytics['distMax']);
                    $passLabel = rtrim(rtrim(number_format($exam->passing_score, 1), '0'), '.');
                @endphp
                <div class="d-flex align-items-end justify-content-between gap-1" style="height:140px;">
                    @foreach($dist as $i => $count)
                    @php
                        $low = $i * 10; $high = $i == 9 ? 100 : $i * 10 + 9;
                        $h = $count ? max(6, round($count / $dmax * 120)) : 2;
                        $isPass = ($i * 10) >= $exam->passing_score;
                        $barColor = $isPass ? '#10B981' : '#F87171';
                    @endphp
                    <div class="d-flex flex-column align-items-center justify-content-end" style="flex:1;height:100%;">
                        <div style="font-size:.68rem;font-weight:700;color:#475569;margin-bottom:2px;">{{ $count ?: '' }}</div>
                        <div title="{{ $count }} estudiante(s): {{ $low }}–{{ $high }}%"
                             style="width:100%;max-width:34px;height:{{ $h }}px;border-radius:5px 5px 0 0;background:{{ $barColor }};"></div>
                    </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-between gap-1 mt-1">
                    @for($i = 0; $i < 10; $i++)
                    <div style="flex:1;text-align:center;font-size:.6rem;color:#94A3B8;">{{ $i * 10 }}</div>
                    @endfor
                </div>
                <div class="d-flex align-items-center gap-3 mt-2" style="font-size:.68rem;color:#64748B;">
                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#10B981;vertical-align:middle;"></span> Aprobado (&ge;{{ $passLabel }}%)</span>
                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#F87171;vertical-align:middle;"></span> No aprobado</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary metrics --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-head"><h6><i class="bi bi-clipboard-data me-2" style="color:#4F46E5;"></i>Resumen</h6></div>
            <div class="p-3">
                @php
                    $passRate = $submitted->count() ? round($passedCount / $submitted->count() * 100) : 0;
                    $rows = [
                        ['Promedio',       $avgPct . '%',                                                        '#7C3AED'],
                        ['Mediana',        $analytics['median'] !== null ? $analytics['median'] . '%' : '—',     '#4F46E5'],
                        ['Nota más alta',  $highestPct . '%',                                                    '#059669'],
                        ['Nota más baja',  $analytics['lowest'] !== null ? $analytics['lowest'] . '%' : '—',     '#DC2626'],
                        ['% Aprobación',   $passRate . '%',                                                      '#0891B2'],
                    ];
                @endphp
                @foreach($rows as $r)
                <div class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <span style="font-size:.8rem;color:#475569;">{{ $r[0] }}</span>
                    <span class="fw-700" style="font-size:.95rem;color:{{ $r[2] }};">{{ $r[1] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ── Item analysis ──────────────────────────────────────────────────────── --}}
@if(count($analytics['itemStats']) > 0)
@php
    $items = collect($analytics['itemStats'])
        ->sortBy(fn($it) => $it['avg'] === null ? 999 : $it['avg'])
        ->values();
    $itemLimit    = 8;
    $itemsVisible = $items->take($itemLimit);
    $itemsHidden  = $items->slice($itemLimit); // keys preserved → numbering stays continuous
@endphp
<div class="card mb-4">
    <div class="card-head">
        <h6>
            <i class="bi bi-list-stars me-2" style="color:#D97706;"></i>Análisis de ítems
            <span class="text-muted" style="font-size:.72rem;font-weight:400;">(de más difícil a más fácil)</span>
        </h6>
        <span class="badge" style="background:#FEF3C7;color:#92400E;font-size:.7rem;">{{ $items->count() }} preguntas</span>
    </div>
    <div class="p-3 d-flex flex-column gap-2">
        @foreach($itemsVisible as $idx => $it)
            @include('exams.partials.item-stat-row', ['idx' => $idx, 'it' => $it])
        @endforeach

        @if($itemsHidden->count() > 0)
        <div id="itemAnalysisMore" style="display:none;" class="d-flex flex-column gap-2">
            @foreach($itemsHidden as $idx => $it)
                @include('exams.partials.item-stat-row', ['idx' => $idx, 'it' => $it])
            @endforeach
        </div>
        <button type="button" id="itemAnalysisToggle" onclick="toggleItemAnalysis()"
                data-total="{{ $items->count() }}"
                class="btn btn-sm btn-outline-secondary align-self-center mt-1" style="font-size:.76rem;">
            <i class="bi bi-chevron-down me-1"></i>Ver todas las {{ $items->count() }} preguntas
        </button>
        @endif
    </div>
</div>
@endif
@endif

{{-- Table --}}
<div class="card">
    <div class="card-head">
        <h6><i class="bi bi-people me-2" style="color:#4F46E5;"></i>Intentos por Estudiante</h6>
        @if($inProgress->count() > 0)
        <span class="badge" style="background:#FEF9C3;color:#854D0E;font-size:.72rem;">
            <i class="bi bi-hourglass me-1"></i>{{ $inProgress->count() }} en curso
        </span>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.875rem;">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th class="px-3 py-3 fw-600 text-muted" style="font-size:.72rem;">ESTUDIANTE</th>
                    <th class="py-3 fw-600 text-muted" style="font-size:.72rem;">INICIO</th>
                    <th class="py-3 fw-600 text-muted" style="font-size:.72rem;">ENTREGA</th>
                    <th class="py-3 fw-600 text-muted" style="font-size:.72rem;">PUNTOS</th>
                    <th class="py-3 fw-600 text-muted" style="font-size:.72rem;">RESULTADO</th>
                    <th class="py-3 fw-600 text-muted" style="font-size:.72rem;">ESTADO</th>
                    <th class="py-3 text-end pe-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($attempts->sortByDesc('submitted_at') as $attempt)
                @php $student = $students[$attempt->student_id] ?? null; @endphp
                <tr class="border-top">
                    <td class="px-3 py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-2 fw-700 text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:32px;height:32px;font-size:.75rem;background:#4F46E5;">
                                {{ $student ? strtoupper(substr($student->name, 0, 1)) : '?' }}
                            </div>
                            <span class="fw-500">{{ $student?->full_name ?? 'ID: '.$attempt->student_id }}</span>
                        </div>
                    </td>
                    <td class="text-muted" style="font-size:.8rem;">{{ $attempt->started_at?->format('d/m H:i') ?? '—' }}</td>
                    <td class="text-muted" style="font-size:.8rem;">{{ $attempt->submitted_at?->format('d/m H:i') ?? '—' }}</td>
                    <td style="font-size:.85rem;">
                        <span class="fw-600">{{ number_format($attempt->score ?? 0, 1) }}</span>
                        <span class="text-muted">/ {{ number_format($attempt->max_score ?? 0, 1) }}</span>
                    </td>
                    <td>
                        @if($attempt->percentage !== null)
                        <div class="d-flex align-items-center gap-2">
                            <div class="flex-grow-1" style="max-width:80px;">
                                <div class="progress" style="height:5px;border-radius:3px;">
                                    <div class="progress-bar {{ $attempt->percentage >= $exam->passing_score ? 'bg-success' : 'bg-danger' }}"
                                         style="width:{{ min(100,$attempt->percentage) }}%"></div>
                                </div>
                            </div>
                            <span class="fw-700" style="font-size:.85rem;color:{{ $attempt->percentage >= $exam->passing_score ? '#059669' : '#DC2626' }};">
                                {{ number_format($attempt->percentage, 1) }}%
                            </span>
                        </div>
                        @else
                        <span class="text-muted" style="font-size:.8rem;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($attempt->status === 'in_progress')
                        <span class="badge" style="background:#FEF9C3;color:#854D0E;font-size:.68rem;">En curso</span>
                        @elseif($attempt->status === 'timed_out')
                        <span class="badge bg-secondary" style="font-size:.68rem;">Tiempo agotado</span>
                        @elseif($attempt->percentage >= $exam->passing_score)
                        <span class="badge" style="background:#D1FAE5;color:#065F46;font-size:.68rem;">✓ Aprobado</span>
                        @else
                        <span class="badge" style="background:#FEE2E2;color:#991B1B;font-size:.68rem;">✗ No aprobado</span>
                        @endif
                        @if(($attempt->pending_grading ?? 0) > 0)
                        <span class="badge d-block mt-1" style="background:#FEF9C3;color:#92400E;font-size:.62rem;">
                            <i class="bi bi-hourglass me-1"></i>{{ $attempt->pending_grading }} pendiente{{ $attempt->pending_grading != 1 ? 's' : '' }}
                        </span>
                        @endif
                        @if(($attempt->focus_loss_count ?? 0) > 0)
                        <span class="badge d-block mt-1" style="background:#FEE2E2;color:#991B1B;font-size:.62rem;"
                              title="El estudiante salió de la pantalla del examen {{ $attempt->focus_loss_count }} vez(ces)">
                            <i class="bi bi-shield-exclamation me-1"></i>{{ $attempt->focus_loss_count }} salida{{ $attempt->focus_loss_count != 1 ? 's' : '' }}
                        </span>
                        @endif
                    </td>
                    <td class="text-end pe-3">
                        @if(in_array($attempt->status, ['submitted','timed_out']))
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('exams.attempt-detail', [$exam, $attempt]) }}"
                               class="btn btn-sm {{ ($attempt->pending_grading ?? 0) > 0 ? 'btn-warning' : 'btn-outline-primary' }}"
                               style="font-size:.75rem;">
                                @if(($attempt->pending_grading ?? 0) > 0)
                                    <i class="bi bi-pencil-square me-1"></i>Calificar
                                @else
                                    <i class="bi bi-eye me-1"></i>Ver
                                @endif
                            </a>
                            <a href="{{ route('exams.attempt-pdf', [$exam, $attempt]) }}" target="_blank"
                               class="btn btn-sm btn-outline-danger" style="font-size:.75rem;" title="Descargar boletín PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                        </div>
                        @elseif($attempt->status === 'in_progress')
                        <form method="POST" action="{{ route('exams.close-attempt', [$exam, $attempt]) }}" class="d-inline"
                              onsubmit="return confirmDanger(
                                  'Se cerrará este intento como entregado y se calificarán las respuestas que el estudiante alcanzó a guardar. Útil para estudiantes que abandonaron el examen y no volverán.',
                                  this, 'Cerrando intento…', 'Calificando lo guardado.')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-warning" style="font-size:.75rem;" title="Cerrar intento abandonado">
                                <i class="bi bi-x-circle me-1"></i>Cerrar
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p class="small">No hay intentos registrados para este examen.</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleItemAnalysis() {
    const more = document.getElementById('itemAnalysisMore');
    const btn  = document.getElementById('itemAnalysisToggle');
    if (!more || !btn) return;
    const opening = more.style.display === 'none';
    more.style.display = opening ? 'flex' : 'none';
    btn.innerHTML = opening
        ? '<i class="bi bi-chevron-up me-1"></i>Ver menos'
        : '<i class="bi bi-chevron-down me-1"></i>Ver todas las ' + btn.dataset.total + ' preguntas';
    if (!opening) btn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>
@endpush
