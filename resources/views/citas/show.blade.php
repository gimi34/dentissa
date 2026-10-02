@extends('layouts.app')
@section('title', 'Detalle de cita')

@php
    $rp = match(auth()->user()->rol) {
        'dentista'  => 'dentista',
        'asistente' => 'asistente',
        default     => 'admin',
    };
@endphp

@section('topbar-actions')
    @if($rp === 'admin' && in_array('cancelada', $disponibles, true))
        <form action="{{ route('admin.citas.destroy', $cita->id) }}" method="POST" style="display:inline">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-ghost"
                    style="color:var(--red)"
                    onclick="return confirm('¿Cancelar esta cita?')">
                Cancelar cita
            </button>
        </form>
    @endif
@endsection

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--text-muted)">
    <a href="{{ route("$rp.citas") }}" style="color:var(--text-muted);text-decoration:none">Citas</a>
    <span>›</span>
    <span style="color:var(--text)">{{ $cita->paciente }}</span>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:20px">

    {{-- ── Columna principal ─────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:18px">

        {{-- Info de la cita --}}
        <div class="card">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px">
                <div>
                    <div style="font-family:'Roboto',sans-serif;font-size:20px;font-weight:500;color:var(--text)">
                        {{ $cita->servicio ?? 'Consulta dental' }}
                    </div>
                    <div style="font-size:13px;color:var(--text-muted);margin-top:3px">
                        {{ \Carbon\Carbon::parse($cita->fecha_hora)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                        · {{ \Carbon\Carbon::parse($cita->fecha_hora)->format('H:i') }} hrs
                    </div>
                </div>
                @include('partials.badge-estado', ['estado' => $cita->estado])
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
                <div style="background:var(--p50);border-radius:var(--radius-sm);padding:12px 14px">
                    <div style="font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:4px">Paciente</div>
                    <div style="font-size:14px;font-weight:500">{{ $cita->paciente }}</div>
                    @if($cita->tel_paciente)
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px">{{ $cita->tel_paciente }}</div>
                    @endif
                    @if($cita->email_paciente)
                        <div style="font-size:12px;color:var(--text-muted)">{{ $cita->email_paciente }}</div>
                    @endif
                </div>

                <div style="background:var(--p50);border-radius:var(--radius-sm);padding:12px 14px">
                    <div style="font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:4px">Dentista</div>
                    <div style="font-size:14px;font-weight:500">{{ $cita->dentista }}</div>
                </div>

                <div style="background:var(--p50);border-radius:var(--radius-sm);padding:12px 14px">
                    <div style="font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:4px">Duración</div>
                    <div style="font-size:14px;font-weight:500">{{ $cita->duracion_min }} min</div>
                    <div style="font-size:12px;color:var(--text-muted)">
                        Termina: {{ \Carbon\Carbon::parse($cita->fecha_hora)->addMinutes($cita->duracion_min)->format('H:i') }}
                    </div>
                </div>
            </div>

            @if($cita->notas_paciente)
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
                    <div style="font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:6px">Notas para el paciente</div>
                    <p style="font-size:13.5px;color:var(--text);line-height:1.6">{{ $cita->notas_paciente }}</p>
                </div>
            @endif
        </div>

        {{-- ── Control interno ───────────────────────────────────────── --}}
        @if($rp === 'admin')
        <div class="card" style="border-left:3px solid var(--p400)" id="control-interno">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--p400)" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
                <span style="font-size:14px;font-weight:500;color:var(--text)">Control interno</span>
                <span style="font-size:11.5px;color:var(--text-light);margin-left:4px">Solo visible para el personal</span>
            </div>

            {{-- Notas internas editables --}}
            <form action="{{ route('admin.citas.notas', $cita->id) }}" method="POST">
                @csrf @method('PATCH')
                <label class="field-label">Notas internas del staff</label>
                <textarea name="notas_internas" class="field" rows="4"
                    placeholder="Ej: Paciente pagó en efectivo. Reagendar en 6 meses. Requiere radiografía previa..."
                    style="resize:vertical;margin-bottom:10px">{{ $cita->notas_internas }}</textarea>
                <button type="submit" class="btn btn-outline btn-sm">Guardar notas</button>
            </form>
        </div>
        @endif

        {{-- Historial de cambios --}}
        <div id="historial">
            <div class="section-head">
                <span class="section-title">Historial de cambios</span>
            </div>
            <div class="card card-0">
                @if($historial->count() > 0)
                    <div style="padding:6px 20px">
                        @foreach($historial as $h)
                            <div class="timeline-item">
                                <div class="timeline-time" style="width:100px">
                                    {{ \Carbon\Carbon::parse($h->fecha)->format('d/m H:i') }}
                                </div>
                                <div class="timeline-dot" style="background:var(--p300)"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">
                                        @if($h->estado_anterior && $h->estado_nuevo)
                                            <span style="color:var(--text-muted)">{{ \App\Agenda\CambioDeEstadoNoPermitido::etiqueta($h->estado_anterior) }}</span>
                                            → <span style="color:var(--p600)">{{ \App\Agenda\CambioDeEstadoNoPermitido::etiqueta($h->estado_nuevo) }}</span>
                                        @elseif($h->estado_nuevo)
                                            Estado: <span style="color:var(--p600)">{{ \App\Agenda\CambioDeEstadoNoPermitido::etiqueta($h->estado_nuevo) }}</span>
                                        @else
                                            Cambio registrado
                                        @endif
                                    </div>
                                    @if($h->motivo)
                                        <div class="timeline-sub">{{ $h->motivo }}</div>
                                    @endif
                                    <div style="font-size:11.5px;color:var(--text-light);margin-top:2px">
                                        por {{ trim($h->cambiado_por_nombre) ?: 'Sistema' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state" style="padding:22px">
                        <p>Sin cambios de estado registrados</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Sidebar derecho ───────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:18px">

        {{-- Cambiar estado --}}
        <div id="estado">
            <div class="section-head"><span class="section-title">Cambiar estado</span></div>
            <div class="card">
                @error('estado')
                    <div class="field-hint field-hint-error" role="alert" style="margin:0 0 12px">{{ $message }}</div>
                @enderror

                @if($aviso)
                    <p style="font-size:13px;color:var(--text-muted);line-height:1.6;margin:0">{{ $aviso }}</p>
                @endif

                @if($disponibles !== [])
                <form action="{{ route("$rp.citas.estado", $cita->id) }}" method="POST">
                    @csrf @method('PATCH')

                    <fieldset style="border:0;padding:0;margin:0 0 14px">
                        <legend class="field-label" style="margin-bottom:8px">
                            Nuevo estado <span style="font-weight:400;text-transform:none;letter-spacing:0">(ahora: {{ \App\Agenda\CambioDeEstadoNoPermitido::etiqueta($cita->estado) }})</span>
                        </legend>
                        <div style="display:flex;flex-direction:column;gap:8px">
                            @foreach($disponibles as $e)
                                <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--white);cursor:pointer;transition:all .15s">
                                    <input type="radio" name="estado" value="{{ $e }}" required
                                           {{ old('estado') === $e ? 'checked' : '' }}
                                           style="accent-color:var(--p400)">
                                    @include('partials.badge-estado', ['estado' => $e])
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div style="margin-bottom:12px">
                        <label class="field-label" for="motivo">Motivo del cambio <span style="font-weight:400;text-transform:none;letter-spacing:0">(opcional)</span></label>
                        <textarea name="motivo" id="motivo" class="field" rows="2" maxlength="500"
                            placeholder="Ej: Paciente confirmó por teléfono..."
                            style="resize:none">{{ old('motivo') }}</textarea>
                        @error('motivo')
                            <div class="field-hint field-hint-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                        Actualizar estado
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- Datos rápidos --}}
        <div>
            <div class="section-head"><span class="section-title">Información</span></div>
            <div class="card" style="font-size:13px">
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">Creada</span>
                        <span>{{ \Carbon\Carbon::parse($cita->creado_en)->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($cita->recordatorio_enviado)
                        <div style="display:flex;justify-content:space-between">
                            <span style="color:var(--text-muted)">Recordatorio</span>
                            <span class="badge badge-green">Enviado</span>
                        </div>
                    @endif
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">ID de cita</span>
                        <span style="font-size:11px;font-family:monospace;color:var(--text-muted)">{{ substr($cita->id, 0, 8) }}...</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Acciones rápidas --}}
        <div style="display:flex;flex-direction:column;gap:8px">
            <a href="{{ route("$rp.pacientes.show", $cita->paciente_id) }}"
               class="btn btn-outline" style="justify-content:center">
               Ver expediente del paciente
            </a>
            <a href="{{ route("$rp.citas.create", ['fecha' => \Carbon\Carbon::parse($cita->fecha_hora)->addMonth()->format('Y-m-d'), 'dentista' => $cita->dentista_id]) }}"
               class="btn btn-ghost" style="justify-content:center">
               Reagendar / Nueva cita
            </a>
        </div>

    </div>
</div>

@push('styles')
<link href="{{ asset('css/citas.css') }}?v={{ filemtime(public_path('css/citas.css')) }}" rel="stylesheet">
@endpush
@endsection