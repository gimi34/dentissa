---
spec: 031-endurecer-produccion
plan: plan.md
status: approved
impl_base: d981c85
---

# Tareas · 031 Endurecer producción tras la apertura

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.
- **Ensayos en el servidor (T040–T042):** los ejecuta el usuario por SSH y pega la salida. El agente
  nunca lee los archivos de `/opt/dentissa/entorno`, claves ni contraseñas, y la nota recoge solo
  resultados, sin secretos. Antes de cada ensayo, el usuario da su aprobación explícita y la nota la
  recoge (constitución, regla de despliegue; plan, Rollout). La producción está abierta: al
  terminar T040 el servidor vuelve a `v0.5.0` con `volver.sh`. Orden: T041 y T042 (Tailscale) antes
  que T040, porque `verificar.sh` exige la red privada desde la 031 (/analyze A1).

## Constitution Check
| Principio | Resultado | Justificación | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan aprobados el 2026-10-06; ninguna tarea de código empieza antes de aprobar estas tareas y de `/analyze`. | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | T010–T012 se escriben antes y fallan hoy; cada tarea de implementación `depende:` de su test. Los controles de seguridad (T020–T023, T027) se comprueban además rompiendo. CA6–CA11 son comprobaciones declaradas (constitución 1.2.1): T002, T031 y T040–T042, anotadas en su tarea. | test: T010, T011, T012 · manual: notas de T002, T031, T040–T042 |
| P3 Autorización en servidor | ➖ | Ninguna tarea añade rutas ni cambia permisos. | — |
| P4 Entrada validada y SQL con bindings | ✅ | T023 solo comprueba si llega `error`; sin SQL. | lint: semgrep `p/php` en T050 |
| P5 Nada sensible en el repo | ✅ | Ningún secreto en scripts ni tests; T024–T026 no imprimen valores; el alta en Tailscale es interactiva (T027). | lint: gitleaks en T050 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | T004 corrige el aviso alto de npm; T029–T031 refrescan y escanean las imágenes. | lint: `npm audit` (T004, T050), `composer audit` (T050), `trivy image` (T031) |
| P7 Auditoría de datos sensibles | ❌ aceptado | Excepción P7 de la 030 (aprobada por el usuario el 2026-10-03; Auditoría de la 031, aprobada el 2026-10-06): T023 emite `Failed` en vez de auditar. | test: T011 (`test_el_fallo_cuenta_como_login_fallido`) |
| P8 Correlación y logs limpios | ✅ | T023 no añade `Log::`. | manual: revisión del diff en T050 |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Sin cambios visuales; T022 comprueba que ninguna pantalla cambia. | — |

## Preparación
- [x] T001 Registrar la línea base: commit de partida, recuento de `php artisan test`, `composer audit --locked`, `npm audit` y las imágenes actuales de los dos Dockerfiles y los dos Compose — docs/specs/031-endurecer-produccion/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cinco datos
  - nota: línea base del 2026-10-06: commit de partida `d981c85` (`impl_base`); `php artisan test` → 183 en verde (1394 aserciones); `composer audit --locked` → sin avisos; `npm audit` → 1 alto (`source-map-js`, GHSA-68fv-2mgg-jv7q, lo corrige T004); imágenes: `Dockerfile` y `docker/prod/Dockerfile` con `php:8.4.25-fpm` y `composer:2.10.2`, `web` con `nginx:1.30.5-alpine`; `docker-compose.yml` con `nginx:1.30.5-alpine`, `mariadb:10.11.18` y `phpmyadmin:5.2.2`; `docker-compose.prod.yml` con `mariadb:10.11.18` y `cloudflare/cloudflared:2026.9.3`.
  - nota: política de commits: uno por tarea, como en la 030.
