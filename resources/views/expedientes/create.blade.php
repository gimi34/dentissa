@extends('layouts.app')
@section('title', 'Nuevo expediente')

@section('content')
<div style="max-width:760px">

    @php $base = auth()->user()->rol === 'admin' ? 'admin' : (auth()->user()->rol === 'dentista' ? 'dentista' : 'asistente'); @endphp

    {{-- Breadcrumb --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--text-muted)">
        <a href="{{ route("{$base}.pacientes") }}" style="color:var(--text-muted);text-decoration:none">Pacientes</a>
        <span>›</span>
        <a href="{{ route("{$base}.pacientes.show", $paciente->id) }}" style="color:var(--text-muted);text-decoration:none">{{ $paciente->nombre_completo }}</a>
        <span>›</span>
        <span style="color:var(--text)">Nuevo expediente</span>
    </div>

    {{-- Paciente info --}}
    <div class="card" style="margin-bottom:18px;padding:14px 18px;background:var(--p50);border-color:var(--p200)">
        <div style="display:flex;align-items:center;gap:10px">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--p200);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;color:var(--p800);flex-shrink:0">
                {{ strtoupper(substr($paciente->nombre_completo, 0, 1)) }}
            </div>
            <div>
                <div style="font-weight:500;font-size:14px;color:var(--text)">{{ $paciente->nombre_completo }}</div>
                <div style="font-size:12px;color:var(--text-muted)">Creando expediente para este paciente</div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route("{$base}.expedientes.store") }}">
        @csrf
        <input type="hidden" name="paciente_id" value="{{ $paciente->id }}">

        <div style="display:flex;flex-direction:column;gap:18px">

            {{-- Dentista y cita --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Asignación
                </div>
                <div class="grid-2">
                    <div class="field-group">
                        <label class="field-label">Dentista responsable <span style="color:var(--p500)">*</span></label>
                        <select name="dentista_id" class="field" required>
                            <option value="">Seleccionar...</option>
                            @foreach($dentistas as $d)
                                <option value="{{ $d->id }}"
                                    {{ (old('dentista_id', $dentistaPreseleccionado) == $d->id) ? 'selected' : '' }}>
                                    {{ $d->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('dentista_id')<div class="error-msg">{{ $message }}</div>@enderror
                    </div>

                    <div class="field-group">
                        <label class="field-label">Cita relacionada <span style="font-weight:400;color:var(--text-light)">(opcional)</span></label>
                        <select name="cita_id" class="field">
                            <option value="">Sin cita asignada</option>
                            @foreach($citas as $c)
                                <option value="{{ $c->id }}" {{ old('cita_id') == $c->id ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::parse($c->fecha_hora)->format('d/m/Y H:i') }}
                                    {{ $c->servicio ? '· '.$c->servicio : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if($citas->isEmpty())
                            <div class="field-hint">No hay citas completadas pendientes de expediente</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Diagnóstico y tratamiento --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Diagnóstico y tratamiento
                </div>

                <div class="field-group" style="margin-bottom:14px">
                    <label class="field-label">Diagnóstico <span style="color:var(--p500)">*</span></label>
                    <textarea name="diagnostico" class="field {{ $errors->has('diagnostico') ? 'field-error' : '' }}"
                        rows="4" style="resize:vertical"
                        placeholder="Describe el diagnóstico clínico del paciente..." required>{{ old('diagnostico') }}</textarea>
                    @error('diagnostico')<div class="error-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field-group" style="margin-bottom:14px">
                    <label class="field-label">Tratamiento realizado</label>
                    <textarea name="tratamiento" class="field" rows="3" style="resize:vertical"
                        placeholder="Procedimiento realizado, materiales utilizados...">{{ old('tratamiento') }}</textarea>
                </div>

                <div class="field-group">
                    <label class="field-label">Observaciones adicionales</label>
                    <textarea name="observaciones" class="field" rows="3" style="resize:vertical"
                        placeholder="Notas clínicas, evolución esperada...">{{ old('observaciones') }}</textarea>
                </div>
            </div>

            {{-- Receta --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Receta médica
                    <span style="font-size:12px;font-weight:400;color:var(--text-muted);margin-left:6px">(opcional)</span>
                </div>
                <div class="field-group" style="margin-bottom:14px">
                    <label class="field-label">Medicamentos y dosis</label>
                    <textarea name="receta" class="field" rows="4" style="resize:vertical;font-family:'DM Mono',monospace,sans-serif;font-size:13px"
                        placeholder="Ej:&#10;- Ibuprofeno 400mg cada 8h por 3 días&#10;- Amoxicilina 500mg cada 12h por 7 días&#10;- Enjuague bucal con clorhexidina 2 veces al día">{{ old('receta') }}</textarea>
                </div>

                <div class="field-group">
                    <label class="field-label">Fecha de próxima visita</label>
                    <input type="date" name="proxima_visita" class="field"
                        value="{{ old('proxima_visita') }}"
                        min="{{ now()->addDay()->format('Y-m-d') }}"
                        style="max-width:220px">
                    @error('proxima_visita')<div class="error-msg">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Acciones --}}
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <a href="{{ route("{$base}.pacientes.show", $paciente->id) }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Guardar expediente
                </button>
            </div>

        </div>
    </form>
</div>

<style>
.field-label { display:block;font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-light);margin-bottom:5px }
.field { display:block;width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13.5px;font-family:'Roboto',sans-serif;color:var(--text);background:var(--white);outline:none;transition:border-color .15s }
.field:focus { border-color:var(--p300);box-shadow:0 0 0 3px rgba(212,83,126,.07) }
.field-error { border-color:var(--red)!important }
.field-hint { font-size:11.5px;color:var(--text-light);margin-top:4px }
.error-msg { font-size:12px;color:var(--red);margin-top:4px }
.field-group { margin-bottom:0 }
textarea.field { line-height:1.6 }
select.field { cursor:pointer }
</style>
@endsection