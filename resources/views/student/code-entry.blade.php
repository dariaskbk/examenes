<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar al Examen — SICORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #F0F2F8;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .entry-wrap {
            display: flex;
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            width: 100%;
            max-width: 780px;
            box-shadow: 0 20px 60px rgba(0,0,0,.12);
        }

        /* Decorative side */
        .entry-deco {
            flex: 1;
            background: linear-gradient(145deg, #312E81, #4F46E5 60%, #7C3AED);
            padding: 2.5rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .entry-deco::after {
            content: '';
            position: absolute;
            width: 280px; height: 280px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
            bottom: -80px; right: -80px;
        }
        .deco-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 1.5rem;
        }
        .deco-icon {
            width: 54px; height: 54px;
            border-radius: 16px;
            background: rgba(255,255,255,.18);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            border: 1px solid rgba(255,255,255,.3);
        }
        .deco-icon i { color: #fff; font-size: 1.6rem; }
        .deco-logo {
            height: 70px;
            width: auto;
            filter: brightness(0) invert(1);
        }
        .entry-deco h2 { color: #fff; font-weight: 800; font-size: 1.5rem; margin-bottom: .5rem; }
        .entry-deco p  { color: rgba(255,255,255,.7); font-size: .85rem; line-height: 1.6; margin: 0; }
        .deco-steps    { margin-top: 1.5rem; list-style: none; padding: 0; }
        .deco-steps li {
            color: rgba(255,255,255,.8);
            font-size: .8rem;
            padding: .4rem 0;
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .step-num {
            width: 22px; height: 22px;
            border-radius: 50%;
            background: rgba(255,255,255,.2);
            border: 1px solid rgba(255,255,255,.4);
            display: flex; align-items: center; justify-content: center;
            font-size: .7rem; font-weight: 700; color: #fff;
            flex-shrink: 0;
        }

        /* Form side */
        .entry-form {
            width: 340px;
            flex-shrink: 0;
            padding: 2.5rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .entry-form h3 { font-weight: 800; font-size: 1.2rem; color: #1E293B; margin-bottom: .25rem; }
        .entry-form .sub { color: #64748B; font-size: .82rem; margin-bottom: 2rem; }

        .code-input-wrap { margin-bottom: 1.5rem; }
        .code-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #94A3B8; margin-bottom: .5rem; display: block; }
        .code-input {
            width: 100%;
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: .2em;
            text-align: center;
            border: 2px solid #E2E8F0;
            border-radius: 14px;
            padding: 1rem .5rem;
            text-transform: uppercase;
            color: #1E293B;
            background: #F8FAFC;
            transition: all .2s;
            outline: none;
        }
        .code-input:focus {
            border-color: #4F46E5;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(79,70,229,.1);
        }
        .code-input::placeholder { color: #CBD5E1; font-size: 1.3rem; }

        .btn-enter {
            width: 100%;
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            border: none; border-radius: 12px;
            padding: .85rem; font-weight: 700; font-size: .9rem; color: #fff;
            cursor: pointer; transition: all .2s;
        }
        .btn-enter:hover { opacity: .9; transform: translateY(-1px); }
        .btn-enter:active { transform: translateY(0); }

        .error-msg {
            background: #FEE2E2; color: #991B1B;
            border-radius: 10px; padding: .6rem .9rem;
            font-size: .82rem; margin-bottom: 1rem;
            display: flex; align-items: center; gap: .5rem;
        }

        .teacher-link {
            text-align: center; margin-top: 1.5rem;
            font-size: .78rem; color: #94A3B8;
        }
        .teacher-link a { color: #4F46E5; font-weight: 600; text-decoration: none; }
        .teacher-link a:hover { text-decoration: underline; }

        @media (max-width: 640px) {
            .entry-deco { display: none; }
            .entry-form { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="entry-wrap">
        <!-- Decorative side -->
        <div class="entry-deco">
            <div class="deco-brand">
                <div class="deco-icon"><i class="bi bi-mortarboard-fill"></i></div>
                <img src="{{ asset('images/sicore-logo.png') }}" alt="SICORE" class="deco-logo">
            </div>

            <h2>Módulo de Exámenes</h2>
            <p>Plataforma de evaluaciones online. Ingresa tu código personal para acceder a tu examen.</p>
            <ul class="deco-steps">
                <li><div class="step-num">1</div>Escribe el código que te dio tu docente</li>
                <li><div class="step-num">2</div>Confirma tus datos personales</li>
                <li><div class="step-num">3</div>Responde el examen y envíalo</li>
            </ul>
        </div>

        <!-- Form side -->
        <div class="entry-form">
            <h3>Acceso al Examen</h3>
            <p class="sub">Ingresa el código de acceso proporcionado por tu docente.</p>

            @if($errors->any())
            <div class="error-msg">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('student.verify') }}">
                @csrf
                <div class="code-input-wrap">
                    <span class="code-label">Código de acceso</span>
                    <input type="text" name="code" id="codeInput"
                           class="code-input"
                           placeholder="XXXX-XXXX"
                           value="{{ old('code') }}"
                           autocomplete="off"
                           maxlength="9"
                           spellcheck="false"
                           required>
                </div>
                <button type="submit" class="btn-enter">
                    <i class="bi bi-arrow-right-circle me-2"></i>Ingresar al Examen
                </button>
            </form>

            <div class="teacher-link">
                ¿Eres docente? <a href="{{ route('login') }}">Acceder al panel</a>
            </div>
        </div>
    </div>

    <script>
        const input = document.getElementById('codeInput');

        function formatCode(raw) {
            const clean = raw.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 8);
            return clean.length > 4 ? clean.slice(0,4) + '-' + clean.slice(4) : clean;
        }

        input.addEventListener('input', function(e) {
            e.target.value = formatCode(e.target.value);
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && e.target.value.endsWith('-')) {
                e.preventDefault();
                e.target.value = e.target.value.slice(0, -1);
            }
        });

        // Auto-fill from ?code= URL parameter (for direct links)
        const urlCode = new URLSearchParams(window.location.search).get('code');
        if (urlCode && !input.value) {
            input.value = formatCode(urlCode);
        }

        input.focus();
    </script>
</body>
</html>
