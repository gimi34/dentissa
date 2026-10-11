@extends('layouts.app')
@section('title', 'Mi salud dental')

@section('content')

{{-- Bienvenida --}}
<div class="welcome-banner">
    <div style="z-index:1">
        <div class="welcome-title" style="margin-bottom:0">Hola, {{ auth()->user()->nombre }}</div>
    </div>
    <div class="welcome-actions">
        <a href="/cliente/agendar" class="btn btn-white">Agendar cita</a>
        <a href="/cliente/perfil" class="btn btn-white-outline">Mi perfil</a>
    </div>
</div>

{{-- Grid principal --}}
<div class="grid-auto">

    {{-- Columna izquierda --}}
    <div style="display:flex;flex-direction:column;gap:20px">

        {{-- Próxima cita --}}
        <div>
            <div class="section-head">
                <h2 class="section-title">Tu próxima cita</h2>
                <a href="/cliente/citas" class="btn btn-ghost btn-sm">Ver todas →</a>
            </div>

            @if(isset($proximaCita) && $proximaCita)
            <div class="card" style="border-left:3px solid var(--p400)">
                <div style="display:flex;gap:16px;align-items:flex-start">
                    <div class="appt-date-box">
                        <div class="appt-day">{{ \Carbon\Carbon::parse($proximaCita->fecha_hora)->format('d') }}</div>
                        <div class="appt-month">{{ \Carbon\Carbon::parse($proximaCita->fecha_hora)->locale('es')->isoFormat('MMM') }}</div>
                    </div>
                    <div style="flex:1">
                        <div style="font-size:16px;font-weight:500;color:var(--text);margin-bottom:6px">
                            {{ $proximaCita->servicio ?? 'Consulta dental' }}
                        </div>
                        <div style="font-size:13px;color:var(--text-muted);display:flex;flex-direction:column;gap:4px">
                            <span>
                                🕐 {{ \Carbon\Carbon::parse($proximaCita->fecha_hora)->format('H:i') }} hrs
                                @if($proximaCita->duracion_min)
                                    · {{ $proximaCita->duracion_min }} min
                                @endif
                            </span>
                            <span>👨‍⚕️ {{ $proximaCita->dentista }}</span>
                        </div>
                        {{-- Estado y acciones con el plazo de 24 horas del paciente (spec 028; /review 028 R2, R21). --}}
                        <div class="formulario-accesible">
                            <div class="proxima-acciones">
                                @include('partials.badge-estado', ['estado' => $proximaCita->estado])
                                <div id="acciones-proxima" class="acciones-fila">
                                    <a href="{{ route('cliente.citas.show', $proximaCita->id) }}" class="btn btn-outline btn-sm">Ver detalles</a>
                                    @if($proximaCita->puede_reprogramar)
                                        <a href="{{ route('cliente.citas.reprogramar', $proximaCita->id) }}" class="btn btn-outline btn-sm">Reprogramar</a>
                                    @endif
                                    @if($proximaCita->puede_cancelar)
                                        <form action="{{ route('cliente.citas.cancelar', $proximaCita->id) }}" method="POST" class="form-en-linea">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('¿Deseas cancelar esta cita?')">Cancelar</button>
                                        </form>
                                    @elseif($proximaCita->aviso_plazo_corto)
                                        <p class="aviso-plazo">{{ $proximaCita->aviso_plazo_corto }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="card" style="text-align:center;padding:36px 24px">
                <div style="width:60px;height:60px;background:var(--p100);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--p400)" stroke-width="1.5">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                </div>
                <div style="font-size:15px;font-weight:500;color:var(--text);margin-bottom:6px">Sin citas próximas</div>
                <div style="font-size:13px;color:var(--text-muted);margin-bottom:18px">Agenda tu próxima visita con nosotros</div>
                <a href="/cliente/agendar" class="btn btn-primary" style="display:inline-flex">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Agendar cita
                </a>
            </div>
            @endif
        </div>

        {{-- Historial de citas --}}
        <div>
            <div class="section-head">
                <span class="section-title">Historial</span>
                <a href="/cliente/citas?filtro=pasadas" class="btn btn-ghost btn-sm">Ver todo →</a>
            </div>
            <div class="card card-0">
                @if(isset($citasPasadas) && $citasPasadas->count() > 0)
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Servicio</th>
                                    <th>Dentista</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($citasPasadas as $cita)
                                <tr>
                                    <td style="white-space:nowrap;color:var(--text-muted)">
                                        {{ \Carbon\Carbon::parse($cita->fecha_hora)->format('d/m/Y') }}
                                    </td>
                                    <td style="font-weight:500">{{ $cita->servicio ?? '—' }}</td>
                                    <td style="color:var(--text-muted)">{{ $cita->dentista }}</td>
                                    <td>@include('partials.badge-estado', ['estado' => $cita->estado])</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        <p>Aún no tienes citas anteriores</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Columna derecha --}}
    <div style="display:flex;flex-direction:column;gap:18px">

        {{-- Acciones rápidas --}}
        <div>
            <div class="section-head"><span class="section-title">¿Qué necesitas?</span></div>
            <div style="display:flex;flex-direction:column;gap:8px">
                <a href="/cliente/agendar" class="quick-action" style="flex-direction:row;text-align:left;padding:14px 16px">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    <span style="font-size:13.5px">Agendar nueva cita</span>
                </a>
                <a href="/cliente/citas" class="quick-action" style="flex-direction:row;text-align:left;padding:14px 16px">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                    <span style="font-size:13.5px">Ver mis citas</span>
                </a>
                <a href="/cliente/perfil" class="quick-action" style="flex-direction:row;text-align:left;padding:14px 16px">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span style="font-size:13.5px">Actualizar mi perfil</span>
                </a>
            </div>
        </div>

        {{-- Tip dental --}}
        <div class="card" style="background:var(--p100);border-color:var(--p200)">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                <span style="font-size:22px">💡</span>
                <div style="font-size:13px;font-weight:600;color:var(--p800)">Consejo dental del día</div>
            </div>
            @php
                $tips = [
                    'Cepilla tus dientes al menos 2 veces al día durante 2 minutos cada vez.',
                    'El hilo dental llega a donde el cepillo no puede. Úsalo diariamente.',
                    'Cambiar tu cepillo de dientes cada 3 meses mantiene una higiene óptima.',
                    'Reducir el consumo de azúcar protege tus dientes del deterioro.',
                    'Las revisiones cada 6 meses ayudan a detectar problemas a tiempo.',
                ];
                $tip = $tips[date('N') % count($tips)];
            @endphp
            <p style="font-size:13px;color:var(--p800);line-height:1.6">{{ $tip }}</p>
        </div>

        {{-- Info de contacto --}}
        <div class="card" style="padding:16px 18px">
            <div style="font-size:13px;font-weight:500;color:var(--text);margin-bottom:12px">Contacto de la clínica</div>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:12.5px;color:var(--text-muted)">
                <div style="display:flex;align-items:center;gap:8px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p400)" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.5a16 16 0 0 0 5.59 5.59l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/>
                    </svg>
                    555-0001
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p400)" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    contacto@clinica.com
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p400)" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Lun–Vie 9:00–18:00 · Sáb 9:00–14:00
                </div>
            </div>
        </div>

    </div>
</div>

@endsection