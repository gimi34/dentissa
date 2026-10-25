---
spec: 032-alertas-cifrado-copias
plan: plan.md
status: draft
---

# Tareas · 032 Alertas y copias cifradas en producción

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
  pega la salida. El agente nunca lee `/opt/dentissa/entorno`, la clave secreta, su frase ni la
  contraseña de aplicación, y la nota recoge solo resultados, sin secretos. Antes de cada ensayo,
  el usuario da su aprobación explícita y la nota la recoge (constitución, regla de despliegue;
  plan, Rollout). La producción está abierta: el ensayo despliega el commit de la rama con el
  orden de D6 y, al terminar T044, el servidor vuelve a `v0.10.0` con `volver.sh` y el temporizador
  queda parado hasta la liberación.

## Constitution Check
| Principio | Resultado | Justificación | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec aprobada el 2026-10-07 y plan el 2026-10-24; ninguna tarea de código empieza antes de aprobar estas tareas y de `/analyze`. | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | T010–T019 se escriben antes y fallan hoy (listener, vigilante, correo, comando y texto de los scripts que no existen todavía); cada tarea de implementación `depende:` de su test y los controles se comprueban además rompiendo. El efecto real de los scripts y de UptimeRobot (CA3, CA7–CA10 en el servidor) son comprobaciones declaradas en la Trazabilidad del plan: T041–T044, anotadas en su tarea. | test: T010–T019 · manual: notas de T041–T044 |
| P3 Autorización en servidor | ➖ | Ninguna tarea añade rutas, endpoints ni permisos; `dentissa:vigilar` es un comando de consola. | — |
| P4 Entrada validada y SQL con bindings | ✅ | T023 lee el log de acceso con una expresión anclada y descarta lo que no encaja (T011, `test_ignora_las_lineas_que_no_tienen_el_formato`); sin SQL nuevo. | test: T011 · lint: semgrep `p/php` en T050 |
| P5 Nada sensible en el repo | ✅ | Ningún secreto en scripts, tests ni `.env.example` (T003 solo añade nombres vacíos); T026 ignora `*.asc` y `*.gpg` en el repo y en el paquete; los preparativos de T040 se hacen fuera del repo. | test: T014 · lint: gitleaks en T050 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias de Composer ni de npm; gpg es del sistema. | lint: `composer audit --locked` y `npm audit --audit-level=high` en T001 y T050; `trivy image` en `/release` |
| P7 Auditoría de datos sensibles | ➖ | Ninguna tarea añade acciones auditables. T020 escribe en el canal `seguridad`, que alerta y no audita; los inicios de sesión fallidos siguen bajo la excepción P7 de la 030, que vence con la 021. | — |
| P8 Correlación y logs limpios | ✅ | T020 escribe solo tipo, método y huellas HMAC; T024 deja `Log::warning('aviso no enviado', ['tipo' => …])` sin el mensaje de la excepción; sin `request_id` hasta la 020. | test: T010 (`test_el_registro_no_contiene_ip_ni_correo`) · manual: revisión de cada `Log::` del diff en T050 |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Sin pantallas; el correo es texto plano. | — |
| P10 Rutas indexables descubribles | ➖ | Sin rutas nuevas ni cambiadas. | — |

## Preparación
- [ ] T001 Registrar la línea base: commit de partida, recuento de `php artisan test`, `composer audit --locked`, `npm audit` y `gpg --version` del PC — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cinco datos
- [ ] T002 Comprobar en la web de UptimeRobot (plan D13) que el plan gratuito permite un monitor HTTP(s) cada 5 minutos con avisos por correo al caer y al volver, y si pide confirmación o tarjeta — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota recoge el intervalo mínimo, los tipos de aviso y que no hay coste, con la fecha de la consulta — cubre: CA3
- [ ] T003 Crear `config/avisos.php` según el contrato del plan (D8, D9): destino `AVISOS_CORREO`, umbral de 5xx (1 %), umbral de fallos (10), ventana (5 min), silencio (30 min), ruta del log de acceso (`/var/log/nginx/dentissa-access.log`), patrón del canal `seguridad`, ruta del estado (`storage/app/avisos/estado.json`) y tope de bytes por ejecución; y `AVISOS_CORREO=` vacío en `.env.example` (local sigue con `MAIL_MAILER=log`) — config/avisos.php, .env.example — hecho cuando: `php artisan config:show avisos` muestra los valores por defecto y `.env.example` no tiene valores

