@php
    use Illuminate\Support\Str;
    $components    = $components ?? collect();
    $linked        = $linkedComponentIds ?? [];
    $missingLinked = $missingLinked ?? collect();
    $sicoreType    = $sicoreType ?? null;

    $checked = old('evaluation_component_ids', array_values(array_unique(array_merge(
        $linked,
        array_filter((array) request('evaluation_component_id')),
        array_filter((array) request('evaluation_component_ids'))
    ))));
    $checked = array_map('intval', (array) $checked);

    // Group by section/group (more useful than by subject for picking)
    $byGroup = $components->groupBy(function ($c) {
        return ($c->section_name ?? '—') . ($c->group_type ? ' ' . $c->group_type : '');
    });

    $typeLabel = match ($sicoreType) {
        'TESTS'      => 'Pruebas',
        'PROJECT'    => 'Proyectos',
        'DAILY_WORK' => 'Trabajo Cotidiano',
        'HOMEWORK'   => 'Tareas',
        default      => 'componentes',
    };
@endphp

@if(($noSubject ?? false) && $missingLinked->isEmpty())
<div class="alert py-2 px-3 mb-0" style="font-size:.82rem;background:#F0F9FF;border:1px solid #BAE6FD;color:#075985;border-radius:10px;">
    <i class="bi bi-flask me-1"></i>
    <strong>Sin materia seleccionada</strong> — este examen será solo <strong>formativo</strong> (no envía nota a SICORE).
    Elige una materia arriba si deseas vincularlo a un componente de calificación.
</div>
@elseif($components->isEmpty() && $missingLinked->isEmpty())
<div class="alert alert-light border py-2 px-3 mb-0" style="font-size:.8rem;">
    <i class="bi bi-inbox me-1 text-muted"></i>No tienes componentes de tipo <strong>{{ $typeLabel }}</strong> en SICORE para esta materia este año.
</div>
@else
{{-- Search filter --}}
<div class="mb-2">
    <input type="text" id="compFilterInput" class="form-control form-control-sm"
           placeholder="Buscar por sección, materia o nombre…"
           oninput="filterComponentList(this.value)">
</div>

<div id="compList" style="max-height:280px;overflow-y:auto;border:1px solid #E2E8F0;border-radius:10px;padding:.5rem .75rem;">
    @foreach($byGroup as $groupLabel => $group)
    <div class="comp-group mb-2" data-search="{{ strtolower($groupLabel) }}">
        <div style="font-size:.7rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em;margin:.25rem 0;">
            <i class="bi bi-people me-1"></i>{{ $groupLabel }}
        </div>
        @foreach($group as $c)
        @php
            $taken     = $c->taken_by_title ?? null;
            $takenExam = $c->taken_by_exam_id ?? null;
            $compName  = trim((string) ($c->name ?? ''));
            $searchKey = strtolower(($c->subject_name ?? '') . ' ' . $groupLabel . ' ' . ($c->evaluation_name ?? '') . ' ' . $compName . ' ' . ($c->period_name ?? ''));
        @endphp
        <div class="form-check d-flex align-items-center gap-2 comp-row" data-search="{{ $searchKey }}"
             style="{{ $taken ? 'opacity:.6;' : '' }}">
            <input class="form-check-input mt-0 comp-checkbox" type="checkbox" name="evaluation_component_ids[]"
                   value="{{ $c->id }}" id="comp{{ $c->id }}"
                   data-section-group="{{ $groupLabel }}"
                   {{ in_array((int) $c->id, $checked) ? 'checked' : '' }}
                   {{ $taken ? 'disabled' : '' }}>
            <label class="form-check-label flex-grow-1" for="comp{{ $c->id }}" style="font-size:.8rem;">
                <strong>{{ $c->subject_name }}</strong>
                · {{ $c->evaluation_name }}{{ $compName !== '' ? ': ' . $compName : '' }}
                · {{ $c->period_name }}
                <span class="text-muted">({{ rtrim(rtrim(number_format($c->value ?? 0, 2), '0'), '.') }}% · máx {{ rtrim(rtrim(number_format($c->max_points ?? 0, 2), '0'), '.') }} pts)</span>
                <span class="text-muted" style="font-size:.66rem;">· #{{ $c->id }}</span>
            </label>
            @if($taken)
            <span class="badge" style="background:#FEF3C7;color:#92400E;font-size:.62rem;font-weight:600;">
                <i class="bi bi-lock me-1"></i>{{ Str::limit($taken, 22) }}
            </span>
            @if($takenExam)
            <a href="{{ route('exams.show', $takenExam) }}" target="_blank"
               class="btn btn-sm btn-link p-0" style="font-size:.7rem;text-decoration:none;color:#0891B2;"
               title="Ir al examen que tiene vinculado este componente">
                <i class="bi bi-box-arrow-up-right"></i>
            </a>
            @endif
            @endif
        </div>
        @endforeach
    </div>
    @endforeach

    @if($missingLinked->isNotEmpty())
    <div class="mb-1">
        <div style="font-size:.7rem;font-weight:700;color:#B45309;text-transform:uppercase;letter-spacing:.04em;margin:.25rem 0;">
            Vinculados (fuera del filtro actual)
        </div>
        @foreach($missingLinked as $c)
        <div class="form-check comp-row">
            <input class="form-check-input" type="checkbox" name="evaluation_component_ids[]"
                   value="{{ $c->id }}" id="comp{{ $c->id }}" checked>
            <label class="form-check-label" for="comp{{ $c->id }}" style="font-size:.8rem;">
                {{ $c->subject_name }} · {{ $c->section_name }}{{ $c->group_type ? ' '.$c->group_type : '' }} · {{ $c->period_name }} <span class="text-muted">(vinculado)</span>
            </label>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endif