- [x] T002 Prueba de concepto de D7 con el Compose de producción en local (carpeta temporal fuera del repo, archivos de entorno de prueba con comillas y una variable interpolada): el entorno de cada servicio según `docker compose config --format json` coincide con `Config.Env` de `docker inspect`, y al cambiar un valor del archivo sin recrear el contenedor aparece la diferencia — docs/specs/031-endurecer-produccion/tasks.md — hecho cuando: la nota recoge el resultado y, si no cuadra, la alternativa del plan (comparar clave a clave solo en `app` y `cloudflared`) — cubre: CA7
  - nota: prueba de concepto del 2026-10-06 en una carpeta temporal fuera del repo (scratchpad `poc-d7`), con un Compose de tres servicios (`app` y `scheduler` con el mismo `app.env`, `db` con `db.env`) y `docker compose config --format json` comparado con `Config.Env` de `docker inspect`, todo dentro de un proceso de `python3` (los valores no pasan por argumentos de proceso).
  - nota: resultado: D7 funciona con dos ajustes que T025 debe aplicar. (1) `compose config` escapa el `$` literal como `$$` (`CON_DOLAR=abc$$def` en la config y `abc$def` en el contenedor): sin desescapar daba un FALLO falso en `app` y `scheduler`; con `str(v).replace('$$', '$')`, OK. (2) Una clave borrada del archivo no aparece en la config: se detecta como sobrante, es decir, una variable del contenedor que no está en la config y no viene de la imagen (`docker image inspect`, `Config.Env`).
  - nota: casos: recién creados → OK en los tres; valor cambiado en `app.env` sin recrear → FALLO en `app` y `scheduler` (1 variable); clave borrada sin recrear → FALLO en `app` y `scheduler` (1 variable); tras `up -d --force-recreate app scheduler` → OK; servicio parado → no se compara. La variable interpolada (`GOOGLE_REDIRECT_URI="${APP_URL}/…"`) y el valor con comillas (`'uno dos'`) coinciden sin ajustes.
  - nota: A10 — la prueba incluye un servicio extra con `env_file` (`scheduler`, como el previsto de la 020), detectado sin lista fija (los servicios salen de la config). No hace falta la alternativa del plan.
  - nota: A15 — incluye un `$` literal, una variable interpolada, valores con comillas y una clave borrada; en T025 los valores pasan por tubería dentro de `python3`, nunca por argumentos de proceso.
- [x] T003 Elegir las versiones de imagen (plan D13): para `php:8.4.N-fpm`, `composer:2.N.N`, `nginx:1.30.N-alpine`, `mariadb:10.11.N`, `phpmyadmin:5.2.N` (solo local) y `cloudflare/cloudflared:AAAA.M.N`, la más nueva de su rama publicada hace ≥ 7×24 h (fecha `tag_last_pushed` en `hub.docker.com/v2/repositories/<imagen>/tags/<etiqueta>`), oficial o del publicador verificado — docs/specs/031-endurecer-produccion/tasks.md — hecho cuando: la nota recoge cada imagen con su etiqueta, fecha y publicador, y la hora de corte — cubre: CA11
  - nota: consulta del 2026-10-06 15:36 UTC en `hub.docker.com/v2/repositories/<imagen>/tags/` (corte: publicada antes del 2026-09-29 15:36 UTC).
  - nota: A8 — la nota daba por hecho que las etiquetas de parche no se reescriben, y no es así: las imágenes oficiales se reconstruyen con la misma etiqueta, y `tag_last_pushed` se mueve (p. ej. `php:8.4.26-fpm` aparece subida el 2026-10-06 07:02, pero la 030 la registró el 2026-10-04 con 2026-09-27 21:58). Regla elegida por el usuario (2026-10-06): cuenta la **primera publicación** de la etiqueta exacta, tomada del registro más antiguo que tengamos (las notas de la 030 o un `tag_last_pushed` anterior); y la elección se **repite antes del ensayo en el servidor**, en T029/T030, para incluir las versiones que para entonces cumplan los 7 días. Rangos: `php` 8.4.x, `composer` 2.10.x, `nginx` 1.30.x, `mariadb` 10.11.x y `phpmyadmin` 5.2.x, sin cambio de versión mayor ni menor; `cloudflared` sigue un calendario, así que vale la más nueva de 2026 con 7 días o más.
  - nota: elección provisional (Docker Official Images, salvo `cloudflare/cloudflared`, del publicador verificado Cloudflare): `php:8.4.26-fpm` (primera publicación 2026-09-27 21:58, registrada en 030 T002; sustituye a 8.4.25), `composer:2.10.2` (2026-09-01; 2.10.3 es del 2026-09-30, no llega), `nginx:1.30.5-alpine` (2026-09-26 19:50), `mariadb:10.11.18` (2026-08-18; 10.11.19 es del 2026-10-02 05:35, llega el 2026-10-09), `phpmyadmin:5.2.2` (solo local, 2025-10-02; 5.2.3 es del 2026-09-29 sin hora registrada: se vuelve a mirar en T030) y `cloudflare/cloudflared:2026.9.3` (2026-09-24 16:23; 2026.10.0 es del 2026-10-05 18:02, llega el 2026-10-12).
