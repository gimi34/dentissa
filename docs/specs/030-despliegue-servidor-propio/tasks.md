---
spec: 030-despliegue-servidor-propio
plan: plan.md
status: approved
---

# Tareas · 030 Producción en un servidor propio expuesto a internet

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.
- **Tareas en el servidor (T040–T046, plan D13):** las ejecuta el usuario por SSH con `cloudflared`
  parado y pega la salida; el agente nunca lee `/opt/dentissa/.env.production`, claves ni
  contraseñas generadas, y la nota recoge solo resultados (sin secretos).

## Constitution Check
| Principio | Resultado | Justificación | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan 030 `approved`; estas tareas se aprueban y se analizan antes de `/implement`. | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | T010, T011, T012 y T014 se escriben antes y fallan hoy; cada implementación `depende:` de su test. T013 es de caracterización y se comprueba rompiendo (`app.debug=true`). Los controles de seguridad (T020–T023, T027) se comprueban además rompiendo lo protegido. Lo que solo existe en el servidor (CA1, CA3, CA6, CA7, CA9 y parte de CA5 y CA10) son comprobaciones declaradas en T040–T046 (constitución 1.2.1); lo público, en T097. | test: T010–T014 (fallo previo anotado) · manual: notas de T040–T046 |
| P3 Autorización en servidor | ➖ | Ninguna tarea cambia rutas protegidas ni permisos; T022 solo añade `throttle` a rutas públicas. | — |
| P4 Entrada validada y SQL con bindings | ✅ | T023 usa Eloquent; ninguna tarea añade entradas. | test: T012 · lint: semgrep `p/php` en T050 |
| P5 Nada sensible en el repo | ✅ | Los secretos solo en el servidor (T040, fuera del repo); T025 deja fuera `.env*`, `.git` y el volcado; T042 lista la imagen. | test: T014 · lint: gitleaks en T050 · manual: T042 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias PHP ni npm nuevas; T002 fija imágenes oficiales con versión exacta. | lint: composer audit, npm audit y trivy fs en T050 |
| P7 Auditoría de datos sensibles | ➖ | Sin eventos de auditoría (spec 020). | — |
| P8 Correlación y logs limpios | ✅ | T023 no escribe contraseñas en el log; T031 comprueba `APP_DEBUG=false` y `LOG_LEVEL=info` en cada despliegue. | test: T012, T013 · manual: T040 |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Sin cambios de vistas: los mensajes salen por el bloque de errores existente. | — |

## Preparación
- [ ] T001 Registrar la línea base en `feature/030-despliegue-servidor-propio`: commit de partida (`impl_base`), recuento de `php artisan test`, `composer audit --locked`, `npm audit` y las imágenes actuales de `Dockerfile` y `docker-compose.yml` — docs/specs/030-despliegue-servidor-propio/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cinco datos
- [ ] T002 Elegir las versiones exactas de imagen (plan D5): `php:8.4.N-fpm`, `composer:2.N.N`, `nginx:1.N.N-alpine`, `mariadb:10.11.N`, `phpmyadmin:5.N.N` (solo local) y `cloudflare/cloudflared:AAAA.M.N`, cada una la más nueva publicada hace ≥ 7×24 h en Docker Hub (fecha de la etiqueta consultada en `hub.docker.com/v2/repositories/<imagen>/tags/<etiqueta>`), imagen oficial o del publicador verificado y sin cambio de versión mayor ni menor respecto a la línea base (PHP 8.4, MariaDB 10.11) — docs/specs/030-despliegue-servidor-propio/tasks.md — hecho cuando: la nota recoge cada imagen con su etiqueta, fecha de publicación y publicador — cubre: CA10 — depende: T001

