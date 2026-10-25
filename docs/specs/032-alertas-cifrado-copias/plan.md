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
- **Restaurar desde el PC, donde está la clave** (decisión del usuario, 2026-10-25, /analyze A5).
  La clave secreta y su frase nunca llegan al servidor. `restaurar.sh` se ejecuta en el PC:
  - primero descifra la copia entera hacia ningún sitio, lo que detecta una clave que no es la
    suya, una copia alterada o una copia cortada;
  - después la vuelve a descifrar y envía el volcado por SSH al servidor, que lo carga en la base
    sin escribirlo en el disco.
  También dirige la vuelta atrás con copia.
- **Vigilante dentro de la aplicación:** `php artisan dentissa:vigilar`, que un temporizador de
  systemd lanza cada minuto. Cuenta los 5xx en el log de acceso de nginx, que se monta en solo
  lectura en `app`, y los fallos de inicio de sesión de un canal de log nuevo, `seguridad`.
  Escribe en ese canal un listener de `Failed` y `Lockout`, sin IP ni correo: solo huellas HMAC.
  Envía los correos con el Mail de Laravel por el SMTP de una cuenta de Gmail que solo sirve para
  enviar (decisión del usuario, 2026-10-25, /analyze A6), con un silencio de 30 minutos por tipo de
  aviso y el recuento acumulado.
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

Queda para el PC, en la tarea de los preparativos: que la frase de la clave se pida una sola vez
para las dos pasadas de `restaurar.sh`, gracias a la caché del `gpg-agent` del llavero normal del
PC (Git para Windows).

Línea base de dependencias (2026-10-23, rama con `main` `b60b4d3` fusionado): `composer audit
--locked` sin avisos y `npm audit` con 0 vulnerabilidades. EX2 sigue vigente hasta el 2026-11-30
(imágenes; el refresco de 031/R4 va aparte).

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 032 aprobada el 2026-10-07; `tasks.md` se aprueba antes de `/implement`. | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Comportamiento nuevo de la aplicación, con tests que fallan hoy: el registro de fallos de acceso (CA2), el vigilante (CA1, CA2, CA4), el contenido del correo (CA5) y el aviso de prueba (CA6). Los scripts de `deploy/` se prueban como en la 030 y la 031, con tests que leen su texto (`ConfiguracionProduccionTest`). Su efecto real (cifrado, restauración, caída vista desde fuera) no es comportamiento de la aplicación: se verifica con las comprobaciones que declara Trazabilidad (constitución, P2) en el ensayo en el servidor, y las de `verificar.sh` se comprueban rompiendo. | test: `RegistroFallosAccesoTest`, `VigilanteTest`, `AvisoProduccionTest`, `ConfiguracionProduccionTest` · manual: comprobaciones de Trazabilidad |
| P3 Autorización en servidor | ➖ | Sin rutas, endpoints ni permisos nuevos. El vigilante es un comando de consola. | — |
| P4 Entrada validada y SQL con bindings | ✅ | La única entrada externa nueva son las líneas del log de acceso de nginx: se leen con una expresión anclada a la estructura del formato `combined` (D8) y se descarta lo que no encaja. Del registro solo se toman la hora y el código, nunca la petición. Sin SQL nuevo. | test: `VigilanteTest::test_ignora_las_lineas_que_no_tienen_el_formato`, `…_no_se_engana_con_corchetes_ni_espacios` · lint: semgrep `p/php` |
| P5 Nada sensible en el repo | ✅ | La contraseña de aplicación de Gmail y el correo de destino van en `app.env`, fuera del clon. La clave pública está en `/opt/dentissa/entorno` y la secreta nunca llega al repositorio ni al paquete. `.env.example` solo lleva nombres vacíos. | lint: gitleaks · test: `ConfiguracionProduccionTest` (los `*.asc` y `*.env` quedan fuera del paquete y del repo) |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas de Composer ni de npm: el Mail de Laravel ya trae Symfony Mailer. GnuPG viene del sistema (Ubuntu y Git para Windows). Línea base limpia; EX2, vigente. | lint: `composer audit --locked`, `npm audit --audit-level=high`, `trivy image` en `/release` |
| P7 Auditoría de datos sensibles | ➖ | No añade ni cambia acciones auditables (sección Auditoría de la spec). El canal `seguridad` sirve para alertar, no para auditar: no cumple ni sustituye el almacén solo anexado de la 021. Los inicios de sesión fallidos siguen bajo la excepción P7 de la 030, que vence con la 021. | — |
| P8 Correlación y logs limpios | ❌ aceptado: el canal `seguridad` escribe una línea por petición fallida sin `X-Request-Id`, que no existe hasta la 020; la 020 lo añadirá también a este canal (su `Context` va en `extra`, A11) — aprobado por el usuario el 2026-10-25 (/analyze A4), vence al liberar la 020 | La parte de logs limpios sí se cumple. El canal `seguridad` guarda tipo, método y huellas HMAC-SHA256 recortadas del origen y de la cuenta, con una clave derivada de `APP_KEY` (D7). Nunca la IP ni el correo. El vigilante solo registra el tipo de un aviso que no se pudo enviar. El nivel `info` y `APP_DEBUG=false` no cambian. | test: `RegistroFallosAccesoTest::test_el_registro_no_contiene_ip_ni_correo` · manual: revisión de cada `Log::` del diff · `/review`: la excepción tiene fuente usuario |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Sin pantallas. El correo es texto plano y no es interfaz de la aplicación. | — |
| P10 Rutas indexables descubribles | ➖ | Sin rutas nuevas ni cambiadas. | — |