- [ ] T004 Corregir el aviso alto de npm (`source-map-js`, GHSA-68fv-2mgg-jv7q) con `npm audit fix`, sin `--force` ni cambios de versión mayor (plan D12) — package-lock.json — hecho cuando: `npm audit` da 0 vulnerabilidades, `git diff package.json` está vacío y `npm run build` termina sin errores, anotado
  - nota: A19 — esta tarea viene de P6 y del plan D12; no la origina ningún criterio de la spec.
  - nota: 2026-10-06, a mitad de tarea: `npm audit fix` sube `source-map-js` a 1.2.2, publicada el 2026-09-30 14:08 UTC. Tiene 6 días, así que no cumple la antigüedad mínima de 7 (agent-security §2.2) y se descartó. Además apareció un aviso **crítico** nuevo, posterior a la línea base: `shell-quote` 1.9.0 (GHSA-pqg4-j6r4-53mv), arrastrado por `concurrently`, que lo fija exactamente en 1.9.0 incluso en su última versión (10.0.5), así que `npm audit fix` no lo resuelve. `concurrently` solo lo usa el script `composer dev`.
  - nota: decisión del usuario (2026-10-06): override `"shell-quote": "1.11.0"` en `package.json`, como el de `rolldown`. La 1.11.0 es del 2026-09-29 02:34 UTC (≥ 7 días), viene del registro oficial con `integrity` y no tiene scripts de instalación; se instaló con `npm install --before=2026-09-29T15:40:00Z`. Por eso esta tarea admite un cambio en `package.json`, solo en `overrides`. Resultado: `npm audit` sin críticos y con 1 alto (`source-map-js`); `npm run build` correcto.
  - nota: pendiente: `npm audit fix` para `source-map-js` 1.2.2 a partir del 2026-10-07 14:08 UTC (7 días). Hecho cuando `npm audit` dé 0. Las demás tareas no dependen de esta.

## Tests (antes de implementar)
- [x] T010 Escribir `CabecerasSeguridadTest` (lee la configuración como texto, como `ConfiguracionProduccionTest`): `comunes.conf` declara CSP, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff` y `Referrer-Policy: strict-origin-when-cross-origin` con `always` y los valores del contrato del plan (`frame-ancestors 'none'`, `object-src 'none'` y sin orígenes externos distintos de los de las fuentes); HSTS solo en `produccion.conf`, con `max-age` ≥ 15552000 e `includeSubDomains`, y no en `comunes.conf` ni en `dentissa.conf`; en `dentissa.conf` y `dentissa.prod.conf`, el `server` y todo `location` con `add_header` propio incluyen su fragmento; `server_tokens off` en las dos; `expose_php = Off` en `docker/nginx/php/local.ini`; `docker/prod/Dockerfile` copia `docker/nginx/seguridad` y `docker-compose.yml` lo monta — tests/Feature/CabecerasSeguridadTest.php — hecho cuando: cada caso falla por la razón esperada (los fragmentos no existen o las configuraciones no los incluyen), anotado — cubre: CA1, CA2, CA3, CA4
  - nota: los seis casos fallan por la razón esperada: no existen `comunes.conf` ni `produccion.conf`; `dentissa.conf` no los incluye ni tiene `server_tokens off`; `local.ini` no tiene `expose_php = Off`; ni el Dockerfile de producción ni `docker-compose.yml` llevan los fragmentos. Pint pasa.
  - nota: A18 — la política solo admite como orígenes externos `https://fonts.googleapis.com` y `https://fonts.gstatic.com` (`ORIGENES_PERMITIDOS`); `fonts.bunny.net` queda fuera.
  - nota: el caso de inclusión en los `location` con `add_header` propio no se ejerce hasta que el `server` incluya el fragmento: se comprueba rompiendo en T021.
- [x] T011 Escribir `GoogleVueltaFallidaTest` con Socialite simulado (como `LimiteIntentosTest::simularGoogle`): `test_sin_inicio_previo_vuelve_al_login_con_mensaje` (`InvalidStateException` → `login` con «No se pudo iniciar sesión con Google. Inténtalo de nuevo.», invitado, sin `google_registro` y sin usuario nuevo); `test_cancelar_en_google_vuelve_al_login_sin_llamar_al_proveedor` (`?error=access_denied`, `shouldNotReceive('user')`); `test_un_error_del_proveedor_vuelve_al_login` (`GuzzleHttp\Exception\ClientException`); `test_el_fallo_no_vincula_una_cuenta_existente` (el `google_id` de una cuenta con el mismo correo no cambia); `test_el_fallo_cuenta_como_login_fallido` (`Event::fake`: `Failed` una vez, guard `web`, sin usuario); `test_los_fallos_siguen_contando_para_el_limite` (cinco fallos y la sexta vuelta con el mensaje de límite); `test_el_error_del_proveedor_se_reporta_y_el_estado_invalido_no` (`Exceptions::fake()`: la `ClientException` se reporta una vez y la `InvalidStateException` no, /analyze A12) — tests/Feature/GoogleVueltaFallidaTest.php — hecho cuando: los siete casos fallan por la razón esperada (500 o falta del evento), anotado — cubre: CA5
  - nota: los siete casos fallan por la razón esperada: la vuelta responde 500 (la excepción de Socialite sin capturar; con `?error=` el proveedor simulado devuelve null y `getId()` revienta). Pint pasa.
  - nota: A14 — cada caso comprueba también la respuesta nueva (302 a `login` con el mensaje, sin sesión ni `google_registro`), así que ninguno pasa hoy: «no vincula una cuenta existente» y «siguen contando para el límite» fallan en la redirección, antes de llegar a sus aserciones propias.
  - nota: el mensaje se compara con el texto literal de la spec (`self::MENSAJE`), no con la constante del controlador: así el test falla por su aserción y no por un error de constante inexistente.
