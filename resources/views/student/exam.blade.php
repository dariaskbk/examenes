<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $exam->title }} — ExamCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/exams/student-exam.css') }}?v={{ filemtime(public_path('css/exams/student-exam.css')) }}">
</head>
<body>

@php
    $previewMode = $previewMode ?? false;
    $typeInfo = [
        'single_choice'       => ['Selección única',    'type-single'],
        'multiple_select'     => ['Selección múltiple', 'type-multiple'],
        'true_false'          => ['Verdadero / Falso',  'type-tf'],
        'short_answer'        => ['Respuesta corta',    'type-short'],
        'matching'            => ['Emparejamiento',      'type-match'],
        'ordering'            => ['Ordenamiento',        'type-order'],
        'identification'      => ['Identificación',      'type-match'],
        'completion'          => ['Completar',           'type-short'],
        'restricted_response' => ['Resp. Restringida',  'type-short'],
        'exercise'            => ['Ejercicio',           'type-short'],
        'written_production'  => ['Prod. Escrita',       'type-short'],
    ];
@endphp

@if($previewMode)
<div style="position:fixed;top:0;left:0;right:0;z-index:9999;background:linear-gradient(90deg,#F59E0B,#D97706);color:#fff;font-size:12px;font-weight:700;text-align:center;padding:4px 0;letter-spacing:.08em;text-transform:uppercase;box-shadow:0 2px 8px rgba(0,0,0,.25);">
    <i class="bi bi-eye-fill me-1"></i>Vista previa docente — las respuestas correctas están marcadas
    · <a href="{{ route('exams.show', $exam) }}" style="color:#fff;text-decoration:underline;">Salir</a>
</div>
@endif

