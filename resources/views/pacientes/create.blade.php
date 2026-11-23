@extends('layouts.app')
@section('title', isset($paciente) ? 'Editar paciente' : 'Nuevo paciente')

@section('content')
<div style="max-width:800px">

    {{-- Breadcrumb --}}
    @php
        $base = match(auth()->user()->rol) {
            'admin'    => 'admin',
            'dentista' => 'dentista',
            default    => 'asistente',
        };
        // Spec 054: el correo solo es obligatorio para quien ya lo tiene (CA5); sin él, el teléfono sí (CA2).
        $correoObligatorio = isset($paciente) && filled($paciente->email);
        $posiblesDuplicados = $posiblesDuplicados ?? [];
        $masDuplicados = $masDuplicados ?? 0;
        $describe = fn (string $campo, bool $conAyuda) => implode(' ', array_filter([
            $errors->has($campo) ? "error-{$campo}" : null,
            $conAyuda ? "ayuda-{$campo}" : null,
        ]));
    @endphp
    @include('partials.migas', ['migas' => isset($paciente)
        ? [
            ['texto' => 'Pacientes', 'url' => route("{$base}.pacientes")],
            ['texto' => $paciente->nombre.' '.$paciente->apellido, 'url' => route("{$base}.pacientes.show", $paciente->id)],
            ['texto' => 'Editar'],
        ]
        : [
            ['texto' => 'Pacientes', 'url' => route("{$base}.pacientes")],
            ['texto' => 'Nuevo paciente'],
        ]])

    <form method="POST" class="form-paciente"
          action="{{ isset($paciente) ? route("{$base}.pacientes.update", $paciente->id) : route("{$base}.pacientes.store") }}">
        @csrf
        @if(isset($paciente)) @method('PUT') @endif
        @if($posiblesDuplicados)
            {{-- Spec 054 (D13): primer envío del formulario, para que Enter en un campo no confirme el duplicado. --}}
            <button type="submit" class="envio-por-defecto" tabindex="-1" aria-hidden="true">Registrar paciente</button>
        @endif

        <div style="display:flex;flex-direction:column;gap:18px">

            @if($posiblesDuplicados)
                {{-- Spec 054 (CA3, CA9): solo nombres y la ficha del rol; nunca qué dato coincide ni otros datos. --}}
                <div class="aviso-duplicado" role="alert">
                    <div class="aviso-duplicado-cabecera">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                        <h2 tabindex="-1" autofocus>Ya existe un paciente con estos datos</h2>
                    </div>
                    <p>Coinciden el nombre y el teléfono o la fecha de nacimiento que escribiste.</p>
                    <ul class="aviso-duplicado-lista">
                        @foreach($posiblesDuplicados as $duplicado)
                            <li class="texto-partible">{{ $duplicado['nombre'] }}</li>
                        @endforeach
                    </ul>
                    @if($masDuplicados > 0)
                        <p>y {{ $masDuplicados }} más</p>
                    @endif
                    <div class="acciones-formulario">
                        {{-- Spec 014 (CA14): el enlace solo a quien puede ver la ficha; el nombre ya está en la lista. --}}
                        @foreach($posiblesDuplicados as $duplicado)
                            @if($duplicado['url'])
                                <a href="{{ $duplicado['url'] }}" class="btn btn-primary texto-partible">Ver ficha de {{ $duplicado['nombre'] }}</a>
                            @endif
                        @endforeach
                        <button type="submit" name="confirmar_duplicado" value="1" class="btn btn-outline">Registrar de todos modos</button>
                    </div>
                </div>
            @endif

            {{-- Sección 1: Datos personales --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Datos personales
                </div>
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label" for="campo-nombre">Nombre <span style="color:var(--p500)">*</span></label>
                        <input type="text" name="nombre" id="campo-nombre" @error('nombre') aria-invalid="true" aria-describedby="error-nombre" @enderror class="field {{ $errors->has('nombre') ? 'field-error' : '' }}"
                            value="{{ old('nombre', $paciente->nombre ?? '') }}" required placeholder="María">
                        @error('nombre')<div class="error-msg" id="error-nombre">{{ $message }}</div>@enderror
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="campo-apellido">Apellido <span style="color:var(--p500)">*</span></label>
                        <input type="text" name="apellido" id="campo-apellido" @error('apellido') aria-invalid="true" aria-describedby="error-apellido" @enderror class="field {{ $errors->has('apellido') ? 'field-error' : '' }}"
                            value="{{ old('apellido', $paciente->apellido ?? '') }}" required placeholder="García">
                        @error('apellido')<div class="error-msg" id="error-apellido">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        @if($correoObligatorio)
                            <label class="field-label" for="campo-email">Correo electrónico <span style="color:var(--p500)">*</span></label>
                        @else
                            <label class="field-label" for="campo-email">Correo electrónico (opcional)</label>
                        @endif
                        <input type="email" name="email" id="campo-email" @error('email') aria-invalid="true" @enderror @if($describe('email', ! $correoObligatorio)) aria-describedby="{{ $describe('email', ! $correoObligatorio) }}" @endif class="field {{ $errors->has('email') ? 'field-error' : '' }}"
                            value="{{ old('email', $paciente->email ?? '') }}" @if($correoObligatorio) required @endif placeholder="correo@ejemplo.com">
                        @error('email')<div class="error-msg" id="error-email">{{ $message }}</div>@enderror
                        @unless($correoObligatorio)
                            <div class="field-hint field-hint-aviso" id="ayuda-email">{{ isset($paciente)
                                ? 'Si añades un correo, el paciente podrá entrar creando su contraseña con «¿Olvidaste tu contraseña?».'
                                : 'Sin correo, el paciente no tendrá acceso a la aplicación y el teléfono es obligatorio.' }}</div>
                        @endunless
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="campo-telefono">Teléfono</label>
                        <input type="tel" name="telefono" id="campo-telefono" @error('telefono') aria-invalid="true" @enderror @if($describe('telefono', ! $correoObligatorio)) aria-describedby="{{ $describe('telefono', ! $correoObligatorio) }}" @endif class="field {{ $errors->has('telefono') ? 'field-error' : '' }}"
                            value="{{ old('telefono', $paciente->telefono ?? '') }}" placeholder="55 1234 5678">
                        @error('telefono')<div class="error-msg" id="error-telefono">{{ $message }}</div>@enderror
                        @unless($correoObligatorio)
                            <div class="field-hint field-hint-aviso" id="ayuda-telefono">Obligatorio si el paciente no tiene correo.</div>
                        @endunless
                    </div>
                </div>
                @if(!isset($paciente))
                    <div class="field-group">
                        <label class="field-label" for="campo-password">Contraseña temporal</label>
                        <input type="text" name="password" id="campo-password" @error('password') aria-invalid="true" @enderror aria-describedby="{{ $describe('password', true) }}" class="field {{ $errors->has('password') ? 'field-error' : '' }}"
                            value="{{ old('password') }}" placeholder="Mín. 6 caracteres (dejar vacío para generar automáticamente)">
                        @error('password')<div class="error-msg" id="error-password">{{ $message }}</div>@enderror
                        <div class="field-hint field-hint-aviso" id="ayuda-password">Solo se usa si hay correo. Si se deja vacía, se genera una contraseña aleatoria.</div>
                    </div>
                @endif
                <div class="grid-3">
                    <div class="field-group">
                        <label class="field-label" for="campo-fecha_nacimiento">Fecha de nacimiento</label>
                        <input type="date" name="fecha_nacimiento" id="campo-fecha_nacimiento" @error('fecha_nacimiento') aria-invalid="true" aria-describedby="error-fecha_nacimiento" @enderror class="field {{ $errors->has('fecha_nacimiento') ? 'field-error' : '' }}"
                            value="{{ old('fecha_nacimiento', isset($paciente->fecha_nacimiento) ? \Carbon\Carbon::parse($paciente->fecha_nacimiento)->format('Y-m-d') : '') }}"
                            max="{{ \App\Agenda\RelojClinica::hoy()->subYear()->format('Y-m-d') }}">
                        @error('fecha_nacimiento')<div class="error-msg" id="error-fecha_nacimiento">{{ $message }}</div>@enderror
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="campo-genero">Género</label>
                        <select name="genero" id="campo-genero" @error('genero') aria-invalid="true" aria-describedby="error-genero" @enderror class="field {{ $errors->has('genero') ? 'field-error' : '' }}">
                            <option value="">No especificar</option>
                            @foreach(['femenino','masculino','otro'] as $g)
                                <option value="{{ $g }}" {{ old('genero', $paciente->genero ?? '') == $g ? 'selected' : '' }}>
                                    {{ ucfirst($g) }}
                                </option>
                            @endforeach
                        </select>
                        @error('genero')<div class="error-msg" id="error-genero">{{ $message }}</div>@enderror
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="campo-tipo_sangre">Tipo de sangre</label>
                        <select name="tipo_sangre" id="campo-tipo_sangre" @error('tipo_sangre') aria-invalid="true" aria-describedby="error-tipo_sangre" @enderror class="field {{ $errors->has('tipo_sangre') ? 'field-error' : '' }}">
                            <option value="">Desconocido</option>
                            @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $ts)
                                <option value="{{ $ts }}" {{ old('tipo_sangre', $paciente->tipo_sangre ?? '') == $ts ? 'selected' : '' }}>
                                    {{ $ts }}
                                </option>
                            @endforeach
                        </select>
                        @error('tipo_sangre')<div class="error-msg" id="error-tipo_sangre">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="field-group" style="margin-top:14px">
                    {{-- Spec 014 (CA8): en el alta del asistente, obligatorio y solo con sus dentistas. --}}
                    @php $doctorObligatorio = ! isset($paciente) && auth()->user()->rol === 'asistente'; @endphp
                    {{-- Spec 014 (CA15): quien tiene al paciente solo por una cita no le cambia el doctor; lo ve fijo. --}}
                    @if(isset($paciente) && ! ($puedeReasignar ?? true))
                    <label class="field-label" for="campo-dentista_fijo">Doctor asignado</label>
                    <input type="text" id="campo-dentista_fijo" class="field" value="{{ $doctorActual ?? 'Sin asignar' }}" readonly aria-describedby="ayuda-dentista_id">
                    <input type="hidden" name="dentista_id" value="{{ $paciente->dentista_id }}">
                    <div class="field-hint field-hint-aviso" id="ayuda-dentista_id">Solo el dentista asignado o la administración cambian el doctor.</div>
                    @else
                    <label class="field-label" for="campo-dentista_id">Doctor asignado @if($doctorObligatorio)<span class="marca-obligatorio">*</span>@endif</label>
                    <select name="dentista_id" id="campo-dentista_id" @error('dentista_id') aria-invalid="true" @enderror aria-describedby="{{ $describe('dentista_id', true) }}" @if($doctorObligatorio) required @endif class="field {{ $errors->has('dentista_id') ? 'field-error' : '' }}">
                        <option value="">{{ $doctorObligatorio ? 'Elige un dentista' : 'Sin asignar' }}</option>
                        @foreach($dentistas as $d)
                            <option value="{{ $d->id }}" {{ old('dentista_id', $paciente->dentista_id ?? '') == $d->id ? 'selected' : '' }}>
                                {{ $d->nombre_completo }}{{ $d->especialidad ? ' · '.$d->especialidad : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('dentista_id')<div class="error-msg" id="error-dentista_id">{{ $message }}</div>@enderror
                    <div class="field-hint {{ $doctorObligatorio ? 'field-hint-aviso' : '' }}" id="ayuda-dentista_id">{{ $doctorObligatorio ? 'Asigna uno de tus dentistas.' : 'Dentista de cabecera del paciente.' }}</div>
                    @endif
                </div>
            </div>

            {{-- Sección 2: Contacto --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Dirección y contacto
                </div>
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label" for="campo-direccion">Dirección</label>
                        <input type="text" name="direccion" id="campo-direccion" @error('direccion') aria-invalid="true" aria-describedby="error-direccion" @enderror class="field {{ $errors->has('direccion') ? 'field-error' : '' }}"
                            value="{{ old('direccion', $paciente->direccion ?? '') }}"
                            placeholder="Calle, número, colonia...">
                        @error('direccion')<div class="error-msg" id="error-direccion">{{ $message }}</div>@enderror
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="campo-ciudad">Ciudad</label>
                        @php
                            $ciudadGuardada = $paciente->ciudad ?? null;
                            $ciudadAntigua = \App\Pacientes\Ciudades::antigua($ciudadGuardada);
                            $ayudasCiudad = array_filter([$errors->has('ciudad') ? 'error-ciudad' : null, $ciudadAntigua !== null ? 'aviso-ciudad' : null]);
                        @endphp
                        <select name="ciudad" id="campo-ciudad" @error('ciudad') aria-invalid="true" @enderror @if($ayudasCiudad) aria-describedby="{{ implode(' ', $ayudasCiudad) }}" @endif class="field {{ $errors->has('ciudad') ? 'field-error' : '' }}">
                            @include('partials.opciones-ciudad', ['guardada' => $ciudadGuardada])
                        </select>
                        @error('ciudad')<div class="error-msg" id="error-ciudad">{{ $message }}</div>@enderror
                        @if($ciudadAntigua !== null)
                            <div class="field-hint field-hint-aviso" id="aviso-ciudad">La ciudad guardada «{{ $ciudadAntigua }}» no está en la lista: elige una o deja «Sin indicar»</div>
                        @endif
                    </div>
                </div>
                <div class="grid-2">
                    <div class="field-group">
                        <label class="field-label" for="campo-contacto_emergencia">Contacto de emergencia</label>
                        <input type="text" name="contacto_emergencia" id="campo-contacto_emergencia" @error('contacto_emergencia') aria-invalid="true" aria-describedby="error-contacto_emergencia" @enderror class="field {{ $errors->has('contacto_emergencia') ? 'field-error' : '' }}"
                            value="{{ old('contacto_emergencia', $paciente->contacto_emergencia ?? '') }}"
                            placeholder="Nombre completo">
                        @error('contacto_emergencia')<div class="error-msg" id="error-contacto_emergencia">{{ $message }}</div>@enderror
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="campo-telefono_emergencia">Teléfono de emergencia</label>
                        <input type="tel" name="telefono_emergencia" id="campo-telefono_emergencia" @error('telefono_emergencia') aria-invalid="true" aria-describedby="error-telefono_emergencia" @enderror class="field {{ $errors->has('telefono_emergencia') ? 'field-error' : '' }}"
                            value="{{ old('telefono_emergencia', $paciente->telefono_emergencia ?? '') }}"
                            placeholder="55 1234 5678">
                        @error('telefono_emergencia')<div class="error-msg" id="error-telefono_emergencia">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Sección 3: Info médica --}}
            <div class="card">
                <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
                    Información médica
                    <span style="font-size:12px;font-weight:400;color:var(--text-muted);margin-left:6px">(opcional)</span>
                </div>
                <div class="grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label" for="campo-alergias">Alergias conocidas</label>
                        <textarea name="alergias" id="campo-alergias" @error('alergias') aria-invalid="true" aria-describedby="error-alergias" @enderror class="field {{ $errors->has('alergias') ? 'field-error' : '' }}" rows="3" style="resize:vertical"
                            placeholder="Ej: Penicilina, látex, anestesia...">{{ old('alergias', $paciente->alergias ?? '') }}</textarea>
                        @error('alergias')<div class="error-msg" id="error-alergias">{{ $message }}</div>@enderror
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="campo-enfermedades_cronicas">Enfermedades crónicas</label>
                        <textarea name="enfermedades_cronicas" id="campo-enfermedades_cronicas" @error('enfermedades_cronicas') aria-invalid="true" aria-describedby="error-enfermedades_cronicas" @enderror class="field {{ $errors->has('enfermedades_cronicas') ? 'field-error' : '' }}" rows="3" style="resize:vertical"
                            placeholder="Ej: Diabetes tipo 2, hipertensión...">{{ old('enfermedades_cronicas', $paciente->enfermedades_cronicas ?? '') }}</textarea>
                        @error('enfermedades_cronicas')<div class="error-msg" id="error-enfermedades_cronicas">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="grid-2">
                    <div class="field-group">
                        <label class="field-label" for="campo-medicamentos_actuales">Medicamentos actuales</label>
                        <input type="text" name="medicamentos_actuales" id="campo-medicamentos_actuales" @error('medicamentos_actuales') aria-invalid="true" aria-describedby="error-medicamentos_actuales" @enderror class="field {{ $errors->has('medicamentos_actuales') ? 'field-error' : '' }}"
                            value="{{ old('medicamentos_actuales', $paciente->medicamentos_actuales ?? '') }}"
                            placeholder="Ej: Metformina 500mg...">
                        @error('medicamentos_actuales')<div class="error-msg" id="error-medicamentos_actuales">{{ $message }}</div>@enderror
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="campo-notas_medicas">Notas médicas generales</label>
                        <input type="text" name="notas_medicas" id="campo-notas_medicas" @error('notas_medicas') aria-invalid="true" aria-describedby="error-notas_medicas" @enderror class="field {{ $errors->has('notas_medicas') ? 'field-error' : '' }}"
                            value="{{ old('notas_medicas', $paciente->notas_medicas ?? '') }}"
                            placeholder="Observaciones relevantes...">
                        @error('notas_medicas')<div class="error-msg" id="error-notas_medicas">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="acciones-formulario">
                @if(isset($paciente))
                    <a href="{{ route("{$base}.pacientes.show", $paciente->id) }}" class="btn btn-ghost">Cancelar</a>
                @else
                    <a href="{{ route("{$base}.pacientes") }}" class="btn btn-ghost">Cancelar</a>
                @endif
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px" aria-hidden="true">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    {{ isset($paciente) ? 'Guardar cambios' : 'Registrar paciente' }}
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