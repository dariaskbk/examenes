<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Docente — SICORE Exámenes</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #F0F2F8;
        }

        /* Left panel */
        .left-panel {
            flex: 1;
            background: linear-gradient(145deg, #1E1B4B 0%, #312E81 45%, #4F46E5 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        .left-panel::before {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: rgba(255,255,255,.04);
            top: -100px; right: -100px;
        }
        .left-panel::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,.04);
            bottom: -80px; left: -80px;
        }
        .left-content { position: relative; z-index: 1; max-width: 360px; }
        .app-logo {
            margin-bottom: 1.75rem;
        }
        .app-logo img {
            height: 44px;
            width: auto;
            /* Convierte el logo negro en blanco para el fondo oscuro */
            filter: brightness(0) invert(1);
        }
        .left-content h1 { color: #fff; font-weight: 800; font-size: 2rem; margin-bottom: .5rem; }
        .left-content p  { color: rgba(255,255,255,.65); font-size: .95rem; line-height: 1.6; }
        .feature-list { list-style: none; padding: 0; margin-top: 2rem; }
        .feature-list li {
            color: rgba(255,255,255,.8);
            font-size: .875rem;
            padding: .5rem 0;
            display: flex;
            align-items: center;
            gap: .75rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .feature-list li i { color: #A5B4FC; font-size: 1rem; width: 20px; text-align: center; }

        /* Right panel */
        .right-panel {
            width: 440px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: #fff;
        }
        .login-box { width: 100%; max-width: 360px; }
        .login-box h2 { font-weight: 800; font-size: 1.5rem; color: #1E293B; margin-bottom: .25rem; }
        .login-box .subtitle { color: #64748B; font-size: .875rem; margin-bottom: 2rem; }

        .form-label { font-size: .78rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }
        .form-control {
            border: 1.5px solid #E2E8F0; border-radius: 10px;
            padding: .65rem .9rem; font-size: .875rem;
            transition: all .15s;
        }
        .form-control:focus {
            border-color: #4F46E5;
            box-shadow: 0 0 0 3px rgba(79,70,229,.12);
        }
        .input-icon-wrap { position: relative; }
        .input-icon-wrap i {
            position: absolute; left: .85rem; top: 50%;
            transform: translateY(-50%);
            color: #94A3B8; font-size: .9rem;
            pointer-events: none;
        }
        .input-icon-wrap .form-control { padding-left: 2.4rem; }

        .btn-login {
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            border: none; border-radius: 10px;
            padding: .75rem; font-weight: 700; font-size: .9rem;
            width: 100%; color: #fff;
            transition: all .2s;
            cursor: pointer;
        }
        .btn-login:hover { opacity: .9; transform: translateY(-1px); }

        .divider {
            display: flex; align-items: center; gap: .75rem;
            color: #94A3B8; font-size: .75rem; margin: 1.5rem 0;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px; background: #E2E8F0;
        }

        .student-link-btn {
            display: flex; align-items: center; justify-content: center;
            gap: .5rem;
            border: 1.5px solid #E2E8F0; border-radius: 10px;
            padding: .65rem;
            color: #374151; font-size: .875rem; font-weight: 500;
            text-decoration: none;
            transition: all .15s;
        }
        .student-link-btn:hover { border-color: #4F46E5; color: #4F46E5; background: #F5F3FF; }

        @media (max-width: 768px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; }
        }
    </style>
</head>
<body>
    <!-- Left decorative panel -->
    <div class="left-panel">
        <div class="left-content">
            <div class="app-logo">
                <img src="{{ asset('images/sicore-logo.png') }}" alt="SICORE">
            </div>
            <h1>Módulo de Exámenes</h1>
            <p>Plataforma de exámenes online integrada con el sistema académico SICORE.</p>
            <ul class="feature-list">
                <li><i class="bi bi-lightning-charge"></i>Creación rápida de exámenes</li>
                <li><i class="bi bi-file-earmark-excel"></i>Importación masiva desde Excel</li>
                <li><i class="bi bi-shuffle"></i>Preguntas y respuestas aleatorias</li>
                <li><i class="bi bi-graph-up"></i>Resultados y estadísticas en tiempo real</li>
                <li><i class="bi bi-key"></i>Acceso seguro con código único por estudiante</li>
            </ul>
        </div>
    </div>

    <!-- Right login panel -->
    <div class="right-panel">
        <div class="login-box">
            <h2>Bienvenido</h2>
            <p class="subtitle">Inicia sesión con tu cuenta de docente.</p>

            @if($errors->any())
            <div class="alert d-flex align-items-center gap-2 mb-3 py-2 px-3"
                 style="background:#FEE2E2;color:#991B1B;border-radius:10px;border:none;font-size:.85rem;">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-envelope"></i>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email') }}" placeholder="correo@institucion.edu" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="password" class="form-control"
                               placeholder="••••••••" required>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember" style="font-size:.8rem;color:#64748B;">Recordar sesión</label>
                    </div>
                </div>
                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                </button>
            </form>

            <div class="divider">o si eres estudiante</div>

            <a href="{{ route('student.entry') }}" class="student-link-btn">
                <i class="bi bi-qr-code"></i>
                Ingresar con código de examen
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
