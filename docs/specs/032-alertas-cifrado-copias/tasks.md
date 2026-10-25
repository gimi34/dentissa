---
spec: 032-alertas-cifrado-copias
plan: plan.md
status: approved
impl_base: 853226f
---

# Tareas · 032 Alertas y copias cifradas en producción

> Versión 2 (2026-10-25), tras `/plan 032 --fix` por los hallazgos A1–A28 de `/analyze` r1. La
> versión 1 está en `history/tasks.v1.md`. Ninguna tarea estaba empezada; se conservan los números
> de las que siguen teniendo sentido, y T022 no se reutiliza. Corregida en su lugar tras `/analyze`
> r2 (A29, A32, A33, A34, A36, A37); los aceptados de r2 van como notas en sus tareas.

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.
- **Preparativos y ensayos en el servidor (T040–T044):** los hace el usuario, en el PC o por SSH, y
  pega la salida.
  - El agente nunca lee `/opt/dentissa/entorno`, la clave secreta, su frase ni la contraseña de
    aplicación, y la nota recoge solo resultados, sin secretos.
  - Antes de cada ensayo, el usuario da su aprobación explícita y la nota la recoge (constitución,
    regla de despliegue; plan, Rollout).
  - La producción está abierta: el ensayo despliega el commit de la rama con el orden de D6. Al
    terminar T044, el servidor vuelve a `v0.10.0` con `restaurar.sh --volver` y el temporizador
    queda parado hasta la liberación.

## Constitution Check
| Principio | Resultado | Justificación | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | La spec se aprobó el 2026-10-07. El plan se aprobó el 2026-10-24 y, corregido, el 2026-10-25. Ninguna tarea de código empieza antes de aprobar estas tareas y de un `/analyze` en pass. | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | T010–T019 se escriben antes y fallan hoy: el listener, el vigilante, el correo, el comando y el texto de los scripts todavía no existen. Cada tarea de implementación `depende:` de su test, y los controles se comprueban además rompiendo. T036 cambia la estructura de `AuthController` sin cambiar su comportamiento, con `LimiteIntentosTest` en verde antes y después. El efecto real de los scripts y de UptimeRobot (CA3, CA7–CA10 en el servidor) son comprobaciones declaradas en la Trazabilidad del plan: T029 en el PC y T041–T044, anotadas en su tarea. | test: T010–T019 · manual: notas de T029 y T041–T044 |
| P3 Autorización en servidor | ➖ | Ninguna tarea añade rutas, endpoints ni permisos; `dentissa:vigilar` es un comando de consola. | — |
| P4 Entrada validada y SQL con bindings | ✅ | T023 lee el log de acceso con una expresión anclada a la estructura de `combined` y descarta lo que no encaja (T011). T036 mantiene la consulta de Eloquent con bindings. Sin SQL nuevo. | test: T011 (`test_ignora_las_lineas_que_no_tienen_el_formato`, `test_no_se_engana_con_corchetes_ni_espacios`) · lint: semgrep `p/php` en T050 |
| P5 Nada sensible en el repo | ✅ | Ningún secreto en scripts, tests ni `.env.example` (T003 solo añade nombres vacíos). T026 ignora `*.asc` y `*.gpg` en el repo y en el paquete, a cualquier profundidad. Los preparativos de T040 se hacen fuera del repo, y la clave de prueba de T029 queda fuera del clon. | test: T014 · lint: gitleaks en T050 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias de Composer ni de npm; gpg, `ssh` y `awk` son del sistema (Ubuntu y Git para Windows). | lint: `composer audit --locked` y `npm audit --audit-level=high` en T001 y T050; `trivy image` en `/release` |
| P7 Auditoría de datos sensibles | ❌ aceptado | T036 toca el login y T020 escribe los fallos de acceso en un canal de alertas sin evento de auditoría. Se apoya en la excepción P7 de la 030 (aprobada por el usuario el 2026-10-03), que vence con la 021 (/analyze A37). Ninguna tarea añade acciones auditables. | `/review`: la excepción de la 030 sigue vigente |
| P8 Correlación y logs limpios | ❌ aceptado | El canal `seguridad` (T020) no lleva `X-Request-Id`, que no existe hasta la 020. Excepción del plan aprobada por el usuario el 2026-10-25 (/analyze A4), que vence al liberar la 020. Logs limpios: T020 escribe solo tipo, método y huellas HMAC con clave derivada; T024 deja `Log::warning('aviso no enviado', ['tipo' => …])` sin el mensaje de la excepción. | test: T010 (`test_el_registro_no_contiene_ip_ni_correo`) · manual: revisión de cada `Log::` del diff en T050 |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Sin pantallas; el correo es texto plano. | — |
| P10 Rutas indexables descubribles | ➖ | Sin rutas nuevas ni cambiadas. | — |

## Preparación
- [x] T001 Registrar la línea base: commit de partida, recuento de `php artisan test`, `composer audit --locked`, `npm audit`, y `gpg --version` y `ssh -V` del Git Bash del PC — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota bajo esta tarea recoge los seis datos
  - nota: línea base 2026-10-25: commit de partida 853226f (impl_base); php artisan test 353 passed (6666 aserciones), en un contenedor efímero de dentissa-app:latest con el worktree montado y el vendor del Compose local; composer audit --locked sin avisos; npm audit 0 vulnerabilidades; Git Bash del PC con gpg (GnuPG) 2.4.9 y OpenSSH_10.3p1.
- [x] T002 Comprobar en la web de UptimeRobot (plan D13) que el plan gratuito permite un monitor HTTP(s) cada 5 minutos con avisos por correo al caer y al volver, y si pide confirmación o tarjeta — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota recoge el intervalo mínimo, los tipos de aviso y que no hay coste, con la fecha de la consulta — cubre: CA3
  - nota: consulta del 2026-10-25 en uptimerobot.com: precios (/pricing) — plan Free con 50 monitores, «5 min. monitoring interval», monitores HTTP(s) y de palabra clave, «No credit card required!»; términos (/terms, modificados el 2026-06-24) — «available for any use, including commercial and business use», sin coste; FAQ — se pueden probar las notificaciones «UP and DOWN», y con un monitor caído comprueba cada minuto. El correo como contacto del plan Free no aparece explícito en las páginas consultadas: se confirma en la práctica con los dos correos del ensayo T043 (caída y vuelta).
- [x] T003 Crear `config/avisos.php` según el contrato del plan (D8, D9) — config/avisos.php, .env.example — hecho cuando: `php artisan config:show avisos` muestra los valores por defecto y `.env.example` no tiene valores
  - `config/avisos.php`:
    - destino `AVISOS_CORREO`;
    - umbral de 5xx (1 %) y umbral de fallos (10);
    - ventana (5 min) y silencio (30 min);
    - ruta del log de acceso (`/var/log/nginx/dentissa-access.log`);
    - patrón del canal `seguridad`;
    - ruta del estado (`storage/app/avisos/estado.json`);
    - tope de 50 MiB por ejecución.
  - `.env.example`: `AVISOS_CORREO=` vacío. En local sigue `MAIL_MAILER=log`.
  - nota: php artisan config:show avisos muestra destino null, umbrales 0.01 y 10, ventana 5, silencio 30, log de acceso, ruta base del canal seguridad, estado y tope 52428800; pint limpio. En .env.example, AVISOS_CORREO= vacío, con una línea de comentario.

## Tests (antes de implementar)
- [x] T010 [P] Escribir `RegistroFallosAccesoTest` (D7, contrato de la línea) — tests/Feature/RegistroFallosAccesoTest.php — hecho cuando: falla por la razón esperada (no hay canal ni listener), anotado — cubre: CA2
  - Casos que escriben una línea en el canal `seguridad`:
    - el login con contraseña equivocada, contra una cuenta y contra un correo sin cuenta: `fallido`/`contrasena`;
    - la vuelta de Google fallida: `fallido`/`google` con `cuenta` `null`;
    - un intento bloqueado (`Lockout`) del login: `bloqueado`/`contrasena`;
    - un intento bloqueado en la vuelta de Google (limitador `google`): `bloqueado`/`google` con `cuenta` `null`, y la respuesta del limitador no cambia (/analyze A33).
  - El login correcto no escribe nada.
  - `test_el_registro_no_contiene_ip_ni_correo`: ni la IP, ni el correo, ni el id.
  - Huellas:
    - el mismo origen y la misma cuenta dan la misma huella, de 16 hexadecimales;
    - una cuenta inactiva da la huella de su cuenta (`cuenta:<id>`);
    - el `Lockout` da la misma huella que el `Failed` de esa cuenta (/analyze A12).
  - La línea tiene la forma del contrato: `message` `acceso_fallido` y los cuatro campos en `context` (/analyze A11).
  - nota: aceptado en /analyze r2 (2026-10-25), A45 — el canal declara `name` `seguridad`, o el test comprueba el valor real de `channel`.
  - nota: falla por la razón esperada (2026-10-25): 9 de 10 casos fallan con «actual size 0» porque no hay canal seguridad ni listener; el canal se redirige a una carpeta temporal por test.
  - nota: test_un_login_correcto_no_escribe_nada pasa desde el principio (todavía no se escribe nada): se comprueba rompiendo en T020, haciendo que el listener escriba también en el login correcto.
  - nota: A45 aplicado: el test exige channel = seguridad, así que T020 declara name en el canal.