## Tests (antes de implementar)
- [ ] T010 [P] Escribir `RegistroFallosAccesoTest` (D7, contrato de la línea): el login con contraseña equivocada contra una cuenta y contra un correo sin cuenta escribe una línea `fallido`/`contrasena` en el canal `seguridad`; la vuelta de Google fallida escribe `fallido`/`google` con `cuenta` `null`; un intento bloqueado (`Lockout`) escribe `bloqueado`; el login correcto no escribe nada; `test_el_registro_no_contiene_ip_ni_correo` (ni la IP, ni el correo, ni el id); el mismo origen y la misma cuenta dan la misma huella, de 16 hexadecimales — tests/Feature/RegistroFallosAccesoTest.php — hecho cuando: falla por la razón esperada (no hay canal ni listener), anotado — cubre: CA2
- [ ] T011 [P] Escribir en `VigilanteTest` los casos de errores (D8, con `Mail::fake()`, reloj fijo y logs en una carpeta temporal): `test_mas_del_1_por_ciento_de_5xx_avisa`, `test_el_1_por_ciento_no_avisa`, `test_ventana_de_5_minutos` (los 5xx de hace 6 minutos no cuentan), `test_ignora_las_lineas_que_no_tienen_el_formato`, `test_solo_lee_lo_nuevo` (desplazamiento guardado, con el tope de bytes) y `test_detecta_log_truncado` (tamaño menor o inodo distinto: vuelve a empezar) — tests/Feature/VigilanteTest.php — hecho cuando: falla por la razón esperada (no existe `App\Avisos\Vigilante`), anotado — cubre: CA1
- [ ] T012 Añadir a `VigilanteTest` los casos de accesos y de silencio: `test_mas_de_10_fallos_por_origen_avisa`, `test_mas_de_10_fallos_por_cuenta_avisa`, `test_10_fallos_no_avisa`, que lee el canal `seguridad` de hoy y de ayer; `test_silencio_de_30_minutos_acumula`, `test_envia_lo_pendiente_al_acabar_el_silencio` (aunque la condición haya terminado), `test_los_tipos_tienen_silencio_propio` y `test_un_envio_fallido_no_marca_el_aviso_como_enviado` (el transporte lanza una excepción: queda `Log::warning` con el tipo y sin el mensaje, y se reintenta al minuto siguiente) — tests/Feature/VigilanteTest.php — hecho cuando: falla por la razón esperada, anotado — cubre: CA2, CA4 — depende: T011
- [ ] T013 [P] Escribir `AvisoProduccionTest`: `test_el_correo_no_lleva_datos_personales_ni_secretos` (con logs de entrada que contienen una IP y un correo, y `APP_KEY` y `MAIL_PASSWORD` de prueba: el cuerpo y el asunto no contienen ninguno, ni las huellas); `test_lleva_tipo_recuentos_horas_y_como_consultar` (asunto `[Dentissa] <tipo>`, recuento, inicio y fin en `CLINICA_ZONA_HORARIA`, acumulado desde el aviso anterior y las órdenes de consulta, en español); `test_el_aviso_de_prueba_se_envia` (`dentissa:vigilar --prueba` envía un aviso de prueba a `AVISOS_CORREO` y sale con 0) — tests/Feature/AvisoProduccionTest.php — hecho cuando: falla por la razón esperada (no existen el correo ni el comando), anotado — cubre: CA5, CA6
- [ ] T014 Añadir a `ConfiguracionProduccionTest`: en `docker-compose.prod.yml`, `app` monta `nginx_logs:/var/log/nginx:ro`; `test_los_archivos_de_clave_quedan_fuera_del_paquete_y_del_repo` (`*.asc` y `*.gpg` en `.gitignore` y en `.dockerignore`) — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: los dos casos fallan por la razón esperada y el resto del archivo sigue en verde, anotado — cubre: CA1, CA8
- [ ] T015 Añadir a `ConfiguracionProduccionTest` los casos de `respaldar.sh` (D2), anclados a las órdenes reales: `test_respaldar_cifra_con_la_clave_publica` (`gpg` con `--recipient-file` y `--trust-model always`, sin `--symmetric` ni `--passphrase`); `test_respaldar_no_escribe_en_claro` (la tubería `mariadb-dump | tee >(tail -n 1 …) | gzip | gpg` hacia `.part` y `mv` a `.sql.gz.gpg`, sin `> …sql` ni `> …sql.gz`, con `pipefail` y la comprobación de `-- Dump completed`); `test_respaldar_se_detiene_sin_gpg_o_sin_clave` (`gpg --version` y `gpg --show-keys` antes de `mariadb-dump`); conserva 14 copias `.sql.gz.gpg` — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: los casos fallan por la razón esperada, anotado — cubre: CA7, CA8 — depende: T014
- [ ] T016 Añadir a `ConfiguracionProduccionTest` los casos de `restaurar.sh` (D3): `test_restaurar_comprueba_antes_de_borrar` (solo acepta `.sql.gz.gpg` y `--clave`; la pasada de comprobación con `gpg --decrypt`, `gzip -cd` y `-- Dump completed` va antes de la confirmación y de `DROP DATABASE`; `--solo-comprobar` sale tras ella); `test_restaurar_limpia_la_clave` (llavero con `mktemp -d -p /dev/shm`, `--pinentry-mode loopback`, `trap … EXIT` con `gpgconf --kill gpg-agent`, borrado del llavero y de la clave si está en `/dev/shm`) — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: los casos fallan por la razón esperada, anotado — cubre: CA9 — depende: T015
- [ ] T017 Añadir a `ConfiguracionProduccionTest` `test_volver_usa_el_restaurar_de_la_version_actual` (D4): `volver.sh` copia `deploy/restaurar.sh` a un temporal antes del `git checkout`, comprueba la copia con `--solo-comprobar` antes de parar nada, usa el temporal después del checkout y avisa de parar `dentissa-vigilar.timer` si la versión de destino no tiene `app/Console/Commands/Vigilar.php` — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: falla por la razón esperada, anotado — cubre: CA9 — depende: T016
- [ ] T018 Añadir a `ConfiguracionProduccionTest`: `test_cifrar_copias_antiguas_cifra_antes_de_borrar` (D5: `gzip -t` y `-- Dump completed` antes de cifrar, `gpg --recipient-file` hacia `.part`, `mv`, `touch -r` y `shred -u` después, y una copia dañada no se borra); y el temporizador de `preparar-vigilancia.sh` (D11: exige root, servicio `Type=oneshot` con `docker compose -f /opt/dentissa/app/docker-compose.prod.yml exec -T -u www-data app php artisan dentissa:vigilar` y la cuenta de despliegue, timer `OnCalendar=minutely`, `systemctl enable --now dentissa-vigilar.timer`) — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: los casos fallan por la razón esperada, anotado — cubre: CA1, CA10 — depende: T017
- [ ] T019 Añadir a `ConfiguracionProduccionTest` los casos de `verificar.sh` (D12): el bloque de configuración tiene 16 comprobaciones; `test_verificar_comprueba_el_envio_de_avisos` (`MAIL_MAILER=smtp`, `MAIL_HOST` y `MAIL_PORT`, `MAIL_USERNAME` y `MAIL_PASSWORD` sin `<…>`, `AVISOS_CORREO` con formato de correo); `test_verificar_no_imprime_valores_del_correo`; sección «Avisos y copias (spec 032)» con el temporizador activo, la clave pública sin `sec`, el llavero sin claves secretas y la lectura del log de acceso desde `app`; `test_verificar_falla_con_copias_en_claro` (`dentissa-*.sql.gz` en `/var/backups/dentissa`) — tests/Feature/ConfiguracionProduccionTest.php — hecho cuando: los casos fallan por la razón esperada, anotado — cubre: CA6, CA8, CA10 — depende: T018