## Tests (antes de implementar)
- [ ] T010 [P] Escribir `LimiteIntentosTest` según la Estrategia de pruebas del plan: bloqueo del login tras 5 fallos con el mensaje de Contratos y desbloqueo pasado el minuto (`travel`); mismo texto con correo existente e inexistente; el bloqueo es por correo e IP; el sexto POST de alta (`/registro` o `/registro/contacto`, contador común) se rechaza sin crear usuario y sin devolver contraseñas en `old()`; la sexta vuelta de Google (Socialite simulado) va a `login` con el mensaje — tests/Feature/LimiteIntentosTest.php — hecho cuando: los cinco casos fallan por la razón esperada (no hay límite) y el resto de la suite sigue en verde — cubre: CA8
- [ ] T011 [P] Escribir `ProxyHttpsTest`: con `X-Forwarded-Proto: https` desde el proxy, `url('/')` y la redirección de `/` salen en https; con `session.secure=true` la cookie de sesión lleva `Secure`; con un `X-Forwarded-For` falso, `request()->ip()` sigue siendo `REMOTE_ADDR` — tests/Feature/ProxyHttpsTest.php — hecho cuando: los casos de https fallan por la razón esperada (esquema http sin `trustProxies`) y el de la IP queda anotado (pasa hoy; se comprueba rompiendo en T020) — cubre: CA2
- [ ] T012 [P] Escribir `RotarCredencialesTest` y la firma vacía del comando (`dentissa:rotar-credenciales`, que lanza "sin implementar"): con `--confirmar` la contraseña anterior deja de valer y la mostrada sí; una cuenta solo de Google no cambia; sin `--confirmar` no cambia nada y devuelve 1; `test_no_escribe_las_contrasenas_en_el_log` (log simulado) — tests/Feature/RotarCredencialesTest.php, app/Console/Commands/RotarCredencialesVolcado.php — hecho cuando: los casos fallan en sus aserciones por la firma vacía — cubre: CA5
- [ ] T013 [P] Escribir `ErroresProduccionTest` (caracterización): con `app.debug=false`, una excepción en una ruta registrada solo en el test devuelve 500 sin el mensaje de la excepción, sin rutas de archivos ni traza — tests/Feature/ErroresProduccionTest.php — hecho cuando: pasa y se comprueba rompiendo (con `app.debug=true` el test falla), anotado — cubre: CA4
- [ ] T014 [P] Escribir `ConfiguracionProduccionTest` leyendo los archivos como texto (sin dependencias nuevas): en `docker-compose.prod.yml` ningún servicio tiene `ports`, no hay `phpmyadmin`, `db` usa `MARIADB_USER` y la app no se conecta como root; toda `image:` y todo `FROM` de los dos Compose y los dos Dockerfiles llevan versión exacta (sin `latest`, con números) y la misma etiqueta en local y producción para cada imagen compartida; el montaje de `local.ini` de los dos Compose apunta a un archivo que existe; `.dockerignore` contiene las exclusiones de Cambios por módulo — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: falla por la razón esperada (no existe el Compose de producción, imágenes sin fijar, montaje roto y `.dockerignore` incompleto) — cubre: CA3, CA5, CA10

## Implementación — aplicación
- [ ] T020 [P] Añadir `trustProxies(at: '*', headers: X_FORWARDED_PROTO | X_FORWARDED_HOST | X_FORWARDED_PORT)` sin `X_FORWARDED_FOR` (plan D3; control de TM4 y TM9) — bootstrap/app.php — hecho cuando: T011 pasa y se comprueba rompiendo (añadir `X_FORWARDED_FOR` hace fallar el caso de la IP; quitar `trustProxies` hace fallar los de https), anotado — cubre: CA2 — depende: T011
- [ ] T021 [P] Limitar el login en `AuthController::login` con `RateLimiter`, clave `minúsculas(email)|ip`: si hay 5 fallos, rechaza sin intentar autenticar con el mensaje de Contratos; cada fallo suma con 60 s y el éxito limpia la clave (control de TM3) — app/Http/Controllers/AuthController.php — hecho cuando: los tres casos de login de T010 pasan y se comprueba rompiendo (sin la comprobación previa el bloqueo falla), anotado — cubre: CA8 — depende: T010
- [ ] T022 Registrar los limitadores `registro` y `google` (5 por minuto por IP, respuestas de Contratos: alta con `back()->withErrors()->withInput()` sin contraseñas; Google con redirección a `login`) y aplicar `throttle:registro` a `POST /registro` y `POST /registro/contacto` y `throttle:google` a `GET /auth/google/callback` (control de TM3) — app/Providers/AppServiceProvider.php, routes/web.php — hecho cuando: T010 pasa entero, la suite sigue en verde y se comprueba rompiendo (sin `throttle` en las rutas fallan los casos de alta y Google), anotado — cubre: CA8 — depende: T010, T021
- [ ] T023 Implementar `dentissa:rotar-credenciales --confirmar` según Contratos (20 caracteres aleatorios por cuenta con contraseña, tabla por la salida estándar una sola vez, cuentas solo de Google intactas, sin `--confirmar` código 1 sin cambios, nada en el log) (control de TM5) — app/Console/Commands/RotarCredencialesVolcado.php — hecho cuando: T012 pasa y se comprueba rompiendo (escribir la contraseña en el log hace fallar `test_no_escribe_las_contrasenas_en_el_log`), anotado — cubre: CA5 — depende: T012