- [x] T012 Añadir a `ConfiguracionProduccionTest` el caso `test_el_acceso_remoto_usa_el_repositorio_firmado_y_openssh`: `deploy/preparar-acceso-remoto.sh` existe, añade el repositorio con `signed-by`, no contiene `| sh` ni `| bash`, llama a `tailscale up` con `--ssh=false`, añade el origen de Tailscale a `Unattended-Upgrade::Allowed-Origins` en un archivo de `/etc/apt/apt.conf.d/` (/analyze A7) y añade `ufw allow in on tailscale0 to any port 22 proto tcp` — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: el caso falla porque el script no existe y el resto del archivo sigue en verde — cubre: CA9
  - nota: `test_el_acceso_remoto_usa_el_repositorio_firmado_y_openssh` falla por la razón esperada (no existe `deploy/preparar-acceso-remoto.sh`); los otros 11 casos del archivo siguen en verde. Comprueba `signed-by` en la línea del repositorio de `pkgs.tailscale.com`, que no haya tubería a `sh` ni a `bash`, `tailscale up` con `--ssh=false`, `Unattended-Upgrade::Allowed-Origins` y la regla de `ufw` por `tailscale0`, ignorando los comentarios. Pint pasa.

## Implementación
### Cabeceras y versiones (CA1–CA4)
- [x] T020 Crear los fragmentos de cabeceras según el contrato del plan (D1–D3): `comunes.conf` con las cuatro cabeceras y `always`; `produccion.conf` con `include` de `comunes.conf` y `Strict-Transport-Security "max-age=15552000; includeSubDomains" always` (control de TM1–TM4) — docker/nginx/seguridad/comunes.conf, docker/nginx/seguridad/produccion.conf — hecho cuando: los casos de contenido de T010 pasan y se comprueba rompiendo (quitar `frame-ancestors` o el HSTS hace fallar su caso), anotado — cubre: CA1, CA2, CA3 — depende: T010
  - nota: los dos casos de contenido de T010 pasan (`los_fragmentos_comunes_declaran_las_cuatro_cabeceras` y `la_conexion_segura_obligatoria_solo_va_en_produccion`); los otros cuatro siguen fallando hasta T021 y T022, como se esperaba.
  - nota: comprobado rompiendo: sin `frame-ancestors 'none'` falla el caso de las cabeceras («Otra web no debe poder incrustar la app»); sin la línea de HSTS falla el de producción; añadiendo `https://fonts.bunny.net` a la CSP falla el de orígenes. Restaurado: pasan.
  - nota: A18 — la CSP solo admite fuentes de `fonts.googleapis.com` y `fonts.gstatic.com`.
- [x] T021 Incluir los fragmentos en las dos configuraciones de nginx (`include /etc/nginx/snippets/dentissa/comunes.conf` en local y `…/produccion.conf` en producción, en el `server` y en el `location` de estáticos) y añadir `server_tokens off` (control de TM5) — docker/nginx/dentissa.conf, docker/nginx/dentissa.prod.conf — hecho cuando: los casos de inclusión y de `server_tokens` de T010 pasan, y se comprueba rompiendo (quitar el `include` del `location` de estáticos hace fallar su caso), anotado — cubre: CA1, CA2, CA3, CA4 — depende: T020
  - nota: pasan los casos de inclusión y de `server_tokens` de T010 (4 de 6). `local.ini` y el montaje y la copia de los fragmentos son de T022.
  - nota: comprobado rompiendo: sin el `include` del `location` de estáticos de `dentissa.prod.conf` falla el caso de inclusión («nginx no heredaría las cabeceras»); sin `server_tokens off` falla `test_nginx_no_muestra_su_version`. Restaurado: pasan.
  - nota: `nginx -t` con `nginx:1.30.5-alpine` y los fragmentos montados en `/etc/nginx/snippets/dentissa`: sintaxis correcta en `dentissa.conf` y en `dentissa.prod.conf`.
