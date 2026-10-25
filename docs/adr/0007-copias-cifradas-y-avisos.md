---
id: 0007
status: accepted
date: 2026-10-23
---

# ADR 0007 · Copias cifradas con GnuPG y avisos por correo con un vigilante propio y UptimeRobot

## Contexto
Desde la spec 030 (ADR 0005), las copias de la base de producción se guardan en claro en
`/var/backups/dentissa` y en el PC del responsable. Contienen todos los datos personales y de salud
y los hashes de las contraseñas. Además, nadie se entera de que la aplicación falla hasta que
alguien se queja. La spec 032 pide:
- copias que solo el responsable pueda abrir, con la clave fuera del servidor (CA7–CA10);
- correos con los errores del servidor, las ráfagas de inicios de sesión fallidos y las caídas
  vistas desde fuera, aunque el servidor esté apagado (CA1–CA6).
No hay presupuesto.

## Decisión
- **Copias:** OpenPGP asimétrico con GnuPG, que ya está en el servidor (Ubuntu 24.04, instalado por
  `preparar-servidor.sh`) y en el PC (Git para Windows).
  - `respaldar.sh` cifra en tubería con `gpg --recipient-file /opt/dentissa/entorno/copias.pub.asc`:
    el servidor solo tiene la clave pública y no puede descifrar.
  - La clave secreta, con frase, vive en el PC del responsable y en una segunda copia aparte. Al
    restaurar se lleva al servidor solo durante la restauración, en un llavero temporal en
    `/dev/shm` que se borra al terminar.
  - Antes de tocar la base, `restaurar.sh` descifra la copia entera hacia `/dev/null`; el MDC de
    OpenPGP detecta las copias alteradas o cortadas.
- **Avisos de la aplicación:**
  - el comando `php artisan dentissa:vigilar`, lanzado cada minuto por un temporizador de systemd,
    lee el log de acceso de nginx (volumen `nginx_logs` en solo lectura en `app`) y el canal de log
    `seguridad`;
  - en el canal `seguridad`, un listener de `Failed` y `Lockout` escribe huellas HMAC, sin IP ni
    correo;
  - el comando envía con el Mail de Laravel por el SMTP de Gmail, con una contraseña de aplicación;
  - como mucho un correo por tipo cada 30 minutos.
- **Caída vista desde fuera:** un monitor de UptimeRobot (plan gratuito) sobre `https://<dominio>/up`
  cada 5 minutos, con avisos por correo al caer y al volver.

## Alternativas consideradas
- **`age`** para las copias: es más simple, pero añade una herramienta al servidor y al PC.
- **`openssl enc` simétrico:** la clave tendría que estar en el servidor para cifrar, y CA8 lo
  prohíbe.
- **`restic` o `borg`:** cifran y deduplican, pero cambian todo el flujo de copias para un solo
  volcado por despliegue.
- **Vigilante en bash con `curl` SMTP:** no hay que tocar el Compose, pero no se puede probar con
  PHPUnit.
- **Healthchecks.io** (latido desde el servidor): detecta el servidor apagado, pero no que la web
  falle a través del túnel.
- **Better Stack:** gratuito con intervalos más cortos, pero con una cuenta más pesada para lo
  mismo.
- **Brevo** para enviar los correos: obliga a crear una cuenta nueva y verificar el remitente; el
  responsable ya usa Gmail.

## Consecuencias
- Si se pierden la clave secreta y su segunda copia, las copias no se recuperan. La guía lo dice y
  el ensayo de restauración usa la segunda copia.
- Restaurar exige que el responsable esté presente, con su clave y su frase. Las copias ya solo se
  hacen al desplegar, con el responsable delante.
- Dos servicios externos gratuitos, UptimeRobot y Gmail, con sus cuentas. Ninguno recibe datos
  personales: los correos solo llevan tipos, recuentos y horas.
- Volver a una versión anterior a la 032 obliga a usar el `restaurar.sh` de la 032 con una copia
  cifrada. `volver.sh` lo copia antes del checkout.
- El disco del servidor sigue sin cifrar (riesgo aceptado RS15.c / EX3), así que un archivo borrado
  puede quedar en el disco.
