---
spec: 032-alertas-cifrado-copias
status: approved
constitution_version: 1.3.0
created: 2026-10-23
---

# Plan · 032 Alertas y copias cifradas en producción

## Enfoque técnico
- **Copias cifradas con GnuPG y la clave pública del responsable** (ADR 0007). `respaldar.sh`
  cifra el volcado en la misma tubería en la que se genera (`mariadb-dump | gzip | gpg`), así que
  la copia nunca existe en claro en el disco. El servidor solo tiene la clave pública: puede cifrar,
  pero no descifrar.
- **Restaurar con la clave del responsable, que solo pasa por el servidor durante la
  restauración.** Se importa en un llavero temporal en memoria (`/dev/shm`) que se borra al
  terminar. Antes de tocar la base, `restaurar.sh` descifra la copia entera hacia ningún sitio:
  así detecta una clave que no es la suya, una copia alterada o una copia cortada.
- **Vigilante dentro de la aplicación:** `php artisan dentissa:vigilar`, que un temporizador de
  systemd lanza cada minuto. Cuenta los 5xx en el log de acceso de nginx, que se monta en solo
  lectura en `app`, y los fallos de inicio de sesión de un canal de log nuevo, `seguridad`.
  Escribe en ese canal un listener de `Failed` y `Lockout`, sin IP ni correo: solo huellas HMAC.
  Envía los correos con el Mail de Laravel por el SMTP de Gmail, con un silencio de 30 minutos por
  tipo de aviso y el recuento acumulado.
- **Caída vista desde fuera con UptimeRobot** (plan gratuito), que comprueba `/up` por la dirección
  pública y avisa por correo al caer y al volver. Funciona también con el servidor apagado.
- `verificar.sh` comprueba que el envío de avisos, el temporizador y la clave pública están en su
  sitio y que no queda ninguna copia en claro. Un script de la liberación cifra las copias
  antiguas.

Prueba de concepto de GnuPG (2026-10-23, gpg 2.4.9 de Git para Windows; el servidor tiene la 2.4.4
de Ubuntu 24.04, instalada por `preparar-servidor.sh`):
- cifrar con `--recipient-file` funciona sin llavero ni clave secreta;
- el llavero del servidor no puede descifrar;
- con la clave buena se recupera el volcado entero, con su línea `-- Dump completed`;
- con una clave equivocada, gpg sale con código 2 («No secret key»);
- con un byte cambiado, también 2 («message has been manipulated», MDC);
- con la copia cortada, también 2 («Invalid packet»).

Queda para el servidor (en la tarea del ensayo): que la frase de la clave se pida una sola vez para
las dos pasadas de `restaurar.sh`, con la caché del agente dentro del llavero temporal.

Línea base de dependencias (2026-10-23, rama con `main` `b60b4d3` fusionado): `composer audit
--locked` sin avisos y `npm audit` con 0 vulnerabilidades. EX2 sigue vigente hasta el 2026-11-30
(imágenes; el refresco de 031/R4 va aparte).

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 032 aprobada el 2026-10-07; `tasks.md` se aprueba antes de `/implement`. | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Comportamiento nuevo de la aplicación, con tests que fallan hoy: el registro de fallos de acceso (CA2), el vigilante (CA1, CA2, CA4), el contenido del correo (CA5) y el aviso de prueba (CA6). Los scripts de `deploy/` se prueban como en la 030 y la 031, con tests que leen su texto (`ConfiguracionProduccionTest`). Su efecto real (cifrado, restauración, caída vista desde fuera) no es comportamiento de la aplicación: se verifica con las comprobaciones que declara Trazabilidad (constitución, P2) en el ensayo en el servidor, y las de `verificar.sh` se comprueban rompiendo. | test: `RegistroFallosAccesoTest`, `VigilanteTest`, `AvisoProduccionTest`, `ConfiguracionProduccionTest` · manual: comprobaciones de Trazabilidad |
| P3 Autorización en servidor | ➖ | Sin rutas, endpoints ni permisos nuevos. El vigilante es un comando de consola. | — |
| P4 Entrada validada y SQL con bindings | ✅ | La única entrada externa nueva son las líneas del log de acceso de nginx: se leen con una expresión anclada y se descarta lo que no encaja. Del registro solo se toman la hora y el código, nunca la petición. Sin SQL nuevo. | test: `VigilanteTest::test_ignora_las_lineas_que_no_tienen_el_formato` · lint: semgrep `p/php` |
| P5 Nada sensible en el repo | ✅ | La contraseña de aplicación de Gmail y el correo de destino van en `app.env`, fuera del clon. La clave pública está en `/opt/dentissa/entorno` y la secreta nunca llega al repositorio ni al paquete. `.env.example` solo lleva nombres vacíos. | lint: gitleaks · test: `ConfiguracionProduccionTest` (los `*.asc` y `*.env` quedan fuera del paquete y del repo) |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas de Composer ni de npm: el Mail de Laravel ya trae Symfony Mailer. GnuPG viene del sistema (Ubuntu y Git para Windows). Línea base limpia; EX2, vigente. | lint: `composer audit --locked`, `npm audit --audit-level=high`, `trivy image` en `/release` |
| P7 Auditoría de datos sensibles | ➖ | No añade ni cambia acciones auditables (sección Auditoría de la spec). El canal `seguridad` sirve para alertar, no para auditar: no cumple ni sustituye el almacén solo anexado de la 021. Los inicios de sesión fallidos siguen bajo la excepción P7 de la 030, que vence con la 021. | — |
| P8 Correlación y logs limpios | ✅ | Logs nuevos sin datos personales. El canal `seguridad` guarda tipo, método y huellas HMAC-SHA256 (clave `APP_KEY`, recortadas) del origen y de la cuenta, nunca la IP ni el correo. El vigilante solo registra el tipo de un aviso que no se pudo enviar. La correlación por petición todavía no existe (020); el nivel `info` y `APP_DEBUG=false` no cambian. | test: `RegistroFallosAccesoTest::test_el_registro_no_contiene_ip_ni_correo` · manual: revisión de cada `Log::` del diff |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Sin pantallas. El correo es texto plano y no es interfaz de la aplicación. | — |
| P10 Rutas indexables descubribles | ➖ | Sin rutas nuevas ni cambiadas. | — |