- [x] T011 [P] Escribir en `VigilanteTest` los casos de errores (D8, con `Mail::fake()`, reloj fijo y logs en una carpeta temporal) — tests/Feature/VigilanteTest.php — hecho cuando: falla por la razón esperada (no existe `App\Avisos\Vigilante`), anotado — cubre: CA1
  - `test_mas_del_1_por_ciento_de_5xx_avisa`, `test_el_1_por_ciento_no_avisa`.
  - `test_ventana_de_5_minutos`: los 5xx de hace 6 minutos no cuentan.
  - `test_ignora_las_lineas_que_no_tienen_el_formato`.
  - `test_no_se_engana_con_corchetes_ni_espacios`: con `[fecha] "GET /" 200` dentro del usuario o de la petición, se lee el código real (/analyze A7).
  - `test_solo_lee_lo_nuevo` y `test_detecta_log_truncado` (tamaño menor o inodo distinto: vuelve a empezar).
  - `test_primera_ejecucion_empieza_al_final` (/analyze A10).
  - `test_lo_que_supera_el_tope_se_lee_despues` (/analyze A10).
  - `test_el_estado_no_guarda_peticiones` (TM14).
  - `test_avisa_si_el_log_cambia_de_formato`: con al menos 20 líneas en la ventana y más de la mitad sin el formato, llega el aviso `formato` (/analyze A36).
  - nota: aceptado en /analyze r3 (2026-10-25), A49 — el aviso `formato` también salta con poco tráfico (p. ej., líneas que no encajan acumuladas entre ventanas, sin ninguna buena en medio), con su caso: 5 líneas malas repartidas en varias ventanas dan el aviso, y 5 buenas no.
  - nota: falla por la razón esperada (2026-10-25): los 14 casos con «Target class [App\Avisos\Vigilante] does not exist».
  - nota: Interfaz que fija el test para T023 y T024: app(Vigilante::class)->ejecutar(): bool; ventanaDeErrores(): ['peticiones' => int, 'errores' => int]; AvisoProduccion con propiedades públicas tipo y recuento. Rutas por config('avisos.*') y reloj con travelTo.
  - nota: Añadido test_detecta_log_rotado (inodo distinto con un log más largo), separado del truncado.
  - nota: A49 aplicado: test_avisa_del_formato_con_poco_trafico (5 líneas en otro formato en tres ventanas dan el aviso formato) y test_pocas_lineas_buenas_no_avisan_del_formato.
- [x] T012 Añadir a `VigilanteTest` los casos de accesos y de silencio — tests/Feature/VigilanteTest.php — hecho cuando: falla por la razón esperada, anotado — cubre: CA2, CA4 — depende: T011
  - Accesos, leyendo el canal `seguridad` de hoy y de ayer:
    - `test_mas_de_10_fallos_por_origen_avisa`, `test_mas_de_10_fallos_por_cuenta_avisa` y `test_10_fallos_no_avisa`;
    - `test_lee_lineas_con_extra`.
  - Silencio:
    - `test_silencio_de_30_minutos_acumula`;
    - `test_envia_lo_pendiente_al_acabar_el_silencio`, aunque la condición haya terminado;
    - `test_los_tipos_tienen_silencio_propio`;
    - `test_cada_evento_se_cuenta_una_vez`: 11 fallos en un minuto y 10 ejecuciones dan un aviso con 11 (/analyze A9).
  - `test_un_envio_fallido_no_marca_el_aviso_como_enviado`: el transporte lanza una excepción, queda `Log::warning` con el tipo y sin el mensaje, y se reintenta al minuto siguiente.
  - nota: aceptado en /analyze r2 (2026-10-25), A44 — la lectura del canal `seguridad` es incremental o con tope, con su caso en VigilanteTest.
  - nota: falla por la razón esperada (2026-10-25): los 11 casos nuevos, como los 14 de T011, con «Target class [App\Avisos\Vigilante] does not exist».
  - nota: Fija para T024: el envío es Mail::to(destino)->send(AvisoProduccion), con la propiedad pública anterior (momento del aviso anterior o null); lo pendiente solo nace si en el silencio se cumple la condición con eventos nuevos.
  - nota: Añadido test_10_fallos_no_avisa con un undécimo fallo de otro origen y otra cuenta, para que el umbral sea por grupo y no total.
  - nota: A44 aplicado: test_el_canal_seguridad_se_lee_con_tope (con el tope de 5 líneas, 11 fallos se leen en tres ejecuciones y el aviso llega en la tercera).
- [x] T013 [P] Escribir `AvisoProduccionTest` — tests/Feature/AvisoProduccionTest.php — hecho cuando: falla por la razón esperada (no existen el correo ni el comando), anotado — cubre: CA5, CA6
  - `test_el_correo_no_lleva_datos_personales_ni_secretos`: con logs de entrada que contienen una IP y un correo, y con `APP_KEY` y `MAIL_PASSWORD` de prueba, ni el cuerpo ni el asunto contienen ninguno, ni las huellas.
  - `test_lleva_tipo_recuentos_horas_y_como_consultar`: asunto `[Dentissa] <tipo>`, recuento, inicio y fin en `CLINICA_ZONA_HORARIA`, acumulado desde el aviso anterior y las órdenes de consulta, en español.
  - `test_el_aviso_de_prueba_se_envia`: `dentissa:vigilar --prueba` envía un aviso de prueba a `AVISOS_CORREO` y sale con 0.
  - `test_vigilar_sale_con_error_si_falla_el_envio` (TM10, /analyze A21).
  - nota: falla por la razón esperada (2026-10-25): tres casos con «The command dentissa:vigilar does not exist» y el de contenido con «Class App\Mail\AvisoProduccion not found».
  - nota: test_el_correo_no_lleva_datos_personales_ni_secretos recorre el camino entero (logs con IP, correo, búsqueda y huellas → comando → correo), así que pasa con T025; T021 cubre el caso de contenido.
  - nota: Fija el constructor del correo: AvisoProduccion(tipo, recuento, desde, hasta, anterior = null), con fechas en UTC; asunto «[Dentissa] <tipo legible>», p. ej. «Errores del servidor».