<div class="exam-shell"{{ $previewMode ? ' style="padding-top:24px;"' : '' }}>
    <!-- Top bar -->
    <header class="top-bar">
        <div class="top-bar-left">
            @if($institution)
            <div class="top-bar-institution"><i class="bi bi-building me-1" style="opacity:.7;"></i>{{ $institution }}</div>
            @endif
            <div class="top-bar-student">
                @if($previewMode)
                    <i class="bi bi-eye-fill me-1" style="color:#FCD34D;"></i><strong>VISTA PREVIA DOCENTE</strong>
                @else
                    <i class="bi bi-person me-1" style="opacity:.7;"></i>{{ $accessCode->student->full_name ?? 'Estudiante' }}{{ $sectionName ? ' • ' . $sectionName : '' }}
                @endif
            </div>
        </div>
        {{-- Accessibility button (center of top bar) --}}
        <button class="a11y-btn" id="a11yBtn" onclick="toggleA11yPanel()"
                title="Opciones de accesibilidad visual" aria-label="Accesibilidad">
            <i class="bi bi-universal-access" style="font-size:.95rem;"></i>
            <span>Accesibilidad</span>
        </button>

        <div class="top-bar-right">
            <div class="top-bar-exam">{{ $exam->title }}</div>
            @if($subject)
            <div class="top-bar-subject"><i class="bi bi-book me-1" style="opacity:.7;"></i>{{ $subject->name }}</div>
            @endif
        </div>
    </header>

    {{-- Accessibility panel --}}
    <div class="a11y-panel" id="a11yPanel" role="dialog" aria-label="Panel de accesibilidad">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span style="font-weight:700;font-size:.85rem;color:#1E293B;">
                <i class="bi bi-universal-access me-1 text-primary"></i>Accesibilidad visual
            </span>
            <button onclick="toggleA11yPanel()" style="background:none;border:none;cursor:pointer;color:#94A3B8;font-size:.9rem;padding:0;line-height:1;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        {{-- Font size --}}
        <div class="a11y-section-title">Tamaño de letra</div>
        <div class="a11y-fs-grid">
            <button class="a11y-fs-btn" data-fs="16" onclick="setFontSize(16)" style="font-size:.85rem;">
                A<small>Normal</small>
            </button>
            <button class="a11y-fs-btn" data-fs="19" onclick="setFontSize(19)" style="font-size:1rem;">
                A<small>Mediana</small>
            </button>
            <button class="a11y-fs-btn" data-fs="22" onclick="setFontSize(22)" style="font-size:1.15rem;">
                A<small>Grande</small>
            </button>
            <button class="a11y-fs-btn" data-fs="25" onclick="setFontSize(25)" style="font-size:1.3rem;">
                A<small>Muy gde.</small>
            </button>
        </div>

        {{-- High contrast --}}
        <div class="a11y-toggle-row">
            <span>
                Alto contraste
                <span class="a11y-toggle-desc">Fondo oscuro, texto brillante</span>
            </span>
            <button class="a11y-switch" id="hcSwitch" onclick="toggleHighContrast()" aria-label="Alto contraste"></button>
        </div>

        {{-- Dyslexia font --}}
        <div class="a11y-toggle-row">
            <span>
                Fuente para dislexia
                <span class="a11y-toggle-desc">OpenDyslexic — mejora la legibilidad</span>
            </span>
            <button class="a11y-switch" id="dyslexicSwitch" onclick="toggleDyslexicFont()" aria-label="Fuente para dislexia"></button>
        </div>

        <div class="a11y-tip">
            <i class="bi bi-keyboard me-1"></i>
            Atajo: <strong>Alt + +</strong> agrandar, <strong>Alt + -</strong> reducir letra.
        </div>
    </div>

    <!-- Question slides -->
    <main class="question-area" id="questionArea">
    <form id="examForm" method="POST" action="{{ route('student.submit', $accessCode->code) }}" onsubmit="return false;">
        @csrf

        @foreach($questions as $i => $question)
        @php
            [$tLabel, $tClass] = $typeInfo[$question->type] ?? [$question->type, 'type-single'];
            $existingText = $textAnswers[$question->id] ?? null;
            $decodedText  = $existingText ? json_decode($existingText, true) : null;
            $isAnswered   = in_array($question->type, ['single_choice','true_false'])
                            ? isset($existingAnswers[$question->id])
                            : (isset($textAnswers[$question->id]) && $textAnswers[$question->id] !== '');
        @endphp

        <div class="q-slide {{ $i === 0 ? 'active' : '' }}" id="slide-{{ $i }}" data-idx="{{ $i }}" data-question-id="{{ $question->id }}">

            <!-- Meta row -->
            <div class="q-meta">
                <span class="type-badge {{ $tClass }}">{{ $tLabel }}</span>
                <span style="font-size:.72rem;color:#94A3B8;">{{ $question->points }} pt{{ $question->points != 1 ? 's' : '' }}</span>
                <button type="button" class="flag-btn" id="flag-{{ $i }}" onclick="toggleFlag({{ $i }})">
                    <i class="bi bi-flag"></i><span>Marcar para revisar</span>
                </button>
            </div>

            <!-- Question header -->
            <div class="q-header">
                <div class="q-num">{{ $i + 1 }}</div>
                {{-- Completion: sentence rendered below with drop zones --}}
                @if($question->type !== 'completion')
                <div class="q-text exam-prose">{!! $question->question_text !!}</div>
                @endif
            </div>

            <!-- Media -->
            @if($question->media_type === 'image' && $question->image)
            <div class="media-box mb-3 p-2"><img src="{{ Storage::url($question->image) }}" onclick="openLightbox(this.src)" title="Clic para ampliar"></div>
            @elseif($question->media_type === 'audio' && $question->audio)
            <div class="media-box mb-3">
                <div class="audio-player" data-src="{{ Storage::url($question->audio) }}">
                    <button type="button" class="audio-play-btn" onclick="toggleAudio(this)">
                        <i class="bi bi-play-fill"></i>
                    </button>
                    <span class="audio-time">0:00 / --:--</span>
                    <audio preload="metadata" style="display:none;">
                        <source src="{{ Storage::url($question->audio) }}">
                    </audio>
                </div>
            </div>
            @elseif($question->media_type === 'video' && $question->video)
            <div class="media-box mb-3"><video controls style="max-height:280px;width:100%;"><source src="{{ Storage::url($question->video) }}"></video></div>
            @endif

            {{-- Single choice / True-False --}}
            @if(in_array($question->type, ['single_choice','true_false']))
            <div class="options-container" data-qid="{{ $question->id }}">
                @foreach($question->options as $opt)
                @php $checked = ($existingAnswers[$question->id] ?? null) == $opt->id; @endphp
                <div class="option-label {{ $checked ? 'selected' : '' }}"
                     onclick="selectSingle(this, {{ $question->id }}, {{ $opt->id }}, {{ $i }})"
                     data-option-id="{{ $opt->id }}">
                    @if($question->type === 'true_false')
                        <div class="opt-circle"></div>
                        <i class="bi bi-{{ $opt->option_text === 'Verdadero' ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' }}"></i>
                    @else
                        <div class="opt-circle"></div>
                    @endif
                    <span style="font-size:.93rem;">{{ $opt->option_text }}</span>
                    @if($previewMode && $opt->is_correct)
                    <span class="badge ms-2" style="background:#D1FAE5;color:#065F46;font-size:.66rem;font-weight:700;">
                        <i class="bi bi-check-circle-fill me-1"></i>Correcta
                    </span>
                    @endif
                </div>
                @endforeach
                <input type="hidden" name="answers[{{ $question->id }}][option_id]" id="sc-{{ $question->id }}" value="{{ $existingAnswers[$question->id] ?? '' }}">
            </div>

            {{-- Multiple select --}}
            @elseif($question->type === 'multiple_select')
            @php $selectedIds = is_array($decodedText) ? $decodedText : []; @endphp
            <p class="text-muted small mb-2"><i class="bi bi-info-circle me-1"></i>Selecciona todas las respuestas correctas.</p>
            <div class="options-container-ms" data-qid="{{ $question->id }}">
                @foreach($question->options as $opt)
                @php $checked = in_array($opt->id, $selectedIds); @endphp
                <div class="option-label {{ $checked ? 'selected' : '' }}"
                     onclick="toggleMS(this, {{ $question->id }}, {{ $i }})"
                     data-option-id="{{ $opt->id }}">
                    <div class="opt-square">{{ $checked ? '✓' : '' }}</div>
                    <span style="font-size:.93rem;">{{ $opt->option_text }}</span>
                    @if($previewMode && $opt->is_correct)
                    <span class="badge ms-2" style="background:#D1FAE5;color:#065F46;font-size:.66rem;font-weight:700;">
                        <i class="bi bi-check-circle-fill me-1"></i>Correcta
                    </span>
                    @endif
                </div>
                @endforeach
                <input type="hidden" name="answers[{{ $question->id }}][text_answer]" id="ms-{{ $question->id }}" value="{{ $existingText ?? '' }}">
            </div>

            {{-- Short answer --}}
            @elseif($question->type === 'short_answer')
            @if($previewMode)
            <div class="alert py-1 px-2 mb-2" style="background:#FEF3C7;border:1px solid #FDE68A;font-size:.74rem;color:#92400E;">
                <i class="bi bi-pencil-square me-1"></i>Calificación manual por el docente
            </div>
            @endif
            <textarea class="short-answer-ta"
                      name="answers[{{ $question->id }}][text_answer]"
                      data-qid="{{ $question->id }}"
                      data-idx="{{ $i }}"
                      placeholder="Escribe tu respuesta aquí...">{{ $existingText ?? '' }}</textarea>

            {{-- Matching --}}
            @elseif($question->type === 'matching')
            @php
                $existingMatch = is_array($decodedText) ? $decodedText : [];
                $definitions   = $question->options->pluck('match_text')->shuffle()->values();
            @endphp
            <p class="text-muted small mb-2"><i class="bi bi-info-circle me-1"></i>Selecciona la definición correcta para cada concepto.</p>
            <div class="matching-wrapper" data-qid="{{ $question->id }}" data-idx="{{ $i }}">
                @foreach($question->options as $opt)
                @php $pre = $existingMatch[(string)$opt->id] ?? ''; @endphp
                <div class="matching-row">
                    <div class="matching-concept">{{ $opt->option_text }}</div>
                    <select class="matching-select {{ $pre ? 'selected' : '' }}"
                            data-option-id="{{ $opt->id }}" onchange="onMatch(this)">
                        <option value="">— Selecciona —</option>
                        @foreach($definitions as $def)
                        <option value="{{ $def }}" {{ $pre === $def ? 'selected' : '' }}>{{ $def }}</option>
                        @endforeach
                    </select>
                </div>
                @if($previewMode && $opt->match_text)
                <div style="font-size:.7rem;color:#065F46;margin:-.4rem 0 .55rem 0;padding-left:.25rem;">
                    <i class="bi bi-check-circle-fill me-1"></i>Correcta: <strong>{{ $opt->match_text }}</strong>
                </div>
                @endif
                @endforeach
            </div>
            <input type="hidden" name="answers[{{ $question->id }}][text_answer]" id="mt-{{ $question->id }}" value="{{ $existingText ?? '' }}">

            {{-- Identification --}}
            @elseif($question->type === 'identification')
            @php $existingIdent = is_array($decodedText) ? $decodedText : []; @endphp
            <p class="text-muted small mb-2"><i class="bi bi-info-circle me-1"></i>Escribe en cada campo qué representa la etiqueta indicada.</p>
            <div class="ident-wrapper" data-qid="{{ $question->id }}" data-idx="{{ $i }}">
                @foreach($question->options->sortBy('order') as $part)
                @php $pre = $existingIdent[$part->option_text] ?? ''; @endphp
                <div class="ident-row mb-2 d-flex align-items-center gap-2">
                    <div class="ident-label-badge flex-shrink-0">{{ $part->option_text }}</div>
                    <input type="text"
                           class="form-control ident-input"
                           data-label="{{ $part->option_text }}"
                           data-qid="{{ $question->id }}"
                           data-idx="{{ $i }}"
                           placeholder="Escribe aquí…"
                           value="{{ $pre }}"
                           oninput="onIdent(this)">
                    @if($previewMode && $part->match_text)
                    <span style="font-size:.72rem;color:#065F46;font-weight:600;">
                        <i class="bi bi-check-circle-fill me-1"></i>{{ $part->match_text }}
                    </span>
                    @endif
                </div>
                @endforeach
            </div>
            <input type="hidden" name="answers[{{ $question->id }}][text_answer]" id="id-{{ $question->id }}" value="{{ $existingText ?? '' }}">

            {{-- Restricted Response / Exercise / Written Production --}}
            @elseif(in_array($question->type, ['restricted_response','exercise','written_production']))
            @php $rubric = $question->rubric; @endphp
            @if(is_array($rubric) && !empty($rubric['levels']) && !empty($rubric['criteria']))
            <div class="mb-3 p-2 rounded-3" style="background:#FFFBEB;border:1px solid #FDE68A;">
                <div class="fw-semibold mb-2" style="color:#78350F;font-size:.85rem;"><i class="bi bi-table me-1"></i>Rúbrica de evaluación</div>
                <div style="overflow-x:auto;">
                <table class="table table-sm mb-0" style="font-size:.78rem;background:#fff;border-radius:6px;">
                    <thead>
                        <tr style="background:#FEF3C7;color:#78350F;">
                            <th style="width:160px;font-size:.7rem;">CRITERIO</th>
                            @foreach($rubric['levels'] as $lvl)
                            <th style="font-size:.7rem;text-align:center;">{{ $lvl['name'] }}<br><span style="font-weight:400;font-size:.66rem;">({{ rtrim(rtrim(number_format($lvl['points'] ?? 0,2),'0'),'.') }} pts)</span></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rubric['criteria'] as $crit)
                        <tr>
                            <td class="fw-600">{{ $crit['name'] }}</td>
                            @foreach($crit['descriptors'] ?? [] as $desc)
                            <td style="color:#475569;">{{ $desc }}</td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
            @elseif($question->grading_criteria)
            <div class="rubric-info mb-3 p-3 rounded-3" style="background:#FFFBEB;border:1px solid #FDE68A;font-size:.82rem;color:#78350F;">
                <div class="fw-semibold mb-1"><i class="bi bi-award me-1"></i>Criterios de evaluación</div>
                <div style="white-space:pre-wrap;">{{ $question->grading_criteria }}</div>
            </div>
            @endif
            {{-- Rich text editor (Quill) for elaborate writing --}}
            <div class="rich-answer-wrap" data-qid="{{ $question->id }}" data-idx="{{ $i }}"
                 data-initial="{{ $existingText ?? '' }}"
                 style="border:2px solid #E2E8F0;border-radius:12px;background:#fff;overflow:hidden;">
                <div class="rich-answer-editor" style="min-height:{{ $question->type === 'written_production' ? '220' : '140' }}px;background:#fff;"></div>
            </div>
            <textarea name="answers[{{ $question->id }}][text_answer]"
                      id="rich-src-{{ $question->id }}" class="d-none">{{ $existingText ?? '' }}</textarea>

            {{-- Completion (drag & drop) --}}
            @elseif($question->type === 'completion')
            @php
                $existingCp = is_array($decodedText) ? $decodedText : [];
                // Parse ___ in question text to numbered drop zones
                $cpBlank = 0;
                $cpSentence = preg_replace_callback('/_{3,}/', function($m) use (&$cpBlank, $question) {
                    $cpBlank++;
                    $pre = '';
                    return '<span class="cp-zone empty" data-blank="'.$cpBlank.'" data-qid="'.$question->id.'" onclick="cpClickZone(this)" ondragover="cpDragOver(event)" ondragleave="cpDragLeave(event)" ondrop="cpDrop(event)"></span>';
                }, $question->question_text); // No escapar: ya es HTML de Quill
                $totalBlanks = $cpBlank;
                // All words (correct + distractors) shuffled for bank display
                $cpWords = $question->options->shuffle()->values();
            @endphp
            <p class="text-muted small mb-1"><i class="bi bi-hand-index me-1"></i>Arrastra o toca una palabra y luego toca el espacio donde va.</p>
            @if($previewMode)
            @php $correctCp = $question->options->where('is_correct', true)->sortBy('order'); @endphp
            <div class="alert mb-2 py-2 px-3" style="background:#D1FAE5;border:1px solid #A7F3D0;font-size:.78rem;color:#065F46;">
                <strong><i class="bi bi-check-circle-fill me-1"></i>Respuestas correctas (por espacio):</strong>
                @foreach($correctCp as $cc)
                <span class="badge ms-1" style="background:#fff;color:#065F46;border:1px solid #A7F3D0;">{{ $cc->order }}. {{ $cc->option_text }}</span>
                @endforeach
            </div>
            @endif
            <div class="completion-wrapper" data-qid="{{ $question->id }}" data-idx="{{ $i }}" data-total="{{ $totalBlanks }}">
                <div class="cp-sentence">{!! $cpSentence !!}</div>
                <div class="cp-bank">
                    <div class="cp-bank-label"><i class="bi bi-collection me-1"></i>Banco de palabras</div>
                    <div id="cp-bank-{{ $question->id }}">
                        @foreach($cpWords as $cpOpt)
                        @php $isUsed = in_array($cpOpt->option_text, array_values($existingCp)); @endphp
                        <span class="cp-word {{ $isUsed ? 'cp-word-used' : '' }}"
                              draggable="true"
                              data-word="{{ $cpOpt->option_text }}"
                              data-qid="{{ $question->id }}"
                              onclick="cpClickWord(this)"
                              ondragstart="cpDragStart(event, this)"
                              ondragend="cpDragEnd(event)">{{ $cpOpt->option_text }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            <input type="hidden" name="answers[{{ $question->id }}][text_answer]" id="cp-{{ $question->id }}" value="{{ $existingText ?? '' }}">

            {{-- Ordering --}}
            @elseif($question->type === 'ordering')
            @php
                $orderedIds = is_array($decodedText) ? $decodedText : [];
                if (!empty($orderedIds)) {
                    $oMap = $question->options->keyBy('id');
                    $sortedOpts = collect($orderedIds)->map(fn($id) => $oMap->get($id))->filter()->values();
                } else {
                    $sortedOpts = $question->options->shuffle()->values();
                }
            @endphp
            <p class="text-muted small mb-2"><i class="bi bi-info-circle me-1"></i>Arrastra los ítems o usa <i class="bi bi-chevron-up"></i><i class="bi bi-chevron-down"></i> para ordenarlos.</p>
            <ul class="ordering-list" id="order-list-{{ $question->id }}" data-qid="{{ $question->id }}" data-idx="{{ $i }}">
                @foreach($sortedOpts as $j => $opt)
                <li class="ordering-item" data-option-id="{{ $opt->id }}" draggable="true">
                    <i class="bi bi-grip-vertical drag-handle"></i>
                    <div class="order-pos">{{ $j + 1 }}</div>
                    <span style="font-size:.93rem;flex:1;">{{ $opt->option_text }}</span>
                    @if($previewMode && $opt->order)
                    <span class="badge me-2" style="background:#D1FAE5;color:#065F46;font-size:.66rem;font-weight:700;" title="Posición correcta">
                        <i class="bi bi-check-circle-fill me-1"></i>Pos. {{ $opt->order }}
                    </span>
                    @endif
                    <div class="order-btns">
                        <button type="button" class="order-btn" onclick="moveOrder(this,-1)"><i class="bi bi-chevron-up"></i></button>
                        <button type="button" class="order-btn" onclick="moveOrder(this,1)"><i class="bi bi-chevron-down"></i></button>
                    </div>
                </li>
                @endforeach
            </ul>
            <input type="hidden" name="answers[{{ $question->id }}][text_answer]" id="or-{{ $question->id }}" value="{{ $existingText ?? '' }}">
            @endif

        </div>
        @endforeach

    </form>
    </main>

    <!-- Bottom navigation bar (always visible) -->
    <nav class="q-nav-bar">
        <button type="button" class="nav-btn" id="btnPrev" onclick="goTo(currentIdx - 1)">
            <i class="bi bi-chevron-left"></i> Anterior
        </button>
        <span class="q-counter" id="qCounter">1 de {{ $questions->count() }}</span>
        <button type="button" class="nav-btn primary" id="btnNext" onclick="navNext()">
            Siguiente <i class="bi bi-chevron-right"></i>
        </button>
    </nav>

    <!-- Sidebar -->
    <aside class="exam-sidebar">
        <!-- Timer -->
        <div class="sidebar-timer-box">
            <div class="sidebar-timer-label">Tiempo restante</div>
            <div class="sidebar-timer-val" id="timerSide">--:--</div>
        </div>

        @if($exam->proctoring && !$previewMode)
        <!-- Proctoring: fullscreen + monitoring indicator -->
        <div style="padding:8px 12px;">
            <button type="button" id="btnFullscreen" onclick="toggleFullscreen()"
                    style="width:100%;background:rgba(255,255,255,.10);color:#fff;border:1px solid rgba(255,255,255,.20);
                           border-radius:10px;padding:7px;font-size:12px;font-weight:600;cursor:pointer;
                           display:flex;align-items:center;justify-content:center;gap:6px;">
                <i class="bi bi-arrows-fullscreen"></i><span id="fsLabel">Pantalla completa</span>
            </button>
            <div style="display:flex;align-items:center;gap:5px;margin-top:8px;font-size:11px;color:rgba(255,255,255,.6);">
                <i class="bi bi-shield-lock-fill" style="color:#FCD34D;"></i>
                Examen monitoreado
                <span id="leaveBadge" style="display:none;margin-left:auto;background:#DC2626;color:#fff;
                      border-radius:10px;padding:1px 6px;font-weight:700;">0</span>
            </div>
        </div>
        @endif

        <!-- Progress -->
        <div class="sidebar-progress">
            <div class="progress-row">
                <span>Progreso</span>
                <span id="progressLabel">0 / {{ $questions->count() }}</span>
            </div>
            <div class="progress" style="height:7px;border-radius:4px;">
                <div class="progress-bar bg-success" id="progressBar" style="width:0%;transition:width .3s;"></div>
            </div>
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-item"><div class="legend-dot" style="background:#F1F5F9;border:1.5px solid #CBD5E1;"></div>Sin responder</div>
            <div class="legend-item"><div class="legend-dot" style="background:#DCFCE7;border:1.5px solid #86EFAC;"></div>Respondida</div>
            <div class="legend-item"><div class="legend-dot" style="background:#FEF9C3;border:1.5px solid #FCD34D;"></div>Para revisar</div>
            <div class="legend-item"><div class="legend-dot" style="background:#4F46E5;"></div>Actual</div>
        </div>

        <!-- Question grid -->
        <div class="sidebar-grid-wrap">
            <div class="sidebar-section-label">Preguntas</div>
            <div class="q-grid" id="qGrid">
                @foreach($questions as $i => $question)
                @php
                    $ans = in_array($question->type, ['single_choice','true_false'])
                        ? isset($existingAnswers[$question->id])
                        : (isset($textAnswers[$question->id]) && $textAnswers[$question->id] !== '');
                @endphp
                <button type="button"
                        class="q-btn {{ $ans ? 'answered' : '' }} {{ $i === 0 ? 'current' : '' }}"
                        id="qbtn-{{ $i }}"
                        onclick="goTo({{ $i }})">{{ $i + 1 }}</button>
                @endforeach
            </div>
        </div>

        <!-- Submit -->
        <div class="sidebar-submit">
            @if($previewMode)
            <a href="{{ route('exams.show', $exam) }}" class="submit-btn" style="display:flex;align-items:center;justify-content:center;text-decoration:none;background:linear-gradient(135deg,#D97706,#F59E0B);">
                <i class="bi bi-box-arrow-left me-2"></i>Salir de la vista previa
            </a>
            @else
            <button type="button" class="submit-btn" onclick="openSubmitModal()">
                <i class="bi bi-send-fill me-2"></i>Enviar Examen
            </button>
            @endif
        </div>
    </aside>
</div>

<!-- Save toast -->
<!-- Lightbox -->
<div class="lb-overlay" id="lbOverlay" onclick="closeLightbox(event)">
    <button class="lb-close" onclick="closeLightbox()"><i class="bi bi-x-lg"></i></button>
    <img id="lbImg" src="" alt="">
</div>

<div class="save-toast" id="saveToast" style="opacity:0;">
    <i class="bi bi-cloud-check me-1"></i>Guardado
</div>

{{-- Time warning banner --}}
<div id="timeWarning" style="display:none;position:fixed;top:70px;left:50%;transform:translateX(-50%);
     z-index:2500;background:#FEF3C7;border:1.5px solid #F59E0B;color:#92400E;
     padding:12px 20px;border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,.18);
     font-weight:700;font-size:14px;display:none;align-items:center;gap:8px;max-width:90vw;">
    <i class="bi bi-alarm-fill" style="font-size:18px;"></i>
    <span id="timeWarningText">Te quedan 5 minutos</span>