## Cambios por módulo
| Módulo / ruta | Cambio | Riesgo |
|---|---|---|
| `deploy/respaldar.sh` | Cifra en tubería con `gpg --recipient-file` hacia `.part` y luego `mv` a `.sql.gz.gpg`. Comprueba la línea final sin escribir en claro. Antes de volcar se detiene si falta gpg o la clave pública. Conserva 14 copias `.gpg` (D2) | medio: una copia rota sin aviso |
| `deploy/restaurar.sh` | Solo acepta `.sql.gz.gpg` y `--clave <archivo>`, con llavero temporal en `/dev/shm` y limpieza con `trap`. Primera pasada de comprobación hacia `/dev/null` antes de confirmar y de borrar; modo `--solo-comprobar` (D3) | alto: es la vía de recuperación |
| `deploy/volver.sh` | Copia `restaurar.sh` a un temporal y comprueba la copia antes del checkout. Avisa de parar el temporizador si la versión de destino no tiene el vigilante (D4) | medio |
| `deploy/cifrar-copias-antiguas.sh` (nuevo) | Cifra las `dentissa-*.sql.gz` que haya, comprobadas antes, y borra las originales (D5) | medio: borra archivos |
| `deploy/preparar-vigilancia.sh` (nuevo) | Instala y activa `dentissa-vigilar.service` y `.timer` (cada minuto) y comprueba gpg y la clave pública (D11) | bajo |
| `deploy/verificar.sh` | 4 comprobaciones de correo (de 12 a 16) y una sección nueva «Avisos y copias» (D12) | medio: falsos FALLO |
| `deploy/README.md` | §4 `app.env` con el correo; §7 copias cifradas, clave y su segunda copia, sustituir las del PC; §8 volver con copia cifrada; § nueva de avisos (Gmail, UptimeRobot, aviso de prueba) | bajo |
| `docker-compose.prod.yml` (ruta protegida, aprobada por el usuario el 2026-10-23) | `app`: `nginx_logs:/var/log/nginx:ro` (D10) | bajo |
| `app/Listeners/RegistrarFalloAcceso.php` (nuevo) | `Failed` y `Lockout` → una línea JSON en el canal `seguridad` (D7) | bajo |
| `app/Avisos/Vigilante.php`, `app/Avisos/Estado.php` (nuevos) | Lectura incremental de los logs, ventanas de 5 min, umbrales y silencio de 30 min; reloj y rutas inyectables (D8) | medio: falsos avisos o avisos perdidos |
| `app/Console/Commands/Vigilar.php` (nuevo) | `dentissa:vigilar` y `dentissa:vigilar --prueba` (D8, CA6) | bajo |
| `app/Mail/AvisoProduccion.php` y `resources/views/correos/aviso.blade.php` (texto) (nuevos) | Correo en español, sin datos personales (D9) | bajo |
| `config/avisos.php` (nuevo), `config/logging.php` | Destino, umbrales, ventana, silencio y rutas; canal `seguridad` (`daily`, 14 días, JSON) | bajo |
| `.env.example` | `AVISOS_CORREO=` (local sigue con `MAIL_MAILER=log`) | bajo |
| `.gitignore`, `.dockerignore` | `*.asc` y `*.gpg` ignorados, para que ni una clave ni una copia entren en el repo ni en el paquete (CA8) | bajo |
| `tests/Feature/RegistroFallosAccesoTest.php`, `tests/Feature/VigilanteTest.php`, `tests/Feature/AvisoProduccionTest.php` (nuevos), `tests/Feature/ConfiguracionProduccionTest.php` | ver Estrategia de pruebas | — |

## Contratos y datos
- **Sin migraciones ni rutas.** Ningún cambio en la base ni en las respuestas HTTP.
- **Copia:** `/var/backups/dentissa/dentissa-AAAAMMDD-HHMMSS.sql.gz.gpg` (600): mensaje OpenPGP
  binario cifrado para la clave pública de `/opt/dentissa/entorno/copias.pub.asc` (variable
  `DENTISSA_CLAVE_PUBLICA`) y, dentro, el `gzip` de `mariadb-dump`. `respaldar.sh` sigue imprimiendo
  la ruta en la última línea, que es lo que usa `desplegar.sh`.
- **`restaurar.sh <copia.sql.gz.gpg> --clave <privada.asc> [--solo-comprobar]`.** La clave privada
  es la exportada con `gpg --armor --export-secret-keys`, protegida con su frase. El script la borra
  al terminar si está en `/dev/shm`.