## Implementación
### Registro de fallos de acceso (CA2)
- [ ] T020 Añadir el canal `seguridad` (`daily`, 14 días, `JsonFormatter`) y crear `App\Listeners\RegistrarFalloAcceso` para `Failed` y `Lockout` según el contrato de la línea: `origen` = HMAC-SHA256 con `APP_KEY` de `AuthController::origenLimite()`, `cuenta` = HMAC de `cuenta:<id>` o de `correo:<minúsculas>` como `claveLimiteLogin`, `null` en la vuelta de Google; recortadas a 16 hexadecimales (control de TM5 y TM8) — config/logging.php, app/Listeners/RegistrarFalloAcceso.php — hecho cuando: T010 pasa dos veces, `LimiteIntentosTest` sigue igual y en verde, y se comprueba rompiendo (escribir la IP en `origen` hace fallar `test_el_registro_no_contiene_ip_ni_correo`), anotado — cubre: CA2 — depende: T003, T010

### Correo y vigilante (CA1, CA2, CA4, CA5, CA6)
- [ ] T021 Crear `App\Mail\AvisoProduccion` y su plantilla de texto en español según el contrato del correo (D9): solo recibe el tipo, los recuentos, el intervalo y el acumulado, nunca líneas de log; horas en `CLINICA_ZONA_HORARIA`; «Cómo consultar el detalle en el servidor» con las órdenes exactas (`journalctl -u dentissa-vigilar`, el canal `seguridad`, el log de acceso de `web`), sin valores (control de TM7) — app/Mail/AvisoProduccion.php, resources/views/correos/aviso.blade.php — hecho cuando: los casos de contenido de T013 pasan y se comprueba rompiendo (pasar una línea de log al cuerpo hace fallar `test_el_correo_no_lleva_datos_personales_ni_secretos`), anotado — cubre: CA5 — depende: T013
- [ ] T023 Crear `App\Avisos\Estado` (desplazamiento e inodo del log de acceso, contadores por minuto de los últimos 5 con total y 5xx y, por tipo de aviso, último envío y recuento pendiente; escritura atómica con temporal + `rename`) y `App\Avisos\Vigilante` con el aviso de errores (D8): lectura incremental del log de acceso desde el desplazamiento guardado, con el tope de bytes y volviendo a empezar si el inodo cambia o el tamaño baja; expresión anclada que toma solo la hora y el código; ventana de 5 minutos y umbral > 1 %; reloj y rutas inyectados (control de TM12 y P4) — app/Avisos/Estado.php, app/Avisos/Vigilante.php — hecho cuando: T011 pasa dos veces y se comprueba rompiendo (quitar el tope o la detección de truncado hace fallar su caso), anotado — cubre: CA1 — depende: T003, T011, T021
- [ ] T024 Añadir a `Vigilante` el aviso de accesos y el silencio (D8): lee las líneas del canal `seguridad` de hoy y de ayer dentro de la ventana y las agrupa por origen y por cuenta, umbral > 10; silencio de 30 minutos por tipo (`errores`, `accesos`) con recuento pendiente que se envía al acabar el silencio; un envío fallido no actualiza el último envío y deja `Log::warning('aviso no enviado', ['tipo' => …])` (control de TM5, TM6 y TM10) — app/Avisos/Vigilante.php, app/Avisos/Estado.php — hecho cuando: T011 y T012 pasan dos veces y se comprueba rompiendo (quitar el silencio hace fallar `test_silencio_de_30_minutos_acumula`), anotado — cubre: CA2, CA4 — depende: T012, T020, T023
- [ ] T025 Crear el comando `dentissa:vigilar` y su opción `--prueba` (D8, CA6): sin opción ejecuta el vigilante y sale con código distinto de 0 si un envío falla; con `--prueba` envía un aviso de prueba a `AVISOS_CORREO` (control de TM10) — app/Console/Commands/Vigilar.php — hecho cuando: T013 pasa completo dos veces, `php artisan list` muestra `dentissa:vigilar` y en local `php artisan dentissa:vigilar --prueba` deja el correo en `laravel.log` (`MAIL_MAILER=log`), anotado — cubre: CA1, CA6 — depende: T013, T024