</div>

@if($exam->proctoring && !$previewMode)
<!-- Proctoring warning overlay (shown when student returns after leaving) -->
<div id="proctorWarn" style="display:none;position:fixed;inset:0;z-index:3000;
     background:rgba(15,23,42,.78);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:18px;max-width:400px;width:90%;padding:1.8rem 1.6rem;text-align:center;
                box-shadow:0 20px 50px rgba(0,0,0,.3);">
        <div style="width:64px;height:64px;border-radius:50%;background:#FEE2E2;margin:0 auto 1rem;
                    display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-exclamation-triangle-fill" style="color:#DC2626;font-size:1.8rem;"></i>
        </div>
        <h5 class="fw-bold mb-2" style="color:#1E293B;">Saliste de la pantalla del examen</h5>
        <p class="text-muted small mb-1">
            Esta acción quedó <strong>registrada</strong> y será visible para tu docente.
        </p>
        <p class="small mb-3" style="color:#DC2626;font-weight:600;">
            Salidas detectadas: <span id="proctorWarnCount">1</span>
        </p>
        <button type="button" onclick="dismissProctorWarn()"
                style="background:#4F46E5;color:#fff;border:none;border-radius:10px;
                       padding:.55rem 1.4rem;font-weight:600;font-size:.85rem;cursor:pointer;">
            Entendido, continuar
        </button>
    </div>
