@extends('layouts.app')
@section('title', $paciente->nombre_completo)

@section('topbar-actions')
    @php $base = auth()->user()->rol === 'admin' ? 'admin' : (auth()->user()->rol === 'dentista' ? 'dentista' : 'asistente'); @endphp
    <a href="{{ route("{$base}.expedientes.create", ['paciente_id' => $paciente->id]) }}"
       class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>
        </svg>
        Nuevo expediente
    </a>
    @if($base === 'admin')
        <a href="{{ route('admin.citas.create', ['paciente_id' => $paciente->id]) }}"
           class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Nueva cita
        </a>
    @endif
@endsection

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--text-muted)">
    @php $base = auth()->user()->rol === 'admin' ? 'admin' : (auth()->user()->rol === 'dentista' ? 'dentista' : 'asistente'); @endphp
    <a href="{{ route("{$base}.pacientes") }}" style="color:var(--text-muted);text-decoration:none">Pacientes</a>
    <span>›</span>
    <span style="color:var(--text)">{{ $paciente->nombre_completo }}</span>
</div>

<div style="display:grid;grid-template-columns:320px 1fr;gap:20px;align-items:start">

    {{-- ── Columna izquierda: perfil ────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:16px">

        {{-- Tarjeta de identidad --}}
        <div class="card" style="text-align:center;padding:28px 22px">
            <div style="width:64px;height:64px;border-radius:50%;background:var(--p200);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:600;color:var(--p800);margin:0 auto 14px">
                {{ strtoupper(substr($paciente->nombre,0,1)) }}{{ strtoupper(substr($paciente->apellido,0,1)) }}
            </div>
            <div style="font-family:'Roboto',sans-serif;font-size:20px;font-weight:500;color:var(--text)">
                {{ $paciente->nombre_completo }}
            </div>
            @if($paciente->fecha_nacimiento)
                <div style="font-size:13px;color:var(--text-muted);margin-top:4px">
                    {{ \Carbon\Carbon::parse($paciente->fecha_nacimiento)->age }} años ·
                    {{ \Carbon\Carbon::parse($paciente->fecha_nacimiento)->locale('es')->isoFormat('D MMM YYYY') }}
                </div>
            @endif
            @if($paciente->tipo_sangre)
                <span class="badge badge-pink" style="margin-top:10px">{{ $paciente->tipo_sangre }}</span>
            @endif

            <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:8px;text-align:left">
                @if($paciente->email)
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $paciente->email }}</span>
                    </div>
                @endif
                @if($paciente->telefono)
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.5a16 16 0 0 0 5.59 5.59l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/>
                        </svg>
                        {{ $paciente->telefono }}
                    </div>
                @endif
                @if($paciente->ciudad)
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p300)" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        {{ $paciente->ciudad }}{{ $paciente->direccion ? ' · '.$paciente->direccion : '' }}
                    </div>
                @endif
            </div>

            @if($base === 'admin')
                <div style="margin-top:16px;display:flex;gap:8px">
                    <a href="{{ route('admin.pacientes.edit', $paciente->id) }}"
                       class="btn btn-outline btn-sm" style="flex:1;justify-content:center">Editar</a>
                    <form action="{{ route('admin.pacientes.toggle', $paciente->id) }}" method="POST" style="flex:1">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm" style="width:100%;justify-content:center;background:{{ $paciente->activo ? 'var(--red-bg)' : 'var(--green-bg)' }};color:{{ $paciente->activo ? 'var(--red)' : 'var(--green)' }};border:none;cursor:pointer">
                            {{ $paciente->activo ? 'Desactivar' : 'Reactivar' }}
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- Info médica --}}
        <div class="card">
            <div style="font-size:13.5px;font-weight:500;color:var(--text);margin-bottom:14px;display:flex;align-items:center;gap:8px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--p400)" stroke-width="2">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
                Información médica
            </div>
            <div style="display:flex;flex-direction:column;gap:12px;font-size:13px">
                @if($paciente->alergias)
                    <div>
                        <div style="font-size:10.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--red);margin-bottom:3px">⚠ Alergias</div>
                        <div style="color:var(--text);line-height:1.5">{{ $paciente->alergias }}</div>
                    </div>
                @endif
                @if($paciente->enfermedades_cronicas)
                    <div>
                        <div style="font-size:10.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:3px">Enf. crónicas</div>
                        <div style="color:var(--text);line-height:1.5">{{ $paciente->enfermedades_cronicas }}</div>
                    </div>
                @endif
                @if($paciente->medicamentos_actuales)
                    <div>
                        <div style="font-size:10.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:3px">Medicamentos</div>
                        <div style="color:var(--text);line-height:1.5">{{ $paciente->medicamentos_actuales }}</div>
                    </div>
                @endif
                @if($paciente->notas_medicas)
                    <div>
                        <div style="font-size:10.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:3px">Notas médicas</div>
                        <div style="color:var(--text);line-height:1.5">{{ $paciente->notas_medicas }}</div>
                    </div>
                @endif
                @if(!$paciente->alergias && !$paciente->enfermedades_cronicas && !$paciente->medicamentos_actuales)
                    <div style="color:var(--text-light);font-size:12.5px;text-align:center;padding:8px 0">Sin antecedentes registrados</div>
                @endif
            </div>
        </div>

        {{-- Contacto emergencia --}}
        @if($paciente->contacto_emergencia || $paciente->telefono_emergencia)
            <div class="card" style="padding:16px 18px">
                <div style="font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:10px">Contacto de emergencia</div>
                @if($paciente->contacto_emergencia)
                    <div style="font-size:13.5px;font-weight:500;color:var(--text)">{{ $paciente->contacto_emergencia }}</div>
                @endif
                @if($paciente->telefono_emergencia)
                    <div style="font-size:13px;color:var(--text-muted);margin-top:3px">{{ $paciente->telefono_emergencia }}</div>
                @endif
            </div>
        @endif

        {{-- Stats --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div class="stat-card" style="padding:14px 16px">
                <div class="stat-value" style="font-size:22px">{{ $totalCitas }}</div>
                <div class="stat-label" style="font-size:12px">Total citas</div>
            </div>
            <div class="stat-card" style="padding:14px 16px">
                <div class="stat-value" style="font-size:22px;color:var(--green)">{{ $citasCompletadas }}</div>
                <div class="stat-label" style="font-size:12px">Completadas</div>
            </div>
        </div>

        @if($proximaCita)
            <div class="card" style="border-left:3px solid var(--p400);padding:14px 16px">
                <div style="font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--p400);margin-bottom:6px">Próxima cita</div>
                <div style="font-size:14px;font-weight:500">{{ $proximaCita->servicio ?? 'Consulta' }}</div>
                <div style="font-size:12.5px;color:var(--text-muted);margin-top:3px">
                    {{ \Carbon\Carbon::parse($proximaCita->fecha_hora)->locale('es')->isoFormat('ddd D MMM · H:mm') }}
                </div>
            </div>
        @endif

    </div>

    {{-- ── Columna derecha: expedientes y citas ─────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:20px">

        {{-- Expedientes clínicos --}}
        <div>
            <div class="section-head">
                <span class="section-title">Expedientes clínicos</span>
                <a href="{{ route("{$base}.expedientes.create", ['paciente_id' => $paciente->id]) }}"
                   class="btn btn-ghost btn-sm">+ Nuevo</a>
            </div>

            @if($expedientes->count() > 0)
                <div style="display:flex;flex-direction:column;gap:12px">
                    @foreach($expedientes as $exp)
                        <div class="card" style="padding:18px 20px">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px">
                                <div>
                                    <div style="font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light)">
                                        {{ \Carbon\Carbon::parse($exp->creado_en)->locale('es')->isoFormat('D MMM YYYY') }}
                                        @if($exp->servicio) · {{ $exp->servicio }} @endif
                                    </div>
                                    <div style="font-size:14px;font-weight:500;color:var(--text);margin-top:3px">
                                        {{ Str::limit($exp->diagnostico, 80) }}
                                    </div>
                                    <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Dr. {{ $exp->dentista }}</div>
                                </div>
                                <a href="{{ route("{$base}.expedientes.show", $exp->id) }}"
                                   class="btn btn-ghost btn-sm" style="flex-shrink:0">Ver →</a>
                            </div>

                            @if($exp->tratamiento)
                                <div style="font-size:13px;color:var(--text-muted);padding:10px 12px;background:var(--p50);border-radius:var(--radius-sm);line-height:1.5">
                                    <span style="font-weight:500;color:var(--text)">Tratamiento:</span> {{ Str::limit($exp->tratamiento, 120) }}
                                </div>
                            @endif

                            @if($exp->proxima_visita)
                                <div style="margin-top:10px;font-size:12.5px;color:var(--p600)">
                                    📅 Próxima visita: <strong>{{ \Carbon\Carbon::parse($exp->proxima_visita)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</strong>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="card">
                    <div class="empty-state" style="padding:24px">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        <p>Sin expedientes clínicos aún</p>
                        <a href="{{ route("{$base}.expedientes.create", ['paciente_id' => $paciente->id]) }}"
                           class="btn btn-outline btn-sm" style="margin-top:10px;display:inline-flex">Crear primer expediente</a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Historial de citas --}}
        <div>
            <div class="section-head">
                <span class="section-title">Historial de citas</span>
                @if($base === 'admin')
                    <a href="{{ route('admin.citas.create', ['paciente_id' => $paciente->id]) }}"
                       class="btn btn-ghost btn-sm">+ Nueva</a>
                @endif
            </div>
            <div class="card card-0">
                @if($citas->count() > 0)
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
                                @foreach($citas as $c)
                                <tr>
                                    <td style="white-space:nowrap;font-size:12.5px;color:var(--text-muted)">
                                        {{ \Carbon\Carbon::parse($c->fecha_hora)->format('d/m/Y H:i') }}
                                    </td>
                                    <td style="font-weight:500;font-size:13px">{{ $c->servicio ?? '—' }}</td>
                                    <td style="font-size:12.5px;color:var(--text-muted)">{{ $c->dentista }}</td>
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

    </div>
</div>
@endsection