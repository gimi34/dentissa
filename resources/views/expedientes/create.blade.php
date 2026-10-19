@extends('layouts.app')
@section('title', 'Nuevo expediente')

@section('content')
<div style="max-width:760px">

    @php $base = auth()->user()->rol === 'admin' ? 'admin' : (auth()->user()->rol === 'dentista' ? 'dentista' : 'asistente'); @endphp

    {{-- Breadcrumb --}}
    <div id="migas" class="migas">
        <a href="{{ route("{$base}.pacientes") }}">Pacientes</a>
        <span>›</span>
        <a href="{{ route("{$base}.pacientes.show", $paciente->id) }}">{{ $paciente->nombre_completo }}</a>
        <span>›</span>
        <span class="migas-actual">Nuevo expediente</span>
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

    <form method="POST" class="form-expediente" action="{{ route("{$base}.expedientes.store") }}">
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
                        <label class="field-label" for="campo-dentista_id">Dentista responsable <span style="color:var(--p500)">*</span></label>
                        <select name="dentista_id" id="campo-dentista_id" @error('dentista_id') aria-invalid="true" aria-describedby="error-dentista_id" @enderror class="field {{ $errors->has('dentista_id') ? 'field-error' : '' }}" required>
                            <option value="">Seleccionar...</option>
                            @foreach($dentistas as $d)
                                <option value="{{ $d->id }}"
                                    {{ (old('dentista_id', $dentistaPreseleccionado) == $d->id) ? 'selected' : '' }}>
                                    {{ $d->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('dentista_id')<div class="error-msg" id="error-dentista_id">{{ $message }}</div>@enderror
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="campo-cita_id">Cita relacionada <span style="font-weight:400;color:var(--text-light)">(opcional)</span></label>
                        <select name="cita_id" id="campo-cita_id" @error('cita_id') aria-invalid="true" aria-describedby="error-cita_id" @enderror class="field {{ $errors->has('cita_id') ? 'field-error' : '' }}">
                            <option value="">Sin cita asignada</option>
                            @foreach($citas as $c)
                                <option value="{{ $c->id }}" {{ old('cita_id') == $c->id ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::parse($c->fecha_hora)->format('d/m/Y H:i') }}
                                    {{ $c->servicio ? '· '.$c->servicio : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('cita_id')<div class="error-msg" id="error-cita_id">{{ $message }}</div>@enderror
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
                    <label class="field-label" for="campo-diagnostico">Diagnóstico <span style="color:var(--p500)">*</span></label>
                    <textarea name="diagnostico" id="campo-diagnostico" @error('diagnostico') aria-invalid="true" aria-describedby="error-diagnostico" @enderror class="field {{ $errors->has('diagnostico') ? 'field-error' : '' }}"
                        rows="4" style="resize:vertical"
                        placeholder="Describe el diagnóstico clínico del paciente..." required>{{ old('diagnostico') }}</textarea>
                    @error('diagnostico')<div class="error-msg" id="error-diagnostico">{{ $message }}</div>@enderror
                </div>

                <div class="field-group" style="margin-bottom:14px">
                    <label class="field-label" for="campo-tratamiento">Tratamiento realizado</label>
                    <textarea name="tratamiento" id="campo-tratamiento" @error('tratamiento') aria-invalid="true" aria-describedby="error-tratamiento" @enderror class="field {{ $errors->has('tratamiento') ? 'field-error' : '' }}" rows="3" style="resize:vertical"
                        placeholder="Procedimiento realizado, materiales utilizados...">{{ old('tratamiento') }}</textarea>
                    @error('tratamiento')<div class="error-msg" id="error-tratamiento">{{ $message }}</div>@enderror
                </div>

                <div class="field-group">
                    <label class="field-label" for="campo-observaciones">Observaciones adicionales</label>
                    <textarea name="observaciones" id="campo-observaciones" @error('observaciones') aria-invalid="true" aria-describedby="error-observaciones" @enderror class="field {{ $errors->has('observaciones') ? 'field-error' : '' }}" rows="3" style="resize:vertical"
                        placeholder="Notas clínicas, evolución esperada...">{{ old('observaciones') }}</textarea>
                    @error('observaciones')<div class="error-msg" id="error-observaciones">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Receta --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Receta médica
                    <span style="font-size:12px;font-weight:400;color:var(--text-muted);margin-left:6px">(opcional)</span>
                </div>
                <div class="field-group" style="margin-bottom:14px">
                    <label class="field-label" for="campo-receta">Medicamentos y dosis</label>
                    <textarea name="receta" id="campo-receta" @error('receta') aria-invalid="true" aria-describedby="error-receta" @enderror class="field {{ $errors->has('receta') ? 'field-error' : '' }}" rows="4" style="resize:vertical;font-family:'DM Mono',monospace,sans-serif;font-size:13px"
                        placeholder="Ej:&#10;- Ibuprofeno 400mg cada 8h por 3 días&#10;- Amoxicilina 500mg cada 12h por 7 días&#10;- Enjuague bucal con clorhexidina 2 veces al día">{{ old('receta') }}</textarea>
                    @error('receta')<div class="error-msg" id="error-receta">{{ $message }}</div>@enderror
                </div>

                <div class="field-group">
                    <label class="field-label" for="campo-proxima_visita">Fecha de próxima visita</label>
                    <input type="date" name="proxima_visita" id="campo-proxima_visita" @error('proxima_visita') aria-invalid="true" aria-describedby="error-proxima_visita" @enderror class="field campo-fecha {{ $errors->has('proxima_visita') ? 'field-error' : '' }}"
                        value="{{ old('proxima_visita') }}"
                        min="{{ now()->addDay()->format('Y-m-d') }}">
                    @error('proxima_visita')<div class="error-msg" id="error-proxima_visita">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Acciones --}}
            <div class="acciones-formulario">
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