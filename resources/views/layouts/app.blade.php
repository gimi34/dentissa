<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dentissa · @yield('title', 'Dashboard')</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
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
                Home
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
                Home
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
            <div id="flash-success" style="background:var(--green-bg);color:var(--green);padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:18px;font-size:13.5px;border:1px solid #9fe1cb;transition:opacity .5s ease;">
                {{ session('success') }}
            </div>
            <script>
                setTimeout(() => {
                    const el = document.getElementById('flash-success');
                    if (el) {
                        el.style.opacity = '0';
                        setTimeout(() => el.remove(), 500);
                    }
                }, 4000);
            </script>
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
                    style="background:var(--p400);color:white;border:none;padding:5px 12px;border-radius:6px;font-size:12px;cursor:pointer;font-family:'Roboto',sans-serif">
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