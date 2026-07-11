@extends('layouts.app')
@section('title', 'Nueva cita')

@section('content')

<div style="max-width:760px">

    {{-- Breadcrumb --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--text-muted)">
        <a href="{{ route('admin.citas') }}" style="color:var(--text-muted);text-decoration:none">Citas</a>
        <span>›</span>
        <span style="color:var(--text)">Nueva cita</span>
    </div>

    <form method="POST" action="{{ route('admin.citas.store') }}">
        @csrf

        <div style="display:flex;flex-direction:column;gap:18px">

            {{-- ── Sección: Paciente y dentista ─────────────────────── --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    ¿Quién y con quién?
                </div>
                <div class="grid-2">
                    <div>
                        <label class="field-label">Paciente <span style="color:var(--p500)">*</span></label>
                        <select name="paciente_id" class="field {{ $errors->has('paciente_id') ? 'field-error' : '' }}" required>
                            <option value="">Seleccionar paciente...</option>
                            @foreach($pacientes as $p)
                                <option value="{{ $p->id }}" {{ old('paciente_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->nombre }}{{ $p->telefono ? ' · '.$p->telefono : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('paciente_id')
                            <div class="field-hint field-hint-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="field-label">Dentista <span style="color:var(--p500)">*</span></label>
                        <select name="dentista_id" class="field {{ $errors->has('dentista_id') ? 'field-error' : '' }}" required
                                id="dentista-select">
                            <option value="">Seleccionar dentista...</option>
                            @foreach($dentistas as $d)
                                <option value="{{ $d->id }}" {{ old('dentista_id') == $d->id ? 'selected' : '' }}>
                                    {{ $d->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('dentista_id')
                            <div class="field-hint field-hint-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ── Sección: Fecha y hora ─────────────────────────────── --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    ¿Cuándo?
                </div>
                <div class="grid-3">
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
                        <input type="time" name="hora" value="{{ old('hora', $horaSugerida) }}"
                               class="field {{ $errors->has('hora') ? 'field-error' : '' }}"
                               min="08:00" max="20:00" step="900" required>
                        @error('hora')
                            <div class="field-hint field-hint-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="field-label">Duración (min) <span style="color:var(--p500)">*</span></label>
                        <input type="number" name="duracion_min" value="{{ old('duracion_min', 30) }}"
                               class="field" min="10" max="300" step="5" required id="duracion-field">
                        @error('duracion_min')
                            <div class="field-hint field-hint-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @error('hora')
                    @if(str_contains($message, 'conflicto') || str_contains($message, 'horario'))
                        <div style="background:var(--amber-bg);border:1px solid #FAC775;border-radius:var(--radius-sm);padding:10px 14px;margin-top:12px;font-size:13px;color:var(--amber)">
                            ⚠️ {{ $message }}
                        </div>
                    @endif
                @enderror
            </div>

            {{-- ── Sección: Servicio ─────────────────────────────────── --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Servicio
                    <span style="font-size:12px;font-weight:400;color:var(--text-muted);margin-left:6px">(opcional)</span>
                </div>

                <select name="servicio_id" class="field" id="servicio-select">
                    <option value="">Sin servicio asignado</option>
                    @php $catActual = ''; @endphp
                    @foreach($servicios as $s)
                        @if($s->categoria !== $catActual)
                            @if($catActual !== '') </optgroup> @endif
                            <optgroup label="{{ $s->categoria }}">
                            @php $catActual = $s->categoria; @endphp
                        @endif
                        <option value="{{ $s->id }}"
                                data-duracion="{{ $s->duracion_min }}"
                                data-precio="{{ $s->precio }}"
                                {{ old('servicio_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->nombre }} · ${{ number_format($s->precio, 0) }} · {{ $s->duracion_min }} min
                        </option>
                    @endforeach
                    @if($catActual !== '') </optgroup> @endif
                </select>

                {{-- Preview del servicio seleccionado --}}
                <div id="servicio-preview" style="display:none;margin-top:12px;background:var(--p50);border-radius:var(--radius-sm);padding:12px 14px;font-size:13px">
                    <div style="display:flex;gap:20px">
                        <div><span style="color:var(--text-muted)">Duración:</span> <strong id="prev-dur">—</strong> min</div>
                        <div><span style="color:var(--text-muted)">Precio referencia:</span> <strong id="prev-precio">—</strong></div>
                    </div>
                    <div style="font-size:11.5px;color:var(--text-light);margin-top:4px">
                        La duración se cargará automáticamente al seleccionar un servicio.
                    </div>
                </div>
            </div>

            {{-- ── Sección: Notas ────────────────────────────────────── --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Notas
                </div>
                <div class="grid-2">
                    <div>
                        <label class="field-label">Notas para el paciente</label>
                        <textarea name="notas_paciente" class="field" rows="3"
                            placeholder="Indicaciones previas, preparación..."
                            style="resize:vertical">{{ old('notas_paciente') }}</textarea>
                        <div class="field-hint">Visible para el paciente</div>
                    </div>
                    <div>
                        <label class="field-label">Notas internas</label>
                        <textarea name="notas_internas" class="field" rows="3"
                            placeholder="Observaciones del staff, contexto clínico..."
                            style="resize:vertical">{{ old('notas_internas') }}</textarea>
                        <div class="field-hint">Solo visible para el personal</div>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <a href="{{ route('admin.citas') }}" class="btn btn-ghost">Cancelar</a>
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

<style>
.field-label { display:block; font-size:11px; font-weight:600; letter-spacing:.07em; text-transform:uppercase; color:var(--text-light); margin-bottom:5px; }
.field {
    display:block; width:100%;
    padding:9px 13px;
    border:1px solid var(--border);
    border-radius:var(--radius-sm);
    font-size:13.5px;
    font-family:'DM Sans',sans-serif;
    color:var(--text);
    background:var(--white);
    outline:none;
    transition:border-color .15s;
}
.field:focus { border-color:var(--p300); box-shadow:0 0 0 3px rgba(212,83,126,.08); }
.field-error { border-color:var(--red)!important; }
.field-hint { font-size:11.5px; color:var(--text-light); margin-top:4px; }
.field-hint-error { color:var(--red); }
textarea.field { line-height:1.6; }
</style>

@push('scripts')
<script>
document.getElementById('servicio-select').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const dur   = opt.dataset.duracion;
    const precio = opt.dataset.precio;
    const preview = document.getElementById('servicio-preview');

    if (dur && precio) {
        document.getElementById('duracion-field').value = dur;
        document.getElementById('prev-dur').textContent = dur;
        document.getElementById('prev-precio').textContent = '$' + Number(precio).toLocaleString('es-MX');
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
});
</script>
@endpush
@endsection