@extends('layouts.app')
@section('title', 'Expediente clínico')

@section('content')
@php $base = auth()->user()->rol === 'admin' ? 'admin' : (auth()->user()->rol === 'dentista' ? 'dentista' : 'asistente'); @endphp

{{-- Migas (spec 035, D14): el componente de la 033. --}}
<div id="migas" class="migas">
    <a href="{{ route("{$base}.pacientes") }}">Pacientes</a>
    <span>›</span>
    <a href="{{ route("{$base}.pacientes.show", $expediente->paciente_id) }}">{{ $expediente->paciente }}</a>
    <span>›</span>
    <span class="migas-actual">Expediente</span>
</div>

{{-- Ficha (spec 035, D2): contenido y columna lateral; en el móvil, una columna en este orden. --}}
<div class="ficha-expediente">

    {{-- ── Columna principal ─────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:18px">

        {{-- Cabecera --}}
        <div class="card">
            <div class="expediente-cabecera">
                <div class="texto-partible">
                    <div style="font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:4px">
                        {{ \Carbon\Carbon::parse($expediente->creado_en)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                        @if($expediente->servicio) · {{ $expediente->servicio }} @endif
                    </div>
                    <div style="font-family:'Roboto',sans-serif;font-size:20px;font-weight:500;color:var(--text)">
                        Expediente de {{ $expediente->paciente }}
                    </div>
                    <div style="font-size:13px;color:var(--text-muted);margin-top:3px">
                        Dr. {{ $expediente->dentista }}
                        @if($expediente->fecha_cita)
                            · Cita: {{ \Carbon\Carbon::parse($expediente->fecha_cita)->format('d/m/Y H:i') }}
                        @endif
                    </div>
                </div>
                <a href="{{ route("{$base}.pacientes.show", $expediente->paciente_id) }}"
                   class="btn btn-ghost btn-sm">← Volver al paciente</a>
            </div>

            {{-- Alertas médicas del paciente --}}
            @if($expediente->alergias)
                <div style="background:var(--red-bg);border:1px solid #F7C1C1;border-radius:var(--radius-sm);padding:10px 14px;font-size:13px;color:var(--red);display:flex;gap:8px;align-items:flex-start">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <div><strong>Alergias del paciente:</strong> {{ $expediente->alergias }}</div>
                </div>
            @endif
            @if($expediente->medicamentos_actuales)
                <div style="background:var(--amber-bg);border:1px solid #FAC775;border-radius:var(--radius-sm);padding:10px 14px;font-size:13px;color:var(--amber);display:flex;gap:8px;align-items:flex-start;margin-top:8px">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div><strong>Medicamentos actuales:</strong> {{ $expediente->medicamentos_actuales }}</div>
                </div>
            @endif
        </div>

        {{-- Formulario de edición (solo doctor/admin; el asistente solo puede ver) --}}
        @if($base !== 'asistente')
        <form action="{{ route("{$base}.expedientes.update", $expediente->id) }}" method="POST" class="form-expediente">
            @csrf @method('PUT')

            <div style="display:flex;flex-direction:column;gap:16px">

                <div class="card">
                    <label for="campo-diagnostico" class="etiqueta-campo" style="font-size:13.5px;font-weight:500;color:var(--text);margin-bottom:12px">Diagnóstico</label>
                    <textarea name="diagnostico" id="campo-diagnostico" @error('diagnostico') aria-invalid="true" aria-describedby="error-diagnostico" @enderror class="field {{ $errors->has('diagnostico') ? 'field-error' : '' }}" rows="4" style="resize:vertical" required>{{ old('diagnostico', $expediente->diagnostico) }}</textarea>
                    @error('diagnostico')<div class="error-msg" id="error-diagnostico">{{ $message }}</div>@enderror
                </div>

                <div class="card">
                    <label for="campo-tratamiento" class="etiqueta-campo" style="font-size:13.5px;font-weight:500;color:var(--text);margin-bottom:12px">Tratamiento realizado</label>
                    <textarea name="tratamiento" id="campo-tratamiento" @error('tratamiento') aria-invalid="true" aria-describedby="error-tratamiento" @enderror class="field {{ $errors->has('tratamiento') ? 'field-error' : '' }}" rows="4" style="resize:vertical"
                        placeholder="Procedimientos, materiales utilizados...">{{ old('tratamiento', $expediente->tratamiento) }}</textarea>
                    @error('tratamiento')<div class="error-msg" id="error-tratamiento">{{ $message }}</div>@enderror
                </div>

                <div class="card">
                    <label for="campo-receta" class="etiqueta-campo" style="font-size:13.5px;font-weight:500;color:var(--text);margin-bottom:12px">Receta médica</label>
                    <textarea name="receta" id="campo-receta" @error('receta') aria-invalid="true" aria-describedby="error-receta" @enderror class="field {{ $errors->has('receta') ? 'field-error' : '' }}" rows="5" style="resize:vertical;font-size:13px"
                        placeholder="Medicamentos, dosis e indicaciones...">{{ old('receta', $expediente->receta) }}</textarea>
                    @error('receta')<div class="error-msg" id="error-receta">{{ $message }}</div>@enderror
                </div>

                <div class="card">
                    <label for="campo-observaciones" class="etiqueta-campo" style="font-size:13.5px;font-weight:500;color:var(--text);margin-bottom:12px">Observaciones</label>
                    <textarea name="observaciones" id="campo-observaciones" @error('observaciones') aria-invalid="true" aria-describedby="error-observaciones" @enderror class="field {{ $errors->has('observaciones') ? 'field-error' : '' }}" rows="3" style="resize:vertical"
                        placeholder="Notas adicionales, evolución esperada...">{{ old('observaciones', $expediente->observaciones) }}</textarea>
                    @error('observaciones')<div class="error-msg" id="error-observaciones">{{ $message }}</div>@enderror
                </div>

                <div class="acciones-formulario">
                    <button type="submit" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Guardar cambios
                    </button>
                </div>
            </div>
        </form>
        @else
            <div style="display:flex;flex-direction:column;gap:16px">
                <div class="card">
                    <div style="font-size:13.5px;font-weight:500;color:var(--text);margin-bottom:12px">Diagnóstico</div>
                    <p style="font-size:13.5px;color:var(--text);line-height:1.6;white-space:pre-line">{{ $expediente->diagnostico }}</p>
                </div>
                @if($expediente->tratamiento)
                    <div class="card">
                        <div style="font-size:13.5px;font-weight:500;color:var(--text);margin-bottom:12px">Tratamiento realizado</div>
                        <p style="font-size:13.5px;color:var(--text-muted);line-height:1.6;white-space:pre-line">{{ $expediente->tratamiento }}</p>
                    </div>
                @endif
                @if($expediente->receta)
                    <div class="card">
                        <div style="font-size:13.5px;font-weight:500;color:var(--text);margin-bottom:12px">Receta médica</div>
                        <p style="font-size:13px;color:var(--text-muted);line-height:1.6;white-space:pre-line">{{ $expediente->receta }}</p>
                    </div>
                @endif
                @if($expediente->observaciones)
                    <div class="card">
                        <div style="font-size:13.5px;font-weight:500;color:var(--text);margin-bottom:12px">Observaciones</div>
                        <p style="font-size:13.5px;color:var(--text-muted);line-height:1.6;white-space:pre-line">{{ $expediente->observaciones }}</p>
                    </div>
                @endif
            </div>
        @endif

    </div>

    {{-- ── Sidebar derecho ───────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:16px">

        {{-- Próxima visita --}}
        <div>
            <div class="section-head"><span class="section-title">Próxima visita</span></div>
            <div class="card" style="padding:16px 18px">
                @if($base !== 'asistente')
                <form action="{{ route("{$base}.expedientes.update", $expediente->id) }}" method="POST" class="form-expediente">
                    @csrf @method('PUT')
                    {{-- Reenviar los campos existentes para no borrarlos --}}
                    <input type="hidden" name="diagnostico"   value="{{ $expediente->diagnostico }}">
                    <input type="hidden" name="tratamiento"   value="{{ $expediente->tratamiento }}">
                    <input type="hidden" name="receta"        value="{{ $expediente->receta }}">
                    <input type="hidden" name="observaciones" value="{{ $expediente->observaciones }}">

                    <label class="field-label" for="campo-proxima_visita">Fecha sugerida</label>
                    <input type="date" name="proxima_visita" id="campo-proxima_visita" @error('proxima_visita') aria-invalid="true" aria-describedby="error-proxima_visita" @enderror class="field {{ $errors->has('proxima_visita') ? 'field-error' : '' }}" style="margin-bottom:10px"
                        value="{{ old('proxima_visita', $expediente->proxima_visita ? \Carbon\Carbon::parse($expediente->proxima_visita)->format('Y-m-d') : '') }}"
                        min="{{ now()->format('Y-m-d') }}">
                    @error('proxima_visita')<div class="error-msg" id="error-proxima_visita">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-outline btn-sm" style="width:100%;justify-content:center">
                        Guardar fecha
                    </button>
                </form>
                @endif

                @if($expediente->proxima_visita)
                    <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);font-size:13px;text-align:center;color:var(--p600);font-weight:500">
                        📅 {{ \Carbon\Carbon::parse($expediente->proxima_visita)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
                    </div>
                    @if(\Carbon\Carbon::parse($expediente->proxima_visita)->isPast())
                        <div style="text-align:center;margin-top:6px">
                            <span class="badge badge-red">Fecha vencida</span>
                        </div>
                    @elseif(\Carbon\Carbon::parse($expediente->proxima_visita)->diffInDays(now()) <= 7)
                        <div style="text-align:center;margin-top:6px">
                            <span class="badge badge-amber">Próxima semana</span>
                        </div>
                    @endif
                @elseif($base === 'asistente')
                    <p style="font-size:13px;color:var(--text-light);text-align:center">Sin fecha sugerida</p>
                @endif
            </div>
        </div>

        {{-- Info del expediente --}}
        <div>
            <div class="section-head"><span class="section-title">Información</span></div>
            <div class="card" style="font-size:13px;padding:16px 18px">
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">Creado</span>
                        <span>{{ \Carbon\Carbon::parse($expediente->creado_en)->format('d/m/Y H:i') }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">Dentista</span>
                        <span class="info-valor">{{ $expediente->dentista }}</span>
                    </div>
                    @if($expediente->servicio)
                        <div style="display:flex;justify-content:space-between">
                            <span style="color:var(--text-muted)">Servicio</span>
                            <span class="info-valor">{{ $expediente->servicio }}</span>
                        </div>
                    @endif
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">ID</span>
                        <span style="font-size:11px;font-family:monospace;color:var(--text-muted)">{{ substr($expediente->id,0,8) }}...</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Acceso rápido --}}
        @if($base !== 'asistente')
            <a href="{{ route("{$base}.expedientes.create", ['paciente_id' => $expediente->paciente_id]) }}"
               class="btn btn-outline" style="justify-content:center;width:100%">
                + Nuevo expediente para este paciente
            </a>
        @endif
    </div>

</div>

<style>
.field-label { display:block;font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:5px }
.field { display:block;width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13.5px;font-family:'Roboto',sans-serif;color:var(--text);background:var(--white);outline:none;transition:border-color .15s }
.field:focus { border-color:var(--p300);box-shadow:0 0 0 3px rgba(212,83,126,.07) }
.error-msg { font-size:12px;color:var(--red);margin-top:4px }
textarea.field { line-height:1.6 }
</style>
@endsection