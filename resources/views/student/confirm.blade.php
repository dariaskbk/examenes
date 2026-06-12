<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar examen — SICORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; min-height: 100vh; background: #F0F2F8; display: flex; align-items: center; justify-content: center; padding: 1rem; }

        .confirm-card {
            background: #fff; border-radius: 20px; overflow: hidden;
            width: 100%; max-width: 500px;
            box-shadow: 0 16px 48px rgba(0,0,0,.1);
        }
        .student-banner {
            background: linear-gradient(135deg, #312E81, #4F46E5 70%, #7C3AED);
            padding: 2rem;
            text-align: center;
            color: #fff;
        }
        .student-avatar {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: rgba(255,255,255,.2);
            border: 3px solid rgba(255,255,255,.4);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; font-weight: 800;
            margin: 0 auto 1rem;
        }
        .student-name { font-size: 1.2rem; font-weight: 800; margin-bottom: .2rem; }
        .student-id   { font-size: .82rem; opacity: .75; }

        .exam-body { padding: 1.75rem; }
        .exam-title { font-weight: 800; font-size: 1.1rem; color: #1E293B; margin-bottom: .25rem; }
        .exam-subject { font-size: .8rem; color: #64748B; margin-bottom: 1.25rem; }

        .meta-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: .75rem; margin-bottom: 1.25rem;
        }
        .meta-item {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: .75rem;
            text-align: center;
        }
        .meta-value { font-size: 1.1rem; font-weight: 800; color: #1E293B; }
        .meta-label { font-size: .68rem; color: #94A3B8; text-transform: uppercase; font-weight: 600; margin-top: 2px; }

        .config-pills { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: 1.25rem; }
        .config-pill { border-radius: 20px; padding: 3px 10px; font-size: .7rem; font-weight: 600; border: 1px solid; }

        .instructions-box {
            background: #F0F9FF; border: 1px solid #BAE6FD;
            border-radius: 10px; padding: .75rem .9rem;
            font-size: .82rem; color: #0369A1; margin-bottom: 1.25rem;
        }
        .warning-box {
            background: #FEF9C3; border: 1px solid #FDE68A;
            border-radius: 10px; padding: .75rem .9rem;
            font-size: .8rem; color: #854D0E; margin-bottom: 1.5rem;
        }
        .btn-back  { border: 1.5px solid #E2E8F0; border-radius: 10px; padding: .7rem 1.25rem; font-weight: 600; font-size: .875rem; background: #fff; color: #374151; cursor: pointer; text-decoration: none; display: inline-block; transition: .15s; }
        .btn-back:hover { border-color: #94A3B8; }
        .btn-start { border: none; border-radius: 10px; padding: .7rem 1.5rem; font-weight: 700; font-size: .9rem; background: linear-gradient(135deg, #059669, #10B981); color: #fff; cursor: pointer; flex: 1; transition: .2s; }
        .btn-start:hover { opacity: .9; transform: translateY(-1px); }

        /* ── Accesibilidad visual ─────────────────────────────────────────────
           Botón y panel fijos en px para que NO se escalen con el zoom de letra. */
        .a11y-fab {
            position: fixed; top: 16px; right: 16px; z-index: 700;
            display: flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: 10px;
            background: #4F46E5; border: none; color: #fff;
            font-size: 13px; font-weight: 700; cursor: pointer;
            box-shadow: 0 6px 18px rgba(79,70,229,.35); transition: background .15s;
        }
        .a11y-fab:hover { background: #4338CA; }
        .a11y-fab i { font-size: 15px; }

        .a11y-panel {
            position: fixed; top: 60px; right: 16px;
            background: #fff; border-radius: 16px; padding: 16px 18px;
            box-shadow: 0 16px 56px rgba(0,0,0,.22); z-index: 700;
            width: 300px; max-width: calc(100vw - 32px); display: none;
            border: 1px solid #E2E8F0; font-size: 14px;
        }
        .a11y-panel.open { display: block; }
        .a11y-section-title { font-size: 11px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: .07em; margin-bottom: 8px; }
        .a11y-fs-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 6px; margin-bottom: 16px; }
        .a11y-fs-btn {
            border: 2px solid #E2E8F0; border-radius: 10px; padding: 7px 4px;
            background: #F8FAFC; cursor: pointer; text-align: center;
            font-family: inherit; font-weight: 700; transition: all .15s; color: #374151; line-height: 1.2;
        }
        .a11y-fs-btn:hover { border-color: #A5B4FC; background: #EEF2FF; color: #4F46E5; }
        .a11y-fs-btn.active { border-color: #4F46E5; background: #4F46E5; color: #fff; }
        .a11y-fs-btn small { display:block; font-size:9px; font-weight:500; opacity:.75; margin-top:1px; }
        .a11y-toggle-row { display: flex; align-items: center; justify-content: space-between; padding: 9px 0; border-top: 1px solid #F1F5F9; font-size: 13px; font-weight: 600; color: #374151; gap: 12px; }
        .a11y-toggle-row span { flex: 1; }
        .a11y-toggle-desc { font-size: 11px; color: #94A3B8; font-weight: 400; display: block; margin-top: 1px; }
        .a11y-switch { width: 40px; height: 22px; border-radius: 11px; background: #E2E8F0; cursor: pointer; position: relative; transition: background .2s; flex-shrink: 0; border: none; outline: none; padding: 0; }
        .a11y-switch::after { content: ''; position: absolute; width: 18px; height: 18px; border-radius: 50%; background: #fff; top: 2px; left: 2px; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
        .a11y-switch.on { background: #4F46E5; }
        .a11y-switch.on::after { transform: translateX(18px); }
        .a11y-tip { margin-top: 11px; padding: 8px 10px; background: #EEF2FF; border-radius: 8px; font-size: 11px; color: #4338CA; line-height: 1.5; }

        /* ── Alto contraste ── */
        body.hc { background: #0D1117; }
        body.hc .confirm-card { background: #161B22; box-shadow: 0 16px 48px rgba(0,0,0,.55); }
        body.hc .exam-title { color: #F0F6FC; }
        body.hc .exam-subject { color: #94A3B8; }
        body.hc .meta-item { background: #0D1117; border-color: #30363D; }
        body.hc .meta-value { color: #F0F6FC; }
        body.hc .meta-label { color: #94A3B8; }
        body.hc .instructions-box { background: #0D1B2A; border-color: #1E3A5F; color: #93C5FD; }
        body.hc .warning-box { background: #2A2410; border-color: #5C4D1A; color: #FDE68A; }
        body.hc .btn-back { background: #161B22; border-color: #30363D; color: #F0F6FC; }

        /* ── Fuente dislexia ── */
        @font-face {
            font-family: 'OpenDyslexic';
            src: url('https://cdn.jsdelivr.net/npm/open-dyslexic@1.0.3/fonts/OpenDyslexic-Regular.otf') format('opentype');
            font-weight: normal; font-style: normal;
        }
        body.dyslexic .exam-title,
        body.dyslexic .exam-subject,
        body.dyslexic .meta-value,
        body.dyslexic .meta-label,
        body.dyslexic .instructions-box,
        body.dyslexic .warning-box,
        body.dyslexic .config-pill,
        body.dyslexic .student-name { font-family: 'OpenDyslexic', 'Comic Sans MS', cursive !important; }
    </style>
</head>
<body>
    {{-- Accessibility floating button --}}
    <button class="a11y-fab" id="a11yBtn" onclick="toggleA11yPanel()"
            title="Opciones de accesibilidad visual" aria-label="Accesibilidad">
        <i class="bi bi-universal-access"></i><span>Accesibilidad</span>
    </button>

    {{-- Accessibility panel --}}
    <div class="a11y-panel" id="a11yPanel" role="dialog" aria-label="Panel de accesibilidad">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span style="font-weight:700;font-size:14px;color:#1E293B;">
                <i class="bi bi-universal-access me-1" style="color:#4F46E5;"></i>Accesibilidad visual
            </span>
            <button onclick="toggleA11yPanel()" style="background:none;border:none;cursor:pointer;color:#94A3B8;font-size:14px;padding:0;line-height:1;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        {{-- Font size --}}
        <div class="a11y-section-title">Tamaño de letra</div>
        <div class="a11y-fs-grid">
            <button class="a11y-fs-btn" data-fs="16" onclick="setFontSize(16)" style="font-size:14px;">
                A<small>Normal</small>
            </button>
            <button class="a11y-fs-btn" data-fs="19" onclick="setFontSize(19)" style="font-size:16px;">
                A<small>Mediana</small>
            </button>
            <button class="a11y-fs-btn" data-fs="22" onclick="setFontSize(22)" style="font-size:18px;">
                A<small>Grande</small>
            </button>
            <button class="a11y-fs-btn" data-fs="25" onclick="setFontSize(25)" style="font-size:21px;">
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

    <div class="confirm-card">
        <div class="student-banner">
            <div class="student-avatar">{{ strtoupper(substr($accessCode->student->name, 0, 1)) }}</div>
            <div class="student-name">{{ $accessCode->student->full_name }}</div>
            <div class="student-id"><i class="bi bi-person-badge me-1"></i>CI: {{ $accessCode->student->cedula }}</div>
        </div>

        <div class="exam-body">
            <div class="exam-title">{{ $exam->title }}</div>
            @if($exam->subject_id)
            <div class="exam-subject"><i class="bi bi-book me-1"></i>{{ optional(\App\Models\Subject::find($exam->subject_id))->name }}</div>
            @endif

            <div class="meta-grid">
                @php
                    $extraMin = (int) ($accessCode->extra_minutes ?? 0);
                    $extraLabel = $extraMin > 0
                        ? ' <span style="color:#6D28D9;font-weight:700;">(+' . $extraMin . ' adecuación)</span>'
                        : '';
                @endphp
                <div class="meta-item">
                    <div class="meta-value"><i class="bi bi-clock text-primary" style="font-size:.95rem;"></i> {{ $exam->duration_minutes + $extraMin }}</div>
                    <div class="meta-label">minutos{!! $extraLabel !!}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-value">{{ $exam->questions_per_exam ?? \App\Models\ExamQuestion::where('exam_id', $exam->id)->count() }}</div>
                    <div class="meta-label">preguntas</div>
                </div>
                <div class="meta-item">
                    <div class="meta-value">{{ $exam->passing_score }}%</div>
                    <div class="meta-label">para aprobar</div>
                </div>
                <div class="meta-item">
                    <div class="meta-value {{ $accessCode->attemptsUsed() >= $exam->max_attempts ? 'text-danger' : 'text-success' }}">
                        {{ $accessCode->attemptsUsed() }}/{{ $exam->max_attempts }}
                    </div>
                    <div class="meta-label">intentos usados</div>
                </div>
            </div>

            <div class="config-pills">
                @if($exam->shuffle_questions)<span class="config-pill" style="background:#EEF2FF;color:#4F46E5;border-color:#C7D2FE;"><i class="bi bi-shuffle me-1"></i>Preguntas aleatorias</span>@endif
                @if($exam->shuffle_answers)<span class="config-pill" style="background:#FEF9C3;color:#854D0E;border-color:#FDE68A;"><i class="bi bi-shuffle me-1"></i>Respuestas aleatorias</span>@endif
                @if($exam->show_results)<span class="config-pill" style="background:#D1FAE5;color:#065F46;border-color:#A7F3D0;"><i class="bi bi-eye me-1"></i>Ver resultado</span>@endif
                @if($exam->max_attempts > 1)<span class="config-pill" style="background:#F5F3FF;color:#7C3AED;border-color:#DDD6FE;"><i class="bi bi-arrow-repeat me-1"></i>{{ $exam->max_attempts }} intentos</span>@endif
            </div>

            @if($exam->instructions)
            <div class="instructions-box">
                <strong><i class="bi bi-info-circle me-1"></i>Instrucciones:</strong><br>
                {{ $exam->instructions }}
            </div>
            @endif

            @if($activeAttempt)
            <div class="warning-box">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Tienes un intento en curso.</strong> Continuarás desde donde lo dejaste y el tiempo seguirá corriendo.
            </div>
            @else
            <div class="warning-box">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Una vez iniciado el examen el tiempo comenzará a correr y <strong>no podrás pausarlo</strong>.
            </div>
            @endif

            <div class="d-flex gap-2">
                <a href="{{ route('student.entry') }}" class="btn-back">
                    <i class="bi bi-arrow-left me-1"></i>Atrás
                </a>
                <form method="POST" action="{{ route('student.start', $accessCode->code) }}" style="flex:1;display:flex;">
                    @csrf
                    <button type="submit" class="btn-start">
                        <i class="bi bi-play-fill me-1"></i>
                        {{ $activeAttempt ? 'Continuar Examen' : 'Comenzar Examen' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    // ── Accesibilidad visual (claves compartidas con la pantalla del examen) ──
    const A11Y_LS_FS  = 'exam_a11y_fs';
    const A11Y_LS_HC  = 'exam_a11y_hc';
    const A11Y_LS_DYS = 'exam_a11y_dys';
    const FS_STEPS = [16, 19, 22, 25];

    function toggleA11yPanel() {
        document.getElementById('a11yPanel').classList.toggle('open');
    }
    document.addEventListener('click', e => {
        const panel = document.getElementById('a11yPanel');
        const btn   = document.getElementById('a11yBtn');
        if (panel.classList.contains('open') && !panel.contains(e.target) && !btn.contains(e.target)) {
            panel.classList.remove('open');
        }
    });

    function setFontSize(px) {
        document.documentElement.style.fontSize = px + 'px';
        document.querySelectorAll('.a11y-fs-btn').forEach(b => {
            b.classList.toggle('active', parseInt(b.dataset.fs, 10) === px);
        });
        try { localStorage.setItem(A11Y_LS_FS, px); } catch(e) {}
    }
    function stepFontSize(delta) {
        const current = parseInt(document.documentElement.style.fontSize || '16', 10);
        const idx = FS_STEPS.indexOf(current);
        const newIdx = Math.max(0, Math.min(FS_STEPS.length - 1, (idx === -1 ? 0 : idx) + delta));
        setFontSize(FS_STEPS[newIdx]);
    }
    function toggleHighContrast() {
        const on = document.body.classList.toggle('hc');
        document.getElementById('hcSwitch').classList.toggle('on', on);
        try { localStorage.setItem(A11Y_LS_HC, on ? '1' : '0'); } catch(e) {}
    }
    function toggleDyslexicFont() {
        const on = document.body.classList.toggle('dyslexic');
        document.getElementById('dyslexicSwitch').classList.toggle('on', on);
        try { localStorage.setItem(A11Y_LS_DYS, on ? '1' : '0'); } catch(e) {}
    }
    document.addEventListener('keydown', e => {
        if (!e.altKey) return;
        if (e.key === '+' || e.key === '=') { e.preventDefault(); stepFontSize(+1); }
        if (e.key === '-' || e.key === '_') { e.preventDefault(); stepFontSize(-1); }
    });

    // Restaurar preferencias guardadas
    (function applyA11yPrefs() {
        try {
            const fs  = localStorage.getItem(A11Y_LS_FS);
            const hc  = localStorage.getItem(A11Y_LS_HC);
            const dys = localStorage.getItem(A11Y_LS_DYS);
            if (fs && FS_STEPS.includes(parseInt(fs, 10))) setFontSize(parseInt(fs, 10));
            else setFontSize(16);
            if (hc === '1')  { document.body.classList.add('hc'); document.getElementById('hcSwitch')?.classList.add('on'); }
            if (dys === '1') { document.body.classList.add('dyslexic'); document.getElementById('dyslexicSwitch')?.classList.add('on'); }
        } catch(e) {}
    })();
    </script>
</body>
</html>