## Cambios por módulo
| Módulo / ruta | Cambio | Riesgo |
|---|---|---|
| `deploy/respaldar.sh` | Cifra en tubería con `gpg --recipient-file` hacia `.part` y luego `mv` a `.sql.gz.gpg`. Comprueba la línea final sin escribir en claro, con una marca que escribe un miembro de la tubería. Antes de volcar se detiene si falta gpg o la clave pública. Conserva 14 copias `.gpg` (D2) | medio: una copia rota sin aviso |
| `deploy/restaurar.sh` | Pasa a ejecutarse **en el PC** (Git Bash): `restaurar.sh <copia.sql.gz.gpg> <cuenta@servidor> [--volver <ref>] [--solo-comprobar]`. Pasada de comprobación local con tres mensajes distintos, confirmación, y carga por SSH con órdenes que no dependen de la versión instalada (D3) | alto: es la vía de recuperación |
| `deploy/volver.sh` | Deja de aceptar una copia: `volver.sh <ref> [--sin-levantar]`. Con `--sin-levantar` deja `app` y `web` parados y `db` sana para que `restaurar.sh --volver` cargue la copia desde el PC. Avisa de parar el temporizador si la versión de destino no tiene el vigilante (D4) | medio |
| `deploy/desplegar.sh` | La orden de vuelta que imprime si falla la verificación sigue el contrato nuevo (D4, /analyze A17) | bajo |
| `deploy/cifrar-copias-antiguas.sh` (nuevo) | Cifra todas las `dentissa-*.sql.gz` que haya y borra las originales. Las dañadas también se cifran, con otro nombre (D5) | medio: borra archivos |
| `deploy/preparar-vigilancia.sh` (nuevo) | Instala y activa `dentissa-vigilar.service` y `.timer` (cada minuto) y comprueba gpg y la clave pública (D11) | bajo |
| `deploy/verificar.sh` | 4 comprobaciones de correo (de 12 a 16) y una sección nueva «Avisos y copias», que no corre con `--interna` (D12) | medio: falsos FALLO |
| `deploy/README.md` | §4 `app.env` con el correo de la cuenta de envío; §7 copias cifradas, clave con frase mínima y su segunda copia, restaurar desde el PC, sustituir las del PC; §8 volver con copia desde el PC; § nueva de avisos (cuenta de Gmail de envío, UptimeRobot, aviso de prueba) | bajo |
| `docker-compose.prod.yml` (ruta protegida, aprobada por el usuario el 2026-10-23) | `app`: `nginx_logs:/var/log/nginx:ro` (D10) | bajo |
| `app/Listeners/RegistrarFalloAcceso.php` (nuevo) | `Failed` y `Lockout` → una línea JSON en el canal `seguridad` (D7) | bajo |
| `app/Http/Controllers/AuthController.php` | La regla de la cuenta del limitador pasa a un método estático público, `sujetoLimite(string $email)`, que usan `claveLimiteLogin` y el listener. El comportamiento del limitador no cambia (D7, /analyze A12) | bajo: `LimiteIntentosTest` lo vigila |
| `app/Avisos/Vigilante.php`, `app/Avisos/Estado.php` (nuevos) | Lectura incremental de los logs, ventanas de 5 min, umbrales y silencio de 30 min; reloj y rutas inyectables (D8) | medio: falsos avisos o avisos perdidos |
| `app/Console/Commands/Vigilar.php` (nuevo) | `dentissa:vigilar` y `dentissa:vigilar --prueba` (D8, CA6) | bajo |
| `app/Mail/AvisoProduccion.php` y `resources/views/correos/aviso.blade.php` (texto) (nuevos) | Correo en español, sin datos personales (D9) | bajo |
| `config/avisos.php` (nuevo), `config/logging.php` | Destino, umbrales, ventana, silencio y rutas; canal `seguridad` (`daily`, 14 días, JSON) | bajo |
| `.env.example` | `AVISOS_CORREO=` (local sigue con `MAIL_MAILER=log`) | bajo |
| `.gitignore`, `.dockerignore` | `*.asc` y `*.gpg` ignorados a cualquier profundidad (`**/*.asc`, `**/*.gpg` en `.dockerignore`), para que ni una clave ni una copia entren en el repo ni en el paquete (CA8) | bajo |
| `tests/Feature/RegistroFallosAccesoTest.php`, `tests/Feature/VigilanteTest.php`, `tests/Feature/AvisoProduccionTest.php` (nuevos), `tests/Feature/ConfiguracionProduccionTest.php` | ver Estrategia de pruebas | — |

## Contratos y datos
- **Sin migraciones ni rutas.** Ningún cambio en la base ni en las respuestas HTTP.
- **Copia:** `/var/backups/dentissa/dentissa-AAAAMMDD-HHMMSS.sql.gz.gpg` (600): mensaje OpenPGP
  binario cifrado para la clave pública de `/opt/dentissa/entorno/copias.pub.asc` (variable
  `DENTISSA_CLAVE_PUBLICA`) y, dentro, el `gzip` de `mariadb-dump`. `respaldar.sh` sigue imprimiendo
  la ruta en la última línea, que es lo que usa `desplegar.sh`.
- **`restaurar.sh <copia.sql.gz.gpg> <cuenta@servidor> [--volver <ref>] [--solo-comprobar]`**,
  en el PC con Git Bash (D3). La clave secreta es la del llavero normal del PC, protegida con su
  frase. Ni la clave ni la frase se envían al servidor; por SSH solo viaja el volcado.
  - Ruta del clon en el servidor: `DENTISSA_APP`, `/opt/dentissa/app` por defecto. El script no
    usa ningún script del servidor para cargar, así que sirve con cualquier versión instalada
    desde la 030 (/analyze A2).
  - Salida con código distinto de 0 y uno de estos mensajes:
    - «la copia no es para tu clave»;
    - «la copia está alterada o cortada (falla la comprobación de integridad)»;
    - «el volcado está incompleto (falta `-- Dump completed`)»;
    - «confirmación incorrecta».
    En ninguno de esos casos se ha tocado la base (/analyze A13).
- **`volver.sh <ref> [--sin-levantar]`**, en el servidor (D4).
  - Sin opción: como en la 030, pero sin copia.
  - Con `--sin-levantar`: hace el checkout y la construcción, para `app` y `web`, deja `db` sana y
    termina sin levantar nada.
  - Si recibe un segundo argumento que es un archivo (la forma de la 030), se detiene antes de
    cambiar nada y muestra la orden de `restaurar.sh --volver` (/analyze A17).
- **Línea del canal `seguridad`** (`storage/logs/seguridad-AAAA-MM-DD.log`), con la forma real del
  `JsonFormatter` de Monolog (/analyze A11):
  `{"message":"acceso_fallido","context":{"tipo":"fallido|bloqueado","metodo":"contrasena|google","origen":"<16 hex>","cuenta":"<16 hex>|null"},"level":200,"level_name":"INFO","channel":"seguridad","datetime":"2026-10-23T18:04:05.123456+00:00","extra":{}}`.
  - El vigilante lee `datetime` y `context`, e ignora `extra`, donde la 020 añadirá `request_id` y
    `actor_id`.
  - `origen` = HMAC-SHA256 de `AuthController::origenLimite()`, es decir, la IPv4 o el prefijo /64
    de IPv6, como el limitador.
  - `cuenta` = HMAC de `AuthController::sujetoLimite($email)`:
    - `cuenta:<id>` si el correo es de una cuenta, esté activa o no;
    - si no, `correo:<minúsculas>`;
    - `null` en la vuelta de Google.
    `Failed` trae el correo en `credentials`, y `Lockout` en el campo `email` de la petición.
  - Clave del HMAC: `hash_hmac('sha256', 'dentissa-seguridad', APP_KEY)`, derivada y no la
    `APP_KEY` misma (/analyze A27).
- **Estado del vigilante** (`storage/app/avisos/estado.json`, en el volumen `storage`):
  - desplazamiento e inodo del log de acceso;
  - contadores por minuto de los últimos 5 (total y 5xx);
  - momento del último fallo de acceso ya contado;
  - por tipo de aviso:
    - último envío y fin del intervalo que cubrió;
    - recuento pendiente: eventos distintos ocurridos después de ese fin, cada uno contado una sola
      vez (/analyze A9).
  Se escribe de forma atómica (temporal + `rename`).
  Sin estado (primera ejecución), el desplazamiento empieza en el final del log de acceso actual y
  no cuenta lo anterior (/analyze A10).