- [x] T022 Añadir `expose_php = Off` a `local.ini`, copiar `docker/nginx/seguridad` a `/etc/nginx/snippets/dentissa/` en la etapa `web` del Dockerfile de producción y montarlo de solo lectura en el `nginx` de local (control de TM5) — docker/nginx/php/local.ini, docker/prod/Dockerfile, docker-compose.yml — hecho cuando: T010 pasa completo · verificación manual: en local, `curl -sI http://localhost:8000/login`, un `.css` y una ruta inexistente llevan las cuatro cabeceras, sin HSTS, sin `X-Powered-By` y con `Server: nginx` sin versión; recorrido de las pantallas de cada rol (admin, dentista, asistente y paciente) con la consola del navegador sin bloqueos de la CSP; una página local de prueba con `<iframe src="http://localhost:8000/login">` no muestra la app — cubre: CA1, CA2, CA3, CA4 — depende: T021
  - nota: A11 — `docker-compose.yml` es una ruta protegida: el usuario aprobó editarlo el 2026-10-06 («sí, apruebo editar docker-compose.yml»). Solo se añadió el montaje de los fragmentos en `nginx`.
  - nota: `CabecerasSeguridadTest` pasa completo (6/6). Se recrearon `nginx` y `app` en local.
  - nota: verificación manual OK 2026-10-06 (agente, en local):
  - nota: - `curl -sI` a `/login`, `/css/app.css` y una ruta inexistente (404): las cuatro cabeceras, sin HSTS, sin `X-Powered-By` y con `Server: nginx` sin versión.
  - nota: - dentista y asistente: con sus cuentas locales se revisaron los recursos del HTML real de 21 pantallas más el login (scripts, hojas de estilo, fuentes, imágenes, `form action`, `iframe`/`object`/`base` y `url()` en estilos) contra la CSP: todo OK, nada bloqueado. La consola de Chrome no recoge las violaciones de CSP como mensajes, así que la comprobación se hizo con un detector validado antes con una violación provocada (`securitypolicyviolation` disparó con un script y una imagen externos).
  - nota: - CA2: una página servida desde otro origen (`127.0.0.1:8099`) con `<iframe src="http://localhost:8000/login">` no muestra la app (bloqueada por `frame-ancestors`); servidor de prueba parado.
  - nota: verificación manual OK 2026-10-06 (usuario): admin (Inicio, Citas, Nueva cita, Pacientes, Personal, Servicios, Usuarios y Reportes) y paciente (Inicio, Agendar, Mis citas y Perfil), sin errores de CSP en la consola y con las pantallas como siempre.

### Vuelta de Google (CA5)
- [x] T023 Manejar el fallo de la vuelta de Google según el contrato del plan (D5): con `error` en la petición no se llama a Socialite; `InvalidStateException` y `GuzzleException` se capturan solo alrededor de `->user()`; en los tres casos `event(new Failed('web', null, ['proveedor' => 'google']))` y `redirect()->route('login')->withErrors(['email' => self::MENSAJE_FALLO_GOOGLE])`; solo la `GuzzleException` se reporta con `report($e)` (/analyze A12); sin `Log::` nuevo (control de TM6 y TM7) — app/Http/Controllers/GoogleAuthController.php — hecho cuando: T011 pasa, `LimiteIntentosTest` sigue en verde y se comprueba rompiendo (sin el `event(...)` falla `test_el_fallo_cuenta_como_login_fallido`; sin la comprobación de `error` falla el caso de cancelar; sin `report($e)` falla el caso del reporte), anotado — cubre: CA5 — depende: T011
  - nota: T011 pasa (7/7) y `LimiteIntentosTest` sigue en verde (16 en total con los dos archivos).
  - nota: comprobado rompiendo: sin `event(new Failed(...))` falla `test_el_fallo_cuenta_como_login_fallido`; sin la comprobación de `error` falla el caso de cancelar (se llama al proveedor); sin `report($e)` falla `test_el_error_del_proveedor_se_reporta_y_el_estado_invalido_no`. Restaurado: pasan.
  - nota: A12 — solo la `GuzzleException` se reporta (`report($e)`); `InvalidStateException` y la vuelta con `error` no dejan rastro en el log, solo el evento `Failed`.
  - nota: Pint: el archivo ya fallaba antes por las flechas alineadas del array de `google_registro` (código previo); se corrigió al tocarlo (solo espacios). semgrep `p/php` sin hallazgos y gitleaks sin fugas sobre el archivo. Sin `Log::` nuevos (P8).