### Compose, temporizador y archivos ignorados (CA1, CA8)
- [ ] T026 Montar `nginx_logs:/var/log/nginx:ro` en `app` (D10; ruta protegida aprobada por el usuario el 2026-10-23) e ignorar `*.asc` y `*.gpg` en el repo y en el paquete (control de TM1 y TM9) — docker-compose.prod.yml, .gitignore, .dockerignore — hecho cuando: los casos de T014 pasan y `docker compose -f docker-compose.prod.yml config` es válido, anotado — cubre: CA1, CA8 — depende: T014
- [ ] T027 Crear `deploy/preparar-vigilancia.sh` (D11): exige root, instala `dentissa-vigilar.service` y `.timer`, `systemctl daemon-reload` y `enable --now`; comprueba antes que gpg y la clave pública están en su sitio; idempotente; al final muestra `systemctl list-timers dentissa-vigilar.timer` (control de TM10) — deploy/preparar-vigilancia.sh — hecho cuando: el caso del temporizador de T018 pasa y `bash -n` limpio — cubre: CA1, CA2 — depende: T018, T025

### Copias cifradas (CA7–CA10)
- [ ] T028 Cifrar en `respaldar.sh` según D2: comprobación previa de `gpg --version` y `gpg --show-keys "$DENTISSA_CLAVE_PUBLICA"` (por defecto `/opt/dentissa/entorno/copias.pub.asc`) antes de volcar; tubería con la marca de la línea final en `/dev/shm`, `.part`, `mv` y `chmod 600`, borrado de `.part` si algo falla; 14 copias `.sql.gz.gpg`; la última línea sigue siendo la ruta de la copia (control de TM1 y TM2) — deploy/respaldar.sh — hecho cuando: los casos de T015 pasan, `bash -n` limpio y se comprueba rompiendo (quitar `pipefail` o la comprobación previa hace fallar su caso), anotado — cubre: CA7, CA8 — depende: T015
- [ ] T029 Rehacer `restaurar.sh` según D3: `restaurar.sh <copia.sql.gz.gpg> --clave <privada.asc> [--solo-comprobar]`; llavero temporal en `/dev/shm`, `--pinentry-mode loopback`; pasada de comprobación entera antes de la confirmación y de `DROP DATABASE`, que dice por qué se detiene (clave ajena, copia alterada o incompleta); pasada de carga; `trap EXIT` de limpieza (control de TM3 y TM4) — deploy/restaurar.sh — hecho cuando: los casos de T016 pasan, `bash -n` limpio y se comprueba rompiendo (mover la comprobación después del `DROP` hace fallar `test_restaurar_comprueba_antes_de_borrar`), anotado — cubre: CA8, CA9 — depende: T016
- [ ] T030 Adaptar `volver.sh` según D4: copia `restaurar.sh` a un temporal antes del checkout, comprueba la copia con `--solo-comprobar` antes de parar nada, pide la clave como `restaurar.sh`, usa el temporal tras el checkout y avisa de parar el temporizador si el destino no tiene `Vigilar.php` (control de TM11) — deploy/volver.sh — hecho cuando: T017 pasa, `bash -n` limpio y se comprueba rompiendo (usar `deploy/restaurar.sh` tras el checkout hace fallar el caso), anotado — cubre: CA9 — depende: T017, T029
- [ ] T031 Crear `deploy/cifrar-copias-antiguas.sh` según D5: por cada `dentissa-*.sql.gz` de `/var/backups/dentissa`, comprobación (`gzip -t` y `-- Dump completed`), cifrado hacia `.part`, `mv` a `.sql.gz.gpg`, `touch -r`, `chmod 600` y `shred -u` de la original; una copia dañada se informa y se deja; al final, el recuento de cifradas y de dejadas — deploy/cifrar-copias-antiguas.sh — hecho cuando: el caso de T018 pasa y `bash -n` limpio — cubre: CA10 — depende: T018
- [ ] T032 Ampliar `verificar.sh` según D12: el bloque de configuración pasa de 12 a 16 comprobaciones de correo y se añade la sección «Avisos y copias (spec 032)»; ninguna comprobación imprime valores (control de TM1, TM9 y TM10) — deploy/verificar.sh — hecho cuando: los casos de T019 pasan, `bash -n` limpio y se comprueba rompiendo (imprimir `MAIL_USERNAME` hace fallar `test_verificar_no_imprime_valores_del_correo`), anotado — cubre: CA6, CA8, CA10 — depende: T019

