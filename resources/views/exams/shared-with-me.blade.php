@extends('layouts.app')
@section('title', 'Compartidos')
@section('breadcrumb')
    <span class="fw-600 text-dark">Compartidos</span>
@endsection

@section('content')

@php
    $receivedPending = $received->where('status', 'pending')->count();
    $sentPending     = $sent->where('status', 'pending')->count();
@endphp

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-share me-2" style="color:#7C3AED;"></i>Compartidos</h5>
        <div class="text-muted" style="font-size:.8rem;">Actividades que has recibido o compartido con otros docentes.</div>
    </div>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-received" data-bs-toggle="tab" data-bs-target="#pane-received" type="button" role="tab">
            <i class="bi bi-inbox me-1"></i>Recibidos
            <span class="badge ms-1" style="background:#EEF2FF;color:#4F46E5;font-size:.66rem;">{{ $received->count() }}</span>
            @if($receivedPending > 0)
            <span class="badge ms-1" style="background:#DC2626;color:#fff;font-size:.6rem;">{{ $receivedPending }} pend.</span>
            @endif
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-sent" data-bs-toggle="tab" data-bs-target="#pane-sent" type="button" role="tab">
            <i class="bi bi-send me-1"></i>Enviados
            <span class="badge ms-1" style="background:#EEF2FF;color:#4F46E5;font-size:.66rem;">{{ $sent->count() }}</span>
            @if($sentPending > 0)
            <span class="badge ms-1" style="background:#FEF9C3;color:#854D0E;font-size:.6rem;">{{ $sentPending }} esperando</span>
            @endif
        </button>
    </li>
</ul>

