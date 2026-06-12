<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
/* ── Reset ───────────────────────────────────────────────────────────────── */
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 10pt;
    color: #1E293B;
    background: #fff;
}

/* ── Header (list / slips) ───────────────────────────────────────────────── */
.header {
    background: #4F46E5;
    padding: 12px 16px 14px;
    margin-bottom: 14px;
}
.header-inner {
    display: table;
    width: 100%;
}
.header-logo-cell {
    display: table-cell;
    vertical-align: middle;
    width: 52px;
}
.header-info-cell {
    display: table-cell;
    vertical-align: middle;
    padding-left: 12px;
}
.header-right-cell {
    display: table-cell;
    vertical-align: middle;
    text-align: right;
    width: 160px;
}
.logo-box {
    width: 44px; height: 44px;
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    text-align: center;
    line-height: 44px;
    color: #fff;
    font-size: 20pt;
    font-weight: 900;
    border: 2px solid rgba(255,255,255,0.35);
}
.brand-name {
    font-size: 9pt;
    color: #fff;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.brand-sub {
    font-size: 6.5pt;
    color: rgba(255,255,255,0.65);
    letter-spacing: 0.04em;
    margin-bottom: 3px;
}
.doc-title {
    font-size: 16pt;
    font-weight: 900;
    color: #fff;
    line-height: 1.15;
}
.doc-sub {
    font-size: 7.5pt;
    color: rgba(255,255,255,0.7);
    margin-top: 2px;
}
.badge-pill {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.4);
    color: #fff;
    border-radius: 20px;
    padding: 3px 10px;
    font-size: 7.5pt;
    font-weight: 700;
    margin-bottom: 4px;
}
.code-count {
    font-size: 22pt;
    font-weight: 900;
    color: #fff;
    line-height: 1;
}
.code-count-lbl {
    font-size: 6.5pt;
    color: rgba(255,255,255,0.7);
    text-transform: uppercase;
    letter-spacing: 0.07em;
}

/* ── Exam info strip ─────────────────────────────────────────────────────── */
.exam-strip {
    display: table;
    width: 100%;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    margin-bottom: 12px;
    overflow: hidden;
}
.exam-strip-left {
    display: table-cell;
    vertical-align: top;
    padding: 10px 14px;
    width: 55%;
    border-right: 1px solid #E2E8F0;
}
.exam-strip-right {
    display: table-cell;
    vertical-align: top;
    padding: 10px 14px;
    width: 45%;
    background: #F8FAFC;
}
.strip-label {
    font-size: 6.5pt;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94A3B8;
    font-weight: 700;
    margin-bottom: 2px;
}
.strip-value {
    font-size: 9pt;
    color: #1E293B;
    font-weight: 600;
    margin-bottom: 7px;
}
.strip-value.big {
    font-size: 11pt;
    font-weight: 900;
    color: #1E293B;
}
.strip-value.accent {
    color: #4F46E5;
    font-size: 7.5pt;
    font-weight: 400;
}