- **Línea del canal `seguridad`** (`storage/logs/seguridad-AAAA-MM-DD.log`):
  `{"momento":"2026-10-23T18:04:05Z","tipo":"fallido|bloqueado","metodo":"contrasena|google","origen":"<16 hex>","cuenta":"<16 hex>|null"}`.
  - `origen` = HMAC-SHA256(`APP_KEY`, `AuthController::origenLimite()`), es decir, la IPv4 o el
    prefijo /64 de IPv6, como el limitador.
  - `cuenta` = HMAC de `cuenta:<id>`, si el correo es de una cuenta, o de `correo:<minúsculas>`,
    con la misma regla que `claveLimiteLogin`; `null` en la vuelta de Google.
- **Estado del vigilante** (`storage/app/avisos/estado.json`, en el volumen `storage`):
  - desplazamiento e inodo del log de acceso;
  - contadores por minuto de los últimos 5 (total y 5xx);
  - por tipo de aviso: último envío y recuento pendiente.
  Se escribe de forma atómica (temporal + `rename`).
- **Variables nuevas de `app.env`:**
  - `MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`;
  - `MAIL_USERNAME`, `MAIL_PASSWORD` (contraseña de aplicación), `MAIL_FROM_ADDRESS`,
    `MAIL_FROM_NAME`;
  - `AVISOS_CORREO`.
- **Correo** (asunto `[Dentissa] <tipo>`):
  - tipo de aviso;
  - recuento en el intervalo, con el inicio y el fin en la hora de la clínica
    (`CLINICA_ZONA_HORARIA`);
  - recuento acumulado desde el aviso anterior, si lo hay;
  - «Cómo consultar el detalle en el servidor», con las órdenes exactas, sin valores.

## Estrategia de pruebas
- **`RegistroFallosAccesoTest`** (Feature, PHPUnit). Escribe una línea:
  - el login con contraseña equivocada, con cuenta y sin ella;
  - la vuelta de Google fallida;
  - un intento bloqueado por el límite (`Lockout`).
  El login correcto no escribe nada. La línea no contiene la IP, el correo ni el id; con el mismo
  origen o la misma cuenta da la misma huella. `LimiteIntentosTest` sigue igual (CA2: el límite no
  cambia).
- **`VigilanteTest`** (Feature, con `Mail::fake()`, reloj fijo y logs de prueba en una carpeta
  temporal):
  - 5xx por encima y en el 1 %;
  - 11 y 10 fallos por origen y por cuenta;
  - silencio de 30 min con el recuento acumulado, que se envía aunque la condición haya terminado
    (CA4);
  - lectura incremental, log truncado o rotado;
  - líneas con otro formato;
  - ventana de 5 min.
- **`AvisoProduccionTest`** (Feature):
  - el cuerpo no contiene IPs, correos, huellas, `APP_KEY` ni `MAIL_PASSWORD`, ni siquiera cuando
    los logs de entrada tienen una IP y un correo (CA5);
  - lleva el tipo, los recuentos, el intervalo y cómo consultar;
  - `dentissa:vigilar --prueba` envía un aviso de prueba (CA6).
- **`ConfiguracionProduccionTest`** (lee el texto de los scripts y del Compose, como en la 030 y
  la 031):
  - `app` monta `nginx_logs` en solo lectura;
  - `respaldar.sh`: `--recipient-file`, sin `--symmetric` ni archivos `.sql`/`.sql.gz` y con la
    comprobación previa de gpg y de la clave;
  - `restaurar.sh`: la comprobación va antes de `DROP DATABASE`, con `trap` de limpieza;
  - `volver.sh`: copia `restaurar.sh` antes del `checkout`;
  - `verificar.sh`: 16 comprobaciones de configuración y la sección «Avisos y copias»;
  - el temporizador de `preparar-vigilancia.sh`;
  - `cifrar-copias-antiguas.sh` cifra antes de borrar.
- **Ensayo en el servidor** (con aprobación previa del usuario, como T040–T046 de la 030):
  - copia cifrada y `find` sin claro;
  - restauración con `CHECKSUM TABLE` antes y después;
  - clave equivocada, copia alterada y copia cortada, sin tocar la base;
  - `volver.sh` a la versión anterior con la copia;
  - aviso de prueba;
  - avisos de 5xx (líneas inyectadas en el log de `web`) y de fallos (11 intentos reales contra
    una cuenta de prueba);
  - UptimeRobot con `cloudflared` parado ≥ 6 min;
  - `verificar.sh` rompiendo (sin `MAIL_PASSWORD`, con una copia en claro, con el temporizador
    parado).