<div class="tab-content">
    {{-- ── Recibidos ─────────────────────────────────────────────────────── --}}
    <div class="tab-pane fade show active" id="pane-received" role="tabpanel">
        @if($received->isEmpty())
        <div class="card p-5 text-center text-muted">
            <i class="bi bi-inbox" style="font-size:2.4rem;color:#CBD5E1;"></i>
            <p class="mt-2 mb-0" style="font-size:.9rem;">Nadie te ha compartido actividades todavía.</p>
        </div>
        @else
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.85rem;">
                    <thead style="background:#F8FAFC;">
                        <tr>
                            <th class="px-3 py-2 fw-600 text-muted" style="font-size:.7rem;">ACTIVIDAD</th>
                            <th class="py-2 fw-600 text-muted" style="font-size:.7rem;">COMPARTIDO POR</th>
                            <th class="py-2 fw-600 text-muted" style="font-size:.7rem;">MENSAJE</th>
                            <th class="py-2 fw-600 text-muted" style="font-size:.7rem;">FECHA</th>
                            <th class="py-2 fw-600 text-muted text-center" style="font-size:.7rem;">ESTADO</th>
                            <th class="py-2 pe-3 text-end fw-600 text-muted" style="font-size:.7rem;">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($received as $share)
                        @php $sender = $share->from_user; @endphp
                        <tr class="border-top">
                            <td class="px-3 py-2">
                                <div class="fw-600">{{ $share->exam?->title ?? '(eliminado)' }}</div>
                                @if($share->exam)
                                <div class="text-muted" style="font-size:.72rem;">
                                    {{ \App\Models\Exam::ACTIVITY_TYPES[$share->exam->activity_type]['label'] ?? $share->exam->activity_type }}
                                    · {{ $share->exam->duration_minutes }} min
                                    · {{ \App\Models\ExamQuestion::where('exam_id', $share->exam->id)->count() }} preguntas
                                </div>
                                @endif
                            </td>
                            <td class="py-2"><span class="fw-500">{{ $sender?->full_name ?? 'Docente desconocido' }}</span></td>
                            <td class="py-2 text-muted" style="font-size:.78rem;max-width:280px;">{{ $share->message ?: '—' }}</td>
                            <td class="py-2 text-muted" style="font-size:.78rem;">{{ $share->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="py-2 text-center">
                                @if($share->status === 'pending')
                                    <span class="badge" style="background:#FEF9C3;color:#854D0E;font-size:.66rem;">Pendiente</span>
                                @elseif($share->status === 'accepted')
                                    <span class="badge" style="background:#D1FAE5;color:#065F46;font-size:.66rem;">✓ Aceptado</span>
                                @else
                                    <span class="badge" style="background:#FEE2E2;color:#991B1B;font-size:.66rem;">✗ Rechazado</span>
                                @endif
                            </td>
                            <td class="py-2 pe-3 text-end">
                                @if($share->status === 'pending' && $share->exam)
                                <div class="d-inline-flex gap-1">
                                    <form method="POST" action="{{ route('shares.accept', $share) }}" class="d-inline"
                                          onsubmit="return confirmAndLoad('Se creará una copia de esta actividad en tu lista como borrador. ¿Continuar?', this, 'Aceptando…', 'Clonando preguntas y opciones.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" style="font-size:.75rem;">
                                            <i class="bi bi-check-lg me-1"></i>Aceptar
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('shares.reject', $share) }}" class="d-inline"
                                          onsubmit="return confirmAndLoad('¿Rechazar esta invitación? La copia NO se creará.', this, 'Rechazando…', 'Marcando como rechazada.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;">
                                            <i class="bi bi-x-lg me-1"></i>Rechazar
                                        </button>
                                    </form>
                                </div>
                                @elseif($share->status === 'accepted' && $share->accepted_exam_id)
                                    <a href="{{ route('exams.show', $share->accepted_exam_id) }}" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">
                                        <i class="bi bi-eye me-1"></i>Ver mi copia
                                    </a>
                                @else
                                    <span class="text-muted" style="font-size:.72rem;">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Enviados ──────────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="pane-sent" role="tabpanel">
        @if($sent->isEmpty())
        <div class="card p-5 text-center text-muted">
            <i class="bi bi-send" style="font-size:2.4rem;color:#CBD5E1;"></i>
            <p class="mt-2 mb-0" style="font-size:.9rem;">Aún no has compartido actividades con otros docentes.</p>
            <div class="text-muted mt-1" style="font-size:.78rem;">Desde la página de un examen tuyo, botón <strong>"Compartir"</strong>.</div>
        </div>
        @else
        @php
            // Group by exam to summarize: same exam may be shared to many teachers
            $sentByExam = $sent->groupBy('exam_id');
        @endphp
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:.85rem;">
                    <thead style="background:#F8FAFC;">
                        <tr>
                            <th class="px-3 py-2 fw-600 text-muted" style="font-size:.7rem;">ACTIVIDAD COMPARTIDA</th>
                            <th class="py-2 fw-600 text-muted" style="font-size:.7rem;">DOCENTE</th>
                            <th class="py-2 fw-600 text-muted" style="font-size:.7rem;">MENSAJE</th>
                            <th class="py-2 fw-600 text-muted" style="font-size:.7rem;">ENVIADO</th>
                            <th class="py-2 fw-600 text-muted" style="font-size:.7rem;">RESPONDIÓ</th>
                            <th class="py-2 fw-600 text-muted text-center" style="font-size:.7rem;">ESTADO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sentByExam as $examId => $group)
                        @php
                            $exam = $group->first()->exam;
                            $accepted = $group->where('status','accepted')->count();
                            $rejected = $group->where('status','rejected')->count();
                            $pending  = $group->where('status','pending')->count();
                        @endphp
                        <tr style="background:#FAFAFB;border-top:2px solid #E2E8F0;">
                            <td colspan="6" class="px-3 py-2">
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <div>
                                        <a href="{{ $exam ? route('exams.show', $exam) : '#' }}" class="fw-600 text-decoration-none">
                                            <i class="bi bi-journal-text me-1" style="color:#4F46E5;"></i>{{ $exam?->title ?? '(eliminado)' }}
                                        </a>
                                        @if($exam)
                                        <span class="text-muted ms-2" style="font-size:.72rem;">
                                            {{ \App\Models\Exam::ACTIVITY_TYPES[$exam->activity_type]['label'] ?? $exam->activity_type }}
                                        </span>
                                        @endif
                                    </div>
                                    <div class="d-flex gap-1" style="font-size:.65rem;">
                                        @if($accepted > 0)<span class="badge" style="background:#D1FAE5;color:#065F46;">{{ $accepted }} aceptado(s)</span>@endif
                                        @if($pending > 0)<span class="badge" style="background:#FEF9C3;color:#854D0E;">{{ $pending }} pendiente(s)</span>@endif
                                        @if($rejected > 0)<span class="badge" style="background:#FEE2E2;color:#991B1B;">{{ $rejected }} rechazado(s)</span>@endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @foreach($group as $share)
                        @php $receiver = $share->to_user; @endphp
                        <tr>
                            <td class="px-3 py-2"></td>
                            <td class="py-2"><span class="fw-500">{{ $receiver?->full_name ?? 'Docente desconocido' }}</span></td>
                            <td class="py-2 text-muted" style="font-size:.78rem;max-width:280px;">{{ $share->message ?: '—' }}</td>
                            <td class="py-2 text-muted" style="font-size:.78rem;">{{ $share->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="py-2 text-muted" style="font-size:.78rem;">{{ $share->responded_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="py-2 text-center">
                                @if($share->status === 'pending')
                                    <span class="badge" style="background:#FEF9C3;color:#854D0E;font-size:.66rem;"><i class="bi bi-hourglass me-1"></i>Pendiente</span>
                                @elseif($share->status === 'accepted')
                                    <span class="badge" style="background:#D1FAE5;color:#065F46;font-size:.66rem;"><i class="bi bi-check-circle-fill me-1"></i>Aceptado</span>
                                @else
                                    <span class="badge" style="background:#FEE2E2;color:#991B1B;font-size:.66rem;"><i class="bi bi-x-circle-fill me-1"></i>Rechazado</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection
