Tu contraseña cambió

El {{ $cuando }} se eligió una contraseña nueva para tu cuenta y cerramos todas tus sesiones abiertas.

Si fuiste tú, no tienes que hacer nada.

¿No fuiste tú? Llama a la consulta: {{ $contacto['telefono'] }}
Horario:
@foreach($contacto['horario'] as $dias => $horas)
{{ $dias }} {{ $horas }}
@endforeach
