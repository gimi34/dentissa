{{-- Spec 032 (CA5): solo tipo, recuentos, horas y cómo consultar; ningún dato de los logs. --}}
Dentissa: {{ $titulo }}

@if ($tipo === 'prueba')
Este es un aviso de prueba: el envío de avisos de producción funciona.
@elseif ($tipo === 'errores')
{{ $recuento }} errores del servidor (respuestas 5xx) entre las {{ $desdeTexto }} y las {{ $hastaTexto }} (hora de la clínica, {{ $zona }}).
En 5 minutos, más del 1 % de las respuestas fueron errores del servidor.
@elseif ($tipo === 'accesos')
{{ $recuento }} inicios de sesión fallidos entre las {{ $desdeTexto }} y las {{ $hastaTexto }} (hora de la clínica, {{ $zona }}).
En 5 minutos hubo más de 10 desde un mismo origen o contra una misma cuenta. El límite de intentos sigue frenándolos.
@elseif ($tipo === 'formato')
{{ $recuento }} líneas del log de acceso de nginx no tienen el formato esperado entre las {{ $desdeTexto }} y las {{ $hastaTexto }} (hora de la clínica, {{ $zona }}).
Mientras no se corrija, el aviso de errores del servidor no puede contar las respuestas.
@endif
@if ($anteriorTexto)
Son los acumulados desde el aviso anterior, de las {{ $anteriorTexto }}: entre medias no se envió ningún correo de este tipo.
@endif

Cómo consultar el detalle en el servidor (por SSH, en /opt/dentissa/app):
- Ejecuciones del vigilante: journalctl -u dentissa-vigilar --since today
@if ($tipo === 'errores' || $tipo === 'formato')
- Log de acceso: docker compose -f docker-compose.prod.yml exec web tail -n 200 /var/log/nginx/dentissa-access.log
- Errores de la aplicación: docker compose -f docker-compose.prod.yml exec app tail -n 100 storage/logs/laravel.log
@elseif ($tipo === 'accesos')
- Fallos de acceso (huellas, sin IP ni correo): docker compose -f docker-compose.prod.yml exec app tail -n 50 storage/logs/seguridad-{{ $dia }}.log
@endif
- Más detalle: deploy/README.md, sección de avisos.