- Puerta de calidad: `php artisan test`, pint, semgrep, gitleaks, `composer audit`, `npm audit`.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Information disclosure: alguien roba una copia del servidor, del PC o de un correo | A04:2025 Cryptographic Failures | `respaldar.sh`, `deploy/README.md` | Cifrado OpenPGP para la clave pública. La secreta, con frase, solo está en el PC y en una segunda copia segura; nunca en el servidor, el repo ni el paquete (CA8) | `ConfiguracionProduccionTest::test_respaldar_cifra_con_la_clave_publica` · `verificar.sh` «el llavero del servidor no tiene clave secreta» · ensayo: el servidor no descifra |
| TM2 | Information disclosure: el volcado queda en claro en el disco mientras se cifra | A04:2025 | `respaldar.sh` | Tubería sin archivos intermedios; la comprobación de la línea final, en memoria (CA7) | `ConfiguracionProduccionTest::test_respaldar_no_escribe_en_claro` · ensayo: `find /var/backups /tmp /dev/shm` durante y después |
| TM3 | Tampering: alguien sustituye, altera o corta una copia para que se restaure | A08:2025 Software or Data Integrity Failures | `restaurar.sh` | MDC de OpenPGP. Pasada de comprobación completa (gpg con código 0, `gzip` válido y `-- Dump completed`) antes de confirmar y de `DROP` (CA9) | `ConfiguracionProduccionTest::test_restaurar_comprueba_antes_de_borrar` · ensayo: clave equivocada, copia alterada y copia cortada, base intacta |
| TM4 | Elevation / disclosure: la clave secreta se queda en el servidor tras restaurar | A04:2025 | `restaurar.sh` | Llavero en `/dev/shm` (`mktemp`) y `trap EXIT` que para el agente, borra el llavero y borra la clave de `/dev/shm` | `ConfiguracionProduccionTest::test_restaurar_limpia_la_clave` · ensayo: `ls /dev/shm` y `gpg --list-secret-keys` vacíos |
| TM5 | Spoofing: alguien prueba contraseñas en ráfaga | A07:2025 Authentication Failures | `RegistrarFalloAcceso`, `Vigilante` | Aviso con más de 10 fallos en 5 min por origen o por cuenta; el limitador de la 030 no cambia (CA2) | `RegistroFallosAccesoTest`, `VigilanteTest::test_mas_de_10_fallos_por_origen_avisa`, `…_por_cuenta_avisa`, `LimiteIntentosTest` |
| TM6 | Denial of service: un atacante inunda el buzón para que se ignoren los avisos | A09:2025 Security Logging and Alerting Failures | `Vigilante` | Como mucho un correo por tipo cada 30 min, con el acumulado. Nada se pierde: lo pendiente se envía al acabar el silencio (CA4) | `VigilanteTest::test_silencio_de_30_minutos_acumula`, `…_envia_lo_pendiente_al_acabar_el_silencio` |
| TM7 | Information disclosure: el correo, en un buzón externo, lleva datos personales o secretos | A09:2025 | `AvisoProduccion` | Solo tipo, recuentos, horas y órdenes de consulta; las entradas del log nunca pasan al correo (CA5) | `AvisoProduccionTest::test_el_correo_no_lleva_datos_personales_ni_secretos` |
| TM8 | Information disclosure: el registro de fallos guarda IPs y correos | A09:2025 | `RegistrarFalloAcceso` | Huellas HMAC recortadas con `APP_KEY`; sin IP, correo ni id | `RegistroFallosAccesoTest::test_el_registro_no_contiene_ip_ni_correo` |
| TM9 | Information disclosure: secretos en la salida de `verificar.sh` o en el repo | A02:2025 Security Misconfiguration | `verificar.sh`, `.env.example` | Comprobaciones sin valores; `app.env` y `*.asc` fuera del clon (CA6, RNF) | `ConfiguracionProduccionTest::test_verificar_no_imprime_valores_del_correo` · gitleaks |
| TM10 | Denial of service: los avisos dejan de funcionar sin que nadie lo note (temporizador parado, contraseña de Gmail revocada) | A09:2025 | `verificar.sh`, `Vigilar`, UptimeRobot | `verificar.sh` comprueba el temporizador y la configuración en cada despliegue. Un envío fallido sale con código distinto de 0 (journal) y deja `Log::warning`. El aviso de prueba está documentado y UptimeRobot es independiente del servidor | `VigilanteTest::test_un_envio_fallido_no_marca_el_aviso_como_enviado` · ensayo de `verificar.sh` rompiendo |
| TM11 | Tampering / DoS: volver a una versión anterior a la 032 con una copia cifrada deja la base a medias | A08:2025 | `volver.sh` | `restaurar.sh` copiado antes del checkout y comprobación de la copia antes de parar nada (D4) | `ConfiguracionProduccionTest::test_volver_usa_el_restaurar_de_la_version_actual` · ensayo de vuelta con copia |
| TM12 | Denial of service: un log de acceso enorme hace que el vigilante tarde o falle | A09:2025 | `Vigilante` | Lectura incremental desde el desplazamiento guardado, con un tope de bytes por ejecución; si el log se trunca o rota, vuelve a empezar | `VigilanteTest::test_solo_lee_lo_nuevo`, `…_detecta_log_truncado` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: >1 % de 5xx en 5 min → correo en ≤ 10 min con tipo, recuento, intervalo y cómo consultar | `Vigilante`, `Vigilar`, temporizador, montaje de `nginx_logs`, `AvisoProduccion` | `VigilanteTest::test_mas_del_1_por_ciento_de_5xx_avisa`, `…_el_1_por_ciento_no_avisa`, `…_ventana_de_5_minutos` · verificación manual: ensayo en el servidor con líneas 5xx inyectadas en el log de `web`; el correo llega en ≤ 10 min |
| CA2 (abuso): >10 fallos en 5 min por origen o cuenta (contraseña o Google) → correo; el límite no cambia | `RegistrarFalloAcceso`, canal `seguridad`, `Vigilante` | `RegistroFallosAccesoTest` (contraseña, Google, `Lockout`; éxito sin línea), `VigilanteTest::test_mas_de_10_fallos_por_origen_avisa`, `…_por_cuenta_avisa`, `…_10_fallos_no_avisa`, `LimiteIntentosTest` (sin cambios) · verificación manual: 11 intentos contra una cuenta de prueba en producción; el correo llega en ≤ 10 min |
| CA3: caída de 5 min desde internet, también con el servidor apagado → correo en ≤ 10 min, y otro al volver | UptimeRobot (D13), `deploy/README.md` | verificación manual: monitor HTTPS de `/up` cada 5 min con avisos al caer y al volver; ensayo con `cloudflared` parado ≥ 6 min (con aprobación), los dos correos llegan |
| CA4 (abuso): como mucho 1 correo por tipo cada 30 min, con el acumulado; nada se pierde | `Vigilante`, `Estado` | `VigilanteTest::test_silencio_de_30_minutos_acumula`, `…_envia_lo_pendiente_al_acabar_el_silencio`, `…_los_tipos_tienen_silencio_propio` |
| CA5 (abuso): el correo no lleva datos personales, de salud, correos, contraseñas, IPs ni secretos | `AvisoProduccion`, plantilla de texto | `AvisoProduccionTest::test_el_correo_no_lleva_datos_personales_ni_secretos`, `…_lleva_tipo_recuentos_horas_y_como_consultar` |
| CA6: `verificar.sh` falla sin envío configurado o con el texto de ejemplo, sin mostrar valores; aviso de prueba en ≤ 5 min | `verificar.sh` (D12), `Vigilar --prueba`, guía | `ConfiguracionProduccionTest::test_verificar_comprueba_el_envio_de_avisos`, `…_no_imprime_valores_del_correo`, `AvisoProduccionTest::test_el_aviso_de_prueba_se_envia` · verificación manual: `verificar.sh` rompiendo (sin `MAIL_PASSWORD`, con el `<…>` de la guía) da FALLO sin valores; el aviso de prueba llega en ≤ 5 min |
| CA7: la copia previa al despliegue se cifra desde el primer momento (también la del PC); sin gpg o sin clave, el despliegue se detiene antes de cambiar nada | `respaldar.sh` (D2), primer despliegue de la 032 (D6) | `ConfiguracionProduccionTest::test_respaldar_cifra_con_la_clave_publica`, `…_no_escribe_en_claro`, `…_se_detiene_sin_gpg_o_sin_clave` · verificación manual: `desplegar.sh` deja `.sql.gz.gpg` y `find` no encuentra claro; con la clave pública renombrada, `desplegar.sh` se detiene en «Copia de la base» y el `HEAD` no cambia |
| CA8 (abuso): lo que abre las copias no está en el servidor, el repo ni el paquete | D1, D3, guía §7 | `ConfiguracionProduccionTest::test_los_archivos_de_clave_quedan_fuera_del_paquete_y_del_repo`, `verificar.sh` «el llavero del servidor no tiene clave secreta» · lint: gitleaks · verificación manual: con solo lo que hay en el servidor no se puede descifrar |
| CA9: con la clave, la base queda exactamente igual (filas y comprobación por tabla), ensayado, también con `volver.sh`; clave ajena, copia alterada o incompleta → se detiene sin tocar la base (reemplaza 030 CA6) | `restaurar.sh` (D3), `volver.sh` (D4) | `ConfiguracionProduccionTest::test_restaurar_comprueba_antes_de_borrar`, `…_limpia_la_clave`, `…_volver_usa_el_restaurar_de_la_version_actual` · verificación manual: ensayo con `CHECKSUM TABLE` y recuento de filas antes y después; los tres rechazos con la base intacta; `volver.sh` con copia cifrada |
| CA10: sin copias en claro en el servidor al liberar; la guía cubre las del PC, la segunda copia de la clave y su pérdida; el disco, riesgo aceptado hasta el primer dato real | `cifrar-copias-antiguas.sh` (D5), `verificar.sh`, `deploy/README.md`, `docs/security.md` (EX3, RS15.c) | `ConfiguracionProduccionTest::test_cifrar_copias_antiguas_cifra_antes_de_borrar`, `…_verificar_falla_con_copias_en_claro` · verificación manual: `verificar.sh` OK tras el script en la liberación; revisión de la guía y de EX3 |
| RNF: gratuito | UptimeRobot gratuito, Gmail, GnuPG del sistema, Mail de Laravel | verificación manual: sin planes de pago (revisión de la guía) |
| RNF: correos en español y comprensibles sin abrir el servidor | `AvisoProduccion` | `AvisoProduccionTest::test_lleva_tipo_recuentos_horas_y_como_consultar` |
| RNF: la detección no ralentiza la aplicación | `RegistrarFalloAcceso` (solo en fallos, una línea), vigilante fuera de las peticiones | verificación manual: el listener no se ejecuta en peticiones correctas (`RegistroFallosAccesoTest`, éxito sin línea) y el vigilante corre en el temporizador |
| RNF: ningún secreto en el repo, el paquete ni la salida de la verificación | `app.env`, `verificar.sh`, `.dockerignore` | gitleaks · `ConfiguracionProduccionTest::test_verificar_no_imprime_valores_del_correo` |
| TM1: copia robada | `respaldar.sh`, guía | `ConfiguracionProduccionTest::test_respaldar_cifra_con_la_clave_publica` · `verificar.sh` sin clave secreta |
| TM2: volcado en claro durante el cifrado | `respaldar.sh` | `ConfiguracionProduccionTest::test_respaldar_no_escribe_en_claro` |
| TM3: copia sustituida, alterada o cortada | `restaurar.sh` | `ConfiguracionProduccionTest::test_restaurar_comprueba_antes_de_borrar` · ensayo de rechazos |
| TM4: clave secreta olvidada en el servidor | `restaurar.sh` | `ConfiguracionProduccionTest::test_restaurar_limpia_la_clave` |
| TM5: contraseñas en ráfaga | `RegistrarFalloAcceso`, `Vigilante` | `RegistroFallosAccesoTest`, `VigilanteTest::test_mas_de_10_fallos_por_origen_avisa`, `LimiteIntentosTest` |
| TM6: buzón inundado | `Vigilante` | `VigilanteTest::test_silencio_de_30_minutos_acumula` |
| TM7: datos en el correo | `AvisoProduccion` | `AvisoProduccionTest::test_el_correo_no_lleva_datos_personales_ni_secretos` |
| TM8: IPs y correos en el registro de fallos | `RegistrarFalloAcceso` | `RegistroFallosAccesoTest::test_el_registro_no_contiene_ip_ni_correo` |
| TM9: secretos en la salida o en el repo | `verificar.sh`, `.gitignore` | `ConfiguracionProduccionTest::test_verificar_no_imprime_valores_del_correo` · gitleaks |
| TM10: avisos caídos sin que se note | `verificar.sh`, `Vigilar` | `VigilanteTest::test_un_envio_fallido_no_marca_el_aviso_como_enviado` · `verificar.sh` rompiendo |
| TM11: vuelta atrás con copia cifrada a medias | `volver.sh` | `ConfiguracionProduccionTest::test_volver_usa_el_restaurar_de_la_version_actual` |
| TM12: log de acceso enorme | `Vigilante` | `VigilanteTest::test_solo_lee_lo_nuevo`, `…_detecta_log_truncado` |