## Implementación — imágenes y Compose
- [ ] T024 Fijar las imágenes de local con las versiones de T002 y corregir el montaje de `local.ini` a `./docker/nginx/php/local.ini` (RD4.a, RS7.d; control de TM7) — Dockerfile, docker-compose.yml — hecho cuando: `docker compose up -d --build` levanta local, `php -i` en `app` muestra `upload_max_filesize` y `date.timezone` de `local.ini`, la suite sigue en verde y los casos de imágenes y montaje de T014 de local pasan — cubre: CA10 — depende: T002, T014
- [ ] T025 [P] Ampliar `.dockerignore` con las exclusiones del plan (`.env*`, `.git`, `node_modules`, `vendor`, `database/*.sql`, `storage/*` salvo estructura, `tests`, `docs`, `.ai`, `aidlc`, `.claude`, `.github`) (control de TM6) — .dockerignore — hecho cuando: el caso de `.dockerignore` de T014 pasa — cubre: CA5, CA10 — depende: T014
- [ ] T026 Crear el Dockerfile de producción multietapa (`app`: php-fpm fijado, extensiones, `composer install --no-dev --optimize-autoloader`, código copiado, permisos de `storage`; `web`: nginx fijado con `public/` de `app` y la conf de producción) y `dentissa.prod.conf` con `set_real_ip_from 172.16.0.0/12`, `real_ip_header CF-Connecting-IP` y `fastcgi_pass app:9000` (plan D3, D4; control de TM4, TM6 y TM7) — docker/prod/Dockerfile, docker/nginx/dentissa.prod.conf — hecho cuando: `docker build -f docker/prod/Dockerfile --target app` y `--target web` terminan en local y `docker run --rm --entrypoint ls` de la imagen `app` no muestra `.env`, `.git`, `database/*.sql` ni `vendor/phpunit`, anotado — cubre: CA10 — depende: T002, T025
- [ ] T027 Crear `docker-compose.prod.yml` según Cambios por módulo: `app`, `web`, `db` y `cloudflared` en una red interna, sin `ports`, `restart: unless-stopped`, `env_file: /opt/dentissa/.env.production`, volúmenes `db_data`, `storage` y `nginx_logs`, `db` con `MARIADB_USER` y el volcado solo en la primera carga, `local.ini` desde `docker/nginx/php/local.ini`, `cloudflared` con `TUNNEL_TOKEN` desde el entorno e imágenes de T002 (control de TM1, TM6 y TM7) — docker-compose.prod.yml — hecho cuando: T014 pasa entero, `docker compose -f docker-compose.prod.yml config` valida la sintaxis con un archivo de entorno de prueba sin secretos, y se comprueba rompiendo (un `ports` en `db` hace fallar T014), anotado — cubre: CA3, CA5, CA9, CA10 — depende: T014, T024, T026