- **Variables nuevas de `app.env`** (la cuenta es la de envío dedicada, D9):
  - `MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`;
  - `MAIL_USERNAME`, `MAIL_PASSWORD` (contraseña de aplicación de la cuenta de envío),
    `MAIL_FROM_ADDRESS` (la misma cuenta), `MAIL_FROM_NAME`;
  - `AVISOS_CORREO` (el correo de siempre del responsable).
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
  origen o la misma cuenta da la misma huella. Una cuenta inactiva da la huella de su cuenta, y un
  `Lockout` la misma que el `Failed` de esa cuenta (/analyze A12). La línea tiene la forma del
  contrato (/analyze A11). `LimiteIntentosTest` sigue igual (CA2: el límite no cambia).
- **`VigilanteTest`** (Feature, con `Mail::fake()`, reloj fijo y logs de prueba en una carpeta
  temporal):
  - 5xx por encima y en el 1 %;
  - 11 y 10 fallos por origen y por cuenta;
  - silencio de 30 min con el recuento acumulado, que se envía aunque la condición haya terminado
    (CA4);
  - lectura incremental, log truncado o rotado;
  - primera ejecución sobre un log grande: no cuenta lo anterior; tráfico mayor que el tope: lo
    que queda se lee en la ejecución siguiente (/analyze A10);
  - líneas con otro formato, y líneas con `[fecha] "GET /" 200` dentro del usuario o de la
    petición: el código leído es el real (/analyze A7);
  - recuento sin duplicados: 11 fallos en un minuto y 10 ejecuciones dan un aviso con 11 (/analyze
    A9);
  - líneas de `seguridad` con campos en `extra`;
  - ventana de 5 min.
- **`AvisoProduccionTest`** (Feature):
  - el cuerpo no contiene IPs, correos, huellas, `APP_KEY` ni `MAIL_PASSWORD`, ni siquiera cuando
    los logs de entrada tienen una IP y un correo (CA5);
  - lleva el tipo, los recuentos, el intervalo y cómo consultar;
  - `dentissa:vigilar --prueba` envía un aviso de prueba (CA6);
  - `dentissa:vigilar` sale con código distinto de 0 si un envío falla (TM10, /analyze A21).
- **`ConfiguracionProduccionTest`** (lee el texto de los scripts y del Compose, como en la 030 y
  la 031):
  - `app` monta `nginx_logs` en solo lectura;
  - `.gitignore` y `.dockerignore` excluyen `*.asc` y `*.gpg` a cualquier profundidad;
  - `respaldar.sh`: `--recipient-file`, sin `--symmetric` ni archivos `.sql`/`.sql.gz`, con la
    comprobación previa de gpg y de la clave, y sin sustitución de procesos `>(…)`; la marca se
    comprueba después de la tubería (/analyze A8);
  - `restaurar.sh`:
    - no contiene `--import`, `--export-secret-keys` ni el envío de la clave;
    - por SSH solo viaja la salida de `gpg --decrypt`;
    - la pasada de comprobación va antes de la confirmación y de `DROP DATABASE`;
    - tres mensajes de rechazo distintos;
    - no depende de `dirname "$0"` en el servidor (/analyze A2, A5, A13);
  - `volver.sh`: `--sin-levantar`, rechazo de la copia como argumento y aviso del temporizador;
  - `desplegar.sh`: la orden de vuelta que imprime sigue el contrato (/analyze A17);
  - `verificar.sh`:
    - 16 comprobaciones de configuración, con `MAIL_FROM_ADDRESS` (/analyze A28);
    - la sección «Avisos y copias» solo fuera de `--interna` (/analyze A3);
  - el temporizador de `preparar-vigilancia.sh`;
  - `cifrar-copias-antiguas.sh` cifra antes de borrar, también las dañadas.
