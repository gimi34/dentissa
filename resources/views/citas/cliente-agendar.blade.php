@extends('layouts.app')
@section('title', 'Agendar cita')

@section('content')

<div style="max-width:640px">

    {{-- Migas de pan: clases con tokens en lugar de style="" (spec 033, T025). --}}
    <div id="migas" class="migas">
        <a href="{{ route('cliente.dashboard') }}">Inicio</a>
        <span>›</span>
        <span class="migas-actual">Agendar cita</span>
    </div>

    <form method="POST" action="{{ route('cliente.agendar.store') }}">
        @csrf

        <div style="display:flex;flex-direction:column;gap:18px">

            {{-- ── Servicio y dentista ──────────────────────────────── --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    ¿Qué necesitas?
                </div>

                <div style="margin-bottom:16px">
                    <label class="field-label">Servicio <span style="color:var(--p500)">*</span></label>
                    <select name="servicio_id" class="field {{ $errors->has('servicio_id') ? 'field-error' : '' }}" required>
                        <option value="">Seleccionar servicio...</option>
                        @php $catActual = ''; @endphp
                        @foreach($servicios as $s)
                            @if($s->categoria !== $catActual)
                                @if($catActual !== '') </optgroup> @endif
                                <optgroup label="{{ $s->categoria }}">
                                @php $catActual = $s->categoria; @endphp
                            @endif
                            <option value="{{ $s->id }}" {{ old('servicio_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->nombre }} · {{ $s->duracion_min }} min
                            </option>
                        @endforeach
                        @if($catActual !== '') </optgroup> @endif
                    </select>
                    @error('servicio_id')
                        <div class="field-hint field-hint-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="field-label">Dentista <span style="color:var(--p500)">*</span></label>
                    <select name="dentista_id" class="field {{ $errors->has('dentista_id') ? 'field-error' : '' }}" required>
                        <option value="">Seleccionar dentista...</option>
                        @foreach($dentistas as $d)
                            <option value="{{ $d->id }}" {{ old('dentista_id') == $d->id ? 'selected' : '' }}>
                                {{ $d->nombre }}{{ $d->especialidad ? ' · '.$d->especialidad : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('dentista_id')
                        <div class="field-hint field-hint-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- ── Fecha y hora ──────────────────────────────────────── --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    ¿Cuándo?
                </div>
                <div class="grid-2">
                    <div>
                        <label class="field-label">Fecha <span style="color:var(--p500)">*</span></label>
                        <input type="date" name="fecha" value="{{ old('fecha', $fechaSugerida) }}"
                               class="field {{ $errors->has('fecha') ? 'field-error' : '' }}"
                               min="{{ now()->format('Y-m-d') }}" required>
                        @error('fecha')
                            <div class="field-hint field-hint-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="field-label">Hora <span style="color:var(--p500)">*</span></label>
                        <input type="time" name="hora" value="{{ old('hora', '09:00') }}"
                               class="field {{ $errors->has('hora') ? 'field-error' : '' }}"
                               min="08:00" max="20:00" step="900" required>
                        @error('hora')
                            <div class="field-hint field-hint-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ── Notas ──────────────────────────────────────────────── --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Notas
                    <span style="font-size:12px;font-weight:400;color:var(--text-muted);margin-left:6px">(opcional)</span>
                </div>
                <textarea name="notas_paciente" class="field" rows="3"
                    placeholder="Cuéntanos brevemente el motivo de tu visita..."
                    style="resize:vertical">{{ old('notas_paciente') }}</textarea>
            </div>

            {{-- Acciones --}}
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <a href="{{ route('cliente.dashboard') }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Agendar cita
                </button>
            </div>

        </div>
    </form>
</div>

@push('styles')
<link href="{{ asset('css/citas.css') }}?v={{ filemtime(public_path('css/citas.css')) }}" rel="stylesheet">
@endpush

@endsection
