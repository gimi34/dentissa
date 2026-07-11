@extends('layouts.app')
@section('title', $miembro->nombre_completo)

@section('topbar-actions')
    <a href="{{ route('admin.personal.edit', $miembro->id) }}" class="btn btn-outline">Editar datos</a>
    <form action="{{ route('admin.personal.toggle', $miembro->id) }}" method="POST" style="display:inline">
        @csrf @method('PATCH')
        <button type="submit" class="btn"
            style="background:{{ $miembro->activo ? 'var(--red-bg)' : 'var(--green-bg)' }};color:{{ $miembro->activo ? 'var(--red)' : 'var(--green)' }};border:none;cursor:pointer"
            onclick="return confirm('{{ $miembro->activo ? '¿Desactivar este miembro?' : '¿Reactivar este miembro?' }}')">
            {{ $miembro->activo ? 'Desactivar' : 'Reactivar' }}
        </button>
    </form>
@endsection

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--text-muted)">
    <a href="{{ route('admin.personal') }}" style="color:var(--text-muted);text-decoration:none">Personal</a>
    <span>›</span>
    <span style="color:var(--text)">{{ $miembro->nombre_completo }}</span>
</div>

@php
    $tipoColor  = match($miembro->tipo) { 'dentista' => 'var(--p500)', 'asistente' => 'var(--blue)', default => 'var(--green)' };
    $tipoBg     = match($miembro->tipo) { 'dentista' => 'var(--p100)', 'asistente' => 'var(--blue-bg)', default => 'var(--green-bg)' };
    $tipoLabel  = match($miembro->tipo) { 'dentista' => 'Dentista', 'asistente' => 'Asistente', default => 'Administrador' };
@endphp