### Guía y riesgo aceptado (CA3, CA6, CA7, CA9, CA10)
- [ ] T033 Actualizar la guía de las copias: §7 con la clave del responsable (D14: generarla en el PC con frase y sin caducidad, exportar la pública al servidor con 600, segunda copia de la secreta fuera del PC y qué pasa si se pierden las dos), copias `.sql.gz.gpg`, restaurar con la clave en `/dev/shm`, y sustituir y borrar las copias en claro del PC; §8 volver con copia cifrada, también a una versión anterior a la 032 con el `restaurar.sh` del tag (`git show <tag>:deploy/restaurar.sh`); y el orden del primer despliegue de la 032 (D6) con `cifrar-copias-antiguas.sh` — deploy/README.md — hecho cuando: cada punto de CA7, CA9 y CA10 sobre la guía tiene su apartado y los comandos coinciden con los scripts de T028–T031 · verificación manual: el usuario revisa §7 y §8 — cubre: CA7, CA9, CA10 — depende: T028, T029, T030, T031
- [ ] T034 Actualizar la guía de los avisos: §4 `app.env` con las variables de correo y la contraseña de aplicación de Gmail (verificación en dos pasos, marcadores `<…>` que `verificar.sh` rechaza); sección nueva de avisos con `preparar-vigilancia.sh`, el aviso de prueba (`dentissa:vigilar --prueba`), cómo consultar el detalle, cómo pararlo, y el monitor de UptimeRobot sobre `https://<dominio>/up` cada 5 minutos con el resultado de T002 — deploy/README.md — hecho cuando: cada punto de CA3 y CA6 sobre la guía tiene su apartado y ningún paso tiene coste · verificación manual: el usuario revisa §4 y la sección de avisos — cubre: CA3, CA6 — depende: T002, T025, T027, T032, T033
- [ ] T035 Dividir RS15 en `docs/security.md` según el plan (Correcciones de riesgos): RS15.b «en curso (spec 032)»; RS15.c (disco de producción) con la excepción **EX3** (motivo, aprobador el responsable, vencimiento al cargar el primer dato real de pacientes, aprobación pendiente en T096); RS15.d (staging) → objetivo 5; RS15.a sigue en la 013; secretos nuevos (contraseña de aplicación, clave de las copias) y fila de copias en «Manejo de secretos» — docs/security.md — hecho cuando: el validador no avisa de RS15 y cada corrección tiene su estado — cubre: CA10