## Implementación — scripts de despliegue
- [ ] T028 [P] Escribir `preparar-servidor.sh` según Cambios por módulo (actualizaciones, `unattended-upgrades`, Docker Engine del repositorio oficial, `ufw` que deniega lo entrante y permite SSH solo desde la red local indicada como argumento, SSH con `PasswordAuthentication no` y `PermitRootLogin no` solo si `~/.ssh/authorized_keys` tiene al menos una clave, `/opt/dentissa` y `/var/backups/dentissa`) (control de TM1) — deploy/preparar-servidor.sh — hecho cuando: `bash -n` pasa, el script se detiene sin tocar SSH si no hay clave autorizada (leído en el código), y usa `set -euo pipefail` — cubre: CA3
- [ ] T029 [P] Escribir `respaldar.sh` (`mariadb-dump` a `/var/backups/dentissa/dentissa-AAAAMMDD-HHMMSS.sql.gz` con permisos 600, conserva 14) y `restaurar.sh <copia>` (pide escribir el nombre de la base para confirmar) (control de TM8) — deploy/respaldar.sh, deploy/restaurar.sh — hecho cuando: `bash -n` pasa en los dos y ninguno muestra contraseñas en la salida ni en la línea de comandos (las toman del entorno del contenedor `db`) — cubre: CA6
- [ ] T030 [P] Escribir `desplegar.sh <tag o commit>` y `volver.sh <ref anterior> [copia]` según Cambios por módulo y Rollout: copia previa, `checkout`, construcción, `up -d` de `app`, `web` y `db` sin tocar `cloudflared`, `migrate --force`, cachés y `verificar.sh`; `volver.sh` restaura la copia solo si se le indica (control de TM8) — deploy/desplegar.sh, deploy/volver.sh — hecho cuando: `bash -n` pasa en los dos, `desplegar.sh` se detiene si el árbol tiene cambios o si falla la copia, y ninguno nombra `cloudflared` en `up`, `stop` ni `down` — cubre: CA1, CA6, CA7
- [ ] T031 [P] Escribir `verificar.sh [--interna]` según Cambios por módulo: `/up` responde 200 (por la red de Docker con `--interna`; por la dirección pública sin él, con redirección de `/` a https); `APP_ENV=production`, `APP_DEBUG=false` y `LOG_LEVEL=info` dentro de `app`; ningún puerto publicado por Compose y nada escuchando fuera de `localhost` salvo SSH; `upload_max_filesize` y `date.timezone` iguales a `local.ini`; sale con código distinto de 0 si algo falla (control de TM2) — deploy/verificar.sh — hecho cuando: `bash -n` pasa y cada comprobación imprime OK o FALLO con su nombre — cubre: CA1, CA3, CA4, CA10
- [ ] T032 [P] Escribir la guía paso a paso del servidor (contenido de Cambios por módulo, más el ensayo de D13, el acceso interno por túnel SSH al contenedor `web` para probar sin exponer, y que el token y las contraseñas nunca se pegan en el chat) — deploy/README.md — hecho cuando: cubre preparar, túnel de Cloudflare, deploy key, archivo de entorno con sus variables (solo nombres), primer despliegue, rotar credenciales, copia a tu PC con `scp`, volver atrás, abrir el túnel y el redirect de Google — cubre: CA1