</div>
@endif

<!-- Submit confirmation modal (Step 1) -->
<div class="confirm-overlay" id="confirmOverlay" style="display:none;">
    <div class="confirm-box">
        <div class="confirm-icon" id="confirmIcon" style="background:#FEF9C3;">
            <i class="bi bi-exclamation-triangle-fill" style="color:#F59E0B;"></i>
        </div>
        <h5 class="text-center fw-bold mb-1" id="confirmTitle">Revisar antes de enviar</h5>
        <p class="text-center text-muted small mb-3" id="confirmSubtitle"></p>

        <div id="confirmChips" class="confirm-chips justify-content-center"></div>

        <div id="confirmStats" style="background:#F8FAFC;border-radius:12px;padding:.9rem;margin:.75rem 0;font-size:.82rem;"></div>

        <div class="d-flex gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary flex-fill" onclick="closeSubmitModal()">
                <i class="bi bi-arrow-left me-1"></i>Regresar
            </button>
            <button type="button" class="btn btn-danger flex-fill fw-semibold" id="confirmNextBtn" onclick="confirmStep2()">
                Continuar con envío <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>
</div>

<!-- Submit confirmation modal (Step 2) -->
<div class="confirm-overlay" id="confirmOverlay2" style="display:none;">
    <div class="confirm-box" style="max-width:380px;">
        <div class="confirm-icon" style="background:#FEE2E2;">
            <i class="bi bi-send-fill" style="color:#DC2626;"></i>
        </div>
        <h5 class="text-center fw-bold mb-1">¿Enviar definitivamente?</h5>
        <p class="text-center text-muted small mb-4">
            Esta acción <strong>no se puede deshacer</strong>. Una vez enviado, no podrás modificar tus respuestas.
        </p>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary flex-fill" onclick="backToStep1()">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </button>
            <button type="button" class="btn flex-fill fw-bold text-white" onclick="doSubmit()"
                    style="background:linear-gradient(135deg,#DC2626,#EF4444);">
                <i class="bi bi-send-fill me-1"></i>Sí, enviar examen
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script>
// Bootstrap config shared between the inline blade script and the feature
// files extracted under public/js/exams/. Keep this block tiny.
@php
    $stateQuestions = $questions->values()->map(function ($q, $i) use ($existingAnswers, $textAnswers) {
        $isChoice = in_array($q->type, ['single_choice','true_false']);
        $answered = $isChoice
            ? isset($existingAnswers[$q->id])
            : (isset($textAnswers[$q->id]) && $textAnswers[$q->id] !== '');
        return ['id' => $q->id, 'type' => $q->type, 'answered' => $answered];
    });