/* ── Stats row ───────────────────────────────────────────────────────────── */
.stats-row {
    display: table;
    width: 100%;
    margin-bottom: 14px;
    border-collapse: separate;
    border-spacing: 6px 0;
}
.stat-box {
    display: table-cell;
    text-align: center;
    border-radius: 8px;
    padding: 8px 4px;
    width: 25%;
}
.stat-box.indigo { background: #EEF2FF; border: 1px solid #C7D2FE; }
.stat-box.green  { background: #ECFDF5; border: 1px solid #A7F3D0; }
.stat-box.amber  { background: #FFFBEB; border: 1px solid #FDE68A; }
.stat-box.purple { background: #F5F3FF; border: 1px solid #DDD6FE; }
.stat-val { font-size: 16pt; font-weight: 900; line-height: 1.1; }
.stat-val.indigo { color: #4F46E5; }
.stat-val.green  { color: #059669; }
.stat-val.amber  { color: #D97706; }
.stat-val.purple { color: #7C3AED; }
.stat-lbl { font-size: 6pt; color: #64748B; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700; margin-top: 2px; }

/* ── Section title ───────────────────────────────────────────────────────── */
.section-title {
    font-size: 8pt;
    font-weight: 900;
    color: #4F46E5;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    border-left: 3px solid #4F46E5;
    padding-left: 7px;
    margin-bottom: 8px;
}

/* ── List table ──────────────────────────────────────────────────────────── */
.codes-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8.5pt;
}
.codes-table thead tr {
    background: #1E293B;
    color: #fff;
}
.codes-table thead th {
    padding: 7px 9px;
    text-align: left;
    font-size: 6.5pt;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 700;
}
.codes-table thead th.center { text-align: center; }
.codes-table tbody tr { border-bottom: 1px solid #F1F5F9; }
.codes-table tbody tr:nth-child(even) { background: #F8FAFC; }
.codes-table tbody tr:nth-child(odd)  { background: #fff; }
.codes-table td { padding: 6px 9px; vertical-align: middle; }
.num-cell   { color: #94A3B8; font-size: 7.5pt; width: 26px; text-align: center; font-weight: 700; }
.name-cell  { font-weight: 700; color: #1E293B; text-transform: uppercase; }
.name-sub   { font-size: 6.5pt; color: #94A3B8; font-weight: 400; margin-top: 1px; text-transform: none; }
.sec-cell   { color: #475569; font-size: 8pt; }
.code-cell  {
    font-family: 'Courier New', monospace;
    font-size: 11.5pt;
    font-weight: 900;
    color: #4F46E5;
    letter-spacing: 0.14em;
    background: #EEF2FF;
    border-radius: 5px;
    padding: 2px 6px;
    white-space: nowrap;
}
.status-submitted { color: #059669; font-weight: 700; font-size: 7.5pt; }
.status-pending   { color: #94A3B8; font-size: 7.5pt; }
.score-pass { color: #059669; font-weight: 900; }
.score-fail { color: #DC2626; font-weight: 900; }

/* ── Slips ───────────────────────────────────────────────────────────────── */
.slips-grid {
    width: 100%;
    border-collapse: collapse;
}
.slips-grid td {
    width: 33.33%;
    padding: 5px;
    vertical-align: top;
}
.slip {
    border: 1.5px dashed #CBD5E1;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    page-break-inside: avoid;
}
.slip-header {
    background: #4F46E5;
    padding: 5px 10px 6px;
    text-align: center;
}
.slip-header-brand {
    font-size: 6.5pt;
    color: #fff;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.slip-header-institution {
    font-size: 5pt;
    color: rgba(255,255,255,0.65);
    text-transform: uppercase;
    letter-spacing: 0.07em;
}
.slip-header-exam {
    font-size: 7pt;
    font-weight: 900;
    color: #fff;
    margin-top: 2px;
    border-top: 1px solid rgba(255,255,255,0.2);
    padding-top: 3px;
}
.slip-body {
    padding: 8px 10px 10px;
    text-align: center;
}
.slip-student {
    font-size: 8pt;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 1px;
    text-transform: uppercase;
}
.slip-section {
    font-size: 6pt;
    color: #94A3B8;
    margin-bottom: 7px;
}
.slip-code-wrap {
    background: #EEF2FF;
    border: 1.5px solid #C7D2FE;
    border-radius: 8px;
    padding: 5px 4px;
    margin-bottom: 6px;
}
.slip-code-label {
    font-size: 5.5pt;
    color: #94A3B8;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-bottom: 2px;
}
.slip-code {
    font-family: 'Courier New', monospace;
    font-size: 15pt;
    font-weight: 900;
    color: #4F46E5;
    letter-spacing: 0.18em;
}
.slip-status-ok {
    display: inline-block;
    background: #ECFDF5;
    color: #059669;
    border-radius: 20px;
    font-size: 6pt;
    font-weight: 700;
    padding: 2px 8px;
    margin-bottom: 5px;
}
.slip-url {
    font-size: 5pt;
    color: #CBD5E1;
    word-break: break-all;
}
.cut-hint {
    text-align: center;
    font-size: 6pt;
    color: #CBD5E1;
    padding: 4px 0 0;
    letter-spacing: 0.05em;
}

/* ── Padrón (portrait) ───────────────────────────────────────────────────── */
.padron-header-table {
    display: table;
    width: 100%;
    margin-bottom: 10px;
    border: 2px solid #1E293B;
}
.padron-seal-cell {
    display: table-cell;
    vertical-align: middle;
    width: 80px;
    text-align: center;
    padding: 8px;
    border-right: 2px solid #1E293B;
}
.padron-seal {
    width: 54px; height: 54px;
    border: 2.5px solid #4F46E5;
    border-radius: 50%;
    text-align: center;
    line-height: 54px;
    color: #4F46E5;
    font-size: 16pt;
    font-weight: 900;
    margin: 0 auto;
}
.padron-title-cell {
    display: table-cell;
    vertical-align: middle;
    padding: 8px 14px;
    text-align: center;
}
.padron-brand {
    font-size: 8.5pt;
    font-weight: 900;
    letter-spacing: 0.16em;
    color: #4F46E5;
    text-transform: uppercase;
    margin-bottom: 1px;
}
.padron-institution {
    font-size: 7pt;
    color: #64748B;
    margin-bottom: 3px;
}
.padron-doc-title {
    font-size: 14pt;
    font-weight: 900;
    color: #1E293B;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.padron-doc-subtitle {
    font-size: 7.5pt;
    color: #475569;
    margin-top: 2px;
}
.padron-date-cell {
    display: table-cell;
    vertical-align: middle;
    width: 80px;
    text-align: center;
    padding: 8px 6px;
    border-left: 2px solid #1E293B;
}
.pmeta-label {
    font-size: 5.5pt;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94A3B8;
    font-weight: 700;
    margin-bottom: 1px;
}
.padron-date-val {
    font-size: 9pt;
    font-weight: 900;
    color: #1E293B;
    margin-bottom: 6px;
}

.padron-meta {
    display: table;
    width: 100%;
    border: 1px solid #CBD5E1;
    border-radius: 6px;
    margin-bottom: 10px;
    background: #F8FAFC;
}
.padron-meta-cell {
    display: table-cell;
    padding: 6px 12px;
    font-size: 7.5pt;
    vertical-align: top;
    width: 25%;
    border-right: 1px solid #E2E8F0;
}
.padron-meta-cell:last-child { border-right: none; }
.pmeta-value { font-weight: 700; color: #1E293B; font-size: 8pt; }

.padron-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 7.5pt;
}
.padron-table thead tr {
    background: #1E293B;
    color: #fff;
}
.padron-table thead th {
    padding: 6px 7px;
    text-align: left;
    font-size: 6pt;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    font-weight: 700;
    border-right: 1px solid rgba(255,255,255,0.12);
}
.padron-table thead th:last-child { border-right: none; }
.padron-table tbody td {
    padding: 0;
    border-bottom: 1px solid #CBD5E1;
    border-right: 1px solid #E2E8F0;
    vertical-align: middle;
    height: 46px;
}
.padron-table tbody td:last-child { border-right: none; }
.padron-table tbody tr:nth-child(even) td { background: #F8FAFC; }
.padron-table tbody tr:nth-child(odd)  td { background: #fff; }
.padron-td-inner { padding: 4px 7px; }
/* column widths set directly on th/td — dompdf ignores colgroup */
.pcol-num  { width: 5%;  text-align: center; }
.pcol-name { width: 35%; }
.pcol-sec  { width: 9%;  }
.pcol-code { width: 13%; }
.pcol-sign { width: 38%; text-align: center; }

.padron-num  { color: #94A3B8; font-size: 7pt; font-weight: 700; }
.padron-name { font-weight: 700; color: #1E293B; font-size: 7.5pt; text-transform: uppercase; }
.padron-name-sub { font-size: 5.5pt; color: #059669; margin-top: 1px; text-transform: none; }
.padron-sec  { color: #475569; font-size: 7pt; }
.padron-code {
    font-family: 'Courier New', monospace;
    font-size: 8pt;
    font-weight: 900;
    color: #4F46E5;
    letter-spacing: 0.08em;
}
.padron-sign-inner {
    margin: 30px 16px 4px;
    border-bottom: 1.5px solid #64748B;
}
.padron-sign-label { font-size: 5.5pt; color: #94A3B8; text-align: center; }

.padron-footer-sigs {
    display: table;
    width: 100%;
    margin-top: 20px;
}
.padron-sig-block {
    display: table-cell;
    text-align: center;
    width: 33.33%;
    padding: 0 14px;
}
.padron-sig-line {
    border-top: 1.5px solid #1E293B;
    padding-top: 5px;
    margin-top: 40px;
}
.padron-sig-name { font-size: 8pt; font-weight: 700; color: #1E293B; }
.padron-sig-role { font-size: 6.5pt; color: #64748B; margin-top: 2px; }

.padron-legend {
    margin-top: 12px;
    padding: 6px 10px;
    background: #FFF7ED;
    border: 1px solid #FED7AA;
    border-radius: 6px;
    font-size: 6.5pt;
    color: #92400E;
}

/* ── Footer ──────────────────────────────────────────────────────────────── */
.footer {
    margin-top: 16px;
    padding-top: 8px;
    border-top: 1px solid #E2E8F0;
    font-size: 6.5pt;
    color: #94A3B8;
    display: table;
    width: 100%;
}
.footer-left  { display: table-cell; text-align: left; }
.footer-right { display: table-cell; text-align: right; }

/* page breaks */
.page-break { page-break-after: always; }
</style>
</head>
<body>

@php
    $actMeta     = $exam->activityMeta();
    $answered    = $lastAttempts->count();
    $pending     = $accessCodes->count() - $answered;
    $subjectName = $subject?->name ?? '—';
    $year        = $exam->year_id ? \App\Models\Year::find($exam->year_id)?->year : null;

    // Pre-compute conditionals to avoid Blade regex edge-cases with accented chars
    $hasCustomInstitution = ($institution !== 'SICORE');
    // Padron header: "Colegio XYZ — Módulo de Exámenes"  or just "Módulo de Exámenes"
    $padronInstitutionLine = $hasCustomInstitution
        ? $institution . ' &mdash; Módulo de Exámenes'
        : 'M&oacute;dulo de Ex&aacute;menes';
    // List/slips header sub-brand suffix
    $brandSuffix = $hasCustomInstitution ? ' &mdash; ' . $institution : '';
    // Footer doc-type label (no accented chars)
    $docTypeLabel = $format === 'padron' ? 'Padr&oacute;n de Firmas'
        : ($format === 'slips' ? 'Tiquetes de Acceso' : 'Lista de C&oacute;digos');
@endphp

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{--  FORMAT: PADRÓN DE FIRMAS (portrait)                                  --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
@if($format === 'padron')

    {{-- Padrón Header --}}
    <table class="padron-header-table">
        <tr>
            <td class="padron-seal-cell">
                <div class="padron-seal">S</div>
            </td>
            <td class="padron-title-cell">
                <div class="padron-brand">SICORE</div>
                <div class="padron-institution">{!! $padronInstitutionLine !!}</div>
                <div class="padron-doc-title">Padrón de Firmas</div>
                <div class="padron-doc-subtitle">Control de participación &mdash; {{ $actMeta['label'] }}</div>
            </td>
            <td class="padron-date-cell">
                <div class="pmeta-label">Fecha</div>
                <div class="padron-date-val">{{ now()->format('d/m/Y') }}</div>
                <div class="pmeta-label">Hora</div>
                <div class="padron-date-val">{{ now()->format('H:i') }}</div>
            </td>
        </tr>
    </table>

    {{-- Padrón Meta --}}
    <table class="padron-meta">
        <tr>
            <td class="padron-meta-cell">
                <div class="pmeta-label">Actividad</div>
                <div class="pmeta-value">{{ \Illuminate\Support\Str::limit($exam->title, 38) }}</div>
            </td>
            <td class="padron-meta-cell">
                <div class="pmeta-label">Materia</div>
                <div class="pmeta-value">{{ $subjectName }}</div>
            </td>
            <td class="padron-meta-cell">
                <div class="pmeta-label">Año lectivo</div>
                <div class="pmeta-value">{{ $year ?? '—' }}</div>
            </td>
            <td class="padron-meta-cell">
                <div class="pmeta-label">Duración / Nota mínima</div>
                <div class="pmeta-value">{{ $exam->duration_minutes }} min &nbsp;/&nbsp; {{ $exam->passing_score }}%</div>
            </td>
        </tr>
    </table>

    {{-- Padrón Table --}}
    <table class="padron-table" style="width:100%;">
        <thead>
            <tr>
                <th class="pcol-num">#</th>
                <th class="pcol-name">Nombre del Estudiante</th>
                <th class="pcol-sec">Secci&oacute;n</th>
                <th class="pcol-code">C&oacute;digo</th>
                <th class="pcol-sign">Firma del Estudiante</th>
            </tr>
        </thead>
        <tbody>
            @foreach($accessCodes as $i => $code)
            @php
                $student     = $students[$code->student_id] ?? null;
                $sectionName = $sectionNames[$code->student_id] ?? null;
                $lastAttempt = $lastAttempts[$code->id] ?? null;
                $fullName    = strtoupper($student?->full_name ?? 'ID: '.$code->student_id);
            @endphp
            <tr>
                <td class="pcol-num padron-num">
                    <div class="padron-td-inner" style="text-align:center;">{{ $i + 1 }}</div>
                </td>
                <td class="pcol-name">
                    <div class="padron-td-inner">
                        <div class="padron-name">{{ $fullName }}</div>
                        @if($lastAttempt)
                            <div class="padron-name-sub">&#10003; Entregado &mdash; {{ number_format($lastAttempt->percentage, 1) }}%</div>
                        @endif
                    </div>
                </td>
                <td class="pcol-sec padron-sec">
                    <div class="padron-td-inner">{{ $sectionName ?? '&mdash;' }}</div>
                </td>
                <td class="pcol-code padron-code">
                    <div class="padron-td-inner">{{ $code->code }}</div>
                </td>
                <td class="pcol-sign">
                    <div class="padron-sign-inner"></div>
                    <div class="padron-sign-label">Firma</div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Legend --}}
    <div class="padron-legend">
        <strong>Instrucción:</strong> Cada estudiante debe firmar en la casilla correspondiente a su nombre al momento de iniciar o finalizar la actividad.
        El código de acceso es de uso personal e intransferible. Conserve este documento para sus registros.
    </div>

    {{-- Signature blocks --}}
    <table class="padron-footer-sigs">
        <tr>
            <td class="padron-sig-block">
                <div class="padron-sig-line">
                    <div class="padron-sig-name">___________________________</div>
                    <div class="padron-sig-role">Docente responsable</div>
                </div>
            </td>
            <td class="padron-sig-block">
                <div class="padron-sig-line">
                    <div class="padron-sig-name">___________________________</div>
                    <div class="padron-sig-role">Visto bueno / Coordinación</div>
                </div>
            </td>
            <td class="padron-sig-block">
                <div class="padron-sig-line">
                    <div class="padron-sig-name">___________________________</div>
                    <div class="padron-sig-role">Sello institucional</div>
                </div>
            </td>
        </tr>
    </table>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{--  FORMAT: LIST  /  SLIPS                                               --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
@else

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-inner">
            <div class="header-logo-cell">
                <div class="logo-box">S</div>
            </div>
            <div class="header-info-cell">
                <div class="brand-name">SICORE</div>
                <div class="brand-sub">
                    M&oacute;dulo de Ex&aacute;menes{!! $brandSuffix !!}
                </div>
                <div class="doc-title">Códigos de Acceso</div>
                <div class="doc-sub">
                    {{ $format === 'slips' ? 'Tiquetes para recortar y distribuir' : 'Lista completa' }}
                    &nbsp;&mdash;&nbsp; {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>
            <div class="header-right-cell">
                <div class="badge-pill">{{ $actMeta['label'] }}</div>
                <div class="code-count">{{ $accessCodes->count() }}</div>
                <div class="code-count-lbl">Código{{ $accessCodes->count() != 1 ? 's' : '' }}</div>
            </div>
        </div>
    </div>

    {{-- ── Exam info strip ─────────────────────────────────────────────── --}}
    <table class="exam-strip">
        <tr>
            <td class="exam-strip-left">
                <div class="strip-label">Actividad</div>
                <div class="strip-value big">{{ $exam->title }}</div>
                <div class="strip-label">Materia</div>
                <div class="strip-value">{{ $subjectName }}</div>
                <div class="strip-label">URL de acceso</div>
                <div class="strip-value accent">{{ $entryUrl }}</div>
            </td>
            <td class="exam-strip-right">
                <div class="strip-label">Año lectivo</div>
                <div class="strip-value">{{ $year ?? '—' }}</div>
                <div class="strip-label">Duración</div>
                <div class="strip-value">{{ $exam->duration_minutes }} minutos</div>
                <div class="strip-label">Nota mínima de aprobación</div>
                <div class="strip-value">{{ $exam->passing_score }}%</div>
                <div class="strip-label">Intentos máximos permitidos</div>
                <div class="strip-value">{{ $exam->max_attempts }}</div>
            </td>
        </tr>
    </table>

    {{-- ── Stats ─────────────────────────────────────────────────────────── --}}
    <table class="stats-row">
        <tr>
            <td class="stat-box indigo">
                <div class="stat-val indigo">{{ $accessCodes->count() }}</div>
                <div class="stat-lbl">Total códigos</div>
            </td>
            <td class="stat-box green" style="padding-left:6px;">
                <div class="stat-val green">{{ $answered }}</div>
                <div class="stat-lbl">Entregados</div>
            </td>
            <td class="stat-box amber" style="padding-left:6px;">
                <div class="stat-val amber">{{ $pending }}</div>
                <div class="stat-lbl">Pendientes</div>
            </td>
            <td class="stat-box purple" style="padding-left:6px;">
                <div class="stat-val purple">{{ $exam->max_attempts }}</div>
                <div class="stat-lbl">Int. máx.</div>
            </td>
        </tr>
    </table>

    {{-- ── Content ───────────────────────────────────────────────────────── --}}
    @if($format === 'slips')

        <div class="cut-hint">&#9988; &mdash; Recorte por las líneas punteadas y entregue cada tiquete al estudiante correspondiente &mdash; &#9988;</div>
        <br>

        @php $chunks = $accessCodes->chunk(3); @endphp
        @foreach($chunks as $chunk)
        <table class="slips-grid">
            <tr>
                @foreach($chunk as $code)
                @php
                    $student     = $students[$code->student_id] ?? null;
                    $sectionName = $sectionNames[$code->student_id] ?? null;
                    $directUrl   = $entryUrl . '?code=' . $code->code;
                    $lastAttempt = $lastAttempts[$code->id] ?? null;
                    $fullName    = strtoupper($student?->full_name ?? 'ID: '.$code->student_id);
                @endphp
                <td>
                    <div class="slip">
                        <div class="slip-header">
                            <div class="slip-header-brand">SICORE</div>
                            @if($institution !== 'SICORE')
                                <div class="slip-header-institution">{{ $institution }}</div>
                            @endif
                            <div class="slip-header-exam">{{ \Illuminate\Support\Str::limit($exam->title, 48) }}</div>
                        </div>
                        <div class="slip-body">
                            <div class="slip-student">{{ $fullName }}</div>
                            @if($sectionName)
                                <div class="slip-section">Sección: {{ $sectionName }} &nbsp;|&nbsp; {{ $actMeta['label'] }}</div>
                            @else
                                <div class="slip-section">{{ $actMeta['label'] }}</div>
                            @endif
                            <div class="slip-code-wrap">
                                <div class="slip-code-label">Código de acceso</div>
                                <div class="slip-code">{{ $code->code }}</div>
                            </div>
                            @if($lastAttempt)
                                <div class="slip-status-ok">&#10003; Entregado &mdash; {{ number_format($lastAttempt->percentage, 1) }}%</div>
                            @endif
                            <div class="slip-url">{{ $directUrl }}</div>
                        </div>
                    </div>
                </td>
                @endforeach
                {{-- Fill empty cells --}}
                @for($i = $chunk->count(); $i < 3; $i++)
                <td></td>
                @endfor
            </tr>
        </table>
        @endforeach

    @else

        {{-- LIST TABLE --}}
        <div class="section-title">Detalle de códigos asignados</div>
        <table class="codes-table">
            <thead>
                <tr>
                    <th style="width:26px;text-align:center;">#</th>
                    <th>Estudiante</th>
                    <th style="width:75px;">Sección</th>
                    <th style="width:120px;">Código de Acceso</th>
                    <th style="width:85px;" class="center">Estado</th>
                    <th style="width:65px;" class="center">Nota</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accessCodes as $i => $code)
                @php
                    $student     = $students[$code->student_id] ?? null;
                    $sectionName = $sectionNames[$code->student_id] ?? null;
                    $lastAttempt = $lastAttempts[$code->id] ?? null;
                    $fullName    = strtoupper($student?->full_name ?? 'ID: '.$code->student_id);
                @endphp
                <tr>
                    <td class="num-cell">{{ $i + 1 }}</td>
                    <td class="name-cell">
                        {{ $fullName }}
                        <div class="name-sub">{{ $entryUrl }}?code={{ $code->code }}</div>
                    </td>
                    <td class="sec-cell">{{ $sectionName ?? '—' }}</td>
                    <td><span class="code-cell">{{ $code->code }}</span></td>
                    <td style="text-align:center;">
                        @if($lastAttempt)
                            <span class="status-submitted">&#10003; Entregado</span>
                        @else
                            <span class="status-pending">Pendiente</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($lastAttempt)
                            <span class="{{ $lastAttempt->percentage >= $exam->passing_score ? 'score-pass' : 'score-fail' }}">
                                {{ number_format($lastAttempt->percentage, 1) }}%
                            </span>
                        @else
                            <span class="status-pending">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    @endif

@endif

{{-- ── Footer ─────────────────────────────────────────────────────────────── --}}
<div class="footer">
    <div class="footer-left">
        SICORE &mdash; Módulo de Exámenes &nbsp;|&nbsp;
        {!! $docTypeLabel !!}
        &nbsp;|&nbsp; Documento confidencial
    </div>
    <div class="footer-right">{{ now()->format('d/m/Y H:i') }}</div>
</div>

</body>
</html>