## Ensayo en el servidor (plan D13, `cloudflared` parado)
- [ ] T040 Preparar el servidor y hacer el primer despliegue interno: el usuario sube la rama, ejecuta `preparar-servidor.sh`, clona con la deploy key, crea `/opt/dentissa/.env.production` (permisos 600), ejecuta `desplegar.sh <commit>` cronometrado, `dentissa:rotar-credenciales --confirmar` (guarda las contraseñas fuera del chat) y `verificar.sh --interna` (control de TM5) — (sin archivos; nota en tasks.md) — hecho cuando: `verificar.sh --interna` sale todo OK, `desplegar.sh` tarda ≤ 15 min sin la verificación, `cloudflared` no está en marcha (`docker compose ps`) y la nota recoge los tiempos y la salida sin secretos — cubre: CA1, CA4, CA10 — depende: T020, T021, T022, T023, T027, T028, T029, T030, T031, T032
- [ ] T041 Comprobar la red (CA3): desde otro equipo de la red local, conexión a 80, 443, 3306, 8080 y 9000 del servidor rechazada o sin respuesta; SSH con contraseña rechazado (`ssh -o PubkeyAuthentication=no`); en el servidor, `ss -tlnp` sin nada fuera de `localhost` salvo `sshd` — (sin archivos; nota en tasks.md) — hecho cuando: las tres comprobaciones salen como se espera y la nota recoge la salida — cubre: CA3 — depende: T040
- [ ] T042 Comprobar credenciales e imagen en el servidor (CA5, CA10; tests de TM5, TM6 y TM7): cada contraseña del README falla con `Auth::validate` dentro de `app`; `SELECT CURRENT_USER()` de la app no es root; `docker run --rm --entrypoint ls` de la imagen `app` del servidor sin `.env`, `.git`, `database/*.sql` ni `vendor/phpunit`; etiquetas de `docker compose images` iguales a T002 — (sin archivos; nota en tasks.md) — hecho cuando: las cuatro comprobaciones salen como se espera y la nota recoge la salida sin secretos — cubre: CA5, CA10 — depende: T040
- [ ] T043 Ensayar copia y restauración (CA6; test de TM8): `respaldar.sh`, recuento de filas por tabla; cambiar un dato ficticio (agendar una cita); `restaurar.sh` con la copia; recuento de filas por tabla igual al de antes y la cita ya no está — (sin archivos; nota en tasks.md) — hecho cuando: los recuentos coinciden, la copia está en `/var/backups/dentissa` con permisos 600 y la nota lo recoge — cubre: CA6 — depende: T040
- [ ] T044 Ensayar la vuelta atrás (CA7; test de TM8): desplegar un segundo commit de la rama con `desplegar.sh` y volver al de T040 con `volver.sh <commit de T040> <copia>`, cronometrado — (sin archivos; nota en tasks.md) — hecho cuando: tras `volver.sh`, `verificar.sh --interna` sale todo OK, `git rev-parse HEAD` en el servidor es el commit de T040 y el tiempo es ≤ 15 min, anotado — cubre: CA7 — depende: T040, T043
- [ ] T045 Ensayar el reinicio (CA9): anotar la última línea de los logs de `web` y de `storage/logs`, `sudo reboot`, cronometrar hasta que `verificar.sh --interna` sale OK — (sin archivos; nota en tasks.md) — hecho cuando: la app vuelve sola en ≤ 5 min, las líneas anotadas siguen en los volúmenes `nginx_logs` y `storage`, y `cloudflared` sigue parado, anotado — cubre: CA9 — depende: T040
- [ ] T046 Verificación manual interna de la aplicación desplegada, por el usuario, con acceso por túnel SSH al contenedor `web` (guía de T032): login con una cuenta de prueba de cada rol (con las contraseñas rotadas) y agendar y cancelar una cita ficticia — (sin archivos; nota en tasks.md) — hecho cuando: el usuario lo confirma y la nota recoge la fecha · verificación manual: login por rol y cita ficticia en el servidor, sin exponer — cubre: CA1 — depende: T040

## Integración y documentación
- [ ] T050 Verificación final: `php artisan test` (recuento de T001 más los tests nuevos, en verde), `vendor/bin/pint --test` sobre los PHP tocados, `composer audit --locked` y `npm audit` con 0 críticas o altas, semgrep `p/php` sobre `app/` y `bootstrap/` sin hallazgos nuevos, gitleaks sobre `impl_base..HEAD` sin hallazgos, trivy fs sin hallazgos salvo el falso positivo conocido (027/R20) y `aidd.py validate` sin errores — (sin archivos; nota en tasks.md) — hecho cuando: todo en verde y anotado — depende: T022, T023, T027, T046
- [ ] T090 Actualizar `docs/architecture.md` según Impacto en arquitectura (entornos, diagrama `cloudflared` → `web` → `app` → `db`, `deploy/`, `docker/prod/` y límites de intentos) y comprobar que el ADR 0005 sigue `accepted`
- [ ] T091 Actualizar `docs/deployment.md` (entornos, plataforma, cómo desplegar, copias, vuelta atrás, verificación, variables de producción solo por nombre), `docs/security.md` y `docs/observability.md`, por corrección: RD1.a, RD1.b, RD3.a, RD3.b, RD3.c, RD4.a, RS4.a, RS4.b, RS4.c, RS6.c, RS7.a, RS7.b, RS7.c, RS7.d, OB4.b y OB7.a → "en curso (spec 030)"; ninguna se marca mitigada antes de la release y las de fuera no cambian — docs/deployment.md, docs/security.md, docs/observability.md — hecho cuando: los tres documentos reflejan el plan y ningún riesgo figura como mitigado — depende: T040
- [ ] T092 Marcar spec como `implemented`: CA2, CA4, CA5 y CA8 por sus tests y las comprobaciones de T042; CA3, CA6, CA7, CA9 y CA10 por T041–T045; CA1 por T040 y T046 (despliegue desde cero y verificación por la red interna), y su parte pública se comprueba en T097

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
  - nota: no hay staging (plan D2, decisión del usuario 2026-10-03); las comprobaciones públicas van en T097.
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `desplegar.sh vX.Y.Z`, `verificar.sh --interna`, arrancar `cloudflared` y `verificar.sh` contra la dirección pública; vigilar 15 min los 5xx de `web` y `app`
  - verificación manual (CA1): dirección pública con HTTPS, login con una cuenta de prueba de cada rol, y agendar y cancelar una cita ficticia.
  - verificación manual (CA2): `curl -I` de `/` y del login por la dirección pública: `Location` en https y cookie de sesión con `Secure`.
  - verificación manual (CA8, TM4): desde fuera, 6 intentos fallidos de login con el sexto rechazado con el mensaje; desde otra red (p. ej. datos del móvil), el mismo correo no está bloqueado.
  - verificación manual: login con Google tras registrar el redirect https en Google Cloud Console.