## Observabilidad
- **Logs nuevos:**
  - canal `seguridad`, `info`: una línea por fallo o bloqueo de inicio de sesión (Contratos);
  - `Log::warning('aviso no enviado', ['tipo' => …])` en `laravel.log` cuando falla el SMTP, sin
    el mensaje de la excepción, que puede llevar el usuario de SMTP.
  Ninguno tiene `request_id`, que no existe hasta la 020.
- **Eventos de auditoría:** ninguno (P7 ➖, sección Auditoría de la spec).
- **Métricas y alertas** (`docs/observability.md` pasa de propuestas a en uso):
  - tasa de 5xx > 1 % en 5 min;
  - fallos de acceso > 10 en 5 min por origen o cuenta;
  - caída vista desde fuera durante 5 min.
  El canal de todas es el correo del responsable. La latencia p95 sigue fuera (roadmap).
- **Verificación tras el despliegue:**
  - `verificar.sh` con la sección «Avisos y copias» en OK;
  - aviso de prueba recibido;
  - monitor en verde en el panel de UptimeRobot;
  - `systemctl list-timers dentissa-vigilar.timer` con la siguiente ejecución en menos de un
    minuto.

## Descubribilidad
No aplica: la spec no crea ni cambia rutas públicas.

## Rollout
- **Feature flag:** no aplica. No cambia ningún flujo de la aplicación: el listener solo escribe en
  un log, y los avisos y las copias son del servidor. Se apaga parando el temporizador.