### Verificación y entorno (CA6, CA7)
- [x] T024 Ampliar `verificar.sh`: el bloque de configuración pasa de 10 a 12 comprobaciones con el formato del id de cliente de Google y el secreto sin marcador (plan D6); y una sección «Cabeceras» que, por la red interna (`wget -S` dentro de `web` sobre `/login`, un `.css`, una ruta inexistente y la 301 que genera nginx con `X-Forwarded-Proto: http`), exige las cuatro cabeceras, sin `X-Powered-By` y `Server` sin versión; y por la dirección pública fuera de `--interna`, solo en las respuestas https (`/login`), además HSTS. La 301 pública de `http://` la genera el borde de Cloudflare y se sigue comprobando solo su destino https (/analyze A3); sin imprimir valores (control de TM8) — deploy/verificar.sh — hecho cuando: `bash -n` pasa y ninguna línea nueva imprime valores de configuración — cubre: CA1, CA3, CA4, CA6 — depende: T022
  - nota: `bash -n` pasa y ninguna línea nueva imprime valores: las comprobaciones solo dicen OK o FALLO con el nombre de la cabecera o de la variable. El archivo se volvió a dejar en LF (una escritura intermedia lo había pasado a CRLF).
  - nota: CA6: el bloque de configuración pasa de 10 a 12. Probado en `app` local: con los valores reales, OK en los dos; con los marcadores de la guía (`<client id>`, `<client secret>`) y con valores vacíos, FALLO en los dos.
  - nota: cabeceras, probado con un nginx `1.30.5-alpine` y `dentissa.prod.conf` más los fragmentos: OK en `/login`, `/css/app.css`, el 404 y la 301 de nginx (con `X-Forwarded-Proto: http`), y OK con HSTS en la 301 simulando la pública. Comprobado rompiendo: sin el `include` del `location` de estáticos, FALLO solo en `/css/app.css` («CSP X-Frame-Options X-Content-Type-Options Referrer-Policy»); y un conjunto sin cabeceras, con `Server: nginx/1.30.5` y `X-Powered-By`, da FALLO en todo.
  - nota: A29 — la 301 interna se juzga con `curl` dentro de `web` y sin `-L`, por sus propias cabeceras (estado 301 y `Location: https://`), sin seguir la redirección; OK con la 301 real y FALLO al quitar el `include`.
  - nota: A3 — la 301 pública (del borde de Cloudflare) solo se comprueba por su destino https; las cabeceras y el HSTS se exigen en la respuesta https de `${url}/login`.
- [ ] T025 Añadir a `verificar.sh` la sección «Entorno aplicado» (plan D7, con el resultado de T002): para cada servicio del Compose en marcha, sin lista fija (/analyze A10), el entorno resuelto por `docker compose config --format json` contra `Config.Env` de `docker inspect`, con `python3`, imprimiendo `OK` o `FALLO <servicio> usa una configuración distinta de la guardada (N variables)` y, en `db`, que `db.env` solo se aplica al crear la base; y la sección «Administración remota» (plan D11): la regla de puertos admite `100.64.0.0/10` y `fd7a:115c:a1e0::/48`, `tailscale status --json` con `BackendState` en `Running`, y SSH sin `password` ni `keyboard-interactive` (`ssh -o PubkeyAuthentication=no -o BatchMode=yes localhost`); las dos secciones corren con y sin `--interna`, y sin `tailscaled` en marcha la sección da FALLO (/analyze A1) (control de TM8 y TM9) — deploy/verificar.sh — hecho cuando: `bash -n` pasa y ninguna línea nueva imprime valores — cubre: CA7, CA8, CA9 — depende: T002, T024
- [ ] T026 Crear `aplicar-entorno.sh <servicio>` según el contrato del plan (D8): cualquier servicio de `docker compose -f docker-compose.prod.yml config --services` salvo `db` (/analyze A10); `up -d --force-recreate --no-deps`, `config:cache` y `route:cache` como `www-data` en `app`, y `verificar.sh --interna` al final; con `db` o un servicio que no existe, mensaje y código 1 (control de TM8) — deploy/aplicar-entorno.sh — hecho cuando: `bash -n` pasa, y sin argumento o con `db` sale con 1 y el mensaje (probado en local sin Docker de producción) — cubre: CA7 — depende: T025

### Administración desde fuera (CA8–CA10)
- [ ] T027 Crear `preparar-acceso-remoto.sh` según el plan (D9): exige root; añade el repositorio de `pkgs.tailscale.com/stable/ubuntu` (`noble`) con su keyring y `signed-by`; instala `tailscale`; añade su origen a `Unattended-Upgrade::Allowed-Origins` en un archivo propio de `/etc/apt/apt.conf.d/` (/analyze A7); `systemctl enable --now tailscaled`; `tailscale up --ssh=false --accept-dns=false`; `ufw allow in on tailscale0 to any port 22 proto tcp` como defensa adicional; idempotente; al final muestra `tailscale ip -4`, la versión instalada, y recuerda desactivar la caducidad de la clave y aplicar la ACL del tailnet en la consola (/analyze A6) (control de TM9, TM11 y TM12) — deploy/preparar-acceso-remoto.sh — hecho cuando: T012 pasa, `bash -n` pasa y se comprueba rompiendo (quitar `signed-by` o `--ssh=false` hace fallar T012), anotado — cubre: CA8, CA9 — depende: T012
- [ ] T028 Actualizar la guía: en §4 y §5, aviso de sustituir los marcadores de Google y de que `verificar.sh` falla si no; §10 «Cambiar un archivo de entorno» con `aplicar-entorno.sh` y la advertencia de `db.env`; §11 «Administrar desde fuera de casa»: `preparar-acceso-remoto.sh`, alta de un dispositivo, desactivar la caducidad de la clave del servidor, la ACL del tailnet que solo deja llegar al servidor por tcp:22 (texto para pegar en la consola, /analyze A6), entrar con `ssh <cuenta>@<nombre en la red privada>`, y retirar un dispositivo (quitarlo del tailnet y su clave de `authorized_keys`); y en §8 (vuelta atrás), que volver a una versión anterior a la 031 con Tailscale activo da un único FALLO conocido de su `verificar.sh` (/analyze A2) (control de TM9 y TM10) — deploy/README.md — hecho cuando: las secciones existen, citan los scripts por su nombre y §11 incluye la ACL — cubre: CA6, CA7, CA8, CA10 — depende: T026, T027

