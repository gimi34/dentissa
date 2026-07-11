<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dentissa · @yield('title', 'Dashboard')</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --p50:  #FDF5F8;
            --p100: #FBEAF0;
            --p200: #F4C0D1;
            --p300: #ED93B1;
            --p400: #D4537E;
            --p500: #B83C65;
            --p600: #993556;
            --p800: #72243E;
            --sidebar-w: 242px;
            --text:        #000000;
            --text-muted:  #000000;
            --text-light:  #C0A0AC;
            --border:      #F0DCE4;
            --bg:          #FDF5F8;
            --white:       #FFFFFF;
            --green:       #1D9E75;
            --green-bg:    #E1F5EE;
            --amber:       #854F0B;
            --amber-bg:    #FAEEDA;
            --red:         #A32D2D;
            --red-bg:      #FCEBEB;
            --blue:        #185FA5;
            --blue-bg:     #E6F1FB;
            --radius:      12px;
            --radius-sm:   8px;
            --radius-lg:   16px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }

        /* ── Sidebar ─────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: #c75078 ;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
        }

        .sidebar-logo {
            padding: 22px 18px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon {
            width: 34px; height: 34px;
            background: var(--p400);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            font-weight: 600;
            color: var(--p600);
            letter-spacing: -0.2px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 14px 10px;
            overflow-y: auto;
        }

        .nav-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-light);
            padding: 10px 10px 4px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 12px;
            border-radius: var(--radius-sm);
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 400;
            transition: all 0.15s ease;
            margin-bottom: 1px;
        }

        .nav-item:hover { background: var(--p100); color: var(--p600); }
        .nav-item.active { background: var(--p100); color: var(--p600); font-weight: 500; }
        .nav-item svg { width: 17px; height: 17px; flex-shrink: 0; }

        .sidebar-user { padding: 14px; border-top: 1px solid var(--border); }

        .user-card {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px;
            border-radius: var(--radius-sm);
            background: var(--p50);
            margin-bottom: 8px;
        }

        .user-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: var(--p200);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            font-weight: 600;
            color: var(--p800);
            flex-shrink: 0;
        }

        .user-name { font-size: 13px; font-weight: 500; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 11px; color: var(--text-muted); margin-top: 1px; }

        .btn-logout {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 7px;
            border-radius: var(--radius-sm);
            background: transparent;
            color: var(--text-muted);
            font-size: 12.5px;
            font-family: 'DM Sans', sans-serif;
            border: none;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-logout:hover { background: var(--p100); color: var(--p600); }

        /* ── Main ─────────────────────────────────── */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* ── Topbar ───────────────────────────────── */
        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title { font-family: 'Playfair Display', serif; font-size: 19px; font-weight: 500; color: var(--text); }
        .topbar-actions { display: flex; align-items: center; gap: 10px; }

        /* ── Buttons ──────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn svg { width: 15px; height: 15px; }

        .btn-primary { background: var(--p400); color: var(--white); }
        .btn-primary:hover { background: var(--p500); }

        .btn-ghost { background: transparent; color: var(--text-muted); }
        .btn-ghost:hover { background: var(--p100); color: var(--p600); }

        .btn-outline { background: var(--white); color: var(--p600); border: 1px solid var(--p200); }
        .btn-outline:hover { background: var(--p100); }

        .btn-sm { padding: 5px 12px; font-size: 12px; }

        /* ── Content ──────────────────────────────── */
        .content { flex: 1; padding: 28px 28px; }

        /* ── Cards ────────────────────────────────── */
        .card {
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 22px;
        }
        .card-0 { padding: 0; overflow: hidden; }

        /* ── Stats grid ───────────────────────────── */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 22px; }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 18px 20px;
        }

        .stat-icon {
            width: 34px; height: 34px;
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 14px;
        }
        .stat-icon svg { width: 17px; height: 17px; }

        .stat-value { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 600; color: var(--text); line-height: 1; }
        .stat-label { font-size: 12.5px; color: var(--text-muted); margin-top: 4px; }

        .stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 11px;
            font-weight: 500;
            margin-top: 10px;
            padding: 2px 8px;
            border-radius: 20px;
        }

        /* ── Section head ─────────────────────────── */
        .section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
        .section-title { font-size: 15px; font-weight: 500; color: var(--text); }

        /* ── Grid layouts ─────────────────────────── */
        .grid-auto { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .grid-2    { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .grid-3    { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }

        /* ── Table ────────────────────────────────── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--text-light);
            padding: 10px 16px;
            border-bottom: 1px solid var(--border);
        }
        td { padding: 11px 16px; font-size: 13px; color: var(--text); border-bottom: 1px solid #FDF0F4; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--p50); }

        /* ── Badges ───────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
        }
        .badge-pink   { background: var(--p100);    color: var(--p600); }
        .badge-green  { background: var(--green-bg); color: var(--green); }
        .badge-amber  { background: var(--amber-bg); color: var(--amber); }
        .badge-red    { background: var(--red-bg);   color: var(--red); }
        .badge-gray   { background: var(--p50);      color: var(--text-muted); }
        .badge-blue   { background: var(--blue-bg);  color: var(--blue); }

        /* ── Timeline ─────────────────────────────── */
        .timeline { display: flex; flex-direction: column; }
        .timeline-item {
            display: flex;
            gap: 14px;
            padding: 13px 0;
            border-bottom: 1px solid #FDF0F4;
        }
        .timeline-item:last-child { border-bottom: none; }
        .timeline-time { width: 48px; flex-shrink: 0; font-size: 12px; color: var(--text-muted); padding-top: 1px; text-align: right; }
        .timeline-dot { width: 10px; height: 10px; border-radius: 50%; margin-top: 4px; flex-shrink: 0; }
        .timeline-content { flex: 1; min-width: 0; }
        .timeline-title { font-size: 13.5px; font-weight: 500; color: var(--text); }
        .timeline-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        /* ── Welcome banner ───────────────────────── */
        .welcome-banner {
            background: linear-gradient(135deg, var(--p400) 0%, var(--p600) 100%);
            border-radius: var(--radius-lg);
            padding: 26px 30px;
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            position: relative;
            overflow: hidden;
        }
        .welcome-banner::before {
            content: '';
            position: absolute; right: -30px; top: -40px;
            width: 180px; height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
        }
        .welcome-banner::after {
            content: '';
            position: absolute; right: 80px; bottom: -50px;
            width: 130px; height: 130px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }
        .welcome-title { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 600; margin-bottom: 4px; }
        .welcome-sub { font-size: 13.5px; opacity: 0.85; }
        .welcome-actions { display: flex; gap: 10px; z-index: 1; }
        .btn-white { background: white; color: var(--p600); font-weight: 500; }
        .btn-white:hover { background: var(--p50); }
        .btn-white-outline { background: transparent; color: white; border: 1.5px solid rgba(255,255,255,0.5); }
        .btn-white-outline:hover { background: rgba(255,255,255,0.12); }

        /* ── Quick actions ────────────────────────── */
        .quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .quick-action {
            background: var(--p50);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 14px 12px;
            text-align: center;
            text-decoration: none;
            color: var(--text);
            transition: all 0.15s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .quick-action:hover { border-color: var(--p300); background: var(--p100); transform: translateY(-1px); }
        .quick-action svg { width: 20px; height: 20px; color: var(--p500); }
        .quick-action span { font-size: 12px; font-weight: 500; color: var(--text-muted); }

        /* ── Appointment card ─────────────────────── */
        .appt-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }
        .appt-date-box {
            background: var(--p100);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            text-align: center;
            flex-shrink: 0;
            min-width: 56px;
        }
        .appt-day { font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 600; color: var(--p600); line-height: 1; }
        .appt-month { font-size: 10px; font-weight: 600; color: var(--p400); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 2px; }

        /* ── Empty state ──────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 36px 20px;
            color: var(--text-muted);
        }
        .empty-state svg { width: 40px; height: 40px; color: var(--p200); margin: 0 auto 12px; display: block; }
        .empty-state p { font-size: 13.5px; }

        /* ── Alert / stock ────────────────────────── */
        .alert-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #FDF0F4;
        }
        .alert-item:last-child { border-bottom: none; }
        .alert-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

        /* ── Scrollbar ────────────────────────────── */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: var(--p200); border-radius: 2px; }
        ::-webkit-scrollbar-track { background: transparent; }
    </style>
    @stack('styles')
