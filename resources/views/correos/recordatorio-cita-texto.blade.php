Hola, {{ $nombre }}:

Te esperamos {{ $cuando }} en la consulta.

Tu cita: {{ $dia }} {{ $fechaCorta }} de {{ $anio }}, a las {{ $hora }}
Con: {{ $dentista }}
@if ($servicio)
Servicio: {{ $servicio }}
@endif
Dónde: {{ $contacto['direccion'] }}

Ver mis citas: {{ $enlace }}

Si necesitas cambiarla o cancelarla, entra en «Mis citas» o llama a la consulta. Este correo es solo un recordatorio: no hace falta responderlo.

Dentissa
Teléfono: {{ $contacto['telefono'] }}
Horario de la consulta: {{ $horario }}