- **Ensayo en el servidor** (con aprobación previa del usuario, como T040–T046 de la 030):
  - copia cifrada y `find / -xdev` sin volcados en claro (`*.sql`, `*.sql.gz`), carpeta personal
    de la cuenta de despliegue incluida (/analyze A16);
  - `desplegar.sh` con la clave pública renombrada, hacia una ref distinta del `HEAD`: se detiene
    y el `HEAD` no cambia (/analyze A15);
  - restauración desde el PC (/analyze A14):
    - con `app` y `web` parados, foto de `CHECKSUM TABLE` y del recuento de filas;
    - copia tomada justo después;
    - restauración y comparación con la foto;
    - el servidor sin claves secretas durante y después;
  - clave equivocada, copia alterada y copia cortada, cada una con su mensaje y sin tocar la base;
  - `restaurar.sh --volver v0.10.0` con la copia, pidiendo la frase una sola vez;
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
| TM3 | Tampering: alguien sustituye, altera o corta una copia para que se restaure | A08:2025 Software or Data Integrity Failures | `restaurar.sh` | MDC de OpenPGP. Pasada de comprobación completa en el PC (gpg con código 0, `gzip` válido y `-- Dump completed`) antes de confirmar y de enviar nada al servidor, con un mensaje distinto por causa (CA9) | `ConfiguracionProduccionTest::test_restaurar_comprueba_antes_de_borrar`, `…_restaurar_distingue_los_rechazos` · ensayo: clave equivocada, copia alterada y copia cortada, cada una con su mensaje y la base intacta |
| TM4 | Elevation / disclosure: la clave secreta o su frase llegan al servidor (al restaurar o por descuido) y quien esté dentro, o entre después, las captura y lee todas las copias | A04:2025 | `restaurar.sh`, `verificar.sh` | Descifrado solo en el PC: por SSH solo viaja el volcado; ningún script del servidor importa claves ni pide la frase. `verificar.sh` comprueba que el llavero del servidor no tiene claves secretas (D3, decisión del usuario 2026-10-25, /analyze A5) | `ConfiguracionProduccionTest::test_la_clave_nunca_llega_al_servidor` · ensayo: `gpg --list-secret-keys` vacío en el servidor durante y después de restaurar |
| TM5 | Spoofing: alguien prueba contraseñas en ráfaga | A07:2025 Authentication Failures | `RegistrarFalloAcceso`, `Vigilante` | Aviso con más de 10 fallos en 5 min por origen o por cuenta; el limitador de la 030 no cambia (CA2) | `RegistroFallosAccesoTest`, `VigilanteTest::test_mas_de_10_fallos_por_origen_avisa`, `…_por_cuenta_avisa`, `LimiteIntentosTest` |
| TM6 | Denial of service: un atacante inunda el buzón para que se ignoren los avisos | A09:2025 Security Logging and Alerting Failures | `Vigilante` | Como mucho un correo por tipo cada 30 min, con el acumulado. Nada se pierde: lo pendiente se envía al acabar el silencio (CA4) | `VigilanteTest::test_silencio_de_30_minutos_acumula`, `…_envia_lo_pendiente_al_acabar_el_silencio` |
| TM7 | Information disclosure: el correo, en un buzón externo, lleva datos personales o secretos | A09:2025 | `AvisoProduccion` | Solo tipo, recuentos, horas y órdenes de consulta; las entradas del log nunca pasan al correo (CA5) | `AvisoProduccionTest::test_el_correo_no_lleva_datos_personales_ni_secretos` |
| TM8 | Information disclosure: el registro de fallos guarda IPs y correos | A09:2025 | `RegistrarFalloAcceso` | Huellas HMAC recortadas, con una clave derivada de `APP_KEY`; sin IP, correo ni id. Es seudonimización: con la `APP_KEY`, que está en el mismo servidor, una IPv4 se recupera por fuerza bruta y una cuenta enumerando ids. Se acepta porque quien tiene la `APP_KEY` ya tiene la base (/analyze A27) | `RegistroFallosAccesoTest::test_el_registro_no_contiene_ip_ni_correo` |
| TM9 | Information disclosure: secretos en la salida de `verificar.sh` o en el repo | A02:2025 Security Misconfiguration | `verificar.sh`, `.env.example` | Comprobaciones sin valores; `app.env` y `*.asc` fuera del clon (CA6, RNF) | `ConfiguracionProduccionTest::test_verificar_no_imprime_valores_del_correo` · gitleaks |
| TM10 | Denial of service: los avisos dejan de funcionar sin que nadie lo note (temporizador parado, contraseña de Gmail revocada) | A09:2025 | `verificar.sh`, `Vigilar`, UptimeRobot | `verificar.sh` comprueba el temporizador y la configuración en cada despliegue. Un envío fallido sale con código distinto de 0 (journal) y deja `Log::warning`. El aviso de prueba está documentado y UptimeRobot es independiente del servidor | `VigilanteTest::test_un_envio_fallido_no_marca_el_aviso_como_enviado`, `AvisoProduccionTest::test_vigilar_sale_con_error_si_falla_el_envio` · ensayo de `verificar.sh` rompiendo |
| TM11 | Tampering / DoS: volver a una versión anterior a la 032 con una copia cifrada deja la base a medias | A08:2025 | `restaurar.sh --volver`, `volver.sh --sin-levantar` | La copia se comprueba entera en el PC antes de cambiar nada en el servidor. La carga usa órdenes que no dependen de la versión de destino (D3, D4) | `ConfiguracionProduccionTest::test_volver_con_copia_no_depende_de_la_version_de_destino` · ensayo de vuelta con copia |
| TM12 | Denial of service: un log de acceso enorme hace que el vigilante tarde o falle | A09:2025 | `Vigilante` | Lectura incremental desde el desplazamiento guardado, con un tope de 50 MiB por ejecución. Sin estado empieza al final del log. Si el log se trunca o rota, vuelve a empezar (/analyze A10) | `VigilanteTest::test_solo_lee_lo_nuevo`, `…_detecta_log_truncado`, `…_primera_ejecucion_empieza_al_final`, `…_lo_que_supera_el_tope_se_lee_despues` |
| TM13 | Information disclosure: la contraseña de aplicación de Gmail, en `app.env` y en el contenedor `app`, da acceso al buzón de su cuenta | A07:2025 Authentication Failures | `app.env`, guía (D9) | La cuenta es de Gmail, solo sirve para enviar y no tiene otros datos ni reenvíos. Los avisos llegan al correo de siempre del responsable. La contraseña de aplicación se revoca sin tocar nada más (decisión del usuario 2026-10-25, /analyze A6) | nota de T040 (cuenta dedicada creada, sin valores) · revisión de la guía |
| TM14 | Information disclosure: el log de acceso, con búsquedas que llevan datos personales en la URL (`docs/observability.md`, registro de acceso), se lee desde `app` | A09:2025 | montaje de `nginx_logs` (D10), `Vigilante` | Montaje en solo lectura. `app` ya tiene la base con esos datos. El vigilante solo guarda hora y código: ni el estado ni el correo llevan la petición (/analyze A7) | `VigilanteTest::test_el_estado_no_guarda_peticiones` · `AvisoProduccionTest::test_el_correo_no_lleva_datos_personales_ni_secretos` |
| TM15 | Tampering: un usuario de la cabecera Basic o una petición con espacios y corchetes falsea la hora o el código leídos | A09:2025 | `Vigilante` | Expresión anclada a la estructura de `combined`: nginx escapa las comillas (`escape=default`), así que el código es el campo que sigue a la primera cadena entre comillas y la hora es el último `[…]` antes de ella (/analyze A7) | `VigilanteTest::test_no_se_engana_con_corchetes_ni_espacios` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: >1 % de 5xx en 5 min → correo en ≤ 10 min con tipo, recuento, intervalo y cómo consultar | `Vigilante`, `Vigilar`, temporizador, montaje de `nginx_logs`, `AvisoProduccion` | `VigilanteTest::test_mas_del_1_por_ciento_de_5xx_avisa`, `…_el_1_por_ciento_no_avisa`, `…_ventana_de_5_minutos`, `…_no_se_engana_con_corchetes_ni_espacios`, `…_primera_ejecucion_empieza_al_final`, `…_lo_que_supera_el_tope_se_lee_despues` · verificación manual: ensayo en el servidor con líneas 5xx inyectadas en el log de `web`; el correo llega en ≤ 10 min |
| CA2 (abuso): >10 fallos en 5 min por origen o cuenta (contraseña o Google) → correo; el límite no cambia | `RegistrarFalloAcceso`, `AuthController::sujetoLimite`, canal `seguridad`, `Vigilante` | `RegistroFallosAccesoTest` (contraseña, Google, `Lockout`, cuenta inactiva, misma huella en `Failed` y `Lockout`; éxito sin línea), `VigilanteTest::test_mas_de_10_fallos_por_origen_avisa`, `…_por_cuenta_avisa`, `…_10_fallos_no_avisa`, `…_lee_lineas_con_extra`, `LimiteIntentosTest` (sin cambios) · verificación manual: 11 intentos contra una cuenta de prueba en producción; el correo llega en ≤ 10 min |
| CA3: caída de 5 min desde internet, también con el servidor apagado → correo en ≤ 10 min, y otro al volver | UptimeRobot (D13), `deploy/README.md` | verificación manual: monitor HTTPS de `/up` cada 5 min con avisos al caer y al volver; ensayo con `cloudflared` parado ≥ 6 min (con aprobación), los dos correos llegan |
| CA4 (abuso): como mucho 1 correo por tipo cada 30 min, con el acumulado; nada se pierde | `Vigilante`, `Estado` | `VigilanteTest::test_silencio_de_30_minutos_acumula`, `…_envia_lo_pendiente_al_acabar_el_silencio`, `…_los_tipos_tienen_silencio_propio`, `…_cada_evento_se_cuenta_una_vez` |
| CA5 (abuso): el correo no lleva datos personales, de salud, correos, contraseñas, IPs ni secretos | `AvisoProduccion`, plantilla de texto | `AvisoProduccionTest::test_el_correo_no_lleva_datos_personales_ni_secretos`, `…_lleva_tipo_recuentos_horas_y_como_consultar` |
| CA6: `verificar.sh` falla sin envío configurado o con el texto de ejemplo, sin mostrar valores; aviso de prueba en ≤ 5 min | `verificar.sh` (D12), `Vigilar --prueba`, guía | `ConfiguracionProduccionTest::test_verificar_comprueba_el_envio_de_avisos` (con `MAIL_FROM_ADDRESS`), `…_no_imprime_valores_del_correo`, `AvisoProduccionTest::test_el_aviso_de_prueba_se_envia` · verificación manual: `verificar.sh` rompiendo (sin `MAIL_PASSWORD`, con el `<…>` de la guía en `MAIL_PASSWORD` y en `MAIL_FROM_ADDRESS`) da FALLO sin valores; el aviso de prueba llega en ≤ 5 min |
| CA7: la copia previa al despliegue se cifra desde el primer momento (también la del PC); sin gpg o sin clave, el despliegue se detiene antes de cambiar nada | `respaldar.sh` (D2), primer despliegue de la 032 (D6) | `ConfiguracionProduccionTest::test_respaldar_cifra_con_la_clave_publica`, `…_no_escribe_en_claro`, `…_se_detiene_sin_gpg_o_sin_clave` · verificación manual: `desplegar.sh` deja `.sql.gz.gpg` y `find / -xdev` no encuentra volcados en claro; varias copias seguidas sin falsos «copia incompleta»; con la clave pública renombrada, `desplegar.sh <ref distinta del HEAD>` se detiene en «Copia de la base» y el `HEAD` no cambia; en el primer despliegue, el paso 0 del Rollout comprueba gpg y la clave antes del checkout |
| CA8 (abuso): lo que abre las copias no está en el servidor, el repo ni el paquete | D1, D3, guía §7 | `ConfiguracionProduccionTest::test_los_archivos_de_clave_quedan_fuera_del_paquete_y_del_repo`, `…_la_clave_nunca_llega_al_servidor`, `verificar.sh` «el llavero del servidor no tiene clave secreta» · lint: gitleaks · verificación manual: con solo lo que hay en el servidor no se puede descifrar; tras restaurar, el servidor sigue sin claves secretas; la frase cumple el mínimo de la guía (nota de los preparativos, sin revelarla) |
| CA9: con la clave, la base queda exactamente igual (filas y comprobación por tabla), ensayado, también con la vuelta atrás; clave ajena, copia alterada o incompleta → se detiene sin tocar la base y dice por qué (reemplaza 030 CA6) | `restaurar.sh` (D3), `volver.sh` (D4), `desplegar.sh` | `ConfiguracionProduccionTest::test_restaurar_comprueba_antes_de_borrar`, `…_restaurar_distingue_los_rechazos`, `…_volver_con_copia_no_depende_de_la_version_de_destino`, `…_desplegar_imprime_la_vuelta_del_contrato` · verificación manual: ensayo con foto de `CHECKSUM TABLE` y recuento de filas con `app` y `web` parados, copia justo después, restauración y comparación; los tres rechazos con su mensaje y la base intacta; `restaurar.sh --volver v0.10.0` con la copia y la frase pedida una sola vez |
| CA10: sin copias en claro en el servidor al liberar; la guía cubre las del PC, la segunda copia de la clave y su pérdida; el disco, riesgo aceptado hasta el primer dato real | `cifrar-copias-antiguas.sh` (D5), `verificar.sh`, `deploy/README.md`, `docs/security.md` (EX3, RS15.c) | `ConfiguracionProduccionTest::test_cifrar_copias_antiguas_cifra_antes_de_borrar` (también las dañadas), `…_verificar_falla_con_copias_en_claro` · verificación manual: `verificar.sh` OK y `find / -xdev` sin volcados en claro tras el script en la liberación; revisión de la guía y de EX3 |
| RNF: gratuito | UptimeRobot gratuito, cuenta de Gmail de envío, GnuPG del sistema, Mail de Laravel | verificación manual: sin planes de pago (revisión de la guía) |
| RNF: correos en español y comprensibles sin abrir el servidor | `AvisoProduccion` | `AvisoProduccionTest::test_lleva_tipo_recuentos_horas_y_como_consultar` |
| RNF: la detección no ralentiza la aplicación | `RegistrarFalloAcceso` (solo en fallos, una línea), vigilante fuera de las peticiones | verificación manual (el RNF no tiene métrica; /analyze A26): el listener no se ejecuta en peticiones correctas (`RegistroFallosAccesoTest`, éxito sin línea) y el vigilante corre en el temporizador, fuera de las peticiones |
| RNF: ningún secreto en el repo, el paquete ni la salida de la verificación | `app.env`, `verificar.sh`, `.dockerignore` | gitleaks · `ConfiguracionProduccionTest::test_verificar_no_imprime_valores_del_correo`, `…_los_archivos_de_clave_quedan_fuera_del_paquete_y_del_repo` |
| TM1: copia robada | `respaldar.sh`, guía | `ConfiguracionProduccionTest::test_respaldar_cifra_con_la_clave_publica` · `verificar.sh` sin clave secreta |
| TM2: volcado en claro durante el cifrado | `respaldar.sh` | `ConfiguracionProduccionTest::test_respaldar_no_escribe_en_claro` |
| TM3: copia sustituida, alterada o cortada | `restaurar.sh` | `ConfiguracionProduccionTest::test_restaurar_comprueba_antes_de_borrar`, `…_restaurar_distingue_los_rechazos` · ensayo de rechazos |
| TM4: clave secreta o frase en el servidor | `restaurar.sh`, `verificar.sh` | `ConfiguracionProduccionTest::test_la_clave_nunca_llega_al_servidor` · ensayo: el servidor sin claves secretas |
| TM5: contraseñas en ráfaga | `RegistrarFalloAcceso`, `Vigilante` | `RegistroFallosAccesoTest`, `VigilanteTest::test_mas_de_10_fallos_por_origen_avisa`, `LimiteIntentosTest` |
| TM6: buzón inundado | `Vigilante` | `VigilanteTest::test_silencio_de_30_minutos_acumula` |
| TM7: datos en el correo | `AvisoProduccion` | `AvisoProduccionTest::test_el_correo_no_lleva_datos_personales_ni_secretos` |
| TM8: IPs y correos en el registro de fallos | `RegistrarFalloAcceso` | `RegistroFallosAccesoTest::test_el_registro_no_contiene_ip_ni_correo` |
| TM9: secretos en la salida o en el repo | `verificar.sh`, `.gitignore` | `ConfiguracionProduccionTest::test_verificar_no_imprime_valores_del_correo` · gitleaks |
| TM10: avisos caídos sin que se note | `verificar.sh`, `Vigilar` | `VigilanteTest::test_un_envio_fallido_no_marca_el_aviso_como_enviado`, `AvisoProduccionTest::test_vigilar_sale_con_error_si_falla_el_envio` · `verificar.sh` rompiendo |
| TM11: vuelta atrás con copia cifrada a medias | `restaurar.sh --volver`, `volver.sh --sin-levantar` | `ConfiguracionProduccionTest::test_volver_con_copia_no_depende_de_la_version_de_destino` |
| TM12: log de acceso enorme | `Vigilante` | `VigilanteTest::test_solo_lee_lo_nuevo`, `…_detecta_log_truncado`, `…_lo_que_supera_el_tope_se_lee_despues` |
| TM13: contraseña de aplicación de Gmail filtrada | cuenta de envío dedicada (D9), guía | verificación manual: nota de los preparativos (cuenta dedicada, sin valores) y revisión de la guía |
| TM14: log de acceso con datos de búsquedas leído desde `app` | montaje `:ro` (D10), `Vigilante` | `VigilanteTest::test_el_estado_no_guarda_peticiones` · `AvisoProduccionTest::test_el_correo_no_lleva_datos_personales_ni_secretos` |
| TM15: líneas del log de acceso que falsean hora o código | `Vigilante` | `VigilanteTest::test_no_se_engana_con_corchetes_ni_espacios` |

