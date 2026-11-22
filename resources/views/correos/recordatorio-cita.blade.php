@extends('correos.marco-acceso')
{{-- Spec 052 (CA1, CA9, CA10; design.md `c2`): el recordatorio de la cita. La fecha y la hora en una tarjeta
     con la semana de la consulta (la agenda de la página de inicio, 038) y el día marcado; con quién, qué y
     dónde antes de la única acción, «Ver mis citas»; el teléfono (como texto: CA9) y el horario en el talón.
     Sin notas ni expediente (CA10). Sin estilos en la plantilla: correos/acceso.css y correos/recordatorio.css
     se pasan a estilos en línea al enviar. --}}

@section('asunto', "Tu cita es {$cuando}, {$dia} {$fechaCorta}, a las {$hora}")

@section('cuerpo')
    <p class="saludo">Hola, {{ $nombre }}:</p>
    <p>Te esperamos {{ $cuando }} en la consulta.</p>
    <table role="presentation" class="cita" cellpadding="0" cellspacing="0" border="0">
        <tr><td class="cita-celda">
            <p class="etiqueta">Tu cita</p>
            <h1 class="cita-dia"><span class="semana-dia">{{ ucfirst($dia) }}</span> <span class="nw">{{ $fechaCorta }}</span> de {{ $anio }}</h1>
            <p class="cita-hora">{{ $hora }}</p>
            <table role="presentation" class="semana" aria-hidden="true" cellpadding="0" cellspacing="0" border="0">
                <tr>@foreach ($semana as $dia_)<td @class(['hoy' => $dia_['hoy'], 'cerrado' => $dia_['cerrado']])>{{ $dia_['letra'] }}</td>@endforeach</tr>
            </table>
        </td></tr>
    </table>
    <dl class="datos">
        <dt>Con</dt><dd>{{ $dentista }}</dd>
        @if ($servicio)
            <dt>Servicio</dt><dd>{{ $servicio }}</dd>
        @endif
        <dt>Dónde</dt><dd>{{ $contacto['direccion'] }}</dd>
    </dl>
    <p class="correo-boton"><a class="btn-primario" href="{{ $enlace }}">Ver mis citas</a></p>
    <p class="nota">Si necesitas cambiarla o cancelarla, entra en «Mis citas» o llama a la consulta. Este correo es solo un recordatorio: no hace falta responderlo.</p>
@endsection

@section('talon')
    <dt>Teléfono</dt><dd><span class="tel">{{ $contacto['telefono'] }}</span></dd>
    <dt>Horario de la consulta</dt><dd>{{ $horario }}</dd>
@endsection