- **Prerrequisitos del responsable, antes de desplegar** (guía):
  1. Par de claves en el PC con su frase y segunda copia de la secreta en un lugar seguro.
  2. `copias.pub.asc` en `/opt/dentissa/entorno` (600).
  3. Contraseña de aplicación de Gmail y variables de correo en `app.env`.
  4. Cuenta de UptimeRobot.
- **Orden del despliegue (D6):**
  1. `git fetch --tags && git checkout <tag>` antes de lanzar el despliegue, para que la copia
     previa la haga ya el `respaldar.sh` nuevo, cifrada.
  2. `bash deploy/desplegar.sh <tag>`.
  3. `bash deploy/preparar-vigilancia.sh`.
  4. `bash deploy/cifrar-copias-antiguas.sh`.
  5. `bash deploy/verificar.sh`.
  6. Aviso de prueba.
  7. Monitor de UptimeRobot.
  8. En el PC: sustituir y borrar las copias en claro.
- **Compatibilidad:** sin migraciones. La versión anterior convive con la clave pública y las
  variables nuevas de `app.env`, que ignora.
- **Rollback:** `bash deploy/volver.sh <anterior>`, sin copia porque no hay migraciones. Después,
  `sudo systemctl disable --now dentissa-vigilar.timer` (lo recuerda `volver.sh`). Las copias
  siguen cifradas. Para restaurarlas desde la versión anterior se usa el `restaurar.sh` del tag de
  la 032 (`git show <tag>:deploy/restaurar.sh > /tmp/restaurar.sh`; la guía lo explica).
- **Métricas a vigilar:**
  - durante la primera hora, ningún correo inesperado y el journal del temporizador sin errores
    (`journalctl -u dentissa-vigilar`);
  - 0 5xx nuevos.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Cifrado de copias → [ADR 0007](../../adr/0007-copias-cifradas-y-avisos.md)** (proposed;
  también recoge D8, D9 y D13). OpenPGP asimétrico con GnuPG y la clave pública del
  responsable (`--recipient-file`, `--trust-model always`), sin llavero en el servidor.
  - Alternativas descartadas:
    - `age`: sería una dependencia nueva en el servidor y en el PC;
    - `openssl enc` simétrico: la clave tendría que estar en el servidor, lo que incumple CA8;
    - cifrar con `restic`/`borg`: otra herramienta y otro formato.
  - Comprobado con una prueba de concepto el 2026-10-23 (Enfoque).