## Observabilidad
- **Logs nuevos:**
  - canal `seguridad`, `info`: una línea por fallo o bloqueo de inicio de sesión (Contratos);
  - `Log::warning('aviso no enviado', ['tipo' => …])` en `laravel.log` cuando falla el SMTP, sin
    el mensaje de la excepción, que puede llevar el usuario de SMTP.
  Ninguno tiene `request_id`, que no existe hasta la 020: P8 queda como «❌ aceptado» hasta
  liberarla (Constitution Check, /analyze A4).
- **Eventos de auditoría:** ninguno (P7 ➖, sección Auditoría de la spec).
- **Métricas y alertas** (`docs/observability.md` pasa de propuestas a en uso):
  - tasa de 5xx > 1 % en 5 min;
  - fallos de acceso > 10 en 5 min por origen o cuenta;
  - caída vista desde fuera durante 5 min.
  El canal de todas es el correo del responsable. La latencia p95 sigue fuera (roadmap).
- **Verificación tras el despliegue:**
  - `verificar.sh`, sin `--interna`, con la sección «Avisos y copias» en OK;
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
  1. Par de claves en el PC, con una frase que cumpla el mínimo de la guía (D14), y segunda copia
     de la secreta en un lugar seguro.
  2. Comprobar en el PC que la frase se pide una sola vez para dos descifrados seguidos (D3).
  3. `copias.pub.asc` en `/opt/dentissa/entorno` (600).
  4. Cuenta de Gmail solo para enviar, con verificación en dos pasos y contraseña de aplicación, y
     las variables de correo en `app.env` (D9).
  5. Cuenta de UptimeRobot.
