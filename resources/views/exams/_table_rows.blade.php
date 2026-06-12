@forelse($exams as $exam)
@php
    $qCount = $questionCounts[$exam->id] ?? 0;
    $aCount = $attemptCounts[$exam->id]  ?? 0;
    $actMeta = $exam->activityMeta();
@endphp
<tr class="border-top exam-row" style="transition:.15s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background=''">

    {{-- Título + tipo + stats rápidas --}}
    <td class="px-3 py-3">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span style="font-size:.67rem;font-weight:700;border-radius:20px;padding:1px 8px;
                         background:{{ $actMeta['bg'] }};color:{{ $actMeta['color'] }};white-space:nowrap;">
                <i class="bi {{ $actMeta['icon'] }} me-1"></i>{{ $actMeta['label'] }}
            </span>
            <a href="{{ route('exams.show', $exam) }}" class="fw-600 text-dark text-truncate" style="max-width:280px;">
                {{ $exam->title }}
            </a>
        </div>
        <div class="d-flex gap-3">
            @if($exam->isQuestionBased())
            <span style="font-size:.7rem;color:#94A3B8;">
                <i class="bi bi-list-ol me-1"></i>{{ $qCount }} pregunta{{ $qCount != 1 ? 's' : '' }}
            </span>
            @endif
            @if($aCount > 0)
            <span style="font-size:.7rem;color:#7C3AED;">
                <i class="bi bi-people me-1"></i>{{ $aCount }} entrega{{ $aCount != 1 ? 's' : '' }}
            </span>
            @endif
        </div>
    </td>

    {{-- Materia --}}
    <td>
        @if($exam->subject_id && isset($subjects[$exam->subject_id]))
        <span class="badge" style="background:#EEF2FF;color:#4F46E5;font-size:.7rem;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;">
            {{ $subjects[$exam->subject_id] }}
        </span>
        @else
        <span class="text-muted" style="font-size:.8rem;">—</span>
        @endif
    </td>

    {{-- Configuración --}}
    <td style="font-size:.78rem;color:#64748B;">
        <div><i class="bi bi-clock me-1"></i>{{ $exam->duration_minutes }} min</div>
        @if($exam->shuffle_questions)<div><i class="bi bi-shuffle me-1" style="color:#A78BFA;"></i>Aleatorio</div>@endif
        @if($exam->questions_per_exam)<div><i class="bi bi-collection me-1"></i>Muestra {{ $exam->questions_per_exam }}</div>@endif
    </td>

    {{-- Disponibilidad --}}
    <td style="font-size:.75rem;color:#64748B;">
        @if($exam->available_from)
            @php $now = now(); $from = $exam->available_from; $until = $exam->available_until; @endphp
            @if($until && $now->gt($until))
                <span style="color:#DC2626;font-size:.72rem;font-weight:600;"><i class="bi bi-lock me-1"></i>Vencido</span><br>
                <span style="font-size:.7rem;">{{ $until->format('d/m H:i') }}</span>
            @elseif($now->lt($from))
                <span style="color:#D97706;font-size:.72rem;font-weight:600;"><i class="bi bi-clock me-1"></i>Próximo</span><br>
                <span>{{ $from->format('d/m H:i') }}</span>
            @else
                <span style="color:#059669;font-size:.72rem;font-weight:600;"><i class="bi bi-broadcast me-1"></i>En curso</span><br>
                <span>→ {{ $until?->format('d/m H:i') ?? '∞' }}</span>
            @endif
        @else
            <span class="text-muted" style="font-size:.73rem;"><i class="bi bi-infinity me-1"></i>Sin límite</span>
        @endif
    </td>

    {{-- Estado --}}
    <td>
        <span class="status-badge badge-{{ $exam->status }}">
            {{ ['draft'=>'Borrador','active'=>'Activo','closed'=>'Cerrado'][$exam->status] }}
        </span>
    </td>

    {{-- Acciones --}}
    <td class="text-end pe-3">
        <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
            <a href="{{ route('exams.show', $exam) }}" class="btn btn-sm btn-outline-secondary" title="Ver"><i class="bi bi-eye"></i></a>
            @if($exam->canBeEdited())
            <a href="{{ route('exams.edit', $exam) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
            @endif
            <a href="{{ route('exams.results', $exam) }}" class="btn btn-sm btn-outline-success" title="Resultados"><i class="bi bi-bar-chart-line"></i></a>
            <form method="POST" action="{{ route('exams.clone', $exam) }}"
                  onsubmit="return confirmAndLoad('¿Duplicar «{{ addslashes($exam->title) }}»? Se creará una copia editable en borrador.', this, 'Duplicando examen…', 'Copiando preguntas y opciones...')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-warning" title="Duplicar"><i class="bi bi-copy"></i></button>
            </form>
            @if($exam->canBeDeleted())
            <form method="POST" action="{{ route('exams.destroy', $exam) }}"
                  onsubmit="return confirmAndLoad('¿Eliminar «{{ addslashes($exam->title) }}»? Esta acción no se puede deshacer.', this, 'Eliminando examen…', 'Por favor espera.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
            </form>
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6">
        <div class="empty-state">
            <i class="bi bi-journal-x"></i>
            <p class="small mb-2">
                @if(request('q') || request('status') || request('subject_id') || request('avail'))
                    No se encontraron exámenes con ese criterio.
                @else
                    No has creado exámenes aún.
                @endif
            </p>
            @if(!request('q') && !request('status') && !request('subject_id') && !request('avail'))
            <a href="{{ route('exams.create') }}" class="btn btn-indigo btn-sm">Crear primer examen</a>
            @endif
        </div>
    </td>
</tr>
@endforelse
