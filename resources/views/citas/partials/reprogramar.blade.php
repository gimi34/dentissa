{{--
    Pantalla «Reprogramar cita» (spec 028), común al personal y al paciente.

    Recibe:
      $cita                   id, fecha_hora, estado, servicio y el nombre que se muestra
      $quien                  ['etiqueta' => 'Paciente' | 'Dentista', 'nombre' => …]
      $motivoNoReprogramable  null si la cita se puede mover; si no, el motivo (A6)
      $rutaGuardar            PATCH de la reprogramación
      $rutaCancelar           PATCH de la cancelación definitiva (la de la 026, D5)
      $camposCancelar         campos ocultos de la cancelación (p. ej. estado=cancelada)
      $rutaDetalle            detalle de la cita

    Solo clases del sistema dentro de `.formulario-accesible` (P9). Cada formulario lleva el
    campo `formulario`: el valor anterior y los errores se pintan solo en su sección (D10);
    sin él, en la de reprogramar (A43). `old()` solo se pinta si es texto (A34).
--}}
@php
    $seccion = old('formulario') === 'no-reagendar' ? 'no-reagendar' : 'reprogramar';
    $anterior = fn (string $campo, string $porDefecto = '') => is_string(old($campo)) ? old($campo) : $porDefecto;
    $inicio = \Carbon\Carbon::parse($cita->fecha_hora);
    $errorDe = fn (string $campo, string $de) => $seccion === $de ? $errors->first($campo) : '';
@endphp
<div class="formulario-accesible" id="reprogramar-cita">
    <div class="pila-tarjetas">
        <div class="card">
            <dl class="resumen-cita">
                <dt>{{ $quien['etiqueta'] }}</dt>
                <dd>{{ $quien['nombre'] }}</dd>
                <dt>Servicio</dt>
                <dd>{{ $cita->servicio ?? 'Consulta dental' }}</dd>
                <dt>Fecha</dt>
                <dd>{{ $inicio->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}</dd>
                <dt>Hora</dt>
                <dd>{{ $inicio->format('H:i') }}</dd>
                <dt>Estado</dt>
                <dd>@include('partials.badge-estado', ['estado' => $cita->estado])</dd>
            </dl>
        </div>

        @if($motivoNoReprogramable !== null)
            <div class="card">
                {{-- El mismo texto ya lo muestra el aviso del layout cuando viene de un rechazo (A43). --}}
                @if(session('error') !== $motivoNoReprogramable)
                    <p class="aviso-plazo">{{ $motivoNoReprogramable }}</p>
                @endif
                <a href="{{ $rutaDetalle }}" class="btn btn-outline">Volver al detalle de la cita</a>
            </div>
        @else
            <section class="card" id="nueva-fecha" aria-labelledby="nueva-fecha-titulo">
                <div class="section-head">
                    <h2 class="section-title" id="nueva-fecha-titulo">Nueva fecha y hora</h2>
                </div>
                <form method="POST" action="{{ $rutaGuardar }}" novalidate>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="formulario" value="reprogramar">

                    @foreach(['fecha' => ['Fecha', 'date', $inicio->format('Y-m-d')], 'hora' => ['Hora', 'time', $inicio->format('H:i')]] as $campo => [$etiqueta, $tipo, $actual])
                        @php $error = $errorDe($campo, 'reprogramar'); @endphp
                        <div class="campo">
                            <label class="field-label" for="reprogramar-{{ $campo }}">{{ $etiqueta }}</label>
                            <input type="{{ $tipo }}" name="{{ $campo }}" id="reprogramar-{{ $campo }}" class="field" required
                                   value="{{ $seccion === 'reprogramar' ? $anterior($campo, $actual) : $actual }}"
                                   @if($error) aria-invalid="true" aria-describedby="reprogramar-{{ $campo }}-error" @endif>
                            @if($error)
                                <p class="field-hint field-hint-error" id="reprogramar-{{ $campo }}-error" role="alert">{{ $error }}</p>
                            @endif
                        </div>
                    @endforeach

                    @php $error = $errorDe('motivo', 'reprogramar'); @endphp
                    <div class="campo">
                        <label class="field-label" for="reprogramar-motivo">Motivo del cambio (opcional)</label>
                        <textarea name="motivo" id="reprogramar-motivo" class="field" rows="2" maxlength="500"
                                  @if($error) aria-invalid="true" aria-describedby="reprogramar-motivo-error" @endif>{{ $seccion === 'reprogramar' ? $anterior('motivo') : '' }}</textarea>
                        @if($error)
                            <p class="field-hint field-hint-error" id="reprogramar-motivo-error" role="alert">{{ $error }}</p>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar nueva fecha</button>
                </form>
            </section>

            <section class="card" id="no-reagendar" aria-labelledby="no-reagendar-titulo">
                <div class="section-head">
                    <h2 class="section-title" id="no-reagendar-titulo">No reagendar</h2>
                </div>
                <p class="aviso-plazo">Si la cita no se va a reagendar, puedes cancelarla definitivamente. No se puede deshacer.</p>
                <form method="POST" action="{{ $rutaCancelar }}" novalidate>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="formulario" value="no-reagendar">
                    @foreach($camposCancelar as $nombre => $valor)
                        <input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">
                    @endforeach

                    @php $error = $errorDe('motivo', 'no-reagendar') ?: $errorDe('estado', 'no-reagendar'); @endphp
                    <div class="campo">
                        <label class="field-label" for="cancelar-motivo">Motivo de la cancelación (opcional)</label>
                        <textarea name="motivo" id="cancelar-motivo" class="field" rows="2" maxlength="500"
                                  @if($error) aria-invalid="true" aria-describedby="cancelar-motivo-error" @endif>{{ $seccion === 'no-reagendar' ? $anterior('motivo') : '' }}</textarea>
                        @if($error)
                            <p class="field-hint field-hint-error" id="cancelar-motivo-error" role="alert">{{ $error }}</p>
                        @endif
                    </div>

                    {{-- Confirmación de una acción irreversible, sin JS (WCAG 3.3.4; /review 028 R6). --}}
                    @php $error = $errorDe('confirmar', 'no-reagendar'); @endphp
                    <div class="campo">
                        <div class="campo-casilla">
                            <input type="checkbox" name="confirmar" id="cancelar-confirmar" value="1" required
                                   @if($error) aria-invalid="true" aria-describedby="cancelar-confirmar-error" @endif>
                            <label class="field-label" for="cancelar-confirmar">Entiendo que la cancelación no se puede deshacer</label>
                        </div>
                        @if($error)
                            <p class="field-hint field-hint-error" id="cancelar-confirmar-error" role="alert">{{ $error }}</p>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-danger">Cancelar la cita definitivamente</button>
                </form>
            </section>
        @endif
    </div>
</div>
