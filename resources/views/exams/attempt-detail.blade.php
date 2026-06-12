@extends('layouts.app')
@section('title', 'Detalle — ' . ($attempt->student->full_name ?? 'Estudiante'))
@section('breadcrumb')
    <a href="{{ route('exams.index') }}">Mis Exámenes</a>
    <span class="mx-1 text-muted">/</span>
    <a href="{{ route('exams.show', $exam) }}">{{ Str::limit($exam->title, 30) }}</a>
    <span class="mx-1 text-muted">/</span>
    <a href="{{ route('exams.results', $exam) }}">Resultados</a>
    <span class="mx-1 text-muted">/</span>
    <span class="fw-600 text-dark">{{ $attempt->student->full_name ?? 'Estudiante' }}</span>
@endsection

@section('content')
@php
    $student   = $attempt->student;
    $passed    = $attempt->percentage >= $exam->passing_score;
    $pct       = number_format($attempt->percentage ?? 0, 1);
    $duration  = $attempt->started_at && $attempt->submitted_at
                    ? $attempt->started_at->diffInMinutes($attempt->submitted_at)
                    : null;
@endphp

{{-- Toolbar --}}
<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('exams.attempt-pdf', [$exam, $attempt]) }}" target="_blank"
       class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" style="font-size:.8rem;">
        <i class="bi bi-file-earmark-pdf"></i> Descargar boletín
    </a>
</div>

{{-- Header card --}}
<div class="card mb-4" style="overflow:hidden;">
    <div style="background:linear-gradient(135deg,#312E81,#4F46E5 60%,#7C3AED);padding:1.5rem 1.75rem;display:flex;align-items:center;gap:1rem;">
        <div class="rounded-3 fw-800 text-white d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:52px;height:52px;font-size:1.3rem;background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.35);">
            {{ $student ? strtoupper(substr($student->name, 0, 1)) : '?' }}
        </div>
        <div class="flex-grow-1">
            <div style="font-size:1.1rem;font-weight:800;color:#fff;">{{ $student?->full_name ?? 'ID: '.$attempt->student_id }}</div>
            @if($student)
            <div style="font-size:.8rem;color:rgba(255,255,255,.7);">
                <i class="bi bi-person-badge me-1"></i>CI: {{ $student->cedula }}
            </div>
            @endif
        </div>
        {{-- Score circle --}}
        <div class="text-center flex-shrink-0">
            <div style="width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.15);border:3px solid {{ $passed ? '#10B981' : '#EF4444' }};display:flex;align-items:center;justify-content:center;flex-direction:column;">
                <div style="font-size:1.2rem;font-weight:800;color:#fff;line-height:1;"><span id="hdr-pct">{{ $pct }}</span>%</div>
            </div>
            <div style="font-size:.68rem;color:{{ $passed ? '#6EE7B7' : '#FCA5A5' }};margin-top:.35rem;font-weight:700;">
                {{ $passed ? '✓ Aprobado' : '✗ No aprobado' }}
            </div>
        </div>
    </div>
    {{-- Meta row --}}
    <div class="d-flex flex-wrap" style="border-top:1px solid #F1F5F9;">
        @php
            $metas = [
                ['icon'=>'journal-text','label'=>'Examen','value'=>Str::limit($exam->title,35)],
                ['icon'=>'clock','label'=>'Inicio','value'=>$attempt->started_at?->format('d/m/Y H:i') ?? '—'],
                ['icon'=>'send-check','label'=>'Entrega','value'=>$attempt->submitted_at?->format('d/m/Y H:i') ?? '—'],
                ['icon'=>'stopwatch','label'=>'Duración','value'=>$duration !== null ? $duration.' min' : '—'],
                ['icon'=>'star','label'=>'Puntos','value'=>'<span id="hdr-score">'.number_format($attempt->score??0,1).'</span> / '.number_format($attempt->max_score??0,1)],
            ];
        @endphp
        @foreach($metas as $m)
        <div class="flex-grow-1 px-3 py-2 d-flex align-items-center gap-2" style="min-width:160px;border-right:1px solid #F1F5F9;">
            <i class="bi bi-{{ $m['icon'] }}" style="color:#4F46E5;font-size:.95rem;"></i>
            <div>
                <div style="font-size:.62rem;color:#94A3B8;text-transform:uppercase;font-weight:700;letter-spacing:.06em;">{{ $m['label'] }}</div>
                <div style="font-size:.82rem;font-weight:600;color:#1E293B;">{!! $m['value'] !!}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Anti-cheat / Proctoring report --}}