## Preparativos y ensayos en el servidor
- [ ] T040 Preparativos del responsable (plan, Rollout; los hace el usuario): par de claves en el PC (D14) con frase; segunda copia de la secreta fuera del PC; `copias.pub.asc` en `/opt/dentissa/entorno` (600); contraseña de aplicación de Gmail y variables de correo en `app.env`; cuenta de UptimeRobot — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota recoge que cada paso está hecho, la huella de la clave pública y la salida de `gpg --show-keys` en el servidor sin `sec`, sin ningún secreto — cubre: CA8 — depende: T034
- [ ] T041 Ensayo de las copias (aprobación explícita antes; lo ejecuta el usuario): `git fetch && git checkout <commit>` y `desplegar.sh <commit>` (orden de D6) dejan `.sql.gz.gpg`; `find /var/backups /tmp /dev/shm` sin volcados en claro durante y después; el servidor no la descifra (`gpg --decrypt` falla); con la clave pública renombrada, `desplegar.sh` se detiene en «Copia de la base» y el `HEAD` no cambia; `cifrar-copias-antiguas.sh` y `verificar.sh` sin copias en claro — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota recoge la aprobación y cada resultado — cubre: CA7, CA8, CA10 — depende: T040
- [ ] T042 Ensayo de los avisos (aprobación explícita antes; lo ejecuta el usuario): `preparar-vigilancia.sh` y `systemctl list-timers` con la siguiente ejecución en menos de un minuto; el aviso de prueba llega en ≤ 5 minutos; líneas 5xx inyectadas en el log de acceso de `web` → correo de errores en ≤ 10 minutos; 11 intentos fallidos contra una cuenta de prueba → correo de accesos en ≤ 10 minutos y el límite responde igual; ningún correo lleva datos (revisión del usuario); `verificar.sh` rompiendo (sin `MAIL_PASSWORD`, con el `<…>` de la guía, con una copia en claro, con el temporizador parado) da FALLO sin valores, y todo OK al restaurar — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota recoge la aprobación, los tiempos de llegada y cada resultado — cubre: CA1, CA2, CA5, CA6, CA10 — depende: T041
- [ ] T043 Ensayo de la caída vista desde fuera (aprobación explícita antes; lo ejecuta el usuario): monitor de UptimeRobot sobre `https://dentissa.app/up` cada 5 minutos en verde; `cloudflared` parado ≥ 6 minutos → correo de caída en ≤ 10 minutos; al arrancarlo, correo de vuelta — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota recoge la aprobación y las horas de parada, de los dos correos y de la vuelta — cubre: CA3 — depende: T042
- [ ] T044 Ensayo de la restauración y la vuelta (aprobación explícita antes; lo ejecuta el usuario, con la segunda copia de la clave): `CHECKSUM TABLE` y recuento de filas de cada tabla antes; `restaurar.sh` con la clave en `/dev/shm` → mismos valores después; si la frase se pide una o dos veces (D3); `/dev/shm` y `gpg --list-secret-keys` vacíos al terminar; con una clave ajena, una copia con un byte cambiado y una copia cortada, se detiene antes de confirmar y la comprobación de las tablas no cambia; `volver.sh v0.10.0 <copia cifrada>` deja la base igual y avisa de parar el temporizador, que se para — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: la nota recoge la aprobación, los valores antes y después y cada rechazo — cubre: CA9 — depende: T043