- **Orden del despliegue (D6):**
  0. Antes de cambiar nada, en el servidor: `command -v gpg` y
     `gpg --show-keys /opt/dentissa/entorno/copias.pub.asc` sin `sec`. Si algo falla, no se sigue
     (CA7, /analyze A15).
  1. `git fetch --tags && git checkout <tag>` antes de lanzar el despliegue, para que la copia
     previa la haga ya el `respaldar.sh` nuevo, cifrada.
  2. `bash deploy/desplegar.sh <tag>`. Su `verificar.sh --interna` no incluye la sección «Avisos y
     copias» (D12, /analyze A3).
  3. `bash deploy/preparar-vigilancia.sh`.
  4. `bash deploy/cifrar-copias-antiguas.sh`.
  5. `bash deploy/verificar.sh`, completo.
  6. Aviso de prueba.
  7. Monitor de UptimeRobot.
  8. En el PC: sustituir y borrar las copias en claro.
- **Compatibilidad:** sin migraciones. La versión anterior convive con la clave pública y las
  variables nuevas de `app.env`, que ignora.
- **Rollback:**
  1. En el servidor, `bash deploy/volver.sh <anterior>`, sin copia porque no hay migraciones.
  2. Después, `sudo systemctl disable --now dentissa-vigilar.timer` (lo recuerda `volver.sh`).
  Las copias siguen cifradas. Se restauran desde el PC con el `restaurar.sh` del clon del PC, que no
  depende de la versión instalada en el servidor (D3).
- **Métricas a vigilar:**
  - durante la primera hora, ningún correo inesperado y el journal del temporizador sin errores
    (`journalctl -u dentissa-vigilar`);
  - 0 5xx nuevos.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Cifrado de copias → [ADR 0007](../../adr/0007-copias-cifradas-y-avisos.md)** (accepted;
  también recoge D8, D9 y D13). OpenPGP asimétrico con GnuPG y la clave pública del
  responsable (`--recipient-file`, `--trust-model always`), sin llavero en el servidor.
  - Alternativas descartadas:
    - `age`: sería una dependencia nueva en el servidor y en el PC;
    - `openssl enc` simétrico: la clave tendría que estar en el servidor, lo que incumple CA8;
    - cifrar con `restic`/`borg`: otra herramienta y otro formato.
  - Comprobado con una prueba de concepto el 2026-10-23 (Enfoque).
  - El ADR se ajusta en esta corrección a la restauración desde el PC y a la cuenta de envío
    (A5, A6).
- **D2 · `respaldar.sh`.**
  - Primero comprueba `gpg --version` y la clave pública con `gpg --show-keys`: si algo falla, se
    detiene antes de volcar, y por tanto antes del checkout de `desplegar.sh` (CA7).
  - La tubería es `mariadb-dump | awk '{print} END {print > marca}' | gzip | gpg …` hacia `.part`,
    con la marca en `/dev/shm`. El `awk` reenvía cada línea y escribe la última en la marca al
    terminar.
  - Al ser un miembro de la tubería, el script ya lo ha esperado cuando la tubería termina, a
    diferencia de una sustitución de procesos `>(…)`, que no se puede esperar (/analyze A8).
  - Con `pipefail`, comprueba `-- Dump completed` en la marca. Solo entonces hace `mv` a
    `.sql.gz.gpg` y `chmod 600`; si falla, borra `.part` y la marca.
  - La marca solo contiene la línea final del volcado, sin datos.
- **D3 · `restaurar.sh` en el PC: la clave no sale del PC** (decisión del usuario, 2026-10-25,
  /analyze A5). Sustituye a la decisión del 2026-10-23 de llevar la clave al servidor: un servidor
  comprometido la capturaría, y CA8 dice que lo que abre las copias no está en el servidor.
  - Se ejecuta en el PC con Git Bash, que trae `gpg` y `ssh`. La copia se baja antes con `scp`, y
    viaja cifrada.
  - **Pasada 1, de comprobación**, en el PC:
    - `gpg --status-fd` con `--decrypt | gzip -cd | tail -n 1` debe salir con código 0 y dar
      `-- Dump completed`;
    - por las líneas de estado de gpg y el resultado de `gzip`, distingue los tres mensajes de
      Contratos (/analyze A13);
    - se hace antes de pedir la confirmación y antes de enviar nada al servidor.
    - `--solo-comprobar` termina aquí.
  - **Confirmación:** se escribe el nombre de la base, que se lee por SSH (`printenv
    MARIADB_DATABASE` en `db`).
  - **Pasada 2:**
    - para `app` y `web`;
    - por SSH, `DROP`/`CREATE`;
    - `gpg --decrypt` del PC → `ssh` → `gzip -cd | docker compose … exec -T db mariadb` en el
      servidor;
    - levanta `app` y `web`, rehace las cachés y ejecuta el `verificar.sh --interna` instalado.
    Las órdenes van dentro del script del PC y existen desde la 030, así que no dependen de la
    versión instalada (/analyze A2). La frase se pide una sola vez: el `gpg-agent` del PC la guarda
    entre las dos pasadas, y se comprueba en los preparativos.
  - **`--volver <ref>`:** antes de la pasada 2, `ssh … bash deploy/volver.sh <ref> --sin-levantar`
    con el `volver.sh` instalado, que es el de la 032 o uno posterior. Luego carga y levanta como
    arriba, con la versión `<ref>` ya construida (/analyze A1).
  - Si la carga se corta (por ejemplo, porque se cae la red), la base queda a medias, como en la
    030 (/review R8). El mensaje da la misma orden para repetirla, porque la copia sigue intacta en
    el PC.
  - Ni la clave ni la frase cruzan el SSH. El volcado sí cruza en claro dentro del canal cifrado de
    SSH y no se escribe en el disco del servidor.