### Imágenes (CA11)
- [ ] T029 [P] Fijar en los dos Dockerfiles las versiones de T003 (`php`, `composer`, `nginx`) (control de TM13) — Dockerfile, docker/prod/Dockerfile — hecho cuando: `test_las_imagenes_de_local_tienen_version_exacta` pasa con las nuevas etiquetas — cubre: CA11 — depende: T003, T022
- [ ] T030 [P] Fijar en los dos Compose las versiones de T003 (`nginx`, `mariadb`, `phpmyadmin`, `cloudflared`) (control de TM13) — docker-compose.yml, docker-compose.prod.yml — hecho cuando: `ConfiguracionProduccionTest` pasa completo (versiones exactas e iguales en local y producción) — cubre: CA11 — depende: T003, T022
- [ ] T031 Escanear con `trivy image --severity HIGH,CRITICAL` las imágenes base de T003 y las construidas (`app` y `web`); renovar EX2 solo para los avisos sin corrección publicada (motivo, aprobador y nuevo vencimiento, con aprobación del usuario) o retirarla, y actualizar RS18: RS18.a queda «en curso (spec 031)» hasta la liberación, en la que pasa a mitigada al haberse refrescado, escaneado y decidido sobre EX2; el residuo, si lo hay, queda en EX2 con su vencimiento (/analyze A4) — docs/security.md — hecho cuando: la nota recoge el resultado por imagen y `docs/security.md` refleja el estado de EX2 y RS18 — cubre: CA11 — depende: T029, T030

## Ensayos en el servidor
- [ ] T040 Ensayo de la verificación en el servidor (aprobación explícita antes; lo ejecuta el usuario): `desplegar.sh <commit de la rama>`, `verificar.sh` completo; romper CA7 (poner el marcador en `GOOGLE_CLIENT_ID` de `app.env` sin recrear → FALLO de «Entorno aplicado» en `app`); `aplicar-entorno.sh app` → FALLO de CA6; restaurar el valor y `aplicar-entorno.sh app` → todo OK; y volver con `volver.sh v0.5.0` → su `verificar.sh` da exactamente el FALLO conocido de «nada escucha fuera de localhost salvo SSH» por `tailscaled`, y ningún otro (/analyze A2) — docs/specs/031-endurecer-produccion/tasks.md — hecho cuando: la nota recoge la aprobación y la salida de cada paso, sin valores — cubre: CA6, CA7, CA8 — depende: T024, T025, T026, T028, T029, T030, T041
- [ ] T041 Ensayo de la administración desde fuera (aprobación explícita antes; lo ejecuta el usuario): `sudo bash deploy/preparar-acceso-remoto.sh`; caducidad de la clave del servidor desactivada; desde el móvil con datos, `ssh` por la red privada; `sudo reboot` y `ssh` por la red privada cronometrado (≤ 5 minutos); `nmap` desde la red privada (solo el 22); comprobador de puertos externo sobre la IP pública de la casa (22, 80, 443, 3306 y 8080 cerrados); `ssh -o PubkeyAuthentication=no` rechazado sin pedir contraseña; `tailscale debug prefs` con `RunSSH: false`; ACL del tailnet aplicada y comprobada con un puerto de prueba que escucha en el servidor (p. ej. `python3 -m http.server 8099`) y no es alcanzable desde la red privada (/analyze A6); `unattended-upgrade --dry-run -d` incluye el origen de Tailscale, y la versión instalada con su fecha de publicación (/analyze A7); `verificar.sh` con la sección «Administración remota» en OK — docs/specs/031-endurecer-produccion/tasks.md — hecho cuando: la nota recoge la aprobación y cada resultado — cubre: CA8, CA9 — depende: T025, T027, T028
- [ ] T042 Ensayo de la retirada de un dispositivo (aprobación explícita antes; lo ejecuta el usuario): un dispositivo de prueba se autoriza en la red privada, entra por `ssh`, se retira siguiendo la guía §11 y deja de llegar, mientras el dispositivo principal sigue entrando — docs/specs/031-endurecer-produccion/tasks.md — hecho cuando: la nota recoge la aprobación y los cuatro resultados — cubre: CA10 — depende: T041