## Integración y documentación
- [ ] T050 Ejecutar la puerta de calidad sobre el diff de la spec: suite completa, `pint --test` en los PHP tocados, `bash -n` en los scripts tocados, gitleaks, semgrep `p/php`, `composer audit --locked`, `npm audit` y revisión de cada `Log::` nuevo — docs/specs/032-alertas-cifrado-copias/tasks.md — hecho cuando: todo pasa sin hallazgos críticos ni altos, anotado con los recuentos — cubre: CA5, CA8 — depende: T020, T025, T026, T027, T028, T029, T030, T031, T032
- [ ] T051 Actualizar el roadmap: en el objetivo 5, OB6.c y RS15.d nuevas (staging) y RS15.c como excepción EX3; la 032 en curso; en Pendientes, «cada despliegue usa el `desplegar.sh` de la versión anterior» (D6) y el umbral de 5xx sin mínimo de peticiones (Riesgos del plan) — docs/roadmap.md — hecho cuando: las entradas existen, citan la 032 y el validador no da errores
- [ ] T090 Actualizar `docs/architecture.md`: Producción (temporizador → `dentissa:vigilar`, `nginx_logs` también en `app`, salida SMTP a Gmail, UptimeRobot), Módulos (`App\Avisos`, `App\Listeners\RegistrarFalloAcceso`) y Copias (`.sql.gz.gpg` y restauración con la clave del responsable, ADR 0007)
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md`: copias cifradas, avisos y la nueva verificación; alertas de 5xx, fallos de acceso y caída en uso, canal `seguridad`, OB6.b «en curso (spec 032)» y OB6.c (staging) nueva → objetivo 5; OB6.a sigue en el objetivo 3
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación — no aplica: no hay staging (objetivo 5)
- [ ] T096 Aprobación humana para producción y aprobación de la excepción EX3 por el responsable
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout), en el orden de D6: `git fetch --tags && git checkout vX.Y.Z`, `desplegar.sh vX.Y.Z`, `preparar-vigilancia.sh`, `cifrar-copias-antiguas.sh`, `verificar.sh` con la dirección pública, aviso de prueba, monitor de UptimeRobot en verde y, en el PC, sustituir y borrar las copias en claro; durante la primera hora, ningún correo inesperado, `journalctl -u dentissa-vigilar` sin errores y 0 5xx nuevos
  - verificación manual (CA7, CA10): `find /var/backups /tmp /dev/shm` sin volcados en claro tras el despliegue.
  - verificación manual (D6): el comando de vuelta que da `/release` usa la versión anterior real (`v0.10.0`), no el tag desplegado.
- [ ] T098 Marcar spec como `released`. Correcciones → mitigada (vX.Y.Z): OB6.b y RS15.b; RS15.c → aceptada (EX3). OB6 sigue abierto (OB6.a → objetivo 3, OB6.c → objetivo 5) y RS15 también (RS15.a → 013, RS15.d → objetivo 5). Spec extendida 030: CA6 con `→ reemplazado por 032 CA9` y fila de Historial

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T011, T014, T018, T042 | T023, T025, T026, T027 |
| CA2 | T010, T012, T042 | T020, T024, T027 |
| CA3 | T002, T043 | T034 |
| CA4 | T012 | T024 |
| CA5 | T013, T042, T050 | T021 |
| CA6 | T013, T019, T042 | T025, T032, T034 |
| CA7 | T015, T041 | T028, T033 |
| CA8 | T014, T015, T019, T040, T041, T050 | T026, T028, T029, T032 |
| CA9 | T016, T017, T044 | T029, T030, T033 |
| CA10 | T018, T019, T041, T042 | T031, T032, T033, T035 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T026, T028, T032 | T015, T019, T041 |
| TM2 | T028 | T015, T041 |
| TM3 | T029 | T016, T044 |
| TM4 | T029 | T016, T044 |
| TM5 | T020, T024 | T010, T012, T042 |
| TM6 | T024 | T012 |
| TM7 | T021 | T013 |
| TM8 | T020 | T010 |
| TM9 | T026, T032 | T014, T019, T050 |
| TM10 | T024, T025, T027, T032 | T012, T019, T042 |
| TM11 | T030 | T017, T044 |
| TM12 | T023 | T011 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| deploy/respaldar.sh | T028 |
| deploy/restaurar.sh | T029 |
| deploy/volver.sh | T030 |
| deploy/cifrar-copias-antiguas.sh | T031 |
| deploy/preparar-vigilancia.sh | T027 |
| deploy/verificar.sh | T032 |
| deploy/README.md | T033, T034 |
| docker-compose.prod.yml | T026 |
| app/Listeners/RegistrarFalloAcceso.php | T020 |
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
| La detección no ralentiza la aplicación | T010 (éxito sin línea), T027 (vigilante en el temporizador) |
| Ningún secreto en el repo, el paquete ni la salida de la verificación | T014, T019, T050 |