</head>
<body>

<!-- ══ SIDEBAR ══════════════════════════════════════════════ -->
<aside class="sidebar">
<div class="sidebar-logo">
    <img src="{{ asset('images/logo-icon.png') }}"
         alt="Melissa López N."
         style="width:100%; max-width:160px; object-fit:contain; display:block; margin:0 auto; mix-blend-mode:screen;">
</div>
    </div>

    <nav class="sidebar-nav">
        @if(auth()->user()->rol === 'admin')
            <span class="nav-label">Principal</span>
            <a href="/admin" class="nav-item {{ request()->is('admin') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                </svg>
                Dashboard
            </a>
            <a href="/admin/citas" class="nav-item {{ request()->is('admin/citas*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <path d="M16 2v4M8 2v4M3 10h18"/>
                </svg>
                Citas
            </a>
            <a href="/admin/pacientes" class="nav-item {{ request()->is('admin/pacientes*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Pacientes
            </a>
            <span class="nav-label">Clínica</span>
            <a href="/admin/personal" class="nav-item {{ request()->is('admin/personal*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                    <path d="M12 11v2M11 13h2"/>
                </svg>
                Personal
            </a>
            <a href="/admin/servicios" class="nav-item {{ request()->is('admin/servicios*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                Servicios
            </a>
            <span class="nav-label">Análisis</span>
            <a href="/admin/reportes" class="nav-item {{ request()->is('admin/reportes*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M18 20V10M12 20V4M6 20v-6"/>
                </svg>
                Reportes
            </a>
            <a href="/admin/usuarios" class="nav-item {{ request()->is('admin/usuarios*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    <path d="M16 3.5a4 4 0 0 1 0 7M20 20c0-3-1.8-5.5-4-6.5"/>
                </svg>
                Usuarios
            </a>

        @elseif(auth()->user()->rol === 'dentista')
            <span class="nav-label">Principal</span>
            <a href="/dentista" class="nav-item {{ request()->is('dentista') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                </svg>
                Dashboard
            </a>
            <a href="/dentista/citas" class="nav-item {{ request()->is('dentista/citas*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                </svg>
                Mis Citas
            </a>
            <a href="/dentista/pacientes" class="nav-item {{ request()->is('dentista/pacientes*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
                Pacientes
            </a>
            <a href="/dentista/expedientes" class="nav-item {{ request()->is('dentista/expedientes*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                Expedientes
            </a>

        @else {{-- cliente --}}
            <span class="nav-label">Mi cuenta</span>
            <a href="/cliente" class="nav-item {{ request()->is('cliente') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                </svg>
                Inicio
            </a>
            <a href="/cliente/citas" class="nav-item {{ request()->is('cliente/citas*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                </svg>
                Mis Citas
            </a>
            <a href="/cliente/agendar" class="nav-item {{ request()->is('cliente/agendar*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="16"/>
                    <line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
                Agendar Cita
            </a>
            <a href="/cliente/perfil" class="nav-item {{ request()->is('cliente/perfil*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Mi Perfil
            </a>
        @endif
    </nav>

    <div class="sidebar-user">
        <div class="user-card">
            <div class="user-avatar">
                {{ strtoupper(substr(auth()->user()->nombre, 0, 1)) }}{{ strtoupper(substr(auth()->user()->apellido, 0, 1)) }}
            </div>
            <div style="min-width:0">
                <div class="user-name">{{ auth()->user()->nombre }} {{ auth()->user()->apellido }}</div>
                <div class="user-role">{{ ucfirst(auth()->user()->rol) }}</div>
            </div>
        </div>
        <form action="/logout" method="POST">
            @csrf
            <button type="submit" class="btn-logout">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>