## Integración y documentación
- [ ] T050 Ejecutar la puerta de calidad sobre el diff de la spec: suite completa, `pint --test` en los PHP tocados, `bash -n` en los scripts tocados, gitleaks, semgrep `p/php`, `composer audit --locked` y `npm audit` — docs/specs/031-endurecer-produccion/tasks.md — hecho cuando: todo pasa sin hallazgos críticos ni altos, anotado con los recuentos — depende: T023, T026, T027, T031
- [ ] T051 Actualizar el roadmap: RS8.c (HSTS en staging) al objetivo 5 y RS8.d (CSP estricta, por pantallas) a Pendientes; la excepción P7 de la 030 vence con la 021, no con la 020 (/analyze A9); las incidencias de la liberación de la 030 que resuelve esta spec, marcadas como atendidas por la 031 (500 de Google, recrear tras cambiar el entorno, marcador de Google, `x-powered-by`, Tailscale) — docs/roadmap.md — hecho cuando: las entradas existen y citan la 031
- [ ] T090 Actualizar `docs/architecture.md`: infraestructura de producción (cabeceras en nginx y administración por la red privada, ADR 0006)
- [ ] T091 Actualizar `docs/deployment.md`, `docs/security.md` y `docs/observability.md`: acceso remoto, `aplicar-entorno.sh` y la regla de recrear; fila del SSH en la superficie de ataque; RS8.a y RS8.b divididas con RS8.c y RS8.d nuevas (estado por corrección, «en curso (spec 031)» las de dentro); nada que cambie en observabilidad salvo, si aplica, `verificar.sh`
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
  - nota: no hay staging (030, plan D2); las comprobaciones públicas van en T097.
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `desplegar.sh vX.Y.Z`, `verificar.sh` con la dirección pública y 15 minutos de vigilancia
  - verificación manual (CA1, RNF): recorrido de las pantallas de cada rol por `https://dentissa.app` con la consola del navegador sin bloqueos de la CSP.
  - verificación manual (CA3, CA4): `curl -sI https://dentissa.app/login` con HSTS y sin `X-Powered-By`.
  - verificación manual (CA5): login con Google correcto y cancelado en Google (vuelve al login con el mensaje).
- [ ] T098 Marcar spec como `released`. Correcciones → mitigada (vX.Y.Z): RS8.a, RS8.b y RS18.a (refrescada, escaneada y EX2 decidida en T031; /analyze A4). RS8 sigue abierto (RS8.c y RS8.d pendientes); RS18 mitigado, con el residuo que siga en EX2 hasta su vencimiento. Specs extendidas: en la 001, fila de Historial; en la 030, CA3 con `→ reemplazado en parte por 031 CA8` y fila de Historial

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T022 (manual), T097 | T020, T021, T022, T024 |
| CA2 | T010, T022 (manual) | T020, T021, T022 |
| CA3 | T010 | T020, T021, T024 |
| CA4 | T010, T022 (manual) | T021, T022, T024 |
| CA5 | T011 | T023 |
| CA6 | T040 | T024, T028 |
| CA7 | T002, T040 | T025, T026, T028 |
| CA8 | T040, T041 | T025, T027, T028 |
| CA9 | T012, T041 | T025, T027 |
| CA10 | T042 | T028 |
| CA11 | T031 | T003, T029, T030 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T020, T021 | T010 |
| TM2 | T020, T021 | T010 |
| TM3 | T020, T021 | T010 |
| TM4 | T020, T021 | T010, T097 |
| TM5 | T021, T022 | T010 |
| TM6 | T023 | T011 |
| TM7 | T023 | T011 |
| TM8 | T024, T025, T026 | T040 |
| TM9 | T025, T027, T028 | T012, T041 |
| TM10 | T028 | T042 |
| TM11 | T027 | T012, T041 |
| TM12 | T027 | T012, T041 |
| TM13 | T029, T030 | T031 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| docker/nginx/seguridad/comunes.conf, produccion.conf | T020 |
| docker/nginx/dentissa.conf, dentissa.prod.conf | T021 |
| docker/nginx/php/local.ini | T022 |
| docker/prod/Dockerfile | T022, T029 |
| docker-compose.yml | T022, T030 |
| Dockerfile (local), docker-compose.prod.yml | T029, T030 |
| app/Http/Controllers/GoogleAuthController.php | T023 |
| deploy/verificar.sh | T024, T025 |
| deploy/aplicar-entorno.sh | T026 |
| deploy/preparar-acceso-remoto.sh | T027 |
| deploy/README.md | T028 |
| package-lock.json | T004 |
| tests/Feature/CabecerasSeguridadTest.php, GoogleVueltaFallidaTest.php, ConfiguracionProduccionTest.php | T010, T011, T012 |
| docs/deployment.md, docs/security.md, docs/architecture.md, docs/roadmap.md, docs/adr/0006 | T031, T051, T090, T091 (el ADR se creó y aceptó en `/plan`) |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Ninguna pantalla pierde funciones ni contenido | T022 (local), T097 (pública) |
| La vía desde fuera vuelve sola tras un reinicio en 5 minutos o menos | T041 |
| Ningún despliegue sin aprobación explícita | T040, T041, T042, T096 |
