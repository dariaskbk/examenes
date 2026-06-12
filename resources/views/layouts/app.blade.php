<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SICORE Exámenes')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --indigo-600: #4F46E5;
            --indigo-700: #4338CA;
            --indigo-900: #312E81;
            --violet-500: #8B5CF6;
            --sidebar-w: 256px;
            --topbar-h: 60px;
            --radius: 12px;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #F0F2F8;
            color: #1E293B;
            margin: 0;
        }
        a { text-decoration: none; }

        /* ── Sidebar ───────────────────────────── */
        .sidebar {
            position: fixed; top: 0; left: 0;
            width: var(--sidebar-w); height: 100vh;
            background: #fff;
            border-right: 1px solid #E2E8F0;
            display: flex; flex-direction: column;
            z-index: 200;
        }
        .sidebar-logo {
            display: flex; align-items: center; gap: 10px;
            padding: 16px 20px;
            border-bottom: 1px solid #F1F5F9;
        }
        .logo-img {
            height: 28px;
            width: auto;
            display: block;
        }
        .logo-sub  { font-size: 0.68rem; color: #94A3B8; margin-top: 2px; }

        .sidebar-section { padding: 16px 12px 4px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #94A3B8; }

        .sidebar nav { padding: 0 10px; }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            color: #64748B;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            margin-bottom: 2px;
        }
        .nav-item i { font-size: 1rem; width: 20px; text-align: center; flex-shrink: 0; }
        .nav-item:hover { background: #F1F5F9; color: #1E293B; }
        .nav-item.active { background: linear-gradient(135deg, #EEF2FF, #F5F3FF); color: var(--indigo-600); font-weight: 600; }
        .nav-item.active i { color: var(--indigo-600); }

        .sidebar-footer {
            margin-top: auto;
            padding: 12px;
            border-top: 1px solid #F1F5F9;
        }
        .user-card {
            display: flex; align-items: center; gap: 10px;
            padding: 10px;
            border-radius: 8px;
            background: #F8FAFC;
        }
        .user-avatar {
            width: 34px; height: 34px; border-radius: 8px;
            background: linear-gradient(135deg, var(--indigo-600), var(--violet-500));
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 700; color: #fff;
            flex-shrink: 0;
        }
        .user-name { font-size: 0.8rem; font-weight: 600; color: #1E293B; line-height: 1.2; }
        .user-email { font-size: 0.68rem; color: #94A3B8; }

        /* ── Main ──────────────────────────────── */
        .main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }
        .topbar {
            height: var(--topbar-h);
            background: #fff;
            border-bottom: 1px solid #E2E8F0;
            display: flex; align-items: center;
            padding: 0 1.5rem;
            position: sticky; top: 0; z-index: 100;
            gap: 1rem;
        }
        .topbar-title { font-weight: 700; font-size: 1rem; color: #1E293B; }
        .topbar-breadcrumb { font-size: 0.8rem; color: #94A3B8; }
        .topbar-breadcrumb a { color: #64748B; }
        .topbar-breadcrumb a:hover { color: var(--indigo-600); }

        .page { padding: 1.5rem; flex: 1; }

        /* ── Cards ─────────────────────────────── */
        .card { border: 1px solid #E2E8F0; border-radius: var(--radius); background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
        .card-head { padding: .9rem 1.1rem; border-bottom: 1px solid #F1F5F9; display: flex; align-items: center; justify-content: space-between; }
        .card-head h6 { margin: 0; font-weight: 700; font-size: .875rem; color: #1E293B; }

        /* ── Badges ─────────────────────────────── */
        .badge-draft   { background:#F1F5F9; color:#475569; border:1px solid #E2E8F0; }
        .badge-active  { background:#D1FAE5; color:#065F46; border:1px solid #A7F3D0; }
        .badge-closed  { background:#FEE2E2; color:#991B1B; border:1px solid #FECACA; }
        .status-badge { border-radius: 20px; padding: 3px 10px; font-size: 0.7rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .status-badge::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; display:inline-block; }

        /* ── Buttons ─────────────────────────────── */
        .btn { border-radius: 8px; font-size: .85rem; font-weight: 500; }
        .btn-primary { background: var(--indigo-600); border-color: var(--indigo-600); }
        .btn-primary:hover { background: var(--indigo-700); border-color: var(--indigo-700); }
        .btn-indigo { background: linear-gradient(135deg, var(--indigo-600), var(--violet-500)); border: none; color: #fff !important; }
        .btn-indigo:hover { opacity: .9; }
        .btn-sm { padding: 0.3rem 0.65rem; font-size: .8rem; }

        /* ── Alerts ─────────────────────────────── */
        .alert { border-radius: var(--radius); border: none; font-size: .875rem; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-danger   { background: #FEE2E2; color: #991B1B; }
        .alert-info     { background: #DBEAFE; color: #1E40AF; }
        .alert-warning  { background: #FEF9C3; color: #854D0E; }

        /* ── Misc ─────────────────────────────── */
        .form-control, .form-select { border-radius: 8px; border: 1.5px solid #E2E8F0; font-size: .875rem; }
        .form-control:focus, .form-select:focus { border-color: var(--indigo-600); box-shadow: 0 0 0 3px rgba(79,70,229,.12); }
        .form-label { font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }

        .section-title { font-weight: 700; font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; color: #94A3B8; margin-bottom: .75rem; }
        .empty-state { text-align: center; padding: 3rem 1rem; color: #94A3B8; }
        .empty-state i { font-size: 2.5rem; display: block; margin-bottom: .75rem; opacity: .3; }

        .logout-btn { background: none; border: 1px solid #E2E8F0; border-radius: 6px; color: #64748B; font-size: .75rem; padding: 5px 10px; width: 100%; cursor: pointer; transition: all .15s; }
        .logout-btn:hover { background: #FEE2E2; color: #991B1B; border-color: #FECACA; }

        /* ── AppLoader overlay ─────────────────────── */
        #app-loader {
            position: fixed; inset: 0;
            background: rgba(15,23,42,.52);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }
        #app-loader.active { display: flex; }
        .app-loader-box {
            background: #fff;
            border-radius: 18px;
            padding: 2rem 2.5rem;
            text-align: center;
            box-shadow: 0 24px 64px rgba(0,0,0,.2);
            min-width: 260px;
            max-width: 340px;
            animation: loaderPop .22s cubic-bezier(.34,1.56,.64,1);
        }
        @keyframes loaderPop {
            from { opacity:0; transform:scale(.88); }
            to   { opacity:1; transform:scale(1); }
        }

        /* ── AppToast ──────────────────────────────── */
        #app-toast {
            position: fixed; bottom: 1.5rem; right: 1.5rem;
            z-index: 9998; min-width: 260px; max-width: 360px;
            pointer-events: none;
        }
        .app-toast-inner {
            border-radius: 12px;
            padding: .75rem 1rem;
            display: flex;
            align-items: center;
            gap: .6rem;
            box-shadow: 0 6px 24px rgba(0,0,0,.13);
            font-size: .85rem;
            font-weight: 600;
            animation: toastIn .25s ease;
        }
        @keyframes toastIn {
            from { opacity:0; transform:translateY(10px); }
            to   { opacity:1; transform:translateY(0); }
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div>
                <img src="{{ asset('images/sicore-logo.png') }}" alt="SICORE" class="logo-img">
                <div class="logo-sub">Módulo de Exámenes</div>
            </div>
        </div>

        <div class="sidebar-section">Principal</div>
        <nav>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
        </nav>

        <div class="sidebar-section">Actividades</div>
        <nav>
            <a href="{{ route('exams.index') }}" class="nav-item {{ request()->routeIs('exams.index') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i> Mis Actividades
            </a>
            <a href="{{ route('exams.create') }}" class="nav-item {{ request()->routeIs('exams.create') ? 'active' : '' }}">
                <i class="bi bi-plus-square"></i> Nueva Actividad
            </a>
            @php $pendingShares = \App\Http\Controllers\ExamShareController::pendingCountForCurrentUser(); @endphp
            <a href="{{ route('shares.index') }}" class="nav-item {{ request()->routeIs('shares.index') ? 'active' : '' }}">
                <i class="bi bi-share"></i> Compartidos
                @if($pendingShares > 0)
                <span class="badge ms-auto" style="background:#DC2626;color:#fff;font-size:.62rem;font-weight:700;border-radius:10px;padding:1px 7px;" title="Invitaciones pendientes por aceptar">{{ $pendingShares }}</span>
                @endif
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-card mb-2">
                <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div class="overflow-hidden">
                    <div class="user-name text-truncate">{{ Auth::user()->name }} {{ Auth::user()->last_name_1 }}</div>
                    <div class="user-email text-truncate">{{ Auth::user()->email }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión
                </button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <div class="main">
        <div class="topbar">
            <div class="flex-grow-1">
                @hasSection('breadcrumb')
                <div class="topbar-breadcrumb">@yield('breadcrumb')</div>
                @else
                <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
                @endif
            </div>
            <span class="text-muted" style="font-size:.75rem;">{{ now()->format('d \d\e F, Y') }}</span>
        </div>

        <div class="page">
            @foreach (['success','error','info','warning'] as $type)
                @if(session($type))
                <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show mb-3 d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-{{ $type === 'success' ? 'check-circle' : ($type === 'error' ? 'x-circle' : 'info-circle') }}-fill"></i>
                    <span>{{ session($type) }}</span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
                @endif
            @endforeach
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    {{-- ── AppLoader: full-page blocking overlay for long actions ─────────── --}}
    <div id="app-loader" role="status" aria-live="polite">
        <div class="app-loader-box">
            <div class="spinner-border mb-3" role="status" style="width:2.4rem;height:2.4rem;color:#4F46E5;border-width:3px;"></div>
            <div id="app-loader-msg" style="font-weight:700;font-size:.96rem;color:#1E293B;">Procesando…</div>
            <div id="app-loader-sub" style="font-size:.78rem;color:#64748B;margin-top:.3rem;min-height:1em;"></div>
        </div>
    </div>

    {{-- ── AppToast: non-blocking corner notification ──────────────────────── --}}
    <div id="app-toast" aria-live="polite"></div>

    <script>
    /* ── AppLoader ─────────────────────────────────────────────────────────── */
    const AppLoader = (() => {
        let el, msgEl, subEl, timer;
        function init() {
            el    = document.getElementById('app-loader');
            msgEl = document.getElementById('app-loader-msg');
            subEl = document.getElementById('app-loader-sub');
        }
        return {
            show(msg = 'Procesando…', sub = '') {
                if (!el) init();
                msgEl.textContent = msg;
                subEl.textContent = sub;
                el.classList.add('active');
                clearTimeout(timer);
            },
            hide() {
                if (!el) init();
                el.classList.remove('active');
                clearTimeout(timer);
            },
            autoHide(ms = 5000) {
                timer = setTimeout(() => this.hide(), ms);
            }
        };
    })();

    /* ── AppToast ──────────────────────────────────────────────────────────── */
    const AppToast = (() => {
        const STYLES = {
            success: { bg:'#D1FAE5', color:'#065F46', border:'#A7F3D0', icon:'check-circle-fill' },
            error:   { bg:'#FEE2E2', color:'#991B1B', border:'#FECACA', icon:'x-circle-fill' },
            info:    { bg:'#DBEAFE', color:'#1E40AF', border:'#BFDBFE', icon:'info-circle-fill' },
            warning: { bg:'#FEF9C3', color:'#854D0E', border:'#FDE68A', icon:'exclamation-triangle-fill' },
        };
        let timer;
        return {
            show(msg, type = 'success', duration = 3200) {
                const el = document.getElementById('app-toast');
                if (!el) return;
                const c = STYLES[type] || STYLES.info;
                el.innerHTML = `<div class="app-toast-inner" style="background:${c.bg};color:${c.color};border:1.5px solid ${c.border};">
                    <i class="bi bi-${c.icon}" style="font-size:1.05rem;flex-shrink:0;"></i>
                    <span>${msg}</span>
                </div>`;
                el.style.display = 'block';
                clearTimeout(timer);
                timer = setTimeout(() => { el.style.display = 'none'; }, duration);
            }
        };
    })();

    /* ── confirmAndLoad: SweetAlert2 confirm + loader then submit ──────────── */
    function confirmAndLoad(msg, form, loaderMsg = 'Procesando…', loaderSub = 'Por favor espera.') {
        Swal.fire({
            title: '¿Estás seguro?',
            text: msg,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#4F46E5',
            cancelButtonColor: '#94A3B8',
            borderRadius: '16px',
            customClass: { popup: 'swal-examcore' },
        }).then(result => {
            if (result.isConfirmed) {
                AppLoader.show(loaderMsg, loaderSub);
                form.submit();
            }
        });
        return false;
    }

    /* ── confirmDanger: red-confirm for destructive actions ─────────────────── */
    function confirmDanger(msg, form, loaderMsg = 'Eliminando…', loaderSub = 'Por favor espera.') {
        Swal.fire({
            title: '¿Eliminar?',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#94A3B8',
            customClass: { popup: 'swal-examcore' },
        }).then(result => {
            if (result.isConfirmed) {
                AppLoader.show(loaderMsg, loaderSub);
                form.submit();
            }
        });
        return false;
    }
    </script>
    @stack('scripts')
</body>
</html>