<div style="display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start">

    {{-- ── Columna izquierda ────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:16px">

        {{-- Tarjeta de identidad --}}
        <div class="card" style="text-align:center;padding:28px 22px">
            <div style="width:68px;height:68px;border-radius:50%;background:{{ $tipoBg }};display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:600;color:{{ $tipoColor }};margin:0 auto 14px">
                {{ strtoupper(substr($miembro->nombre,0,1)) }}{{ strtoupper(substr($miembro->apellido,0,1)) }}
            </div>
            <div style="font-family:'Playfair Display',serif;font-size:20px;font-weight:500;color:var(--text)">
                {{ $miembro->nombre_completo }}
            </div>
            <div style="margin-top:6px;display:flex;align-items:center;justify-content:center;gap:6px">
                <span style="width:7px;height:7px;border-radius:50%;background:{{ $tipoColor }};display:inline-block"></span>
                <span style="font-size:13px;color:var(--text-muted)">{{ $tipoLabel }}</span>
                @if(!$miembro->activo)
                    <span class="badge badge-red" style="margin-left:4px">Inactivo</span>
                @endif
            </div>
            @if($miembro->especialidad)
                <div style="font-size:13px;color:var(--text-muted);margin-top:4px">{{ $miembro->especialidad }}</div>
            @endif

            <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:8px;text-align:left">
                @if($miembro->email)
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $miembro->email }}</span>
                    </div>
                @endif
                @if($miembro->telefono)
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.5a16 16 0 0 0 5.59 5.59l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/>
                        </svg>
                        {{ $miembro->telefono }}
                    </div>
                @endif
                @if($miembro->numero_cedula)
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        </svg>
                        Cédula: {{ $miembro->numero_cedula }}
                    </div>
                @endif
                @if($miembro->universidad)
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        {{ $miembro->universidad }}
                    </div>
                @endif
                @if($miembro->anios_experiencia)
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        {{ $miembro->anios_experiencia }} años de experiencia
                    </div>
                @endif
            </div>
        </div>

        {{-- Biografía --}}
        @if($miembro->biografia)
            <div class="card" style="padding:16px 18px">
                <div style="font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:8px">Acerca de</div>
                <p style="font-size:13.5px;color:var(--text-muted);line-height:1.7">{{ $miembro->biografia }}</p>
            </div>
        @endif

        {{-- Stats solo para dentistas --}}
        @if($miembro->tipo === 'dentista')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div class="stat-card" style="padding:14px 16px">
                    <div class="stat-value" style="font-size:22px">{{ $totalCitas }}</div>
                    <div class="stat-label" style="font-size:12px">Total citas</div>
                </div>
                <div class="stat-card" style="padding:14px 16px">
                    <div class="stat-value" style="font-size:22px;color:var(--p500)">{{ $citasEsteMes }}</div>
                    <div class="stat-label" style="font-size:12px">Este mes</div>
                </div>
            </div>
        @endif

    </div>

    {{-- ── Columna derecha ───────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:20px">

        {{-- Horarios semanales --}}
        <div>
            <div class="section-head">
                <span class="section-title">Horarios de atención</span>
            </div>
            <div class="card">
                <form action="{{ route('admin.personal.horarios', $miembro->id) }}" method="POST">
                    @csrf

                    <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:18px">
                        @foreach($dias as $num => $nombre)
                        @php $horario = $horarios->get($num); @endphp
                        <div style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:var(--radius-sm);background:{{ $horario ? 'var(--p50)' : 'transparent' }};border:1px solid {{ $horario ? 'var(--p200)' : 'var(--border)' }};transition:all .15s" id="fila-{{ $num }}">

                            {{-- Toggle día --}}
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;min-width:110px">
                                <input type="checkbox"
                                    name="dias[{{ $num }}][activo]"
                                    value="1"
                                    {{ $horario ? 'checked' : '' }}
                                    onchange="toggleDia({{ $num }}, this.checked)"
                                    style="accent-color:var(--p400);width:15px;height:15px">
                                <span style="font-size:13.5px;font-weight:{{ $horario ? '500' : '400' }};color:{{ $horario ? 'var(--text)' : 'var(--text-muted)' }}" id="lbl-{{ $num }}">
                                    {{ $nombre }}
                                </span>
                            </label>

                            {{-- Horas --}}
                            <div style="display:flex;align-items:center;gap:8px;flex:1" id="horas-{{ $num }}"
                                 class="{{ $horario ? '' : 'hidden-row' }}">
                                <input type="time"
                                    name="dias[{{ $num }}][hora_inicio]"
                                    value="{{ $horario->hora_inicio ?? '09:00' }}"
                                    class="field" style="width:110px;padding:6px 10px;font-size:13px"
                                    {{ $horario ? '' : 'disabled' }}>
                                <span style="color:var(--text-muted);font-size:12px">a</span>
                                <input type="time"
                                    name="dias[{{ $num }}][hora_fin]"
                                    value="{{ $horario->hora_fin ?? '18:00' }}"
                                    class="field" style="width:110px;padding:6px 10px;font-size:13px"
                                    {{ $horario ? '' : 'disabled' }}>

                                @if($horario)
                                    @php
                                        $inicio = \Carbon\Carbon::parse($horario->hora_inicio);
                                        $fin    = \Carbon\Carbon::parse($horario->hora_fin);
                                        $horas  = $inicio->diffInMinutes($fin) / 60;
                                    @endphp
                                    <span style="font-size:12px;color:var(--text-muted);margin-left:4px">
                                        {{ number_format($horas, 1) }}h
                                    </span>
                                @endif
                            </div>

                            @if(!$horario)
                                <span style="font-size:12px;color:var(--text-light)" id="sin-{{ $num }}">No disponible</span>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    <div style="display:flex;justify-content:flex-end">
                        <button type="submit" class="btn btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Guardar horarios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Citas recientes (solo dentistas) --}}
        @if($miembro->tipo === 'dentista')
            <div>
                <div class="section-head">
                    <span class="section-title">Citas recientes</span>
                    <a href="{{ route('admin.citas', ['dentista_id' => $miembro->id]) }}"
                       class="btn btn-ghost btn-sm">Ver todas →</a>
                </div>
                <div class="card card-0">
                    @if($citasRecientes->count() > 0)
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Paciente</th>
                                        <th>Servicio</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($citasRecientes as $c)
                                    <tr>
                                        <td style="font-size:12.5px;color:var(--text-muted);white-space:nowrap">
                                            {{ \Carbon\Carbon::parse($c->fecha_hora)->format('d/m/Y H:i') }}
                                        </td>
                                        <td style="font-weight:500;font-size:13px">{{ $c->paciente }}</td>
                                        <td style="font-size:12.5px;color:var(--text-muted)">{{ $c->servicio ?? '—' }}</td>
                                        <td>@include('partials.badge-estado', ['estado' => $c->estado])</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state" style="padding:22px">
                            <p>Sin citas registradas</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

    </div>
</div>

<style>
.field { display:block;width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13.5px;font-family:'DM Sans',sans-serif;color:var(--text);background:var(--white);outline:none;transition:border-color .15s }
.field:focus { border-color:var(--p300) }
.hidden-row { display:none!important }
</style>

@push('scripts')
<script>
function toggleDia(num, checked) {
    const fila  = document.getElementById('fila-' + num);
    const horas = document.getElementById('horas-' + num);
    const lbl   = document.getElementById('lbl-' + num);
    const sin   = document.getElementById('sin-' + num);
    const inputs = horas ? horas.querySelectorAll('input') : [];

    if (checked) {
        fila.style.background = 'var(--p50)';
        fila.style.borderColor = 'var(--p200)';
        lbl.style.fontWeight = '500';
        lbl.style.color = 'var(--text)';
        if (horas) { horas.classList.remove('hidden-row'); inputs.forEach(i => i.disabled = false); }
        if (sin)   { sin.style.display = 'none'; }
    } else {
        fila.style.background = 'transparent';
        fila.style.borderColor = 'var(--border)';
        lbl.style.fontWeight = '400';
        lbl.style.color = 'var(--text-muted)';
        if (horas) { horas.classList.add('hidden-row'); inputs.forEach(i => i.disabled = true); }
        if (sin)   { sin.style.display = 'inline'; }
    }
}
</script>
@endpush
@endsection