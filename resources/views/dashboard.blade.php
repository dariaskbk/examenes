@extends('layouts.app')
@section('title', 'Dashboard — ExamCore')
@section('page-title', 'Dashboard')

@section('content')
{{-- Pending grading alert --}}
@if($pendingGrading > 0)
<div class="alert d-flex align-items-center gap-3 mb-4"
     style="background:#FEF9C3;border:1.5px solid #FDE68A;border-radius:12px;padding:.85rem 1.1rem;">
    <i class="bi bi-hourglass-split flex-shrink-0" style="font-size:1.3rem;color:#D97706;"></i>
    <div class="flex-grow-1">
        <div style="font-weight:700;font-size:.9rem;color:#92400E;">
            {{ $pendingGrading }} respuesta{{ $pendingGrading != 1 ? 's' : '' }} corta{{ $pendingGrading != 1 ? 's' : '' }} pendiente{{ $pendingGrading != 1 ? 's' : '' }} de calificar
        </div>
        <div style="font-size:.78rem;color:#B45309;margin-top:2px;">
            Revisa los resultados de tus exámenes para completar la calificación manual.
        </div>
    </div>
    <a href="{{ route('exams.index') }}?status=active" class="btn btn-sm btn-warning flex-shrink-0" style="font-size:.78rem;">
        <i class="bi bi-pencil-square me-1"></i>Ver exámenes
    </a>
</div>
@endif

{{-- Stats row --}}
<div class="row g-3 mb-4">
    @php
        $stats = [
            ['icon' => 'journal-text', 'label' => 'Total Exámenes',   'value' => $totalExams,    'color' => '#4F46E5', 'bg' => '#EEF2FF'],
            ['icon' => 'play-circle',  'label' => 'Activos',           'value' => $activeExams,   'color' => '#059669', 'bg' => '#D1FAE5'],
            ['icon' => 'people',       'label' => 'Intentos totales',  'value' => $totalAttempts, 'color' => '#7C3AED', 'bg' => '#F5F3FF'],
            ['icon' => 'hourglass-split','label'=> 'En curso ahora',   'value' => $inProgressCount,'color'=> '#D97706', 'bg' => '#FEF9C3'],
        ];
    @endphp
    @foreach($stats as $stat)
    <div class="col-6 col-lg-3">
        <div class="card p-3 d-flex flex-row align-items-center gap-3">
            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:46px;height:46px;background:{{ $stat['bg'] }};">
                <i class="bi bi-{{ $stat['icon'] }}" style="font-size:1.2rem;color:{{ $stat['color'] }};"></i>
            </div>
            <div>
                <div class="fw-800" style="font-size:1.5rem;color:{{ $stat['color'] }};line-height:1;">{{ $stat['value'] }}</div>
                <div style="font-size:.72rem;color:#64748B;margin-top:2px;">{{ $stat['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3">
    {{-- Exams list --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-head">
                <h6><i class="bi bi-journal-text me-2" style="color:#4F46E5;"></i>Mis Exámenes</h6>
                <a href="{{ route('exams.create') }}" class="btn btn-indigo btn-sm">
                    <i class="bi bi-plus me-1"></i>Nuevo
                </a>
            </div>
            <div>
                @forelse($exams->take(8) as $exam)
                @php $subjectName = $subjectNames[$exam->subject_id] ?? null; @endphp
                <a href="{{ route('exams.show', $exam) }}" class="d-flex align-items-center gap-3 px-3 py-3 border-bottom text-decoration-none"
                   style="transition:.15s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background=''">
                    <div class="flex-shrink-0 rounded-2 d-flex align-items-center justify-content-center"
                         style="width:38px;height:38px;background:{{ ['draft'=>'#F1F5F9','active'=>'#D1FAE5','closed'=>'#FEE2E2'][$exam->status] }};">
                        <i class="bi bi-{{ ['draft'=>'file-text','active'=>'play-circle','closed'=>'lock'][$exam->status] }}"
                           style="color:{{ ['draft'=>'#64748B','active'=>'#059669','closed'=>'#DC2626'][$exam->status] }};"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-600 small text-dark text-truncate">{{ $exam->title }}</div>
                        <div style="font-size:.72rem;color:#94A3B8;">
                            {{ $subjectName ?? 'Sin materia' }} · {{ $exam->duration_minutes }} min
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <span class="status-badge badge-{{ $exam->status }}" style="font-size:.65rem;">
                            {{ ['draft'=>'Borrador','active'=>'Activo','closed'=>'Cerrado'][$exam->status] }}
                        </span>
                    </div>
                </a>
                @empty
                <div class="empty-state">
                    <i class="bi bi-journal-x"></i>
                    <p class="small mb-2">Aún no has creado exámenes.</p>
                    <a href="{{ route('exams.create') }}" class="btn btn-indigo btn-sm">Crear primer examen</a>
                </div>
                @endforelse
            </div>
            @if($exams->count() > 8)
            <div class="p-3 text-center border-top">
                <a href="{{ route('exams.index') }}" class="small text-primary fw-500">Ver todos ({{ $exams->count() }})</a>
            </div>
            @endif
        </div>
    </div>

    {{-- Recent attempts --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-head">
                <h6><i class="bi bi-clock-history me-2" style="color:#7C3AED;"></i>Últimos Resultados</h6>
            </div>
            <div>
                @forelse($recentAttempts as $attempt)
                @php
                    $student = $students[$attempt->student_id] ?? null;
                    $exam    = $exams->find($attempt->exam_id);
                    $passed  = $exam && $attempt->percentage >= $exam->passing_score;
                @endphp
                <div class="d-flex align-items-center gap-3 px-3 py-3 border-bottom">
                    <div class="flex-shrink-0 rounded-2 d-flex align-items-center justify-content-center fw-700 text-white"
                         style="width:36px;height:36px;font-size:.75rem;background:{{ $passed ? '#059669' : '#DC2626' }};">
                        {{ $student ? strtoupper(substr($student->name, 0, 1)) : '?' }}
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="small fw-500 text-truncate">{{ $student?->full_name ?? 'Estudiante' }}</div>
                        <div style="font-size:.7rem;color:#94A3B8;" class="text-truncate">{{ $exam?->title ?? '' }}</div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <div class="fw-700 small {{ $passed ? 'text-success' : 'text-danger' }}">
                            {{ number_format($attempt->percentage, 0) }}%
                        </div>
                        <div style="font-size:.65rem;color:#94A3B8;">{{ $attempt->submitted_at?->diffForHumans() }}</div>
                    </div>
                </div>
                @empty
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p class="small">Sin intentos recientes.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
