@extends('layouts.app')
@section('title', 'Galería de trabajos')

{{--
    Gestión de la galería de trabajos (spec 039; CA8–CA11, CA15; candidato `c3`, design.md).
    Datos en FotosGaleriaController: $fotos (ordenadas), $quitar (la foto del diálogo, o null) y
    $limite. Quitar se confirma en una página (`admin.galeria.quitar`), sin JavaScript (D7).
    Solo clases del sistema dentro de `.formulario-accesible` (P9); la salida va siempre escapada.
--}}
@php
    $meses = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $fecha = fn ($dia) => $dia->day.' '.$meses[$dia->month].' '.$dia->year;
    $total = $fotos->count();
    $error = fn (string $campo) => $errors->first($campo);
    $describe = fn (string $campo, ?string $ayuda = null) => trim(($error($campo) ? "{$campo}-error " : '').($ayuda ?? ''));
    $paciente = old('con_paciente');
@endphp

@section('topbar-actions')
    <span class="formulario-accesible">
        <a href="#subir" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Subir foto
        </a>
    </span>
@endsection

@section('content')
<div class="formulario-accesible galeria-gestion">
    {{-- /review R3: con el diálogo abierto, el fondo queda inerte (sin JavaScript, desde el servidor). --}}
    <p class="galeria-gestion__intro" @if($quitar) inert @endif>Las fotos se ven en este orden en la página de inicio y en <a href="{{ route('galeria.publica') }}">la galería</a>. Hay {{ $total }} de {{ $limite }}.</p>

    <div class="galeria-gestion__grid" @if($quitar) inert @endif>
        <section class="card" aria-labelledby="t-fotos">
            <h2 id="t-fotos">Fotos publicadas</h2>
            @if($total === 0)
                <p class="galeria-gestion__intro">Aún no hay fotos publicadas. Sube la primera con el formulario.</p>
            @else
                <ol class="foto-lista" role="list">
                    @foreach($fotos as $foto)
                        <li class="foto-tarjeta">
                            <img class="foto-tarjeta__img" src="{{ route('galeria.foto', [$foto, 800], false) }}" width="{{ $foto->ancho }}" height="{{ $foto->alto }}" alt="" loading="lazy" data-origen="propia">
                            <div>
                                <span class="foto-tarjeta__pos">Posición {{ $foto->posicion }}</span>
                                <p class="foto-tarjeta__desc">{{ $foto->descripcion }}</p>
                                @if($foto->con_paciente)
                                    <p class="foto-tarjeta__estado"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg> Consentimiento declarado por {{ $foto->consentimiento_nombre }} el {{ $fecha($foto->consentimiento_en) }}</p>
                                @else
                                    <p class="foto-tarjeta__estado">Sin pacientes en la foto</p>
                                @endif
                            </div>
                            <div class="foto-tarjeta__acciones">
                                @unless($loop->first)
                                    <form method="POST" action="{{ route('admin.galeria.subir', $foto) }}" class="form-en-linea">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline" aria-label="Mover arriba: {{ $foto->descripcion }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M12 19V5m-6 6 6-6 6 6"/></svg> Arriba</button>
                                    </form>
                                @endunless
                                @unless($loop->last)
                                    <form method="POST" action="{{ route('admin.galeria.bajar', $foto) }}" class="form-en-linea">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline" aria-label="Mover abajo: {{ $foto->descripcion }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M12 5v14m-6-6 6 6 6-6"/></svg> Abajo</button>
                                    </form>
                                @endunless
                                <a href="{{ route('admin.galeria.quitar', $foto) }}" class="btn btn-outline btn-quitar" aria-label="Quitar: {{ $foto->descripcion }}">Quitar</a>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <section class="card" id="subir" aria-labelledby="t-subir">
            <h2 id="t-subir">Nueva foto</h2>
            @if($errors->any())
                {{-- /review R5: tras la recarga, el aviso recibe el foco y lleva a cada campo con error. --}}
                <div class="aviso-error" role="alert" tabindex="-1" autofocus>
                    <p><strong>No se publicó la foto.</strong> Corrige lo marcado y vuelve a subirla.</p>
                    <ul class="aviso-error__lista">
                        @foreach(['foto' => 'foto', 'descripcion' => 'descripcion', 'con_paciente' => 'con_paciente-no', 'consentimiento' => 'consentimiento'] as $campo => $destino)
                            @if($error($campo))<li><a href="#{{ $destino }}">{{ $error($campo) }}</a></li>@endif
                        @endforeach
                    </ul>
                </div>
            @endif
            <form class="galeria-form" action="{{ route('admin.galeria.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                <div class="campo">
                    <label class="field-label" for="foto">Foto</label>
                    @if($error('foto'))<p class="campo-error" id="foto-error">{{ $error('foto') }}</p>@endif
                    <input class="field campo-archivo" type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" aria-describedby="{{ $describe('foto', 'foto-ayuda') }}" @if($error('foto')) aria-invalid="true" @endif>
                    <p class="ayuda" id="foto-ayuda">JPG, PNG o WebP, hasta 5 MB. Se publica sin los datos de la cámara.</p>
                </div>
                <div class="campo">
                    <label class="field-label" for="descripcion">Descripción</label>
                    @if($error('descripcion'))<p class="campo-error" id="descripcion-error">{{ $error('descripcion') }}</p>@endif
                    <textarea class="field" id="descripcion" name="descripcion" rows="3" maxlength="160" aria-describedby="{{ $describe('descripcion', 'descripcion-ayuda') }}" @if($error('descripcion')) aria-invalid="true" @endif>{{ is_string(old('descripcion')) ? old('descripcion') : '' }}</textarea>
                    <p class="ayuda" id="descripcion-ayuda">Se lee debajo de la foto y es su texto alternativo.</p>
                </div>
                <fieldset class="campo-paciente" @if($error('con_paciente')) aria-describedby="con_paciente-error" @endif>
                    <legend>¿Aparece la boca o los dientes de un paciente?</legend>
                    @if($error('con_paciente'))<p class="campo-error" id="con_paciente-error">{{ $error('con_paciente') }}</p>@endif
                    <label class="opcion"><input type="radio" id="con_paciente-no" name="con_paciente" value="no" @checked($paciente === 'no') @if($error('con_paciente')) aria-invalid="true" @endif> No, no aparece ningún paciente</label>
                    <label class="opcion"><input type="radio" id="con_paciente-si" name="con_paciente" value="si" @checked($paciente === 'si') @if($error('con_paciente')) aria-invalid="true" @endif> Sí, aparece un paciente</label>
                </fieldset>
                <div class="consentimiento">
                    @if($error('consentimiento'))<p class="campo-error" id="consentimiento-error">{{ $error('consentimiento') }}</p>@endif
                    <label class="campo-casilla"><input type="checkbox" id="consentimiento" name="consentimiento" value="1" @checked(old('consentimiento')) aria-describedby="{{ $describe('consentimiento', 'consentimiento-ayuda') }}" @if($error('consentimiento')) aria-invalid="true" @endif> Tengo el consentimiento escrito del paciente para publicar esta foto y no se le puede reconocer</label>
                    <p class="ayuda" id="consentimiento-ayuda">Guarda el documento firmado en la clínica; aquí queda tu nombre y la fecha.</p>
                </div>
                <button type="submit" class="btn btn-primary">Publicar foto</button>
            </form>
        </section>
    </div>

    @if($quitar)
        <div class="modal-overlay">
            <div class="modal-box card" role="dialog" aria-modal="true" aria-labelledby="t-quitar" aria-describedby="d-quitar">
                <h2 id="t-quitar">¿Quitar esta foto?</h2>
                <p id="d-quitar">«{{ $quitar->descripcion }}» deja de verse en la página de inicio y en la galería, y su dirección deja de abrirse.</p>
                <div class="acciones-fila">
                    <a href="{{ route('admin.galeria.index') }}" class="btn btn-outline" autofocus>Cancelar</a>
                    <form method="POST" action="{{ route('admin.galeria.destroy', $quitar) }}" class="form-en-linea">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Quitar</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