- **D2 · `respaldar.sh`.**
  - Primero comprueba `gpg --version` y la clave pública con `gpg --show-keys`: si algo falla, se
    detiene antes de volcar, y por tanto antes del checkout de `desplegar.sh` (CA7).
  - La tubería es `mariadb-dump | tee >(tail -n 1 > <marca en /dev/shm>) | gzip | gpg …` hacia
    `.part`. Con `pipefail`, espera a que termine el `tail` y comprueba `-- Dump completed` en la
    marca. Solo entonces hace `mv` a `.sql.gz.gpg` y `chmod 600`; si falla, borra `.part`.
  - La marca solo contiene la línea final del volcado, sin datos.
- **D3 · `restaurar.sh`, con la clave en el servidor solo mientras dura** (decisión del usuario,
  2026-10-23).
  - El responsable sube la clave exportada a `/dev/shm`.
  - El script la importa en un llavero creado con `mktemp -d -p /dev/shm` y usa `--pinentry-mode
    loopback` con la frase pedida por la terminal.
  - Pasada 1, de comprobación: `gpg --decrypt | gzip -cd | tail -n 1` debe salir con código 0 y
    dar `-- Dump completed`. Se hace antes de pedir la confirmación y de `DROP`.
  - Pasada 2: carga en la base. La frase se pide una sola vez gracias a la caché del agente.
  - `trap EXIT`: `gpgconf --kill gpg-agent`, borrar el llavero y la clave de `/dev/shm`.
  - `--solo-comprobar` hace solo la pasada 1.
  - Queda para el ensayo en el servidor: la caché de la frase con gpg 2.4.4. Si no funciona, se
    pide dos veces y se anota.
- **D4 · `volver.sh`.**
  - Antes del `checkout`, copia `deploy/restaurar.sh` a un temporal y, si hay copia, la comprueba
    con `--solo-comprobar` antes de parar nada.
  - Después del `checkout` usa esa copia, así que vuelve a cualquier versión, también anterior a la
    032, con una copia cifrada (Notas para /plan de la spec).
  - Si la versión de destino no tiene `app/Console/Commands/Vigilar.php`, avisa de parar el
    temporizador.
- **D5 · Copias en claro existentes: cifrarlas y borrar las originales** (decisión del usuario,
  2026-10-23).
  - `cifrar-copias-antiguas.sh` comprueba cada `dentissa-*.sql.gz` (`gzip -t` y `-- Dump
    completed`) y la cifra hacia `.part`.
  - Hace `mv` a `.sql.gz.gpg` conservando la fecha (`touch -r`) y borra la original con `shred -u`.
    En ext4 con journal, `shred` no garantiza el borrado físico: es el residuo del disco sin cifrar
    (RS15.c, EX3).
  - Una copia dañada no se cifra: se informa y se deja para que el responsable decida.
- **D6 · Primer despliegue de la 032.** `desplegar.sh` hace la copia con el `respaldar.sh` de la
  versión instalada antes del checkout, así que el de v0.10.0 la haría en claro.
  - En esta liberación, el checkout del tag se hace antes de `desplegar.sh` (Rollout). Así la copia
    previa ya sale cifrada (CA7).
  - El inconveniente es que `desplegar.sh` informa como «versión anterior» el propio tag. La guía y
    `/release` dan el comando de vuelta con la versión anterior real.
  - El problema general («cada despliegue usa el `desplegar.sh` de la versión anterior») sigue en
    el roadmap.
- **D7 · Fallos de acceso en un canal propio.** `App\Listeners\RegistrarFalloAcceso`, descubierto
  automáticamente, escucha `Failed` (contraseña y Google) y `Lockout` (login y Google).
  - Canal `seguridad` `daily` con 14 días y `JsonFormatter`.
  - Contiene huellas HMAC, no IPs ni correos (Contratos; P8).
  - Alternativas descartadas:
    - deducir los fallos del log de nginx: la respuesta es la misma redirección 302 con éxito y con
      fallo;
    - una tabla nueva: exige una migración y no es la auditoría de la 021.
  - `Lockout` cuenta como fallo: también es un intento rechazado.
- **D8 · Vigilante en PHP (`app/Avisos`) y comando `dentissa:vigilar`** (decisión del usuario,
  2026-10-23).
  - Cada minuto:
    1. lee lo nuevo del log de acceso desde el desplazamiento guardado, con un tope por ejecución
       y volviendo a empezar si el inodo cambia o el tamaño baja, y acumula total y 5xx por minuto;
    2. lee las líneas de `seguridad` de hoy y de ayer dentro de los últimos 5 min y las agrupa por
       origen y por cuenta;
    3. evalúa los umbrales de la spec;
    4. aplica el silencio de 30 min por tipo de aviso (`errores`, `accesos`) con recuento pendiente;
       lo pendiente se envía al acabar el silencio aunque la condición haya terminado.
  - El reloj y las rutas se inyectan para los tests.
  - Un envío fallido no actualiza el «último envío»: se reintenta al minuto siguiente.
  - Alternativa descartada: `deploy/vigilar.sh` con `curl` SMTP, que no se puede probar con
    PHPUnit.
- **D9 · Correo con el Mail de Laravel por el SMTP de Gmail** con contraseña de aplicación
  (decisión del usuario, 2026-10-23).
  - `smtp.gmail.com:587` con STARTTLS, `MAIL_FROM_ADDRESS` = la cuenta y destino `AVISOS_CORREO`.
  - Necesita la verificación en dos pasos de la cuenta (guía).
  - `app` ya tiene salida a internet, que usa la vuelta de Google.
- **D10 · `nginx_logs` en solo lectura en `app`** (ruta protegida `docker-compose.prod.yml`,
  aprobada por el usuario el 2026-10-23). Es la única forma de que el vigilante cuente las
  respuestas que da nginx, también las 502/504 que no pasan por Laravel.