- [ ] T098 Marcar spec como `released`: RD1.a, RD1.b, RD3.a, RD3.b, RD3.c, RD4.a, RS4.a, RS4.b, RS4.c, RS6.c, RS7.a, RS7.b, RS7.c, RS7.d, OB4.b y OB7.a → mitigada (vX.Y.Z); RS4 pasa a mitigado (todas sus correcciones dentro); RD1, RD3, RS6, RS7, OB4, OB7 siguen con las correcciones de fuera pendientes; objetivo 5 del roadmap actualizado

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T040, T046 | T030, T031, T032 |
| CA2 | T011 | T020 |
| CA3 | T014, T041 | T027, T028, T031 |
| CA4 | T013, T040 | T031 |
| CA5 | T012, T014, T042 | T023, T025, T027 |
| CA6 | T043 | T029, T030 |
| CA7 | T044 | T030 |
| CA8 | T010 | T021, T022 |
| CA9 | T045 | T027 |
| CA10 | T014, T042 | T002, T024, T025, T026, T027, T031 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T027, T028 | T014, T041 |
| TM2 | T031 | T013, T040 |
| TM3 | T021, T022 | T010 |
| TM4 | T020, T026 | T011 |
| TM5 | T023, T040 | T012, T042 |
| TM6 | T025, T026, T027 | T014, T042, T050 |
| TM7 | T024, T026, T027 | T014, T042 |
| TM8 | T029, T030 | T043, T044 |
| TM9 | T020 | T011 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| bootstrap/app.php | T020 |
| app/Providers/AppServiceProvider.php | T022 |
| routes/web.php | T022 |
| app/Http/Controllers/AuthController.php | T021 |
| app/Console/Commands/RotarCredencialesVolcado.php | T012, T023 |
| docker/prod/Dockerfile | T026 |
| docker/nginx/dentissa.prod.conf | T026 |
| docker-compose.prod.yml | T027 |
| docker-compose.yml | T024 |
| Dockerfile | T024 |
| .dockerignore | T025 |
| deploy/preparar-servidor.sh | T028 |
| deploy/respaldar.sh, deploy/restaurar.sh | T029 |
| deploy/desplegar.sh, deploy/volver.sh | T030 |
| deploy/verificar.sh | T031 |
| deploy/README.md | T032 |
| tests/Feature/*.php (5 nuevos) | T010, T011, T012, T013, T014 |
| docs/adr/0005-produccion-servidor-propio-cloudflare-tunnel.md | T090 (creado y aceptado en `/plan`) |
| docs/deployment.md, docs/security.md, docs/observability.md | T091 |
| docs/architecture.md | T090 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Desplegar en 15 minutos o menos | T040 (cronometrado) |
| Vuelta atrás en 15 minutos o menos | T044 |
| Reinicio en 5 minutos o menos | T045 |
| Ningún despliegue sin aprobación | T096 |