- **D4 · `volver.sh`.**
  - Sin copia: igual que en la 030. La forma `volver.sh <ref> <copia>` se rechaza antes de cambiar
    nada, con la orden de `restaurar.sh --volver` (/analyze A17).
  - `--sin-levantar`: checkout, construcción, `app` y `web` parados, `db` sana, y fin.
  - Como en la 030, todo va en `main()`, que el checkout no cambia a mitad.
  - Si la versión de destino no tiene `app/Console/Commands/Vigilar.php`, avisa de parar el
    temporizador.
  - `desplegar.sh` imprime, si la verificación falla:
    - `bash deploy/volver.sh <anterior>` si no hubo migraciones;
    - si las hubo, la orden del PC: `scp <servidor>:<copia> .` y
      `bash deploy/restaurar.sh <copia> <servidor> --volver <anterior>`.
- **D5 · Copias en claro existentes: cifrarlas y borrar las originales** (decisión del usuario,
  2026-10-23).
  - `cifrar-copias-antiguas.sh` comprueba cada `dentissa-*.sql.gz` (`gzip -t` y `-- Dump
    completed`) y la cifra hacia `.part`.
  - Hace `mv` a `.sql.gz.gpg` conservando la fecha (`touch -r`) y borra la original con `shred -u`.
    En ext4 con journal, `shred` no garantiza el borrado físico: es el residuo del disco sin cifrar
    (RS15.c, EX3).
  - Una copia dañada también se cifra, como `.sql.gz.danada.gpg`, y se borra la original, así que
    no queda nada en claro (CA10). El script informa de ella para que el responsable decida si la
    borra; `respaldar.sh` no la cuenta entre las 14 (/analyze A16).
  - Busca en `/var/backups/dentissa`. La guía y T097 revisan además, con `find / -xdev`, los
    volcados (`*.sql`, `*.sql.gz`) que pudieran quedar de los ensayos de la 030 en la carpeta
    personal de la cuenta de despliegue o en `/tmp`.
- **D6 · Primer despliegue de la 032.** `desplegar.sh` hace la copia con el `respaldar.sh` de la
  versión instalada antes del checkout, así que el de v0.10.0 la haría en claro.
  - En esta liberación, el checkout del tag se hace antes de `desplegar.sh` (Rollout). Así la copia
    previa ya sale cifrada (CA7).
  - El paso 0 del Rollout comprueba gpg y la clave pública antes de ese checkout. Así, «se detiene
    antes de cualquier cambio» de CA7 también se cumple en el primer despliegue (/analyze A15).
  - El inconveniente es que `desplegar.sh` informa como «versión anterior» el propio tag. La guía y
    `/release` dan el comando de vuelta con la versión anterior real.
  - El problema general («cada despliegue usa el `desplegar.sh` de la versión anterior») sigue en
    el roadmap.
- **D7 · Fallos de acceso en un canal propio.** `App\Listeners\RegistrarFalloAcceso`, descubierto
  automáticamente, escucha `Failed` (contraseña y Google) y `Lockout` (login y Google).
  - Canal `seguridad` `daily` con 14 días y `JsonFormatter` (forma de la línea en Contratos).
  - Contiene huellas HMAC, no IPs ni correos (Contratos; P8).
    - La clave se deriva de `APP_KEY` y no es la `APP_KEY` misma.
    - Es seudonimización, aceptada en TM8 (/analyze A27).
  - **Regla común de la cuenta** (/analyze A12): la parte de `claveLimiteLogin` que elige
    `cuenta:<id>` o `correo:<minúsculas>` pasa a `AuthController::sujetoLimite(string $email)`,
    pública y estática como `origenLimite`. La usan el limitador y el listener.
    - El correo sale de `Failed::$credentials['email']` o, en `Lockout`, del campo `email` de la
      petición.
    - En la vuelta de Google no hay correo y `cuenta` es `null`.
    - La consulta busca la cuenta por correo sin mirar `activo`, como el limitador, así que una
      cuenta inactiva da su huella de cuenta.
  - Alternativas descartadas:
    - deducir los fallos del log de nginx: la respuesta es la misma redirección 302 con éxito y con
      fallo;
    - una tabla nueva: exige una migración y no es la auditoría de la 021.
  - `Lockout` cuenta como fallo: también es un intento rechazado.
- **D8 · Vigilante en PHP (`app/Avisos`) y comando `dentissa:vigilar`** (decisión del usuario,
  2026-10-23).
  - Cada minuto:
    1. **Log de acceso:** lee lo nuevo desde el desplazamiento guardado.
       - Tope de 50 MiB por ejecución; lo que no cabe se lee en la siguiente.
       - Vuelve a empezar si el inodo cambia o el tamaño baja.
       - Sin estado, empieza al final del log (/analyze A10).
       - Acumula total y 5xx por minuto.
       - Cada línea se interpreta con una expresión anclada a la estructura de `combined`: nginx
         escapa las comillas (`escape=default`), así que el código es el número que sigue a la
         primera cadena entre comillas y la hora es el último `[…]` antes de ella. Lo que no encaja
         se descarta (/analyze A7).
    2. **Canal `seguridad`:** lee las líneas de hoy y de ayer dentro de los últimos 5 min (campos
       `datetime` y `context`, ignorando `extra`) y las agrupa por origen y por cuenta.
    3. **Umbrales:** evalúa los de la spec sobre la ventana deslizante de 5 min.
    4. **Silencio:** 30 min por tipo de aviso (`errores`, `accesos`).
       - Cada aviso informa los eventos distintos ocurridos desde el fin del intervalo del aviso
         anterior, cada uno contado una sola vez (/analyze A9).
       - Lo pendiente se envía al acabar el silencio, aunque la condición haya terminado.
  - El estado solo guarda desplazamientos, contadores y momentos, nunca peticiones (TM14).
  - El reloj y las rutas se inyectan para los tests.
  - Un envío fallido no actualiza el «último envío»: se reintenta al minuto siguiente, y el comando
    sale con código distinto de 0.
  - Alternativa descartada: `deploy/vigilar.sh` con `curl` SMTP, que no se puede probar con
    PHPUnit.
- **D9 · Correo con el Mail de Laravel por el SMTP de Gmail** con contraseña de aplicación
  (decisión del usuario, 2026-10-23), **desde una cuenta solo para enviar** (decisión del usuario,
  2026-10-25, /analyze A6).
  - Una contraseña de aplicación da acceso a todo el buzón de su cuenta (TM13). Por eso la cuenta
    es nueva:
    - sin otros datos ni reenvíos;
    - con verificación en dos pasos;
    - solo envía a `AVISOS_CORREO`, el correo de siempre del responsable.
  - `smtp.gmail.com:587` con STARTTLS; `MAIL_FROM_ADDRESS` = la cuenta de envío.
  - `app` ya tiene salida a internet, que usa la vuelta de Google.
