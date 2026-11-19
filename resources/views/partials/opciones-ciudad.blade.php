{{--
    Spec 051: las opciones del campo «Ciudad». Recibe `$guardada`, la ciudad del paciente (o nada).

    Tras un envío con errores manda lo que se eligió, también «Sin indicar»; se mira la clave porque
    `old()` no distingue «Sin indicar» de otro formulario de la página, como el de la contraseña (D14).
--}}
@php
    $antigua = \App\Pacientes\Ciudades::antigua($guardada ?? null);
    $elegida = array_key_exists('ciudad', session()->getOldInput()) ? old('ciudad') : trim((string) ($guardada ?? ''));
    $valida = is_string($elegida) && (\App\Pacientes\Ciudades::contiene($elegida) || $elegida === $antigua);
    $elegida = $valida ? $elegida : '';
@endphp
@if($antigua !== null)
    <option value="{{ $antigua }}" @selected($elegida === $antigua)>{{ $antigua }} (no está en la lista)</option>
@endif
<option value="" @selected($elegida === '')>Sin indicar</option>
@foreach(\App\Pacientes\Ciudades::grupos() as $estado => $municipios)
    <optgroup label="{{ $estado }}">
        @foreach($municipios as $municipio)
            <option value="{{ $municipio }}" @selected($elegida === $municipio)>{{ $municipio }}</option>
        @endforeach
    </optgroup>
@endforeach
<option value="{{ \App\Pacientes\Ciudades::OTRO_LUGAR }}" @selected($elegida === \App\Pacientes\Ciudades::OTRO_LUGAR)>{{ \App\Pacientes\Ciudades::OTRO_LUGAR }}</option>