<!-- ══ MAIN ═══════════════════════════════════════════════════ -->
<main class="main">
    <header class="topbar">
        <h1 class="topbar-title">@yield('title', 'Dashboard')</h1>
        <div class="topbar-actions">
            @yield('topbar-actions')
        </div>
    </header>

    <div class="content">
        @if(session('success'))
            <div style="background:var(--green-bg);color:var(--green);padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:18px;font-size:13.5px;border:1px solid #9fe1cb;">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div style="background:var(--red-bg);color:var(--red);padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:18px;font-size:13.5px;border:1px solid #f7c1c1;">
                {{ session('error') }}
            </div>
        @endif
@if(session('password_generada'))
@php $pg = session('password_generada'); @endphp
<div style="background:#fff;border:2px solid var(--p300);border-radius:var(--radius);padding:18px 22px;margin-bottom:20px;position:relative">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--p500)" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <span style="font-size:14px;font-weight:600;color:var(--p600)">
            Paciente registrado — Credenciales de acceso
        </span>
    </div>
    <div style="background:var(--p50);border-radius:var(--radius-sm);padding:14px 16px;font-size:13.5px;display:flex;flex-direction:column;gap:6px">
        <div><span style="color:var(--text-muted);min-width:100px;display:inline-block">Paciente:</span> <strong>{{ $pg['nombre'] }}</strong></div>
        <div><span style="color:var(--text-muted);min-width:100px;display:inline-block">Correo:</span> <code style="background:var(--p100);padding:2px 8px;border-radius:4px;font-size:13px">{{ $pg['email'] }}</code></div>
        <div style="display:flex;align-items:center;gap:10px">
            <span style="color:var(--text-muted);min-width:100px;display:inline-block">Contraseña:</span>
            <code id="pwd-gen" style="background:var(--p200);padding:4px 12px;border-radius:4px;font-size:15px;font-weight:600;letter-spacing:0.1em;color:var(--p800)">{{ $pg['password'] }}</code>
            <button onclick="navigator.clipboard.writeText('{{ $pg['password'] }}');this.textContent='¡Copiado!';setTimeout(()=>this.textContent='Copiar',2000)"
                    style="background:var(--p400);color:white;border:none;padding:5px 12px;border-radius:6px;font-size:12px;cursor:pointer;font-family:'DM Sans',sans-serif">
                Copiar
            </button>
        </div>
    </div>
    <p style="font-size:12px;color:var(--text-muted);margin-top:10px">
        ⚠ Anota esta contraseña ahora. No se volverá a mostrar. El paciente puede cambiarla desde su perfil.
    </p>
</div>
@endif
        @yield('content')
    </div>
</main>

@stack('scripts')
</body>
</html>