@endphp
window.ExamState = {
    attemptId:    {{ $attempt->id }},
    totalQ:       {{ $questions->count() }},
    previewMode:  {{ $previewMode ? 'true' : 'false' }},
    paused:       {{ !empty($attempt->paused_at) ? 'true' : 'false' }},
    lsPrefix:     "ec_{{ $attempt->id }}_",
    secondsLeft:  {{ $previewMode ? ($exam->duration_minutes * 60) : $attempt->remaining_seconds }},
    urls: {
        save:   "{{ $previewMode ? '' : route('student.save-answer', $attempt->id) }}",
        submit: "{{ $previewMode ? '' : route('student.submit', $accessCode->code) }}",
    },
    questionIds: @json($questions->pluck('id')->all()),
    questions:   @json($stateQuestions),
    @if($exam->proctoring && !$previewMode)
    proctoring: {
        urls: {
            log:    "{{ route('student.proctor-log', $attempt->id) }}",
            status: "{{ route('student.exam-status', $attempt->id) }}",
        },
        initialLeaveCount: {{ (int) $attempt->focus_loss_count }},
    },
    @endif
};
</script>
<script src="{{ asset('js/exams/student-exam.js') }}?v={{ filemtime(public_path('js/exams/student-exam.js')) }}"></script>
<script src="{{ asset('js/exams/student-a11y.js') }}?v={{ filemtime(public_path('js/exams/student-a11y.js')) }}"></script>
@if($exam->proctoring && !$previewMode)
<script src="{{ asset('js/exams/student-proctoring.js') }}?v={{ filemtime(public_path('js/exams/student-proctoring.js')) }}"></script>
@endif
</body>
</html>