- [x] T014 Añadir a `ConfiguracionProduccionTest` los casos del Compose y de los archivos ignorados — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: los dos casos fallan por la razón esperada y el resto del archivo sigue en verde, anotado — cubre: CA1, CA8
  - En `docker-compose.prod.yml`, `app` monta `nginx_logs:/var/log/nginx:ro`.
  - `test_los_archivos_de_clave_quedan_fuera_del_paquete_y_del_repo`: `*.asc` y `*.gpg` en `.gitignore`, y `**/*.asc` y `**/*.gpg` en `.dockerignore` (/analyze A18).
  - nota: falla por la razón esperada (2026-10-25): app no monta nginx_logs en solo lectura y .dockerignore no excluye **/*.asc; los otros 17 casos siguen en verde.
  - nota: El caso del Compose también exige que web siga montando nginx_logs con escritura.
- [x] T015 Añadir a `ConfiguracionProduccionTest` los casos de `respaldar.sh` (D2), anclados a las órdenes reales — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: los casos fallan por la razón esperada, anotado — cubre: CA7, CA8 — depende: T014
  - `test_respaldar_cifra_con_la_clave_publica`: `gpg` con `--recipient-file` y `--trust-model always`, sin `--symmetric` ni `--passphrase`.
  - `test_respaldar_no_escribe_en_claro`:
    - la tubería `mariadb-dump | awk … | gzip | gpg` va hacia `.part`, con `mv` a `.sql.gz.gpg`;
    - sin `> …sql` ni `> …sql.gz`;
    - sin sustitución de procesos `>(…)` (/analyze A8);
    - con `pipefail`, y la comprobación de `-- Dump completed` en la marca va después de la tubería.
  - `test_respaldar_se_detiene_sin_gpg_o_sin_clave`: `gpg --version` y `gpg --show-keys` antes de `mariadb-dump`.
  - Conserva 14 copias `dentissa-*.sql.gz.gpg`.
  - nota: falla por la razón esperada (2026-10-25): los cuatro casos no encuentran la clave pública, la tubería con awk y gpg, las comprobaciones previas ni la retención de .sql.gz.gpg en el respaldar.sh de la 030.
  - nota: Fija las órdenes de T028: CLAVE_PUBLICA con DENTISSA_CLAVE_PUBLICA, copia y parte, marca con mktemp -p /dev/shm, la tubería mariadb-dump | awk -v marca … | gzip | gpg … --output parte --encrypt, la comprobación de la marca tras la tubería y mv -- parte copia. Además rechaza una clave pública con ^sec: (CA8).
- [x] T016 Añadir a `ConfiguracionProduccionTest` los casos de `restaurar.sh`, que se ejecuta en el PC (D3) — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: los casos fallan por la razón esperada, anotado — cubre: CA8, CA9 — depende: T015
  - `test_restaurar_comprueba_antes_de_borrar`: la pasada de comprobación local (`gpg --decrypt`, `gzip -cd` y `-- Dump completed`) va antes de la confirmación y de cualquier `ssh` que pare servicios o haga `DROP DATABASE`; `--solo-comprobar` sale tras ella.
  - `test_restaurar_distingue_los_rechazos`: los cuatro mensajes de Contratos, con «no se pudo usar la clave» distinto de «alterada o cortada», a partir de `--status-fd` y de `gzip` (/analyze A13, A34).
  - `test_restaurar_no_toca_la_base_sin_datos_descifrados` (/analyze A29):
    - en la orden remota de la pasada 2, el `DROP`/`CREATE` va después de leer la primera línea del volcado y solo si la hay;
    - `pipefail` en el PC y en la orden remota;
    - el éxito exige el código 0 de gpg y de la orden remota, y `-- Dump completed` como última línea cargada.
  - `test_la_clave_nunca_llega_al_servidor`:
    - sin `--import`, `--export-secret-keys`, `--pinentry-mode` ni `scp` o `ssh` de archivos `.asc`;
    - la entrada del `ssh` de la carga es la salida de `gpg --decrypt`;
    - ningún script de `deploy/` que corre en el servidor llama a `gpg --decrypt` ni importa claves (/analyze A5).
  - Usa `DENTISSA_APP` (por defecto `/opt/dentissa/app`) y no `dirname "$0"` para las órdenes del servidor (/analyze A2).
  - nota: aceptado en /analyze r2 (2026-10-25), A43 — `<ref>` y `DENTISSA_APP` se validan (tag, commit o ruta absoluta sin espacios) o se citan con `printf %q` en la orden remota, con su caso en el test.
  - nota: aceptado en /analyze r3 (2026-10-25), A50 — caso del primer despliegue sin copia: `desplegar.sh` imprime una orden coherente.
  - nota: aceptado en /analyze r3 (2026-10-25), A51 — caso con un volcado cortado después de la primera línea: la orden remota registra la última línea cargada y el script dice «la carga quedó incompleta».
  - nota: falla por la razón esperada (2026-10-25): los cinco casos fallan sobre el restaurar.sh de la 030 (no exige .sql.gz.gpg, no tiene la pasada con --status-fd, ni el heredoc CARGA, ni DENTISSA_APP).
  - nota: Clasificación fijada con una prueba de concepto el 2026-10-25 (gpg 2.4.9 de Git Bash, llavero temporal): buena → DECRYPTION_OKAY; clave ajena → NO_SECKEY + ERROR pkdecrypt_failed 33554449 (código 17); frase incorrecta → NO_SECKEY + pkdecrypt_failed 67108875 (11); cancelada → NO_SECKEY + pkdecrypt_failed 67108963 (99); alterada → BADMDC + DECRYPTION_FAILED; cortada → DECRYPTION_FAILED sin BADMDC. default-cache-ttl del agente: 600 s.
  - nota: A43 aplicado: test_restaurar_no_depende_de_su_ruta_y_cita_lo_remoto exige validar DENTISSA_APP y la versión de --volver, y citar con printf %q cada argumento remoto.
  - nota: A51 aplicado: la carga remota guarda la última línea con un awk de su tubería y sale con 4 sin -- Dump completed; el PC dice «la carga quedó incompleta».
  - nota: A50: el caso sin copia es de desplegar.sh y va en el test de T017 (test_desplegar_imprime_la_vuelta_del_contrato).
- [x] T017 Añadir a `ConfiguracionProduccionTest` los casos de la vuelta atrás (D4) — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: los dos casos fallan por la razón esperada, anotado — cubre: CA9 — depende: T016
  - `test_volver_con_copia_no_depende_de_la_version_de_destino` (/analyze A1, A17):
    - `volver.sh` admite `--sin-levantar` (checkout, construcción, `app` y `web` parados, `db` sana, sin `up` de `app` ni `web`);
    - rechaza un segundo argumento de archivo con la orden de `restaurar.sh --volver`;
    - avisa de parar `dentissa-vigilar.timer` si el destino no tiene `app/Console/Commands/Vigilar.php`;
    - `restaurar.sh --volver` llama a `volver.sh <ref> --sin-levantar` y carga y levanta con sus propias órdenes de `docker compose`.
  - `test_desplegar_imprime_la_vuelta_del_contrato`: si la verificación falla, `desplegar.sh` imprime `volver.sh <anterior>` sin copia solo cuando `migrate:status --pending` contó 0 migraciones antes de `migrate --force`; en otro caso, o si no pudo contarlas, imprime la orden de `restaurar.sh … --volver <anterior>` (/analyze A32).
  - nota: aceptado en /analyze r2 (2026-10-25), A40 — cualquier segundo argumento de `volver.sh` distinto de `--sin-levantar` se rechaza antes del checkout, con su caso en el test.
  - nota: aceptado en /analyze r3 (2026-10-25), A50 — `test_desplegar_imprime_la_vuelta_del_contrato` cubre tres salidas reales de Laravel 12 (0 pendientes, N pendientes, fallo del comando) y el caso sin copia.
  - nota: falla por la razón esperada (2026-10-25): volver.sh de la 030 no rechaza otros argumentos ni tiene --sin-levantar, y desplegar.sh no define sin_migraciones_pendientes().
  - nota: A40 aplicado: volver.sh rechaza antes del checkout cualquier segundo argumento distinto de --sin-levantar, o más de dos, con la orden de restaurar.sh --volver.
  - nota: A50 aplicado: el caso ejecuta en bash la función sin_migraciones_pendientes() de desplegar.sh con cuatro salidas simuladas (sin pendientes y código 0 → si; con una migración Pending y código 1, un fallo de conexión, o «No pending migrations» con código 1 → no), y comprueba que el primer despliegue, sin copia, no propone ninguna vuelta. Criterio sobre la salida real de Laravel 12: StatusCommand imprime «No pending migrations» y sale con 0 sin pendientes, y con distinto de 0 si las hay (vendor/laravel/framework, StatusCommand.php:81-88).
- [x] T018 Añadir a `ConfiguracionProduccionTest` los casos de los dos scripts nuevos — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: los casos fallan por la razón esperada, anotado — cubre: CA1, CA10 — depende: T017
  - `test_cifrar_copias_antiguas_cifra_antes_de_borrar` (D5):
    - `gzip -t` y `-- Dump completed` antes de cifrar;
    - `gpg --recipient-file` hacia `.part`, luego `mv`, `touch -r` y `shred -u`;
    - una copia dañada también se cifra, como `.sql.gz.danada.gpg`, y se borra su original (/analyze A16).
  - El temporizador de `preparar-vigilancia.sh` (D11):
    - exige root;
    - servicio `Type=oneshot` con la cuenta de despliegue y `docker compose -f /opt/dentissa/app/docker-compose.prod.yml exec -T -u www-data app php artisan dentissa:vigilar`;
    - timer `OnCalendar=minutely`;
    - `systemctl enable --now dentissa-vigilar.timer`.
  - nota: falla por la razón esperada (2026-10-25): los dos scripts no existen.
  - nota: Fija el orden de cifrar-copias-antiguas.sh (comprobar → gpg hacia .part → mv → touch -r → shred -u, una sola vez) y las unidades de preparar-vigilancia.sh: servicio oneshot con User=\ (la de SUDO_USER, nunca root) y el ExecStart del plan, temporizador OnCalendar=minutely con WantedBy=timers.target, daemon-reload y enable --now.
- [x] T019 Añadir a `ConfiguracionProduccionTest` los casos de `verificar.sh` (D12) — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: los casos fallan por la razón esperada, anotado — cubre: CA6, CA8, CA10 — depende: T018
  - El bloque de configuración tiene 16 comprobaciones.
  - `test_verificar_comprueba_el_envio_de_avisos`: `MAIL_MAILER=smtp`, `MAIL_HOST` y `MAIL_PORT`, `MAIL_USERNAME` y `MAIL_PASSWORD` sin `<…>`, y `MAIL_FROM_ADDRESS` y `AVISOS_CORREO` con formato de correo y sin `<…>` (/analyze A28).
  - `test_verificar_no_imprime_valores_del_correo`.
  - La sección «Avisos y copias (spec 032)» está dentro del bloque que no corre con `--interna` (/analyze A3). Comprueba el temporizador activo, la clave pública sin `sec`, el llavero sin claves secretas y la lectura del log de acceso desde `app`.
  - `test_verificar_falla_con_copias_en_claro`: `dentissa-*.sql.gz` en `/var/backups/dentissa` o en la carpeta personal de la cuenta de despliegue.
  - nota: aceptado en /analyze r2 (2026-10-25), A42 — `verificar.sh` exige TLS obligatorio en el envío (465 con TLS implícito, o 587 con el cifrado exigido en la configuración del mailer), con su caso en el test.
  - nota: falla por la razón esperada (2026-10-25): el bloque tiene 12 comprobaciones, no hay sección «Avisos y copias» ni búsqueda de copias en claro. El caso «no imprime valores» ya reconoce las 12 actuales y falla solo por el recuento.
  - nota: A42 aplicado: TLS obligatorio con el puerto 465 (Laravel elige smtps, TLS implícito, con port 465: MailManager::createSmtpTransport), que no exige tocar config/mail.php. Sustituye el 587 con STARTTLS de D9 dentro de lo que permite el aceptado; Gmail admite 465. La guía (T034) dirá MAIL_PORT=465.
  - nota: A28 aplicado: MAIL_FROM_ADDRESS y AVISOS_CORREO con FILTER_VALIDATE_EMAIL, que también rechaza el <…> de la guía.

## Implementación
### Registro de fallos de acceso (CA2)
- [x] T036 Extraer la regla de la cuenta del limitador a `AuthController::sujetoLimite(string $email): string`, pública y estática como `origenLimite` (`cuenta:<id>` buscando por correo sin mirar `activo`; si no hay cuenta, `correo:<minúsculas>`), y usarla en `claveLimiteLogin`, sin cambiar su comportamiento (D7, /analyze A12) — app/Http/Controllers/AuthController.php — hecho cuando: `LimiteIntentosTest` pasa sin cambios antes y después, `pint --test` limpio, y el método es público y estático — cubre: CA2 — depende: T010
  - nota: LimiteIntentosTest 9/9 en verde antes (línea base) y después; pint limpio. sujetoLimite(string \) es público y estático, con la misma consulta (sin mirar activo) y claveLimiteLogin la usa; el comentario de la regla pasa al método nuevo.
- [x] T020 Añadir el canal `seguridad` (`daily`, 14 días, `JsonFormatter`) y crear `App\Listeners\RegistrarFalloAcceso` para `Failed` y `Lockout` según el contrato de la línea (control de TM5 y TM8) — config/logging.php, app/Listeners/RegistrarFalloAcceso.php — hecho cuando: T010 pasa dos veces, `LimiteIntentosTest` sigue en verde, y se comprueba rompiendo (escribir la IP en `origen` hace fallar `test_el_registro_no_contiene_ip_ni_correo`), anotado — cubre: CA2 — depende: T003, T010, T036
  - `message` `acceso_fallido`; `context` con `tipo`, `metodo`, `origen` y `cuenta`.
  - `origen` = HMAC de `AuthController::origenLimite()`.
  - `metodo`: `google` en un `Failed` con `proveedor` `google` o en un `Lockout` de la ruta de la vuelta de Google; `contrasena` en otro caso (/analyze A33).
  - `cuenta` = HMAC de `AuthController::sujetoLimite()`: el correo sale de `Failed::$credentials` o del campo `email` de la petición en `Lockout`, y solo se usa si es un texto no vacío; es `null` en la vuelta de Google.
  - El listener nunca cambia la respuesta del limitador.
  - Clave HMAC derivada: `hash_hmac('sha256', 'dentissa-seguridad', APP_KEY)` (/analyze A27).
  - Huellas recortadas a 16 hexadecimales.
  - nota: aceptado en /analyze r2 (2026-10-25), A45 — `name` `seguridad` en el canal, o el contrato con el valor real.
  - nota: T010 pasa dos veces (10/10) y LimiteIntentosTest sigue en verde (9/9); pint limpio. event:list muestra Failed → handleFallo y Lockout → handleBloqueo por descubrimiento automático.
  - nota: Comprobado rompiendo (2026-10-25): escribir la IP en origen hace fallar test_el_registro_no_contiene_ip_ni_correo (y los dos de formato de huella); un handler extra para Login hace fallar test_un_login_correcto_no_escribe_nada (la comprobación pendiente de T010); tomar todo Lockout como contraseña hace fallar test_el_bloqueo_de_la_vuelta_de_google_escribe_sin_cuenta. Restaurado tras cada uno.
  - nota: A45 aplicado: el canal declara name seguridad y T010 lo comprueba.
  - nota: El registro va en try/catch con report(): un fallo al escribir el log nunca cambia la respuesta del login ni del limitador.

### Correo y vigilante (CA1, CA2, CA4, CA5, CA6)
- [x] T021 Crear `App\Mail\AvisoProduccion` y su plantilla de texto en español según el contrato del correo (D9) (control de TM7) — app/Mail/AvisoProduccion.php, resources/views/correos/aviso.blade.php — hecho cuando: los casos de contenido de T013 pasan y se comprueba rompiendo (pasar una línea de log al cuerpo hace fallar `test_el_correo_no_lleva_datos_personales_ni_secretos`), anotado — cubre: CA5 — depende: T013
  - Solo recibe el tipo, los recuentos, el intervalo y el acumulado, nunca líneas de log.
  - Horas en `CLINICA_ZONA_HORARIA`.
  - «Cómo consultar el detalle en el servidor», con las órdenes exactas (`journalctl -u dentissa-vigilar`, el canal `seguridad`, el log de acceso de `web`) y sin valores.
  - nota: test_lleva_tipo_recuentos_horas_y_como_consultar pasa; pint limpio. Asuntos: «[Dentissa] Errores del servidor», «Inicios de sesión fallidos», «El log de acceso tiene otro formato» y «Aviso de prueba». Cuerpo revisado en tres tipos renderizados: solo recuento, horas de la clínica, acumulado y órdenes de consulta.
  - nota: Comprobado rompiendo (2026-10-25): sin la conversión a la hora de la clínica falla el caso de contenido. La rotura de CA5 (una línea de log en el cuerpo) necesita el camino entero del comando y se hace en T025, cuando test_el_correo_no_lleva_datos_personales_ni_secretos puede pasar.
- [x] T023 Crear `App\Avisos\Estado` y `App\Avisos\Vigilante` con el aviso de errores (D8) (control de TM12, TM14, TM15 y P4) — app/Avisos/Estado.php, app/Avisos/Vigilante.php — hecho cuando: T011 pasa dos veces y se comprueba rompiendo (quitar el tope, la detección de truncado o el anclaje de la expresión hace fallar su caso), anotado — cubre: CA1 — depende: T003, T011, T021
  - `Estado`:
    - desplazamiento e inodo del log de acceso;
    - contadores por minuto de los últimos 5, con total y 5xx;
    - momento del último fallo de acceso contado;
    - por tipo de aviso: último envío, fin del intervalo cubierto y recuento pendiente;
    - sin peticiones;
    - escritura atómica, con temporal y `rename`.
  - `Vigilante`, aviso de errores:
    - lectura incremental desde el desplazamiento, con tope de 50 MiB;
    - vuelve a empezar si el inodo cambia o el tamaño baja;
    - sin estado, empieza al final del log;
    - expresión anclada a `combined`: el código es el número que sigue a la primera cadena entre comillas, y la hora es el último `[…]` antes de ella;
    - ventana de 5 minutos y umbral > 1 %;
    - aviso `formato` si, con al menos 20 líneas en la ventana, más de la mitad no encajan (/analyze A36);
    - reloj y rutas inyectados.
  - nota: aceptado en /analyze r3 (2026-10-25), A49 — detección del cambio de formato con poco tráfico, según el caso de T011.
  - nota: Los 14 casos de T011 pasan dos veces; los de T012 siguen fallando hasta T024. pint limpio.
  - nota: Comprobado rompiendo (2026-10-25), uno a uno y restaurando: sin tope falla test_lo_que_supera_el_tope_se_lee_despues; sin la comprobación de tamaño, test_detecta_log_truncado; sin la del inodo, test_detecta_log_rotado; empezando por el byte 0, test_primera_ejecucion_empieza_al_final; guardando la línea en el estado, test_el_estado_no_guarda_peticiones; con una expresión ingenua (primer corchete y primer número), test_no_se_engana_con_corchetes_ni_espacios y test_ignora_las_lineas_que_no_tienen_el_formato.
  - nota: Corregido el test de T011 test_detecta_log_rotado: tenía 3 errores entre 300 peticiones, justo el 1 %, que no avisa; ahora el log nuevo lleva 147 respuestas buenas (250 en total), sigue siendo más largo que lo leído y el caso sigue probando el inodo.
  - nota: Las líneas vacías no cuentan como «sin formato», para que un salto de línea suelto no acerque el aviso formato (A49).
  - nota: A49 aplicado: aviso formato también con 5 líneas seguidas sin el formato y ninguna buena en medio (malas_seguidas en el estado).
- [x] T024 Añadir a `Vigilante` el aviso de accesos y el silencio (D8) (control de TM5, TM6 y TM10) — app/Avisos/Vigilante.php, app/Avisos/Estado.php — hecho cuando: T011 y T012 pasan dos veces y se comprueba rompiendo (quitar el silencio hace fallar `test_silencio_de_30_minutos_acumula`, y contar desde el inicio de la ventana hace fallar `test_cada_evento_se_cuenta_una_vez`), anotado — cubre: CA2, CA4 — depende: T012, T020, T023
  - Accesos: lee las líneas del canal `seguridad` de hoy y de ayer dentro de la ventana (`datetime` y `context`, ignorando `extra`) y las agrupa por origen y por cuenta; umbral > 10.
  - Silencio:
    - 30 minutos por tipo (`errores`, `accesos`, `formato`);
    - cada aviso cuenta los eventos distintos desde el fin del intervalo del anterior;
    - lo pendiente se envía al acabar el silencio.
  - Envío fallido: no actualiza el último envío y deja `Log::warning('aviso no enviado', ['tipo' => …])`.
  - nota: aceptado en /analyze r2 (2026-10-25), A44 — lectura incremental o con tope del canal `seguridad`.
  - nota: VigilanteTest completo (T011 y T012, 25 casos) pasa dos veces; pint limpio.
  - nota: Comprobado rompiendo (2026-10-25), uno a uno y restaurando: sin silencio fallan test_silencio_de_30_minutos_acumula, test_los_tipos_tienen_silencio_propio y test_cada_evento_se_cuenta_una_vez; contando otra vez lo de la ventana durante el silencio (sin exigir eventos nuevos) fallan test_silencio_de_30_minutos_acumula, test_envia_lo_pendiente_al_acabar_el_silencio y test_cada_evento_se_cuenta_una_vez; dando por enviado un envío fallido, test_un_envio_fallido_no_marca_el_aviso_como_enviado; con el umbral sobre el total y no por grupo, test_10_fallos_no_avisa; sin tope en el canal seguridad, test_el_canal_seguridad_se_lee_con_tope.
  - nota: A44 aplicado: el canal seguridad se lee de forma incremental, por archivo diario (hoy y ayer), con el mismo tope que el log de acceso.
  - nota: Corregida la mecánica de test_un_envio_fallido_no_marca_el_aviso_como_enviado (T012): Mail::fake() después de Mail::shouldReceive no se puede crear de nuevo; se guarda la falsificación del setUp y se restaura con Mail::swap. Lo que comprueba no cambia.
- [x] T025 Crear el comando `dentissa:vigilar` y su opción `--prueba` (D8, CA6) (control de TM10) — app/Console/Commands/Vigilar.php — hecho cuando: T013 pasa completo dos veces, `php artisan list` muestra `dentissa:vigilar`, y en local `php artisan dentissa:vigilar --prueba` deja el correo en `laravel.log` (`MAIL_MAILER=log`), anotado — cubre: CA1, CA6 — depende: T013, T024
  - Sin opción, ejecuta el vigilante y sale con código distinto de 0 si un envío falla.
  - Con `--prueba`, envía un aviso de prueba a `AVISOS_CORREO`.
  - nota: T013 completo (4 casos) pasa dos veces; php artisan list muestra dentissa:vigilar; pint limpio.
  - nota: Verificación local (2026-10-25): con MAIL_MAILER=log y AVISOS_CORREO de prueba, dentissa:vigilar --prueba escribe «Aviso de prueba enviado.» y deja en laravel.log el correo con «Subject: [Dentissa] Aviso de prueba» (el log se borró después).
  - nota: Comprobado rompiendo (2026-10-25): una línea del log de acceso o las huellas del canal seguridad en el cuerpo del correo hacen fallar test_el_correo_no_lleva_datos_personales_ni_secretos (la rotura de CA5 que T021 dejó para aquí); salir con 0 aunque falle un envío hace fallar test_vigilar_sale_con_error_si_falla_el_envio.
  - nota: El aviso de prueba lo envía el propio comando (sin método nuevo en Vigilante), con el mismo Log::warning sin mensaje si falla.

### Compose, temporizador y archivos ignorados (CA1, CA8)
- [x] T026 Montar `nginx_logs:/var/log/nginx:ro` en `app` (D10; ruta protegida aprobada por el usuario el 2026-10-23) e ignorar `*.asc` y `*.gpg` en `.gitignore`, y `**/*.asc` y `**/*.gpg` en `.dockerignore` (control de TM1, TM9 y TM14) — docker-compose.prod.yml, .gitignore, .dockerignore — hecho cuando: los casos de T014 pasan y `docker compose -f docker-compose.prod.yml config` es válido, anotado — cubre: CA1, CA8 — depende: T014
  - nota: Los dos casos de T014 pasan; docker compose -f docker-compose.prod.yml config válido (con archivos de entorno vacíos en una carpeta temporal fuera del repo): app monta nginx_logs en /var/log/nginx con read_only y web sigue con escritura. Ningún .asc ni .gpg en git ls-files.
  - nota: Comprobado rompiendo (2026-10-25): sin :ro falla test_app_monta_el_log_de_nginx_en_solo_lectura; con *.asc solo en la raíz del .dockerignore falla test_los_archivos_de_clave_quedan_fuera_del_paquete_y_del_repo.
  - nota: Ruta protegida docker-compose.prod.yml: cambio aprobado por el usuario el 2026-10-23 (plan D10).
- [x] T027 Crear `deploy/preparar-vigilancia.sh` (D11) (control de TM10) — deploy/preparar-vigilancia.sh — hecho cuando: el caso del temporizador de T018 pasa y `bash -n` limpio — cubre: CA1, CA2 — depende: T018, T025
  - Exige root.
  - Comprueba antes que gpg y la clave pública están en su sitio.
  - Instala `dentissa-vigilar.service` y `.timer`, `systemctl daemon-reload` y `enable --now`.
  - Es idempotente.
  - Al final muestra `systemctl list-timers dentissa-vigilar.timer`.
  - nota: test_preparar_vigilancia_instala_el_temporizador pasa; bash -n limpio en Git Bash y en el contenedor. La instalación real se ensaya en el servidor en T042.
  - nota: Además de lo de la tarea, comprueba que la cuenta de SUDO_USER está en el grupo docker (sin él, el servicio no puede usar docker compose) y al final da la orden del aviso de prueba.

### Copias cifradas (CA7–CA10)
- [x] T028 Cifrar en `respaldar.sh` según D2 (control de TM1 y TM2) — deploy/respaldar.sh — hecho cuando: los casos de T015 pasan, `bash -n` limpio y se comprueba rompiendo (quitar `pipefail` o la comprobación previa hace fallar su caso), anotado — cubre: CA7, CA8 — depende: T015
  - Antes de volcar, comprueba `gpg --version` y `gpg --show-keys "$DENTISSA_CLAVE_PUBLICA"` (por defecto `/opt/dentissa/entorno/copias.pub.asc`).
  - Tubería `mariadb-dump | awk '{print} END {print > marca}' | gzip | gpg …`, con la marca en `/dev/shm`, hacia `.part`.
  - Tras la tubería, comprueba `-- Dump completed` en la marca. Entonces hace `mv` y `chmod 600`; si algo falla, borra `.part` y la marca.
  - Conserva 14 copias `.sql.gz.gpg`.
  - La última línea sigue siendo la ruta de la copia.
  - nota: Los cuatro casos de T015 pasan; bash -n limpio.
  - nota: Prueba local en Git Bash (2026-10-25), con un docker falso que entrega un volcado con un nombre de paciente, una clave de prueba fuera del repo y la marca en una carpeta local (el PC no tiene /dev/shm): tres copias seguidas .sql.gz.gpg sin falsos «incompleta» (A8); ningún archivo con el nombre en claro ni marcas sobrantes; la copia se descifra con la clave de prueba y acaba en -- Dump completed; sin clave pública o con un archivo de clave SECRETA se detiene sin ninguna llamada a docker; con el volcado cortado se detiene sin dejar copia ni .part.
  - nota: La prueba destapó un fallo, corregido: el trap EXIT usaba las variables local de main(), que ya no existen al salir (con set -u, «parte: unbound variable», y las marcas no se borraban). parte y marca pasan a ser globales. También se comprueba la clave secreta antes que la pública, para que el mensaje lo diga.
  - nota: Comprobado rompiendo (2026-10-25): sin pipefail, con la marca en una sustitución de procesos o con una copia en claro antes de mover falla test_respaldar_no_escribe_en_claro; sin la comprobación de gpg, test_respaldar_se_detiene_sin_gpg_o_sin_clave.
  - nota: Corregido el caso de T015 test_respaldar_conserva_14_copias_cifradas: buscaba el echo final en ordenes(), que quita los echo; ahora lee el script con leer(). Lo que comprueba no cambia.
- [x] T029 Reescribir `restaurar.sh` para el PC según D3 y el contrato del plan (control de TM3, TM4 y TM11) — deploy/restaurar.sh — hecho cuando: los casos de T016 pasan, `bash -n` limpio, y se comprueba rompiendo (mover la comprobación después del `ssh` del `DROP` hace fallar `test_restaurar_comprueba_antes_de_borrar`), anotado · verificación manual en el PC con Git Bash, con una clave de prueba y una copia de prueba fuera del clon: `--solo-comprobar` da OK con la copia buena, y la copia para otra clave, la copia con un byte cambiado y la copia cortada dan cada una su mensaje; una frase incorrecta da «no se pudo usar la clave»; y con la caché del agente vaciada entre pasadas (`gpgconf --reload gpg-agent`) y la frase cancelada en la pasada 2, la orden remota no recibe datos y no toca nada (probado contra una orden remota de prueba que solo registra si llega la primera línea), anotado — cubre: CA8, CA9 — depende: T016
  - Uso: `restaurar.sh <copia.sql.gz.gpg> <cuenta@servidor> [--volver <ref>] [--solo-comprobar]`.
  - Pasada 1, local, con `--status-fd` y los cuatro mensajes de Contratos.
  - Confirmación con el nombre de la base, leído por SSH.
  - Pasada 2 (/analyze A29):
    - para `app` y `web`;
    - `gpg --decrypt` → `ssh` → una orden remota que espera la primera línea descomprimida y solo entonces hace `DROP`/`CREATE` y carga esa línea y el resto en `docker compose … exec -T db mariadb`;
    - sin datos, no toca la base y vuelve a levantar `app` y `web`;
    - éxito solo con código 0 de gpg y de la orden remota, con `pipefail` en los dos lados y `-- Dump completed` como última línea cargada;
    - levanta `app` y `web`, rehace las cachés y ejecuta `verificar.sh --interna`.
  - Con `--volver <ref>`, antes de la pasada 2, `ssh … bash deploy/volver.sh <ref> --sin-levantar`.
  - En el servidor usa `DENTISSA_APP` (por defecto `/opt/dentissa/app`).
  - Si la carga se corta, da la orden para repetir.
  - nota: aceptado en /analyze r2 (2026-10-25), A43 — validar o citar `<ref>` y `DENTISSA_APP` en la orden remota.
  - nota: aceptado en /analyze r3 (2026-10-25), A51 — la orden remota conoce la última línea cargada con un miembro de su tubería (como el `awk` de D2), sin escribir el volcado en el disco.
  - nota: Los cinco casos de T016 pasan; bash -n limpio.
  - nota: Verificación manual OK 2026-10-25 en el PC (Git Bash, gpg 2.4.9), con una clave y copias de prueba en una carpeta temporal fuera del clon, la frase por gpg.conf (pinentry-mode loopback y passphrase-file, solo en la prueba) y un servidor simulado (ssh y docker falsos): --solo-comprobar da OK con la buena; la de otra clave da «la copia no es para tu clave»; la alterada y la cortada, «alterada o cortada»; un volcado sin la línea final, «el volcado está incompleto»; una frase incorrecta, «no se pudo usar la clave (frase incorrecta o cancelada)». Restauración completa: stop app web → DROP → carga (última línea cargada: -- Dump completed) → up, cachés y verificar --interna, sin marcas sobrantes. Con --volver v0.10.0, volver.sh v0.10.0 --sin-levantar antes de la carga. Confirmación incorrecta: ninguna orden de cambio. Caché vaciada en la confirmación y frase cancelada en la pasada 2 (A29): ni DROP ni carga, app y web vuelven a arrancar y el script lo dice.
  - nota: Comprobado rompiendo (2026-10-25): sin la pasada de comprobación falla test_restaurar_comprueba_antes_de_borrar; con el DROP antes de recibir la primera línea, test_restaurar_no_toca_la_base_sin_datos_descifrados; con el mismo mensaje para la frase y la clave ajena, test_restaurar_distingue_los_rechazos; subiendo la clave por SSH, test_la_clave_nunca_llega_al_servidor.
  - nota: A43 aplicado: DENTISSA_APP tiene que ser una ruta absoluta de letras, números, . _ / y -; la versión de --volver, lo mismo sin la barra inicial; cada argumento de la orden remota va con printf %q. Probado: «/opt/dentissa/app; rm -rf /» y «v1;reboot» se rechazan antes de nada.
  - nota: Corregido el test de T016 test_restaurar_distingue_los_rechazos: la expresión ponía \b delante de && y ||, donde no puede haber límite de palabra, y no aceptaba un rechazo tras un &&; ahora exige un espacio. Lo que comprueba no cambia.
- [x] T030 Adaptar `volver.sh` según D4 (control de TM11) — deploy/volver.sh — hecho cuando: el caso de `volver.sh` de T017 pasa, `bash -n` limpio y se comprueba rompiendo (levantar `app` con `--sin-levantar` hace fallar el caso), anotado — cubre: CA9 — depende: T017, T029
  - `volver.sh <ref> [--sin-levantar]`.
  - Un segundo argumento de archivo se rechaza antes de cambiar nada, con la orden de `restaurar.sh --volver`.
  - Avisa de parar el temporizador si el destino no tiene `Vigilar.php`.
  - nota: aceptado en /analyze r2 (2026-10-25), A40 — rechazar cualquier segundo argumento distinto de `--sin-levantar` antes del checkout.
  - nota: test_volver_con_copia_no_depende_de_la_version_de_destino pasa; bash -n limpio. Probado en Git Bash: «volver.sh v0.10.0 <copia>», «volver.sh v0.10.0 --sin-levantr» y «volver.sh v0.10.0 --sin-levantar extra» se rechazan antes del checkout, con la orden de restaurar.sh --volver.
  - nota: Comprobado rompiendo (2026-10-25): levantar app y web con --sin-levantar, ignorar un segundo argumento desconocido o volver a llamar a restaurar.sh hacen fallar el caso.
  - nota: A40 aplicado: cualquier segundo argumento distinto de --sin-levantar, o más de dos, se rechaza antes de cambiar nada.
  - nota: Ajustada una aserción de T017: exigía que volver.sh no contuviera el texto deploy/restaurar.sh, pero el mensaje del rechazo lo nombra a propósito; ahora exige que no lo ejecute (ninguna orden bash deploy/restaurar.sh).
- [x] T037 Alinear con D4 la orden de vuelta que imprime `desplegar.sh` cuando falla la verificación: contar antes de `migrate --force` las migraciones pendientes con `php artisan migrate:status --pending`; `volver.sh <anterior>` solo si contó 0 y, en otro caso o si no pudo contarlas (/analyze A32), `scp <servidor>:<copia> .` y `bash deploy/restaurar.sh <copia> <servidor> --volver <anterior>` desde el PC (/analyze A17) — deploy/desplegar.sh — hecho cuando: `test_desplegar_imprime_la_vuelta_del_contrato` de T017 pasa y `bash -n` limpio — cubre: CA9 — depende: T017, T030
  - nota: aceptado en /analyze r3 (2026-10-25), A50 — el criterio de «0 pendientes» se fija sobre la salida real de `migrate:status --pending` en Laravel 12 (o contando las filas de `migrations` antes y después de `migrate --force`), y se distingue de un fallo del comando. Sin copia, el mensaje no propone una vuelta con copia inexistente.
  - nota: test_desplegar_imprime_la_vuelta_del_contrato pasa (con la función ejecutada en bash sobre las cuatro salidas simuladas); los otros dos casos de desplegar.sh siguen en verde; bash -n limpio.
  - nota: Comprobado rompiendo (2026-10-25): aceptar el texto aunque el código de salida diga que hay pendientes, dar por hecho que no hay migraciones o contarlas después de migrar hacen fallar el caso.
  - nota: A50 aplicado: «0 pendientes» = migrate:status --pending --no-ansi dice «No pending migrations» y sale con 0 (salida real de Laravel 12, StatusCommand); un fallo del comando o cualquier otra salida → la vuelta con la copia. Sin copia (primer despliegue) sigue el mensaje de la 030, que no propone ninguna vuelta.
- [x] T031 Crear `deploy/cifrar-copias-antiguas.sh` según D5 — deploy/cifrar-copias-antiguas.sh — hecho cuando: el caso de T018 pasa y `bash -n` limpio — cubre: CA10 — depende: T018
  - Por cada `dentissa-*.sql.gz` de `/var/backups/dentissa`, comprueba `gzip -t` y `-- Dump completed`.
  - Las buenas se cifran hacia `.part` y luego `mv` a `.sql.gz.gpg`; las dañadas, como `.sql.gz.danada.gpg`.
  - Después: `touch -r`, `chmod 600` y `shred -u` de la original.
  - Al final, el recuento de cifradas y de dañadas, y la sugerencia de revisar las dañadas.
  - nota: test_cifrar_copias_antiguas_cifra_antes_de_borrar pasa; bash -n limpio.
  - nota: Prueba local en Git Bash (2026-10-25), con una clave de prueba y tres copias en claro (una buena, una sin la línea final, una con el gzip cortado): la buena queda como .sql.gz.gpg con su fecha original y se descifra hasta -- Dump completed; las dos dañadas quedan como .sql.gz.danada.gpg; ninguna en claro; una segunda ejecución no hace nada.
  - nota: Comprobado rompiendo (2026-10-25): borrar antes de cifrar o borrar una dañada sin cifrarla hacen fallar el caso.
  - nota: Si hay dañadas, el aviso final dice que no se pueden restaurar y que se borren cuando no se quieran (restaurar.sh solo acepta .sql.gz.gpg).
- [ ] T032 Ampliar `verificar.sh` según D12 (control de TM1, TM4, TM9 y TM10) — deploy/verificar.sh — hecho cuando: los casos de T019 pasan, `bash -n` limpio y se comprueba rompiendo (imprimir `MAIL_USERNAME` hace fallar `test_verificar_no_imprime_valores_del_correo`), anotado — cubre: CA6, CA8, CA10 — depende: T019
  - El bloque de configuración pasa de 12 a 16 comprobaciones de correo, con `MAIL_FROM_ADDRESS`.
  - Sección nueva «Avisos y copias (spec 032)», fuera de `--interna`. La búsqueda de copias en claro incluye la carpeta personal de la cuenta de despliegue.
  - Ninguna comprobación imprime valores.
  - nota: aceptado en /analyze r2 (2026-10-25), A42 — comprobación de TLS obligatorio en el envío.

### Guía y riesgo aceptado (CA3, CA6, CA7, CA9, CA10)
- [ ] T033 Actualizar la guía de las copias — deploy/README.md — hecho cuando: cada punto de CA7, CA9 y CA10 sobre la guía tiene su apartado y los comandos coinciden con los scripts de T028–T031 y T037 · verificación manual: el usuario revisa §7 y §8 — cubre: CA7, CA9, CA10 — depende: T028, T029, T030, T031, T037
  - §7, la clave del responsable (D14):
    - se genera en el PC con una frase de al menos 5 palabras al azar o 20 caracteres, y sin caducidad (/analyze A19);
    - la pública se lleva al servidor con permisos 600;
    - segunda copia de la secreta fuera del PC;
    - qué pasa si se pierden las dos;
    - con el PC perdido, la segunda copia se importa en otro equipo con gpg y SSH.
  - §7, las copias:
    - son `.sql.gz.gpg`;
    - se bajan con `scp` y se restauran desde el PC con `restaurar.sh`;
    - las copias en claro del PC se sustituyen y se borran.
  - §8: volver con copia desde el PC con `restaurar.sh --volver`.
  - Orden del primer despliegue de la 032 (D6), con el paso 0, `cifrar-copias-antiguas.sh` y `find / -xdev` de volcados en claro.
- [ ] T034 Actualizar la guía de los avisos (control de TM13) — deploy/README.md — hecho cuando: cada punto de CA3 y CA6 sobre la guía tiene su apartado y ningún paso tiene coste · verificación manual: el usuario revisa §4 y la sección de avisos — cubre: CA3, CA6 — depende: T002, T025, T027, T032, T033
  - §4 `app.env`, con las variables de correo de una cuenta de Gmail que solo sirve para enviar (D9, /analyze A6):
    - verificación en dos pasos;
    - contraseña de aplicación;
    - sin otros datos ni reenvíos;
    - marcadores `<…>`, que `verificar.sh` rechaza.
  - Sección nueva de avisos:
    - `preparar-vigilancia.sh`;
    - el aviso de prueba (`dentissa:vigilar --prueba`);
    - cómo consultar el detalle y cómo pararlo;
    - el monitor de UptimeRobot sobre `https://<dominio>/up` cada 5 minutos, con el resultado de T002.
- [ ] T035 Actualizar `docs/security.md` según el plan (Correcciones de riesgos e Impacto en arquitectura, /analyze A23) — docs/security.md — hecho cuando: el validador no avisa de RS15 y cada corrección tiene su estado — cubre: CA10
  - **Riesgos conocidos**, RS15 dividida:
    - RS15.b «en curso (spec 032)»;
    - RS15.c (disco de producción) con la excepción **EX3**;
    - RS15.d (staging) → objetivo 5;
    - RS15.a sigue en la 013.
  - **Excepciones aceptadas:** EX3, con motivo, aprobador (el responsable), vencimiento al cargar el primer dato real de pacientes y aprobación pendiente en T096.
  - **Superficie de ataque:** el temporizador del vigilante como tarea programada y la salida SMTP; desaparece «ni tareas programadas».
  - **Manejo de secretos:** la contraseña de aplicación de la cuenta de envío, la clave pública en el servidor, la secreta solo en el PC y su segunda copia, y la fila de copias.

## Preparativos y ensayos en el servidor
- [ ] T040 Preparativos del responsable (plan, Rollout; los hace el usuario) — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota recoge que cada paso está hecho, sin ningún secreto ni la frase: la huella de la clave pública, la salida de `gpg --show-keys` en el servidor sin `sec`, la frase con el mínimo cumplido, la frase pedida una sola vez y la cuenta de envío dedicada creada — cubre: CA8 — depende: T034
  - Par de claves en el PC (D14), con una frase que cumpla el mínimo de la guía, y segunda copia de la secreta fuera del PC.
  - En el PC, dos descifrados seguidos de una prueba piden la frase una sola vez (D3), y la nota recoge el `default-cache-ttl` del agente (`gpgconf --list-options gpg-agent`).
  - `copias.pub.asc` en `/opt/dentissa/entorno` (600).
  - Cuenta de Gmail solo para enviar (TM13), con verificación en dos pasos y contraseña de aplicación, y las variables de correo en `app.env`.
  - Cuenta de UptimeRobot.
- [ ] T041 Ensayo de las copias (aprobación explícita antes; lo ejecuta el usuario) — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota recoge la aprobación y cada resultado — cubre: CA7, CA8, CA10 — depende: T040
  - Paso 0 del Rollout. Después, `git fetch && git checkout <commit>` y `desplegar.sh <commit>` (orden de D6): deja `.sql.gz.gpg` y `desplegar.sh` sale con 0 (/analyze A3).
  - Dos `respaldar.sh` seguidos más, sin falsos «copia incompleta» (/analyze A8).
  - `find / -xdev` sin volcados en claro durante y después.
  - El servidor no descifra la copia: `gpg --decrypt` falla.
  - Con la clave pública renombrada, `desplegar.sh <ref distinta del HEAD>` se detiene en «Copia de la base» y el `HEAD` no cambia (/analyze A15).
  - `cifrar-copias-antiguas.sh` y `verificar.sh` completo, sin copias en claro.
  - nota: aceptado en /analyze r2 (2026-10-25), A30 — el `verificar.sh` completo de T041 anota como esperado el FALLO «temporizador activo» (el temporizador se instala en T042); el de T042 tiene que salir todo OK.
- [ ] T042 Ensayo de los avisos (aprobación explícita antes; lo ejecuta el usuario) — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota recoge la aprobación, los tiempos de llegada y cada resultado — cubre: CA1, CA2, CA5, CA6, CA10 — depende: T041
  - `preparar-vigilancia.sh`, y `systemctl list-timers` con la siguiente ejecución en menos de un minuto.
  - El aviso de prueba llega en ≤ 5 minutos, desde la cuenta de envío, al correo de siempre.
  - Líneas 5xx inyectadas en el log de acceso de `web` → correo de errores en ≤ 10 minutos.
  - 11 intentos fallidos contra una cuenta de prueba → correo de accesos en ≤ 10 minutos, y el límite responde igual.
  - Ningún correo lleva datos (revisión del usuario).
  - `verificar.sh` rompiendo da FALLO sin valores, y todo OK al restaurar:
    - sin `MAIL_PASSWORD`;
    - con el `<…>` de la guía en `MAIL_PASSWORD` y en `MAIL_FROM_ADDRESS`;
    - con una copia en claro;
    - con el temporizador parado.
  - nota: aceptado en /analyze r2 (2026-10-25), A41 — «con una copia en claro» usa un señuelo sin datos, `dentissa-señuelo.sql.gz`, que se borra al terminar.
- [ ] T043 Ensayo de la caída vista desde fuera (aprobación explícita antes; lo ejecuta el usuario): monitor de UptimeRobot sobre `https://dentissa.app/up` cada 5 minutos en verde; `cloudflared` parado ≥ 6 minutos → correo de caída en ≤ 10 minutos; al arrancarlo, correo de vuelta — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota recoge la aprobación y las horas de parada, de los dos correos y de la vuelta — cubre: CA3 — depende: T042
- [ ] T044 Ensayo de la restauración y la vuelta desde el PC (aprobación explícita antes; lo ejecuta el usuario, con la segunda copia de la clave importada en un llavero temporal del PC con `GNUPGHOME`) — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota recoge la aprobación, la foto y la comparación, cada rechazo con su mensaje y el resultado de la vuelta — cubre: CA9 — depende: T043
  - Foto, con `app` y `web` parados: `CHECKSUM TABLE` y recuento de filas de cada tabla (/analyze A14).
  - `respaldar.sh` justo después; `scp` de la copia al PC.
  - `restaurar.sh <copia> <servidor>` → mismos valores que la foto.
  - `gpg --list-secret-keys` y `/dev/shm` del servidor sin claves durante y después (/analyze A5).
  - La copia para otra clave, la copia con un byte cambiado y la copia cortada se detienen antes de confirmar, cada una con su mensaje (/analyze A13), y la foto de la base no cambia.
  - Con la caché del agente vaciada entre pasadas y la frase cancelada en la pasada 2, la base no cambia y `app` y `web` vuelven a estar en marcha (/analyze A29).
  - `restaurar.sh <copia> <servidor> --volver v0.10.0`:
    - pide la frase una sola vez (/analyze A1);
    - deja la base igual que la foto;
    - avisa de parar el temporizador, que se para.
  - nota: aceptado en /analyze r2 (2026-10-25), A31 — la comparación con la foto se hace antes de dar tráfico a la aplicación, o excluyendo las tablas volátiles (`cache`, `sessions`, `jobs`) declarándolo; la nota dice cómo.
  - nota: aceptado en /analyze r2 (2026-10-25), A35 — la nota recoge la duración de `restaurar.sh --volver` frente a los 15 min de 030 CA7.

## Integración y documentación
- [ ] T050 Ejecutar la puerta de calidad sobre el diff de la spec: suite completa, `pint --test` en los PHP tocados, `bash -n` en los scripts tocados, gitleaks, semgrep `p/php`, `composer audit --locked`, `npm audit` y revisión de cada `Log::` nuevo — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: todo pasa sin hallazgos críticos ni altos, anotado con los recuentos — cubre: CA5, CA8 — depende: T020, T025, T026, T027, T028, T029, T030, T031, T032, T036, T037
  - nota: aceptado en /analyze r2 (2026-10-25), A47 — registrar el coste de explorar los listeners al arrancar, o comprobar que `desplegar.sh` hace `event:cache`, y anotarlo en la verificación manual del RNF «no ralentiza».
- [ ] T051 Actualizar el roadmap — docs/roadmap.md — hecho cuando: las entradas existen, citan la 032 y el validador no da errores
  - En el objetivo 5: OB6.c y RS15.d nuevas (staging), RS15.c como excepción EX3, y la 032 en curso.
  - En Pendientes:
    - «cada despliegue usa el `desplegar.sh` de la versión anterior» (D6);
    - el umbral de 5xx sin mínimo de peticiones, riesgo aceptado por el usuario (A26);
    - pasar `dentissa:vigilar` al planificador de la 020 y retirar el temporizador (D11, A20);
    - la vuelta de la 038 a v0.11.0 al retomarla (D15, A25).
  - nota: aceptado en /analyze r2 (2026-10-25), A39 — la entrada del roadmap dice que la 020 debe añadir el `scheduler` al Compose de producción, mover `dentissa:vigilar` a él y retirar el temporizador.
- [ ] T090 Actualizar `docs/architecture.md` en sus secciones reales (/analyze A23), según el ADR 0007:
  - **Diagrama de componentes:** el temporizador → `dentissa:vigilar` en `app`, `nginx_logs` también en `app`, la salida SMTP a Gmail y UptimeRobot hacia `/up`.
  - **Módulos:** `App\Avisos` y `App\Listeners\RegistrarFalloAcceso`.
  - **Integraciones externas:** Gmail SMTP con la cuenta de envío y UptimeRobot; desaparece «No hay correo…».
  - **Despliegue:** copias `.sql.gz.gpg`, restauración y vuelta con copia desde el PC, `preparar-vigilancia.sh` y `cifrar-copias-antiguas.sh`.
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md`:
  - **deployment.md:** copias cifradas, restauración y vuelta desde el PC, avisos y la nueva verificación.
  - **observability.md:**
    - alertas de 5xx, fallos de acceso y caída, en uso;
    - canal `seguridad`;
    - OB6.b «en curso (spec 032)»;
    - OB6.c (staging), nueva → objetivo 5;
    - OB6.a sigue en el objetivo 3;
    - junto a la brecha del registro de acceso (objetivo 3), que el vigilante depende del formato `combined` y que cambiarlo exige ajustar `App\Avisos\Vigilante` (el aviso `formato` lo detecta) (/analyze A36).
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación — no aplica: no hay staging (objetivo 5)
- [ ] T096 Aprobación humana para producción y aprobación de la excepción EX3 por el responsable
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout), en el orden de D6:
  - en el servidor:
    1. paso 0: `command -v gpg` y `gpg --show-keys` sin `sec`;
    2. `git fetch --tags && git checkout vX.Y.Z`;
    3. `desplegar.sh vX.Y.Z`;
    4. `preparar-vigilancia.sh`;
    5. `cifrar-copias-antiguas.sh`;
    6. `verificar.sh` completo, con la dirección pública;
    7. aviso de prueba;
  - en la web: monitor de UptimeRobot en verde;
  - en el PC: sustituir y borrar las copias en claro;
  - durante la primera hora: ningún correo inesperado, `journalctl -u dentissa-vigilar` sin errores y 0 5xx nuevos.
  - verificación manual (CA7, CA10): `find / -xdev` sin volcados en claro tras el despliegue.
  - verificación manual (D6): el comando de vuelta que da `/release` usa la versión anterior real (`v0.10.0`), no el tag desplegado.
- [ ] T098 Marcar spec como `released` (/analyze A24):
  - Correcciones → mitigada (vX.Y.Z): OB6.b y RS15.b. RS15.c → aceptada (EX3).
  - Siguen abiertos OB6 (OB6.a → objetivo 3, OB6.c → objetivo 5) y RS15 (RS15.a → 013, RS15.d → objetivo 5).
  - Spec extendida 030, con fila de Historial:
    - CA6 con `→ reemplazado por 032 CA9`;
    - en «Fuera de alcance», «Envío de correos desde producción» → atendido por la 032;
    - en Cobertura, los destinos de RS15.b y OB6.b → 032.
  - Spec 001, CA9: el canal `seguridad` de la 032 registra los fallos para avisar, pero no es el registro de auditoría; sigue → 020/021.
  - nota: aceptado en /analyze r2 (2026-10-25), A35 — anotar 030 CA7 como modificado por 032 CA9 (vuelta con copia desde el PC).
  - nota: aceptado en /analyze r2 (2026-10-25), A48 — la 001 solo se anota porque su comportamiento no cambia; no entra en `extends`.

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T011, T014, T018, T042 | T023, T025, T026, T027 |
| CA2 | T010, T012, T042 | T020, T024, T027, T036 |
| CA3 | T002, T043 | T034 |
| CA4 | T012 | T024 |
| CA5 | T013, T042, T050 | T021 |
| CA6 | T013, T019, T042 | T025, T032, T034 |
| CA7 | T015, T041 | T028, T033 |
| CA8 | T014, T015, T016, T019, T040, T041, T050 | T026, T028, T029, T032 |
| CA9 | T016, T017, T044 | T029, T030, T033, T037 |
| CA10 | T018, T019, T041, T042 | T031, T032, T033, T035 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T026, T028, T032 | T015, T019, T041 |
| TM2 | T028 | T015, T041 |
| TM3 | T029 | T016, T044 |
| TM4 | T029, T032 | T016, T019, T044 |
| TM5 | T020, T024 | T010, T012, T042 |
| TM6 | T024 | T012 |
| TM7 | T021 | T013 |
| TM8 | T020 | T010 |
| TM9 | T026, T032 | T014, T019, T050 |
| TM10 | T024, T025, T027, T032 | T012, T013, T019, T042 |
| TM11 | T029, T030 | T017, T044 |
| TM12 | T023 | T011 |
| TM13 | T034, T040 | T042 |
| TM14 | T023, T026 | T011, T013 |
| TM15 | T023 | T011 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| deploy/respaldar.sh | T028 |
| deploy/restaurar.sh | T029 |
| deploy/volver.sh | T030 |
| deploy/desplegar.sh | T037 |
| deploy/cifrar-copias-antiguas.sh | T031 |
| deploy/preparar-vigilancia.sh | T027 |
| deploy/verificar.sh | T032 |
| deploy/README.md | T033, T034 |
| docker-compose.prod.yml | T026 |
| app/Listeners/RegistrarFalloAcceso.php | T020 |
| app/Http/Controllers/AuthController.php | T036 |
| app/Avisos/Vigilante.php, app/Avisos/Estado.php | T023, T024 |
| app/Console/Commands/Vigilar.php | T025 |
| app/Mail/AvisoProduccion.php, resources/views/correos/aviso.blade.php | T021 |
| config/avisos.php, config/logging.php | T003, T020 |
| .env.example | T003 |
| .gitignore, .dockerignore | T026 |
| tests/Feature/RegistroFallosAccesoTest.php, VigilanteTest.php, AvisoProduccionTest.php, ConfiguracionProduccionTest.php | T010, T011, T012, T013, T014, T015, T016, T017, T018, T019 |
| docs/security.md, docs/observability.md, docs/deployment.md, docs/architecture.md, docs/roadmap.md, docs/adr/0007 | T035, T051, T090, T091 (el ADR se creó y aceptó en `/plan`) |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Gratuito | T002, T034 (revisión de la guía) |
| Correos en español y comprensibles sin abrir el servidor | T013, T042 |
| La detección no ralentiza la aplicación | T010 (éxito sin línea), T027 (vigilante en el temporizador), verificación manual (el RNF no tiene métrica, /analyze A26) |
| Ningún secreto en el repo, el paquete ni la salida de la verificación | T014, T019, T050 |