@php
    $flags = $attempt->cheat_flags ?? [];
    $leaveCount = (int) ($attempt->focus_loss_count ?? 0);
    $typeLabels = [
        'screen_leave'    => ['Salida de pantalla / pestaña', 'box-arrow-up-right', '#DC2626'],
        'fullscreen_exit' => ['Salió de pantalla completa',   'fullscreen-exit',    '#D97706'],
        'copy'            => ['Intento de copiar',            'clipboard',          '#7C3AED'],
        'paste'           => ['Intento de pegar',             'clipboard-plus',     '#7C3AED'],
        'contextmenu'     => ['Clic derecho',                 'mouse',              '#64748B'],
    ];
    $typeCounts = [];
    foreach ($flags as $f) {
        $t = $f['type'] ?? 'desconocido';
        $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1;
    }
@endphp
@if($exam->proctoring)
<div class="card mb-4">
    <div class="card-head">
        <h6>
            <i class="bi bi-shield-lock me-2" style="color:{{ $leaveCount > 0 ? '#DC2626' : '#059669' }};"></i>
            Reporte de monitoreo
        </h6>
    </div>
    <div class="p-3">
        @if(empty($flags) && $leaveCount === 0)
        <div class="d-flex align-items-center gap-2" style="color:#059669;font-size:.85rem;">
            <i class="bi bi-check-circle-fill"></i>
            Sin incidencias detectadas durante el examen.
        </div>
        @else
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.82rem;border-radius:10px;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Se detectaron <strong>{{ $leaveCount }} salida(s) de pantalla</strong> y
            <strong>{{ count($flags) }} evento(s)</strong> registrados en total.
        </div>

        {{-- Summary by type --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach($typeCounts as $type => $count)
            @php $meta = $typeLabels[$type] ?? [$type, 'dot', '#64748B']; @endphp
            <span class="badge" style="background:{{ $meta[2] }}1A;color:{{ $meta[2] }};font-size:.72rem;padding:.4rem .6rem;border-radius:8px;">
                <i class="bi bi-{{ $meta[1] }} me-1"></i>{{ $meta[0] }}: {{ $count }}
            </span>
            @endforeach
        </div>

        {{-- Timeline (most recent first) --}}
        <details>
            <summary style="cursor:pointer;font-size:.78rem;color:#4F46E5;font-weight:600;">
                Ver cronología detallada ({{ count($flags) }})
            </summary>
            <div class="mt-2" style="max-height:260px;overflow-y:auto;">
                @foreach(array_reverse($flags) as $f)
                @php
                    $t = $f['type'] ?? '';
                    $meta = $typeLabels[$t] ?? [$t, 'dot', '#64748B'];
                    $at = isset($f['at']) ? \Carbon\Carbon::parse($f['at'])->format('d/m H:i:s') : '—';
                @endphp
                <div class="d-flex align-items-center gap-2 py-1" style="border-bottom:1px solid #F1F5F9;font-size:.76rem;">
                    <i class="bi bi-{{ $meta[1] }}" style="color:{{ $meta[2] }};"></i>
                    <span style="color:#374151;">{{ $meta[0] }}</span>
                    <span class="ms-auto text-muted" style="font-variant-numeric:tabular-nums;">{{ $at }}</span>
                </div>
                @endforeach
            </div>
        </details>
        @endif
    </div>
</div>
@endif

{{-- Answers --}}
<div class="card">
    <div class="card-head">
        <h6><i class="bi bi-list-check me-2" style="color:#4F46E5;"></i>Respuestas del Estudiante</h6>
        <span style="font-size:.78rem;color:#64748B;">{{ $attempt->answers->count() }} preguntas</span>
    </div>
    <div>
        @foreach($attempt->answers as $i => $answer)
        @php
            $q         = $answer->question;
            $type      = $q->type;
            $isShort   = in_array($type, ['short_answer','restricted_response','exercise','written_production']);
            $isMatch   = $type === 'matching';
            $isOrder   = $type === 'ordering';
            $isIdent   = $type === 'identification';
            $isCompl   = $type === 'completion';
            $isMultiS  = $type === 'multiple_select';
            $isRadio   = in_array($type, ['single_choice','multiple_choice','true_false']);
            $earned    = $answer->points_earned ?? 0;
            $correct   = $answer->is_correct;
            $textDecoded = $answer->text_answer ? json_decode($answer->text_answer, true) : null;

            $typePills = [
                'single_choice'       => ['Selección Única',    '#EEF2FF','#4F46E5','#C7D2FE'],
                'multiple_choice'     => ['Selección Única',    '#EEF2FF','#4F46E5','#C7D2FE'],
                'multiple_select'     => ['Selección Múltiple', '#FDF4FF','#9333EA','#E9D5FF'],
                'true_false'          => ['Verdadero / Falso',  '#F5F3FF','#7C3AED','#DDD6FE'],
                'short_answer'        => ['Respuesta Corta',    '#FEF9C3','#854D0E','#FDE68A'],
                'matching'            => ['Emparejamiento',     '#FFF7ED','#9A3412','#FCA571'],
                'ordering'            => ['Ordenamiento',       '#F0F9FF','#075985','#7DD3FC'],
                'identification'      => ['Identificación',     '#FFF1F2','#9F1239','#FECDD3'],
                'completion'         => ['Completar',           '#F0FDF4','#065F46','#86EFAC'],
                'restricted_response' => ['Resp. Restringida',  '#F0FDF4','#14532D','#BBF7D0'],
                'exercise'            => ['Ejercicio',          '#FFFBEB','#78350F','#FDE68A'],
                'written_production'  => ['Prod. Escrita',      '#F5F3FF','#4C1D95','#DDD6FE'],
            ];
            [$tLabel,$tBg,$tColor,$tBorder] = $typePills[$type] ?? ['?','#F1F5F9','#64748B','#E2E8F0'];

            if ($isShort && !$isIdent) { $dotBg='#FEF9C3'; $dotColor='#854D0E'; $dotIcon='pencil'; }
            elseif ($correct)    { $dotBg='#D1FAE5'; $dotColor='#065F46'; $dotIcon='check-lg'; }
            elseif ($earned>0)   { $dotBg='#FEF9C3'; $dotColor='#854D0E'; $dotIcon='dash-circle'; }
            else                 { $dotBg='#FEE2E2'; $dotColor='#991B1B'; $dotIcon='x-lg'; }
        @endphp
        <div class="d-flex gap-3 align-items-start px-4 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
            {{-- Status icon --}}
            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-2"
                 style="width:32px;height:32px;margin-top:2px;background:{{ $dotBg }};color:{{ $dotColor }};">
                <i class="bi bi-{{ $dotIcon }}" style="font-size:.85rem;"></i>
            </div>

            {{-- Question body --}}
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span style="font-size:.68rem;color:#94A3B8;font-weight:700;">{{ $i+1 }}</span>
                    <span class="type-pill" style="background:{{ $tBg }};color:{{ $tColor }};border-color:{{ $tBorder }};">{{ $tLabel }}</span>
                </div>
                <div class="mb-2 exam-prose" style="font-size:.875rem;font-weight:600;color:#1E293B;">{!! $q->question_text !!}</div>

                @if($isIdent)
                    {{-- Identification: show each label with student answer vs correct --}}
                    @php $identStudentMap = is_array($textDecoded) ? $textDecoded : []; @endphp
                    @if($q->grading_criteria || true)
                    <div class="d-flex flex-column gap-1 mb-2">
                        @foreach($q->options->sortBy('order') as $part)
                        @php
                            $studentIdAns = trim($identStudentMap[$part->option_text] ?? '');
                            $correctIdAns = $part->match_text;
                            $idMatch = mb_strtolower($studentIdAns) === mb_strtolower(trim($correctIdAns));
                        @endphp
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded-2" style="font-size:.82rem;background:{{ $idMatch ? '#F0FDF4' : ($studentIdAns ? '#FEF2F2' : '#F8FAFC') }};border:1px solid {{ $idMatch ? '#BBF7D0' : ($studentIdAns ? '#FECACA' : '#E2E8F0') }};">
                            <span class="ident-label-badge" style="width:24px;height:24px;font-size:.7rem;">{{ $part->option_text }}</span>
                            <span style="min-width:40%;">
                                @if($studentIdAns)
                                    <span style="color:{{ $idMatch ? '#065F46' : '#991B1B' }};">{{ $studentIdAns }}</span>
                                @else
                                    <span class="text-muted fst-italic">(sin respuesta)</span>
                                @endif
                            </span>
                            @if(!$idMatch)
                            <span class="ms-auto" style="color:#059669;font-size:.75rem;"><i class="bi bi-check me-1"></i>{{ $correctIdAns }}</span>
                            @else
                            <i class="bi bi-check-circle-fill ms-auto" style="color:#059669;"></i>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    <div class="d-flex align-items-center gap-2 px-2 py-1 rounded-2 mb-2" style="background:#F8FAFC;border:1px solid #E2E8F0;font-size:.8rem;">
                        <i class="bi bi-bar-chart me-1" style="color:#4F46E5;"></i>
                        <span>Puntaje automático: <strong>{{ $answer->points_earned ?? 0 }}</strong> de {{ $q->points }} pts</span>
                        @if($answer->is_correct)<span class="ms-2 badge" style="background:#D1FAE5;color:#065F46;">Correcta</span>
                        @elseif(($answer->points_earned ?? 0) > 0)<span class="ms-2 badge" style="background:#FEF9C3;color:#854D0E;">Parcial</span>
                        @else<span class="ms-2 badge" style="background:#FEE2E2;color:#991B1B;">Incorrecta</span>@endif
                    </div>
                    @endif

                @elseif($isCompl)
                    {{-- Completion: show each blank with placed word vs correct --}}
                    @php
                        $cpStudentMap  = is_array($textDecoded) ? $textDecoded : [];
                        $cpCorrectOpts = $q->options->where('is_correct', true)->sortBy('order');
                    @endphp
                    <div class="d-flex flex-column gap-1 mb-2">
                        @foreach($cpCorrectOpts as $copt)
                        @php
                            $placed   = trim($cpStudentMap[(string)$copt->order] ?? '');
                            $expected = $copt->option_text;
                            $match    = mb_strtolower($placed) === mb_strtolower($expected);
                        @endphp
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded-2" style="font-size:.82rem;background:{{ $match ? '#F0FDF4' : ($placed ? '#FEF2F2' : '#F8FAFC') }};border:1px solid {{ $match ? '#BBF7D0' : ($placed ? '#FECACA' : '#E2E8F0') }};">
                            <span class="fw-bold" style="width:24px;color:#065F46;">{{ $copt->order }}</span>
                            <span style="min-width:40%;">
                                @if($placed)
                                    <span style="color:{{ $match ? '#065F46' : '#991B1B' }};">{{ $placed }}</span>
                                @else
                                    <span class="text-muted fst-italic">(sin respuesta)</span>
                                @endif
                            </span>
                            @if(!$match)
                            <span class="ms-auto" style="color:#059669;font-size:.75rem;"><i class="bi bi-check me-1"></i>{{ $expected }}</span>
                            @else
                            <i class="bi bi-check-circle-fill ms-auto" style="color:#059669;"></i>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    <div class="d-flex align-items-center gap-2 px-2 py-1 rounded-2 mb-2" style="background:#F8FAFC;border:1px solid #E2E8F0;font-size:.8rem;">
                        <i class="bi bi-bar-chart me-1" style="color:#4F46E5;"></i>
                        <span>Puntaje: <strong>{{ $answer->points_earned ?? 0 }}</strong> de {{ $q->points }} pts</span>
                        @if($answer->is_correct)<span class="ms-2 badge" style="background:#D1FAE5;color:#065F46;">Correcta</span>
                        @elseif(($answer->points_earned ?? 0) > 0)<span class="ms-2 badge" style="background:#FEF9C3;color:#854D0E;">Parcial</span>
                        @else<span class="ms-2 badge" style="background:#FEE2E2;color:#991B1B;">Incorrecta</span>@endif
                    </div>

                @elseif($isShort)
                    @if($q->grading_criteria)
                    <div class="rounded-2 px-3 py-2 mb-2" style="background:#FFFBEB;border:1px solid #FDE68A;font-size:.8rem;color:#78350F;">
                        <i class="bi bi-award me-1"></i><strong>Criterios:</strong> {{ $q->grading_criteria }}
                    </div>
                    @endif
                    <div class="rounded-2 px-3 py-2 mb-2" style="background:#F8FAFC;border:1px solid #E2E8F0;font-size:.82rem;">
                        <span style="color:#94A3B8;font-size:.7rem;font-weight:700;text-transform:uppercase;">Respuesta del estudiante</span><br>
                        @php $isRich = in_array($q->type, ['restricted_response','exercise','written_production']); @endphp
                        @if(empty($answer->text_answer))
                            <span class="text-muted">(sin respuesta)</span>
                        @elseif($isRich)
                            <div class="exam-prose" style="color:#1E293B;">{!! $answer->text_answer !!}</div>
                        @else
                            <span style="color:#1E293B;white-space:pre-wrap;">{{ $answer->text_answer }}</span>
                        @endif
                    </div>
                    {{-- Grading widget --}}
                    <div class="grade-widget mt-2 p-3 rounded-3"
                         style="background:#F8FAFC;border:1px solid #E2E8F0;"
                         data-answer-id="{{ $answer->id }}"
                         data-max="{{ $q->points }}"
                         data-url="{{ route('exams.grade-answer', [$exam, $attempt, $answer]) }}">

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span style="font-size:.72rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;">
                                <i class="bi bi-pencil-square me-1"></i>Calificación manual
                            </span>
                            @if($answer->is_correct === null)
                            <span class="grade-pending-badge" style="font-size:.72rem;color:#854D0E;background:#FEF9C3;padding:2px 8px;border-radius:20px;border:1px solid #FDE68A;">
                                <i class="bi bi-hourglass me-1"></i>Pendiente
                            </span>
                            @else
                            <span class="grade-saved-badge" style="font-size:.72rem;color:#065F46;background:#D1FAE5;padding:2px 8px;border-radius:20px;border:1px solid #A7F3D0;">
                                <i class="bi bi-check-circle me-1"></i>Revisado
                            </span>
                            @endif
                        </div>

                        @php $rubric = $q->rubric; $usingRubric = is_array($rubric) && !empty($rubric['levels']) && !empty($rubric['criteria']); @endphp

                        @if($usingRubric)
                        {{-- Rubric grading: click a cell per criterion --}}
                        <div class="rubric-grader mb-2" data-answer-id="{{ $answer->id }}">
                            <div class="text-muted mb-1" style="font-size:.74rem;">
                                <i class="bi bi-table me-1"></i>Selecciona un nivel por criterio. El puntaje se calcula automáticamente.
                            </div>
                            <table class="table table-sm mb-1" style="font-size:.78rem;">
                                <thead>
                                    <tr style="background:#FAFAFB;">
                                        <th style="width:160px;font-size:.7rem;color:#64748B;">CRITERIO</th>
                                        @foreach($rubric['levels'] as $j => $lvl)
                                        <th style="font-size:.7rem;text-align:center;color:#64748B;">
                                            {{ $lvl['name'] }}<br>
                                            <span style="font-weight:400;color:#94A3B8;">{{ rtrim(rtrim(number_format($lvl['points'] ?? 0,2),'0'),'.') }} pts</span>
                                        </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rubric['criteria'] as $i => $crit)
                                    @php $selectedLvl = ($answer->grading_choices ?? [])[$i] ?? null; @endphp
                                    <tr>
                                        <td class="fw-600 align-middle">{{ $crit['name'] }}</td>
                                        @foreach($crit['descriptors'] ?? [] as $j => $desc)
                                        @php $isSel = (string)$selectedLvl === (string)$j; @endphp
                                        <td class="rubric-cell" data-crit="{{ $i }}" data-lvl="{{ $j }}"
                                            style="cursor:pointer;vertical-align:top;font-size:.74rem;background:{{ $isSel ? '#D1FAE5' : '#fff' }};border:{{ $isSel ? '2px solid #10B981' : '1px solid #E2E8F0' }};color:#475569;transition:.1s;">
                                            <div style="min-height:48px;">{{ $desc }}</div>
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="d-flex align-items-center justify-content-between">
                                <span style="font-size:.78rem;color:#475569;">
                                    Total: <strong class="rubric-total">{{ $answer->points_earned ?? 0 }}</strong> / {{ $q->points }} pts
                                </span>
                                <span style="font-size:.7rem;color:#94A3B8;">Tope: {{ $q->points }} pts (no excede)</span>
                            </div>
                        </div>
                        @else
                        {{-- Score row (simple manual mode) --}}
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <label style="font-size:.8rem;color:#374151;font-weight:600;white-space:nowrap;">Puntaje obtenido:</label>
                            <input type="number" class="grade-input form-control form-control-sm"
                                   min="0" max="{{ $q->points }}" step="0.5"
                                   value="{{ $answer->points_earned ?? 0 }}"
                                   style="width:80px;font-size:.85rem;">
                            <span style="font-size:.8rem;color:#94A3B8;white-space:nowrap;">de {{ $q->points }} pts</span>
                        </div>
                        @endif

                        {{-- Feedback textarea --}}
                        <div class="mb-2">
                            <label style="font-size:.78rem;color:#374151;font-weight:600;">
                                <i class="bi bi-chat-left-text me-1" style="color:#4F46E5;"></i>Retroalimentación para el estudiante
                                <span style="color:#94A3B8;font-weight:400;">(opcional)</span>
                            </label>
                            <textarea class="grade-feedback form-control form-control-sm mt-1"
                                      rows="3" maxlength="2000"
                                      placeholder="Escribe aquí los comentarios sobre la respuesta del estudiante…"
                                      style="font-size:.82rem;resize:vertical;">{{ $answer->feedback ?? '' }}</textarea>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-indigo grade-save-btn" style="font-size:.78rem;">
                                <i class="bi bi-check-lg me-1"></i>Guardar calificación
                            </button>
                            <span class="grade-status" style="font-size:.75rem;display:none;font-weight:600;"></span>
                        </div>
                    </div>

                @elseif($isRadio)
                    <div class="d-flex flex-column gap-1">
                        @foreach($q->options as $opt)
                        @php $sel = $answer->option_id == $opt->id; $cor = $opt->is_correct; @endphp
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded-2"
                             style="font-size:.82rem;
                                    background:{{ $sel && $cor ? '#D1FAE5' : ($sel && !$cor ? '#FEE2E2' : ($cor ? '#F0FDF4' : 'transparent')) }};
                                    border:1px solid {{ $sel && $cor ? '#A7F3D0' : ($sel && !$cor ? '#FECACA' : ($cor ? '#BBF7D0' : 'transparent')) }};">
                            @if($sel)<i class="bi bi-check2-circle flex-shrink-0" style="color:{{ $cor ? '#059669' : '#DC2626' }};"></i>
                            @else<i class="bi bi-circle flex-shrink-0" style="color:#CBD5E1;font-size:.7rem;"></i>@endif
                            <span style="color:{{ $sel && !$cor ? '#991B1B' : ($cor ? '#065F46' : '#374151') }};">{{ $opt->option_text }}</span>
                            @if($cor && !$sel)<span class="ms-auto" style="font-size:.65rem;color:#059669;font-weight:700;">✓ Correcta</span>@endif
                        </div>
                        @endforeach
                    </div>

                @elseif($isMultiS)
                    @php $selectedIds = is_array($textDecoded) ? $textDecoded : []; @endphp
                    <div class="d-flex flex-column gap-1">
                        @foreach($q->options as $opt)
                        @php $sel = in_array($opt->id, $selectedIds); $cor = $opt->is_correct; @endphp
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded-2"
                             style="font-size:.82rem;
                                    background:{{ $sel && $cor ? '#D1FAE5' : ($sel && !$cor ? '#FEE2E2' : ($cor ? '#F0FDF4' : 'transparent')) }};
                                    border:1px solid {{ $sel && $cor ? '#A7F3D0' : ($sel && !$cor ? '#FECACA' : ($cor ? '#BBF7D0' : 'transparent')) }};">
                            @if($sel)<i class="bi bi-{{ $cor ? 'check-square-fill' : 'x-square-fill' }} flex-shrink-0" style="color:{{ $cor ? '#059669' : '#DC2626' }};"></i>
                            @else<i class="bi bi-square flex-shrink-0" style="color:#CBD5E1;font-size:.8rem;"></i>@endif
                            <span style="color:{{ $sel && !$cor ? '#991B1B' : ($cor ? '#065F46' : '#374151') }};">{{ $opt->option_text }}</span>
                            @if($cor && !$sel)<span class="ms-auto" style="font-size:.65rem;color:#059669;font-weight:700;">✓ Correcta</span>@endif
                        </div>
                        @endforeach
                    </div>

                @elseif($isMatch)
                    @php $studentMap = is_array($textDecoded) ? $textDecoded : []; @endphp
                    <div class="d-flex flex-column gap-1">
                        @foreach($q->options as $pair)
                        @php
                            $submitted = trim($studentMap[(string)$pair->id] ?? '');
                            $pairOk    = mb_strtolower($submitted) === mb_strtolower(trim($pair->match_text ?? ''));
                        @endphp
                        <div class="px-2 py-1 rounded-2" style="font-size:.82rem;background:{{ $submitted ? ($pairOk ? '#D1FAE5' : '#FEE2E2') : 'transparent' }};border:1px solid {{ $submitted ? ($pairOk ? '#A7F3D0' : '#FECACA') : 'transparent' }};">
                            <span class="fw-semibold">{{ $pair->option_text }}</span>
                            <span style="color:#94A3B8;margin:0 .3rem;">→</span>
                            <span style="color:{{ $pairOk ? '#065F46' : '#991B1B' }};">{{ $submitted ?: '(sin respuesta)' }}</span>
                            @if(!$pairOk)
                            <span style="display:block;font-size:.7rem;color:#059669;margin-top:.15rem;">✓ {{ $pair->match_text }}</span>
                            @endif
                        </div>
                        @endforeach
                    </div>

                @elseif($isOrder)
                    @php
                        $orderedIds = is_array($textDecoded) ? $textDecoded : [];
                        $optById    = $q->options->keyBy('id');
                    @endphp
                    <div class="d-flex flex-column gap-1">
                        @if(empty($orderedIds))
                        <div style="font-size:.82rem;color:#94A3B8;">(sin respuesta)</div>
                        @else
                        @foreach($orderedIds as $pos => $optId)
                        @php $item = $optById->get($optId); $posOk = $item && $item->order === ($pos+1); @endphp
                        <div class="d-flex align-items-center gap-2 px-2 py-1 rounded-2"
                             style="font-size:.82rem;background:{{ $posOk ? '#D1FAE5' : '#FEE2E2' }};border:1px solid {{ $posOk ? '#A7F3D0' : '#FECACA' }};">
                            <span style="width:22px;height:22px;background:{{ $posOk ? '#059669' : '#DC2626' }};color:#fff;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0;">{{ $pos+1 }}</span>
                            <span>{{ $item?->option_text ?? '?' }}</span>
                            @if(!$posOk && $item)<span class="ms-auto" style="font-size:.65rem;color:#64748B;white-space:nowrap;">correcta: pos. {{ $item->order }}</span>@endif
                        </div>
                        @endforeach
                        @endif
                    </div>
                @endif
            </div>

            {{-- Points --}}
            <div class="flex-shrink-0 text-end" style="min-width:52px;" id="pts-{{ $answer->id }}">
                <div class="pts-val" style="font-size:1rem;font-weight:800;color:{{ $earned > 0 ? '#059669' : '#DC2626' }};">
                    {{ number_format($earned, 1) }}
                </div>
                <div style="font-size:.68rem;color:#94A3B8;">/ {{ $q->points }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/exams/attempt-detail.css') }}?v={{ filemtime(public_path('css/exams/attempt-detail.css')) }}">

@push('scripts')
<script src="{{ asset('js/exams/attempt-detail.js') }}?v={{ filemtime(public_path('js/exams/attempt-detail.js')) }}"></script>
@endpush
@endsection
