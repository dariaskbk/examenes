<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; color: #1E293B; background: #fff; }

/* Header */
.header { background: #4F46E5; padding: 12px 16px 14px; margin-bottom: 14px; }
.header-inner { display: table; width: 100%; }
.h-logo { display: table-cell; vertical-align: middle; width: 52px; }
.h-info { display: table-cell; vertical-align: middle; padding-left: 12px; }
.h-right { display: table-cell; vertical-align: middle; text-align: right; width: 150px; }
.logo-box { width: 44px; height: 44px; background: rgba(255,255,255,0.2); border-radius: 10px;
    text-align: center; line-height: 44px; color: #fff; font-size: 20pt; font-weight: 900;
    border: 2px solid rgba(255,255,255,0.35); }
.brand-name { font-size: 9pt; color: #fff; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase; }
.brand-sub { font-size: 6.5pt; color: rgba(255,255,255,0.7); letter-spacing: 0.04em; }
.doc-title { font-size: 14pt; font-weight: 900; color: #fff; line-height: 1.15; }
.doc-sub { font-size: 7.5pt; color: rgba(255,255,255,0.75); margin-top: 2px; }

.section-pad { padding: 0 16px; }

/* Info + result boxes */
.boxes { display: table; width: 100%; margin-bottom: 12px; }
.box { display: table-cell; vertical-align: top; width: 50%; }
.box-inner { border: 1px solid #E2E8F0; border-radius: 8px; padding: 9px 11px; }
.box-left  { margin-right: 6px; }
.box-right { margin-left: 6px; }
.kv { margin-bottom: 3px; font-size: 8.5pt; }
.kv b { color: #475569; }
.name-big { font-size: 12pt; font-weight: 900; text-transform: uppercase; color: #1E293B; margin-bottom: 4px; }

.result-row { display: table; width: 100%; }
.result-cell { display: table-cell; vertical-align: middle; }
.score-big { font-size: 26pt; font-weight: 900; line-height: 1; }
.pct { font-size: 9pt; color: #64748B; }
.badge { display: inline-block; padding: 4px 12px; border-radius: 14px; font-size: 9pt; font-weight: 900; }
.b-pass { background: #D1FAE5; color: #065F46; }
.b-fail { background: #FEE2E2; color: #991B1B; }

.proctor-note { background: #FEF2F2; border: 1px solid #FECACA; border-radius: 6px;
    padding: 6px 10px; font-size: 8pt; color: #991B1B; margin-bottom: 12px; }

.sec-title { font-size: 9.5pt; font-weight: 900; color: #4F46E5; text-transform: uppercase;
    letter-spacing: 0.05em; margin: 4px 0 6px; border-bottom: 2px solid #EEF2FF; padding-bottom: 3px; }

/* Questions table */
table.q { width: 100%; border-collapse: collapse; }
table.q th { background: #F8FAFC; color: #64748B; font-size: 7pt; text-transform: uppercase;
    letter-spacing: 0.04em; text-align: left; padding: 5px 6px; border-bottom: 1px solid #E2E8F0; }
table.q td { padding: 6px; border-bottom: 1px solid #F1F5F9; vertical-align: top; font-size: 8.5pt; }
.c-num { width: 22px; text-align: center; color: #94A3B8; font-weight: 700; }
.c-pts { width: 52px; text-align: center; font-weight: 700; }
.c-st  { width: 78px; text-align: center; }
.q-text { font-weight: 600; color: #1E293B; }
.ans { font-size: 7.8pt; margin-top: 2px; }
.ans .lbl { color: #94A3B8; }
.ans .stu { color: #1E293B; }
.ans .cor { color: #059669; }
.fb { font-size: 7.6pt; color: #6D28D9; margin-top: 2px; font-style: italic; }

.st-tag { display: inline-block; padding: 2px 6px; border-radius: 8px; font-size: 7pt; font-weight: 800; }
.st-correct   { background: #D1FAE5; color: #065F46; }
.st-partial   { background: #FEF3C7; color: #92400E; }
.st-incorrect { background: #FEE2E2; color: #991B1B; }
.st-pending   { background: #E0E7FF; color: #3730A3; }
.st-blank     { background: #F1F5F9; color: #64748B; }

/* Footer signature */
.sign { margin-top: 28px; display: table; width: 100%; }
.sign-cell { display: table-cell; width: 50%; text-align: center; padding: 0 24px; }
.sign-line { border-top: 1px solid #94A3B8; margin-top: 34px; padding-top: 4px; font-size: 8pt; color: #64748B; }
.foot { margin-top: 14px; text-align: center; font-size: 7pt; color: #94A3B8; }
</style>
</head>
@php
    use Illuminate\Support\Str;

    $tz = 'America/Costa_Rica';
    $genAt       = now()->setTimezone($tz)->format('d/m/Y H:i');
    $submittedAt = $attempt->submitted_at
        ? $attempt->submitted_at->copy()->setTimezone($tz)->format('d/m/Y H:i')
        : '—';

    $hasCustomInstitution = ($institution !== 'SICORE');
    $brandSub = $hasCustomInstitution ? $institution : 'Módulo de Exámenes';

    $studentName = strtoupper($student?->full_name ?? ('ID: ' . $attempt->student_id));
    $cedula      = $student?->cedula ?? '—';
    $teacherName = $teacher?->full_name ?? '—';

    $pct     = $attempt->percentage !== null ? number_format($attempt->percentage, 1) : '0.0';
    $passed  = $attempt->percentage !== null && $attempt->percentage >= $exam->passing_score;
    $scoreColor = $passed ? '#059669' : '#DC2626';

    $duration = ($attempt->started_at && $attempt->submitted_at)
        ? $attempt->started_at->diffInMinutes($attempt->submitted_at) . ' min'
        : '—';

    $statusLabels = [
        'correct'   => ['Correcta',      'st-correct'],
        'partial'   => ['Parcial',       'st-partial'],
        'incorrect' => ['Incorrecta',    'st-incorrect'],
        'pending'   => ['Pendiente',     'st-pending'],
        'blank'     => ['Sin responder', 'st-blank'],
    ];

    $extraMin = (int) ($attempt->accessCode->extra_minutes ?? 0);
@endphp
<body>

    <div class="header">
        <div class="header-inner">
            <div class="h-logo"><div class="logo-box">S</div></div>
            <div class="h-info">
                <div class="brand-name">SICORE</div>
                <div class="brand-sub">{{ $brandSub }}</div>
            </div>
            <div class="h-right">
                <div class="doc-title">Boletín de Examen</div>
                <div class="doc-sub">{{ $genAt }}</div>
            </div>
        </div>
    </div>

    <div class="section-pad">
        {{-- Student + result --}}
        <div class="boxes">
            <div class="box">
                <div class="box-inner box-left">
                    <div class="name-big">{{ $studentName }}</div>
                    <div class="kv"><b>Cédula:</b> {{ $cedula }}</div>
                    @if($sectionName)<div class="kv"><b>Sección:</b> {{ $sectionName }}</div>@endif
                    <div class="kv"><b>Examen:</b> {{ $exam->title }}</div>
                    @if($subject)<div class="kv"><b>Materia:</b> {{ $subject->name }}</div>@endif
                    <div class="kv"><b>Docente:</b> {{ $teacherName }}</div>
                </div>
            </div>
            <div class="box">
                <div class="box-inner box-right">
                    <div class="result-row">
                        <div class="result-cell">
                            <div class="score-big" style="color: {{ $scoreColor }};">{{ $pct }}%</div>
                            <div class="pct">{{ number_format($attempt->score ?? 0, 1) }} / {{ number_format($attempt->max_score ?? 0, 1) }} pts</div>
                        </div>
                        <div class="result-cell" style="text-align: right;">
                            <span class="badge {{ $passed ? 'b-pass' : 'b-fail' }}">{{ $passed ? 'APROBADO' : 'REPROBADO' }}</span>
                        </div>
                    </div>
                    <div class="kv" style="margin-top: 8px;"><b>Entrega:</b> {{ $submittedAt }}</div>
                    <div class="kv"><b>Duración:</b> {{ $duration }}@if($extraMin > 0) <span style="color:#6D28D9;font-weight:700;">(+{{ $extraMin }} min adecuación)</span>@endif</div>
                    <div class="kv"><b>Nota mínima:</b> {{ rtrim(rtrim(number_format($exam->passing_score,1),'0'),'.') }}%</div>
                </div>
            </div>
        </div>

        @if($exam->proctoring && ($attempt->focus_loss_count ?? 0) > 0)
        <div class="proctor-note">
            &#9888; Monitoreo: se detectaron <b>{{ $attempt->focus_loss_count }}</b> salida(s) de la pantalla del examen.
        </div>
        @endif

        {{-- Per-question breakdown --}}
        <div class="sec-title">Desglose por pregunta</div>
        <table class="q">
            <thead>
                <tr>
                    <th class="c-num">#</th>
                    <th>Pregunta</th>
                    <th class="c-pts">Puntos</th>
                    <th class="c-st">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report as $r)
                @php [$stLabel, $stClass] = $statusLabels[$r['status']] ?? ['—', 'st-blank']; @endphp
                <tr>
                    <td class="c-num">{{ $r['num'] }}</td>
                    <td>
                        <div class="q-text">{{ Str::limit($r['text'], 160) }}</div>
                        @if($r['student'] !== null)
                        <div class="ans"><span class="lbl">Tu respuesta:</span> <span class="stu">{{ Str::limit($r['student'], 120) }}</span></div>
                        @endif
                        @if($r['correct'] !== null)
                        <div class="ans"><span class="lbl">Correcta:</span> <span class="cor">{{ Str::limit($r['correct'], 120) }}</span></div>
                        @endif
                        @if($r['feedback'])
                        <div class="fb">Obs.: {{ Str::limit($r['feedback'], 160) }}</div>
                        @endif
                    </td>
                    <td class="c-pts" style="color: {{ $r['earned'] !== null && $r['earned'] >= $r['points'] ? '#059669' : ($r['earned'] ? '#92400E' : '#94A3B8') }};">
                        {{ $r['earned'] !== null ? rtrim(rtrim(number_format($r['earned'],2),'0'),'.') : '—' }}/{{ rtrim(rtrim(number_format($r['points'],2),'0'),'.') }}
                    </td>
                    <td class="c-st"><span class="st-tag {{ $stClass }}">{{ $stLabel }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Signature --}}
        <div class="sign">
            <div class="sign-cell"><div class="sign-line">Firma del docente</div></div>
            <div class="sign-cell"><div class="sign-line">Firma del estudiante</div></div>
        </div>

        <div class="foot">Generado por SICORE &mdash; Módulo de Exámenes &middot; {{ $genAt }} (hora de Costa Rica)</div>
    </div>

</body>
</html>