- **D10 · `nginx_logs` en solo lectura en `app`** (ruta protegida `docker-compose.prod.yml`,
  aprobada por el usuario el 2026-10-23). Es la única forma de que el vigilante cuente las
  respuestas que da nginx, también las 502/504 que no pasan por Laravel.
  - El log tiene búsquedas con datos personales en la URL: TM14. Esta spec no cambia lo que se
    registra en él.
  - `www-data` tiene que poder leerlo. Lo comprueba `verificar.sh` (D12) y, si los permisos del
    log de nginx no lo permiten, se ajustan en `/implement` dentro de esta misma decisión.
- **D11 · Temporizador de systemd.** `deploy/preparar-vigilancia.sh` (con `sudo`) instala
  `dentissa-vigilar.service`.
  - Es de tipo `oneshot` y corre con la cuenta de despliegue:
    `docker compose -f /opt/dentissa/app/docker-compose.prod.yml exec -T -u www-data app php artisan dentissa:vigilar`.
  - Lo lanza `dentissa-vigilar.timer` (`OnCalendar=minutely`), con `enable --now`.
  - Un `oneshot` no se solapa consigo mismo.
  - Se descarta el planificador de Laravel, que hoy necesitaría un proceso más fuera del
    contenedor.
  - **Cuando llegue la 020**, que añade un servicio `scheduler` con `schedule:work`,
    `dentissa:vigilar` pasa a ese planificador (`everyMinute()->withoutOverlapping()`) y el
    temporizador se retira, para no tener dos mecanismos. Se anota en el roadmap y en la 020
    (/analyze A20).
- **D12 · `verificar.sh`.**
  - Al bloque de configuración se añaden 4 comprobaciones (12 → 16):
    - `MAIL_MAILER=smtp`;
    - `MAIL_HOST` y `MAIL_PORT` definidos;
    - `MAIL_USERNAME` y `MAIL_PASSWORD` definidos y sin `<…>`;
    - `MAIL_FROM_ADDRESS` y `AVISOS_CORREO` con formato de correo y sin `<…>` (/analyze A28).
  - Sección nueva «Avisos y copias (spec 032)». Solo corre sin `--interna`: así `desplegar.sh` y
    `volver.sh` pasan antes de instalar el temporizador y de cifrar las copias antiguas, y el paso 5
    del Rollout la comprueba completa (/analyze A3). Comprueba:
    - temporizador activo;
    - clave pública válida, con `gpg --show-keys` sin `sec`;
    - el llavero de la cuenta de despliegue sin claves secretas;
    - ninguna `dentissa-*.sql.gz` en `/var/backups/dentissa` ni en la carpeta personal de la
      cuenta de despliegue;
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
  - **Frase:** como mínimo 5 palabras al azar o 20 caracteres, y no se usa en ningún otro sitio. En
    el PC están juntas las copias y la clave, así que una copia robada del PC solo la protege la
    frase (/analyze A19).
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
  - Al retomar la 038, su Rollout ya no puede volver a v0.10.0 sin cruzar la 032 (temporizador,
    copias cifradas). Su vuelta pasa a ser a la versión de la 032 (v0.11.0); se anota al retomarla
    (/analyze A25).
- **Correcciones de riesgos:**
  - OB6.b dentro, en producción; OB6.c (staging), fuera.
  - RS15.b dentro, en las copias de producción.
  - RS15.c (disco de producción): excepción **EX3**, que vence al cargar el primer dato real de
    pacientes y la aprueba el responsable en `/release`.
  - RS15.d (staging), fuera. RS15.a sigue en la 013.
  - OB6.a, fuera (objetivo 3).
  - Con esta spec, OB6 y RS15 no quedan mitigados del todo.

## Impacto en arquitectura
Secciones de `docs/architecture.md` que hay que actualizar en T090 (/analyze A23):
- **Diagrama de componentes:** temporizador → `dentissa:vigilar` en `app`; `nginx_logs` también
  en `app`; salida SMTP a Gmail; UptimeRobot hacia `/up`.
- **Módulos:** `App\Avisos` (vigilante, estado, correo) y `App\Listeners\RegistrarFalloAcceso`.
- **Integraciones externas:** Gmail SMTP (cuenta de envío) y UptimeRobot. Desaparece «No hay
  correo…».
- **Despliegue:** copias `.sql.gz.gpg`, restauración y vuelta con copia desde el PC,
  `preparar-vigilancia.sh` y `cifrar-copias-antiguas.sh`.

Otros documentos que hay que actualizar:
- `docs/observability.md`: alertas en uso, canal `seguridad`, OB6 dividida.
- `docs/security.md`:
  - **Superficie de ataque:** el temporizador del vigilante es una tarea programada; desaparece
    «ni tareas programadas». Salida SMTP.
  - **Manejo de secretos:** contraseña de aplicación de la cuenta de envío, clave pública en el
    servidor, clave secreta solo en el PC y su segunda copia, y fila de copias.
  - **Excepciones aceptadas:** EX3.
  - **Riesgos conocidos:** RS15 dividida.
- `docs/deployment.md`: copias, restauración desde el PC, avisos y verificación.
- Specs afectadas, anotadas en `/release` (/analyze A24):
  - **030:**
    - CA6 → reemplazado por 032 CA9;
    - en «Fuera de alcance», «Envío de correos desde producción» → atendido por la 032;
    - en Cobertura, los destinos de RS15.b y OB6.b → 032.
  - **001 CA9:** el canal `seguridad` registra los fallos para avisar, pero no es el registro de
    auditoría; registrarlos sigue en la 020/021.

## Riesgos y mitigaciones
- **Perder la clave secreta deja las copias inservibles.** Mitigación: segunda copia obligatoria
  en la guía, que se comprueba en el ensayo de restauración con esa segunda copia.
- **Falsos avisos con poco tráfico:** un 5xx entre 10 peticiones ya es un 10 %. La spec no fija un
  mínimo de peticiones. Riesgo aceptado por el usuario el 2026-10-25 (/analyze A26): si molesta,
  el umbral se ajusta en otra spec, y el silencio de 30 min limita el ruido.
- **Google puede revocar la contraseña de aplicación** si cambia la contraseña de la cuenta de
  envío. Mitigación: `verificar.sh` no lo detecta (no envía), pero el aviso de prueba sí. El journal
  del temporizador registra el fallo y UptimeRobot no depende de ello.
- **`shred` no borra físicamente en ext4 con journal.** Residuo aceptado en EX3.
- **El primer despliegue depende de seguir el orden de D6.** Mitigación: `/release` da los
  comandos exactos, con el paso 0, y comprueba con `find` que no hay claro.
- **La restauración necesita el PC y SSH al servidor.** Con el PC perdido, la segunda copia de la
  clave se importa en otro equipo con gpg y SSH (guía §7).
- **Una carga cortada por la red deja la base a medias**, como en la 030. Mitigación: la copia sigue
  en el PC y el mensaje da la orden para repetir.
- **Rotar `APP_KEY` cambia las huellas** y reinicia la agrupación de fallos durante 5 min. Es
  aceptable.