- **D11 · Temporizador de systemd.** `deploy/preparar-vigilancia.sh` (con `sudo`) instala
  `dentissa-vigilar.service`.
  - Es de tipo `oneshot` y corre con la cuenta de despliegue:
    `docker compose -f /opt/dentissa/app/docker-compose.prod.yml exec -T -u www-data app php artisan dentissa:vigilar`.
  - Lo lanza `dentissa-vigilar.timer` (`OnCalendar=minutely`), con `enable --now`.
  - Un `oneshot` no se solapa consigo mismo.
  - Se descarta el planificador de Laravel, que también necesitaría un cron fuera del contenedor.
- **D12 · `verificar.sh`.**
  - Al bloque de configuración se añaden 4 comprobaciones (12 → 16):
    - `MAIL_MAILER=smtp`;
    - `MAIL_HOST` y `MAIL_PORT` definidos;
    - `MAIL_USERNAME` y `MAIL_PASSWORD` definidos y sin `<…>`;
    - `AVISOS_CORREO` con formato de correo.
  - Sección nueva «Avisos y copias (spec 032)»:
    - temporizador activo;
    - clave pública válida, con `gpg --show-keys` sin `sec`;
    - el llavero de la cuenta de despliegue sin claves secretas;
    - ninguna `dentissa-*.sql.gz` en `/var/backups/dentissa`;
    - `app` puede leer el log de acceso.
  - Ninguna comprobación imprime valores.
- **D13 · UptimeRobot** (decisión del usuario, 2026-10-23).
  - Monitor HTTP(s) de `https://<dominio>/up` cada 5 min (plan gratuito), con el correo del
    responsable como contacto de alerta (avisa al caer y al volver).
  - Con el servidor apagado, Cloudflare responde con un error (530/1033) y el monitor lo cuenta
    como caída.
  - En `/implement` se comprueba en la web de UptimeRobot el intervalo y la confirmación del plan
    gratuito, y se anota en la guía.
- **D14 · Clave del responsable.**
  - Se genera en el PC con el gpg de Git para Windows (`gpg --quick-generate-key "Dentissa copias"
    default default never`, con frase).
  - No caduca: la caducidad solo impediría cifrar copias nuevas, no proteger las viejas, y obligaría
    a renovarla en el servidor.
  - La pública va al servidor. La secreta se exporta, con su frase, a una segunda copia fuera del
    PC (memoria USB guardada aparte o gestor de contraseñas).
  - Si se pierde, las copias no se recuperan (guía, CA10).
- **D15 · Orden con la 038 y versión** (decisión del usuario, 2026-10-24, al aprobar el plan).
  - La 038 está en pausa (decisión del usuario, 2026-10-22), así que la 032 sale antes, con la
    siguiente minor (v0.11.0).
  - La que salga después trae `main`: conflictos previstos en `deploy/verificar.sh`,
    `deploy/README.md`, `docs/security.md` y el roadmap.
- **Correcciones de riesgos:**
  - OB6.b dentro, en producción; OB6.c (staging), fuera.
  - RS15.b dentro, en las copias de producción.
  - RS15.c (disco de producción): excepción **EX3**, que vence al cargar el primer dato real de
    pacientes y la aprueba el responsable en `/release`.
  - RS15.d (staging), fuera. RS15.a sigue en la 013.
  - OB6.a, fuera (objetivo 3).
  - Con esta spec, OB6 y RS15 no quedan mitigados del todo.

## Impacto en arquitectura
Secciones de `docs/architecture.md` que hay que actualizar en T090:
- **Producción:** temporizador → `dentissa:vigilar`, el volumen `nginx_logs` también en `app`,
  salida SMTP a Gmail y UptimeRobot como servicio externo.
- **Módulos:** `App\Avisos` (vigilante, estado, correo) y `App\Listeners\RegistrarFalloAcceso`.
- **Copias:** formato `.sql.gz.gpg` y flujo de restauración con la clave del responsable.

Otros documentos que hay que actualizar:
- `docs/observability.md`: alertas en uso, canal `seguridad`, OB6 dividida.
- `docs/security.md`:
  - RS15 dividida y EX3;
  - secretos nuevos (contraseña de aplicación, clave);
  - fila de copias en «Manejo de secretos».
- `docs/deployment.md`: copias, avisos y verificación.

## Riesgos y mitigaciones
- **Perder la clave secreta deja las copias inservibles.** Mitigación: segunda copia obligatoria
  en la guía, que se comprueba en el ensayo de restauración con esa segunda copia.
- **Falsos avisos con poco tráfico:** un 5xx entre 10 peticiones ya es un 10 %. La spec no fija un
  mínimo de peticiones. Se acepta y, si molesta, el umbral se ajusta en otra spec.
- **Google puede revocar la contraseña de aplicación** si cambia la contraseña de la cuenta.
  Mitigación: `verificar.sh` no lo detecta (no envía), pero el aviso de prueba sí. El journal del
  temporizador registra el fallo y UptimeRobot no depende de ello.
- **`shred` no borra físicamente en ext4 con journal.** Residuo aceptado en EX3.
- **El primer despliegue depende de seguir el orden de D6.** Mitigación: `/release` da los
  comandos exactos y comprueba con `find` que no hay claro.
- **Rotar `APP_KEY` cambia las huellas** y reinicia la agrupación de fallos durante 5 min. Es
  aceptable.
