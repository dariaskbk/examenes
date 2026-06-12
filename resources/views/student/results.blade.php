<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados — ExamCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; min-height: 100vh; background: #F0F2F8; padding: 1rem; }

        .page-wrap { max-width: 680px; margin: 0 auto; }

        /* Hero card */
        .hero-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,.08);
            margin-bottom: 1rem;
        }
        .hero-banner {
            background: linear-gradient(135deg, #312E81, #4F46E5 60%, #7C3AED);
            padding: 2rem 1.5rem;
            text-align: center;
            color: #fff;
        }
        .score-ring {
            width: 110px; height: 110px;
            border-radius: 50%;
            border: 4px solid;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            margin: 0 auto 1rem;
        }
        .score-ring.pass { border-color: #10B981; background: rgba(16,185,129,.15); }
        .score-ring.fail { border-color: #EF4444; background: rgba(239,68,68,.15); }
        .score-ring .pct  { font-size: 1.8rem; font-weight: 800; line-height: 1; }
        .score-ring .sym  { font-size: .75rem; opacity: .75; }
        .verdict { font-size: 1.2rem; font-weight: 800; margin-bottom: .25rem; }
        .student-name { font-size: .85rem; opacity: .75; }
        .exam-name    { font-size: .78rem; opacity: .6; margin-top: .1rem; }

        /* Stat row */
        .stat-row { display: flex; }
        .stat-cell { flex: 1; text-align: center; padding: 1rem .5rem; border-right: 1px solid #F1F5F9; }
        .stat-cell:last-child { border-right: none; }
        .stat-val  { font-size: 1.2rem; font-weight: 800; color: #1E293B; }
        .stat-lbl  { font-size: .68rem; color: #94A3B8; text-transform: uppercase; font-weight: 600; margin-top: 2px; }

        /* Answer review */
        .review-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,.08);
            margin-bottom: 1rem;
        }
        .review-head {
            padding: .9rem 1.25rem;
            border-bottom: 1px solid #F1F5F9;
            font-weight: 700;
            font-size: .9rem;
            color: #1E293B;
            display: flex; align-items: center; gap: .5rem;
        }
        .q-block { padding: 1rem 1.25rem; border-bottom: 1px solid #F8FAFC; }
        .q-block:last-child { border-bottom: none; }

        .status-dot {
            width: 28px; height: 28px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: .8rem;
        }
        .dot-pass    { background: #D1FAE5; color: #065F46; }
        .dot-fail    { background: #FEE2E2; color: #991B1B; }
        .dot-manual  { background: #FEF9C3; color: #854D0E; }
        .dot-blank   { background: #F1F5F9; color: #94A3B8; }

        .q-text { font-size: .85rem; font-weight: 600; color: #1E293B; margin-bottom: .6rem; }

        .opt {
            display: flex; align-items: center; gap: .5rem;
            padding: .4rem .75rem; border-radius: 8px;
            font-size: .8rem; margin-bottom: .3rem;
            border: 1px solid transparent;
        }
        .opt-selected-correct { background: #D1FAE5; border-color: #A7F3D0; color: #065F46; }
        .opt-selected-wrong   { background: #FEE2E2; border-color: #FECACA; color: #991B1B; }
        .opt-correct-unsel    { background: #F0FDF4; border-color: #BBF7D0; color: #166534; }
        .opt-neutral          { background: #F8FAFC; color: #374151; }

        .pts-badge { font-size: .72rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; }

        .home-btn {
            display: block; width: 100%; text-align: center;
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            color: #fff; font-weight: 700; font-size: .9rem;
            border: none; border-radius: 12px; padding: .8rem;
            text-decoration: none; transition: .2s;
        }
        .home-btn:hover { opacity: .9; color: #fff; transform: translateY(-1px); }
    </style>
</head>
<body>
@php
    $passed = $attempt->percentage >= $exam->passing_score;
    $pct    = number_format($attempt->percentage ?? 0, 1);
    $student = $accessCode->student;
@endphp

<div class="page-wrap">
    {{-- Hero --}}
    <div class="hero-card">
        <div class="hero-banner">
            <div class="score-ring {{ $passed ? 'pass' : 'fail' }}">
                <div class="pct">{{ $pct }}</div>
                <div class="sym">%</div>
            </div>
            <div class="verdict">{{ $passed ? '¡Aprobado!' : 'No aprobado' }}</div>
            <div class="student-name">{{ $student->full_name }}</div>
            <div class="exam-name">{{ $exam->title }}</div>
        </div>
        <div class="stat-row">
            <div class="stat-cell">
                <div class="stat-val" style="color:#4F46E5;">{{ number_format($attempt->score ?? 0, 1) }}</div>
                <div class="stat-lbl">Puntos</div>
            </div>
            <div class="stat-cell">
                <div class="stat-val">{{ number_format($attempt->max_score ?? 0, 1) }}</div>
                <div class="stat-lbl">Total</div>
            </div>
            <div class="stat-cell">
                <div class="stat-val" style="color:{{ $passed ? '#059669' : '#DC2626' }};">{{ $exam->passing_score }}%</div>
                <div class="stat-lbl">Mínimo</div>
            </div>
            <div class="stat-cell">
                <div class="stat-val" style="color:{{ $passed ? '#059669' : '#DC2626' }};">
                    <i class="bi bi-{{ $passed ? 'patch-check-fill' : 'patch-exclamation-fill' }}" style="font-size:1rem;"></i>
                </div>
                <div class="stat-lbl">{{ $passed ? 'Aprobado' : 'Reprobado' }}</div>
            </div>
        </div>
    </div>

    {{-- Answer review --}}
    @if($exam->show_correct_answers && $attempt->answers->count())
    <div class="review-card">
        <div class="review-head">
            <i class="bi bi-list-check" style="color:#4F46E5;"></i>
            Revisión de respuestas
        </div>
        @foreach($attempt->answers as $idx => $answer)
        @php
            $q         = $answer->question;
            $type      = $q->type;
            $isShort   = $type === 'short_answer';
            $isMatch   = $type === 'matching';
            $isOrder   = $type === 'ordering';
            $isMultiS  = $type === 'multiple_select';
            $isRadio   = in_array($type, ['single_choice','multiple_choice','true_false']);
            $isCorrect = $answer->is_correct;
            $earned    = $answer->points_earned ?? 0;

            if ($isShort)         { $dotClass = 'dot-manual'; $icon = 'pencil'; }
            elseif ($isCorrect)   { $dotClass = 'dot-pass';   $icon = 'check-lg'; }
            elseif ($earned > 0)  { $dotClass = 'dot-manual'; $icon = 'dash-circle'; }
            elseif ($answer->option_id || $answer->text_answer) { $dotClass = 'dot-fail'; $icon = 'x-lg'; }
            else                  { $dotClass = 'dot-blank';  $icon = 'dash'; }

            $textDecoded = $answer->text_answer ? json_decode($answer->text_answer, true) : null;
        @endphp
        <div class="q-block">
            <div class="d-flex gap-2 align-items-start">
                <div class="status-dot {{ $dotClass }} mt-1">
                    <i class="bi bi-{{ $icon }}"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="q-text exam-prose"><strong>{{ $idx+1 }}.</strong> {!! $q->question_text !!}</div>

                    @if($isShort)
                        @php
                            $writingTypes = ['restricted_response','exercise','written_production'];
                            $isRich       = in_array($q->type, $writingTypes);
                            $hasAnswer    = !empty($answer->text_answer);
                        @endphp
                        <div class="opt opt-neutral" style="display:block;padding:.5rem .75rem;">
                            <span style="font-size:.7rem;color:#94A3B8;font-weight:700;text-transform:uppercase;">Tu respuesta</span><br>
                            @if(!$hasAnswer)
                                <span class="text-muted">(sin respuesta)</span>
                            @elseif($isRich)
                                <div class="exam-prose mt-1">{!! $answer->text_answer !!}</div>
                            @else
                                <span>{{ $answer->text_answer }}</span>
                            @endif
                        </div>
                        @if($answer->is_correct === null)
                        <div style="font-size:.72rem;color:#854D0E;margin-top:.3rem;">
                            <i class="bi bi-hourglass me-1"></i>Pendiente de revisión manual
                        </div>
                        @else
                        @if($answer->feedback)
                        <div class="mt-2 p-2 rounded-2" style="background:#EFF6FF;border:1px solid #BFDBFE;font-size:.82rem;">
                            <div style="font-size:.68rem;font-weight:700;color:#1D4ED8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem;">
                                <i class="bi bi-chat-left-text-fill me-1"></i>Retroalimentación del docente
                            </div>
                            <span style="color:#1E3A5F;">{{ $answer->feedback }}</span>
                        </div>
                        @endif
                        @endif

                    @elseif($isRadio)
                        @foreach($q->options as $opt)
                        @php
                            $sel = $answer->option_id == $opt->id;
                            $cor = $opt->is_correct;
                            if ($sel && $cor)       $cls = 'opt-selected-correct';
                            elseif ($sel && !$cor)  $cls = 'opt-selected-wrong';
                            elseif ($cor)           $cls = 'opt-correct-unsel';
                            else                    $cls = 'opt-neutral';
                        @endphp
                        <div class="opt {{ $cls }}">
                            @if($sel)
                                <i class="bi bi-{{ $cor ? 'check-circle-fill' : 'x-circle-fill' }} flex-shrink-0"></i>
                            @elseif($cor)
                                <i class="bi bi-check-circle flex-shrink-0" style="opacity:.6;"></i>
                            @else
                                <i class="bi bi-circle flex-shrink-0" style="opacity:.25;font-size:.7rem;"></i>
                            @endif
                            <span>{{ $opt->option_text }}</span>
                            @if($cor && !$sel)
                            <span class="ms-auto" style="font-size:.65rem;font-weight:700;color:#059669;white-space:nowrap;">Correcta</span>
                            @endif
                        </div>
                        @endforeach

                    @elseif($isMultiS)
                        @php $selectedIds = is_array($textDecoded) ? $textDecoded : []; @endphp
                        @foreach($q->options as $opt)
                        @php
                            $sel = in_array($opt->id, $selectedIds);
                            $cor = $opt->is_correct;
                            if ($sel && $cor)       $cls = 'opt-selected-correct';
                            elseif ($sel && !$cor)  $cls = 'opt-selected-wrong';
                            elseif ($cor)           $cls = 'opt-correct-unsel';
                            else                    $cls = 'opt-neutral';
                        @endphp
                        <div class="opt {{ $cls }}">
                            <i class="bi bi-{{ $sel ? ($cor ? 'check-square-fill' : 'x-square-fill') : ($cor ? 'check-square' : 'square') }} flex-shrink-0" style="{{ !$sel && $cor ? 'opacity:.6;' : '' }}{{ !$sel && !$cor ? 'opacity:.25;font-size:.7rem;' : '' }}"></i>
                            <span>{{ $opt->option_text }}</span>
                            @if($cor && !$sel)
                            <span class="ms-auto" style="font-size:.65rem;font-weight:700;color:#059669;white-space:nowrap;">Correcta</span>
                            @endif
                        </div>
                        @endforeach

                    @elseif($isMatch)
                        @php $studentMap = is_array($textDecoded) ? $textDecoded : []; @endphp
                        @foreach($q->options as $pair)
                        @php
                            $submitted = trim($studentMap[(string)$pair->id] ?? '');
                            $correct   = mb_strtolower($submitted) === mb_strtolower(trim($pair->match_text ?? ''));
                        @endphp
                        <div class="opt {{ $submitted ? ($correct ? 'opt-selected-correct' : 'opt-selected-wrong') : 'opt-neutral' }}" style="flex-wrap:wrap;gap:.3rem;">
                            <span class="fw-semibold" style="min-width:30%;">{{ $pair->option_text }}</span>
                            <span style="color:#94A3B8;">→</span>
                            <span style="flex:1;">{{ $submitted ?: '(sin respuesta)' }}</span>
                            @if(!$correct)
                            <span style="font-size:.65rem;color:#059669;font-weight:700;width:100%;padding-top:.2rem;">
                                ✓ {{ $pair->match_text }}
                            </span>
                            @endif
                        </div>
                        @endforeach

                    @elseif($isOrder)
                        @php
                            $orderedIds = is_array($textDecoded) ? $textDecoded : [];
                            $optById    = $q->options->keyBy('id');
                        @endphp
                        @if(empty($orderedIds))
                        <div class="opt opt-neutral">(sin respuesta)</div>
                        @else
                        @foreach($orderedIds as $pos => $optId)
                        @php
                            $item    = $optById->get($optId);
                            $correct = $item && $item->order === ($pos + 1);
                        @endphp
                        <div class="opt {{ $correct ? 'opt-selected-correct' : 'opt-selected-wrong' }}" style="align-items:center;">
                            <span class="me-2" style="width:22px;height:22px;background:{{ $correct ? '#059669' : '#DC2626' }};color:#fff;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0;">{{ $pos+1 }}</span>
                            <span>{{ $item->option_text ?? '?' }}</span>
                            @if(!$correct && $item)
                            <span class="ms-auto" style="font-size:.65rem;color:#94A3B8;white-space:nowrap;">pos. correcta: {{ $item->order }}</span>
                            @endif
                        </div>
                        @endforeach
                        @endif
                    @endif

                </div>
                <div class="flex-shrink-0 text-end" style="min-width:44px;">
                    <div style="font-size:.9rem;font-weight:800;color:{{ $earned>0?'#059669':'#DC2626' }};">
                        {{ number_format($earned, 1) }}
                    </div>
                    <div style="font-size:.65rem;color:#94A3B8;">/ {{ $q->points }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @elseif(!$exam->show_correct_answers)
    <div class="review-card" style="padding:1.5rem;text-align:center;">
        <i class="bi bi-eye-slash" style="font-size:2rem;color:#CBD5E1;"></i>
        <p style="font-size:.85rem;color:#94A3B8;margin:.75rem 0 0;">El docente no ha habilitado la revisión de respuestas para este examen.</p>
    </div>
    @endif

    <a href="{{ route('student.entry') }}" class="home-btn">
        <i class="bi bi-house me-2"></i>Volver al inicio
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
