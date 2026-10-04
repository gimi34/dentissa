---
spec: 030-despliegue-servidor-propio
status: approved
created: 2026-10-03
---

# Plan · 030 Producción en un servidor propio expuesto a internet

## Enfoque técnico
Producción corre en el servidor del usuario (Ubuntu Server 24.04) con un Compose propio,
`docker-compose.prod.yml`, separado del de local. Tiene cuatro servicios:
- `app`: php-fpm, con la imagen de la aplicación construida con un Dockerfile de producción, sin
  código montado y sin dependencias de desarrollo;
- `web`: nginx, con `public/` copiado de la misma construcción;
- `db`: MariaDB, sin puertos publicados;
- `cloudflared`: el túnel saliente hacia Cloudflare, que es la única vía de entrada.

No se abre ningún puerto en el router y phpMyAdmin no existe en producción. nginx toma la IP real
del cliente de `CF-Connecting-IP` (`real_ip`, solo desde la red de Docker). Laravel se fía del proxy
para el protocolo y el host, pero nunca para la IP (prueba de concepto del 2026-10-03). Así el
límite de intentos (RS4) cuenta por cliente real y no se puede saltar con un `X-Forwarded-For`
falso.

El servidor se prepara y opera con scripts versionados en `deploy/`, que el usuario ejecuta por
SSH: preparar el servidor, desplegar, copia de seguridad, restaurar, volver a la versión anterior y
verificar. Los secretos viven en un archivo de entorno que solo existe en el servidor.

En la aplicación cambian tres cosas:
- `trustProxies` acotado;
- tres limitadores de intentos (login, alta y vuelta de Google);
- un comando que cambia las contraseñas de las cuentas del volcado.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 030 `approved` el 2026-10-03 (CA8 retocado en este plan, decisión del usuario). | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Constitución 1.2.1. Los criterios de comportamiento tienen tests PHPUnit: CA2 (HTTPS detrás del proxy), CA5 (comando de contraseñas) y CA8 (límites) fallan hoy. CA4 (error genérico) es de caracterización y se comprueba encendiendo la depuración. Las partes comprobables de CA3 y CA10 (sin puertos, sin phpMyAdmin, imágenes fijadas, `.dockerignore`) tienen tests estáticos que fallan hoy. Lo que solo existe en el servidor (CA1, CA6, CA7, CA9 y las partes de red de CA3) se verifica con comprobaciones declaradas en `deploy/verificar.sh` y en el ensayo, anotadas en su tarea. | test: `LimiteIntentosTest`, `ProxyHttpsTest`, `RotarCredencialesTest`, `ErroresProduccionTest`, `ConfiguracionProduccionTest` (fallo previo anotado) · manual: comprobaciones del servidor anotadas |
| P3 Autorización en servidor | ➖ | Login, alta y vuelta de Google son rutas públicas cuya autorización no cambia; solo se les añade un límite de intentos. No hay rutas nuevas con datos. | — |
| P4 Entrada validada y SQL con bindings | ✅ | Sin entradas nuevas. El comando usa Eloquent/Query Builder y la validación existente no cambia. | test: `RotarCredencialesTest` · lint: semgrep `p/php` |
| P5 Nada sensible en el repo | ✅ | Secretos (`APP_KEY`, contraseñas de la base, token del túnel, OAuth) solo en `/opt/dentissa/.env.production` en el servidor (permisos 600). `.dockerignore` deja fuera `.env*`, `.git` y `database/*.sql`. Las contraseñas nuevas se muestran una vez por consola y nunca se escriben en el repo ni en logs. | test: `ConfiguracionProduccionTest::test_el_paquete_excluye_secretos_y_sobrantes` · lint: gitleaks |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias PHP ni npm nuevas. Línea base del 2026-10-03: `composer audit` y `npm audit` con 0 avisos. Las imágenes se fijan por versión exacta. | lint: composer audit, npm audit y trivy fs en T040 |
| P7 Auditoría de datos sensibles | ➖ | La spec no crea eventos de auditoría: el registro aún no existe (spec 020); spec, Auditoría. | — |
| P8 Correlación y logs limpios | ✅ | Producción con `APP_DEBUG=false` y `LOG_LEVEL=info` (comprobado en cada despliegue). Sin logs nuevos; el comando de contraseñas no escribe en el log. | test: `ErroresProduccionTest`, `RotarCredencialesTest::test_no_escribe_las_contrasenas_en_el_log` · manual: `deploy/verificar.sh` |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Sin cambios de interfaz: los mensajes de límite se muestran con el bloque de errores que ya tienen las vistas de login y registro. | — |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `bootstrap/app.php` | `$middleware->trustProxies(at: '*', headers: X_FORWARDED_PROTO \| X_FORWARDED_HOST \| X_FORWARDED_PORT)`, **sin** `X_FORWARDED_FOR`. La IP del cliente es `REMOTE_ADDR`, que nginx toma de `CF-Connecting-IP` (D3) | Medio: en local, sin proxy, no cambia nada |
| `app/Providers/AppServiceProvider.php` | `RateLimiter::for('registro', …)` y `RateLimiter::for('google', …)`: 5 por minuto por IP, con respuesta que vuelve con el error de Contratos (alta: `back()->withErrors()->withInput()` sin contraseñas; Google: a `login` con el error) | Bajo |
| `routes/web.php` | `throttle:registro` en `POST /registro` y `POST /registro/contacto`; `throttle:google` en `GET /auth/google/callback` | Bajo |
| `app/Http/Controllers/AuthController.php` | En `login`, `RateLimiter` con clave `minúsculas(email)|ip`. Si se superan 5 fallos, rechaza sin intentar autenticar (con el mismo mensaje aunque la cuenta no exista); cada fallo suma con 60 s de espera y el éxito limpia la cuenta | Medio: toca el login |
| `app/Console/Commands/RotarCredencialesVolcado.php` (nuevo) | `dentissa:rotar-credenciales --confirmar`: a cada usuario con contraseña le pone una aleatoria de 20 caracteres y muestra la tabla correo → contraseña una sola vez por la salida estándar. Las cuentas solo de Google no cambian. Sin `--confirmar` no hace nada. Pensado para ejecutarse una vez en el primer despliegue, antes de abrir el túnel | Medio: cambia contraseñas; requiere confirmación |
| `docker/prod/Dockerfile` (nuevo) | Multietapa sobre imágenes fijadas: `app` (php-fpm con extensiones, `composer install --no-dev --optimize-autoloader`, código copiado y permisos de `storage`) y `web` (nginx con `public/` copiado de `app` y `dentissa.prod.conf`) | Medio |
| `docker/nginx/dentissa.prod.conf` (nuevo) | Como `dentissa.conf`, más `set_real_ip_from 172.16.0.0/12` y `real_ip_header CF-Connecting-IP`; `fastcgi_pass app:9000`; logs a `/var/log/nginx` en un volumen | Bajo |
| `docker-compose.prod.yml` (nuevo) | Los servicios `app`, `web`, `db` y `cloudflared` en una red interna, **sin `ports`**, con `restart: unless-stopped` y `env_file: /opt/dentissa/.env.production`. Volúmenes: `db_data`, `storage` (logs de Laravel y sesiones) y `nginx_logs`. `db` usa usuario propio (`MARIADB_USER`), carga el volcado solo la primera vez y la contraseña de root no se usa en la app. `local.ini` se monta desde `docker/nginx/php/local.ini`. Imágenes con versión exacta | Medio |
| `docker-compose.yml` | Montaje de `local.ini` corregido a `./docker/nginx/php/local.ini` (RD4.a) e imágenes fijadas por versión exacta (RS7.d), las mismas que en producción | Bajo: solo local |
| `Dockerfile` | `FROM php` y `composer` fijados a las mismas versiones exactas que `docker/prod/Dockerfile` | Bajo |
| `.dockerignore` | Excluye `.env*`, `.git`, `node_modules`, `vendor`, `database/*.sql`, `storage/*` (salvo la estructura), `tests`, `docs`, `.ai`, `aidlc`, `.claude` y `.github` | Bajo |
| `deploy/preparar-servidor.sh` (nuevo) | Se ejecuta una vez, con sudo, en el Ubuntu recién instalado: actualiza el sistema, activa `unattended-upgrades`, instala Docker Engine desde el repositorio oficial y configura `ufw` (deniega todo lo entrante y permite SSH solo desde la red local). SSH queda solo con clave (`PasswordAuthentication no`, `PermitRootLogin no`) **después** de comprobar que existe una clave autorizada. Crea `/opt/dentissa` y `/var/backups/dentissa` | Alto: puede dejar al usuario sin acceso si se ejecuta mal; el script lo comprueba antes |
| `deploy/respaldar.sh` (nuevo) | `mariadb-dump` de la base a `/var/backups/dentissa/dentissa-AAAAMMDD-HHMMSS.sql.gz` (permisos 600); conserva las 14 más recientes | Bajo |
| `deploy/restaurar.sh` (nuevo) | Restaura una copia en la base, previa confirmación escrita | Medio |
| `deploy/desplegar.sh` (nuevo) | `desplegar.sh <tag o commit>` (en producción, siempre el tag `vX.Y.Z`; un commit solo en el ensayo de D13): comprueba el árbol, hace la copia (`respaldar.sh`), `git fetch --tags` y `checkout` de la referencia, construye las imágenes, `up -d` de `app`, `web` y `db` (nunca arranca ni para `cloudflared`), `migrate --force`, `config:cache` y `route:cache`, y ejecuta `verificar.sh`. Si falla la verificación, lo indica y propone `volver.sh` | Medio |
| `deploy/volver.sh` (nuevo) | `volver.sh vX.Y.Z-anterior [copia]`: `checkout` del tag anterior, construcción y `up`; si hubo migración, restaura la copia indicada; después ejecuta `verificar.sh` | Medio |
| `deploy/verificar.sh` (nuevo) | Verificación posterior: `GET /up` por la dirección pública (200); `APP_DEBUG=false` y `APP_ENV=production` dentro de `app`; ningún puerto de Docker publicado (`docker compose ps`) ni escuchando fuera de `localhost`, salvo SSH (`ss -tlnp`); `upload_max_filesize` y `date.timezone` iguales a `local.ini`; HTTPS en la redirección de `/` | Bajo |
| `deploy/README.md` (nuevo) | Guía paso a paso: preparar el servidor, crear el túnel en Cloudflare (Zero Trust → Tunnels, hostname público → `http://web:80`), la deploy key de solo lectura en GitHub, `/opt/dentissa/.env.production`, primer despliegue, rotar credenciales, copia a tu PC con `scp`, volver atrás y registrar el redirect de Google | Bajo |
| `tests/Feature/LimiteIntentosTest.php` (nuevo) | CA8 | — |
| `tests/Feature/ProxyHttpsTest.php` (nuevo) | CA2 y TM4 | — |
| `tests/Feature/RotarCredencialesTest.php` (nuevo) | CA5 | — |
| `tests/Feature/ErroresProduccionTest.php` (nuevo) | CA4 | — |
| `tests/Feature/ConfiguracionProduccionTest.php` (nuevo) | Partes estáticas de CA3, CA5 y CA10 | — |
| `docs/adr/0005-produccion-servidor-propio-cloudflare-tunnel.md` (nuevo) | ADR de la plataforma (RD3.a) | — |
| `docs/deployment.md`, `docs/security.md`, `docs/observability.md`, `docs/architecture.md` | Entornos, cómo desplegar, copias, vuelta atrás, verificación y checklist; estados de los riesgos por corrección; logs | — |

## Contratos y datos
Sin migraciones ni cambios de esquema.

**Rutas afectadas** (sin cambios de autorización ni de respuesta en el caso normal):

| Ruta | Límite | Al superarlo |
|---|---|---|
| `POST /login` | 5 fallos por minuto por `email|ip`; el éxito limpia el contador | `back()` con el error en `email` |
| `POST /registro`, `POST /registro/contacto` | 5 peticiones por minuto por IP, en un contador común | `back()->withInput()` (sin contraseñas) con el error en `email` |
| `GET /auth/google/callback` | 5 peticiones por minuto por IP | redirección a `login` con el error en `email` |

**Mensaje** (el mismo en los tres casos, sin revelar si la cuenta existe; N = segundos que faltan):
"Demasiados intentos. Vuelve a intentarlo en N segundos."

**Comando:** `php artisan dentissa:rotar-credenciales --confirmar`
- Salida: una tabla correo → contraseña nueva, solo por consola.
- Código de salida 0. Sin `--confirmar`, código 1 y no cambia nada.

**Variables de entorno de producción** (solo los nombres; los valores van en `/opt/dentissa/.env.production`):
- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<subdominio>`, `LOG_LEVEL=info`;
- `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN` vacío;
- `DB_*`, con un usuario sin privilegios de root, y `DB_ROOT_PASSWORD`, que solo usa `db`;
- `GOOGLE_*` con el redirect en https;
- `CLOUDFLARE_TUNNEL_TOKEN`.

## Estrategia de pruebas
- **`LimiteIntentosTest` (CA8, TM3):**
  - `test_tras_cinco_fallos_el_login_se_bloquea_un_minuto`: cinco contraseñas erróneas y el sexto intento, aun con la contraseña correcta, se rechaza con el mensaje; pasado el minuto (`travel`), entra.
  - `test_el_mensaje_no_revela_si_la_cuenta_existe`: el mismo texto con un correo existente y con uno inexistente.
  - `test_el_bloqueo_es_por_correo_y_direccion`: otro correo desde la misma IP no queda bloqueado.
  - `test_el_alta_admite_cinco_intentos_por_minuto_y_direccion`: el sexto POST se rechaza sin crear el usuario.
  - `test_la_vuelta_de_google_admite_cinco_por_minuto`: con Socialite simulado, la sexta vuelta va a `login` con el mensaje.
  - Todos fallan hoy (no hay límite).
- **`ProxyHttpsTest` (CA2, TM4, TM9):**
  - con `X-Forwarded-Proto: https`, `url('/')` y las redirecciones salen en https;
  - con `SESSION_SECURE_COOKIE=true`, la cookie de sesión lleva `Secure`;
  - con un `X-Forwarded-For` falso, `request()->ip()` sigue siendo `REMOTE_ADDR` (la IP que deja nginx).
  - Falla hoy, porque sin `trustProxies` el esquema es http.
- **`RotarCredencialesTest` (CA5, TM5):**
  - tras el comando con `--confirmar`, la contraseña anterior de una cuenta ya no funciona y la mostrada sí;
  - una cuenta solo de Google no cambia;
  - sin `--confirmar` no cambia nada y devuelve 1;
  - `test_no_escribe_las_contrasenas_en_el_log`.
  - Falla hoy, porque el comando no existe.
- **`ErroresProduccionTest` (CA4, TM2):** con `app.debug=false`, una excepción en una ruta de prueba devuelve 500 sin el mensaje de la excepción, sin rutas de archivos ni traza. Es de caracterización (Laravel ya lo hace): se comprueba poniendo `app.debug=true` un momento.
- **`ConfiguracionProduccionTest` (CA3, CA5, CA10, TM1, TM6, TM7):** lee `docker-compose.prod.yml`, `docker-compose.yml`, `Dockerfile`, `docker/prod/Dockerfile` y `.dockerignore`.
  - Ningún servicio de producción tiene `ports`.
  - No hay `phpmyadmin` en producción.
  - Toda imagen (`image:` y `FROM`) tiene una etiqueta con versión exacta (sin `latest`, con números) y es la misma en local y en producción.
  - `.dockerignore` contiene las exclusiones de Cambios por módulo.
  - El montaje de `local.ini` apunta a un archivo que existe.
  - `db` usa `MARIADB_USER` y la app no usa root.
  - Falla hoy.
- **Comprobaciones en el servidor (P2 1.2.1), anotadas en su tarea.** Se hacen en el ensayo de D13, durante `/implement` y con `cloudflared` parado, salvo las que necesitan la dirección pública (CA1, CA2 y CA8 con la IP real), que se hacen en `/release`:
  - `deploy/verificar.sh --interna` en el ensayo (CA3, CA4, CA10) y `verificar.sh` contra la dirección pública en `/release` (CA1).
  - Desde otro equipo de la red: `nmap` o intento de conexión a 3306, 8080 y 80 del servidor, todos cerrados, y SSH con contraseña rechazado (CA3).
  - Las contraseñas del README no autentican en el servidor tras rotarlas (CA5).
  - Copia y restauración ensayadas, con la base igual por recuento de filas y suma de comprobación del volcado (CA6).
  - Vuelta atrás ensayada, cronometrada en 15 minutos o menos (CA7).
  - `sudo reboot` del servidor, cronometrado en 5 minutos o menos, y los logs anteriores siguen en sus volúmenes (CA9).
  - `docker run --rm --entrypoint ls` de la imagen sin `.env`, `.git`, `database/*.sql` ni `vendor` de desarrollo (CA10).
  - Desde fuera, 6 intentos de login fallidos con el sexto rechazado, comprobando que dos redes distintas no se bloquean entre sí (CA8 con la IP real).
- **Regresión:** la suite completa. Los tests existentes de login y registro no deben superar el límite: cada test se ejecuta con el limitador limpio.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Information disclosure / Elevation: acceso directo a la base de datos, phpMyAdmin u otros servicios del servidor | A02:2025 | `docker-compose.prod.yml`, `ufw`, SSH | Sin puertos publicados; sin phpMyAdmin; `ufw` deniega lo entrante salvo SSH desde la red local; SSH solo con clave | `ConfiguracionProduccionTest`; comprobación de red de CA3 |
| TM2 | Information disclosure: páginas de depuración con detalles internos | A02:2025 | configuración de producción | `APP_DEBUG=false`, comprobado en cada despliegue | `ErroresProduccionTest`; `deploy/verificar.sh` |
| TM3 | Spoofing: fuerza bruta de contraseñas, altas masivas y abuso del acceso con Google | A07:2025 | `AuthController`, rutas de alta y Google | Limitadores de CA8 | `LimiteIntentosTest` |
| TM4 | Spoofing / Tampering: falsear la IP con `X-Forwarded-For` para saltarse el límite | A07:2025 | nginx, `bootstrap/app.php` | IP de `CF-Connecting-IP` solo desde la red de Docker (`real_ip`); Laravel no se fía de `X-Forwarded-For` | `ProxyHttpsTest` (XFF ignorado); comprobación de CA8 en producción |
| TM5 | Elevation: entrar con las contraseñas publicadas del volcado | A07:2025 | cuentas del volcado | Comando de contraseñas en el primer despliegue, antes de abrir el túnel | `RotarCredencialesTest`; login con las del README rechazado en producción |
| TM6 | Information disclosure: secretos (claves, token del túnel, `.env`) en la imagen o el repo | A04:2025 | `.dockerignore`, `env_file` | Secretos solo en `/opt/dentissa/.env.production` (600); la imagen excluye `.env*`, `.git` y el volcado | `ConfiguracionProduccionTest`; gitleaks; listado de la imagen (CA10) |
| TM7 | Tampering: una imagen mutable (`latest`) cambia entre construcciones | A03:2025 | Dockerfiles y Compose | Imágenes con versión exacta, iguales en local y producción | `ConfiguracionProduccionTest` |
| TM8 | Tampering / DoS: pérdida de datos por un despliegue fallido | A08:2025 | `deploy/` | Copia antes de cada despliegue; restauración y vuelta atrás ensayadas | Ensayos de CA6 y CA7 |
| TM9 | Information disclosure: sesión robada por una conexión sin cifrar | A04:2025 | `trustProxies`, sesión | HTTPS de Cloudflare; URLs en https; cookie `Secure` | `ProxyHttpsTest`; `curl -I` en producción (CA2) |

Casos de abuso de la spec → amenazas: CA3 → TM1; CA4 → TM2; CA5 → TM5 y TM6; CA8 → TM3 y TM4.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: despliegue desde cero con verificación posterior | `deploy/*`, `docker-compose.prod.yml`, `docker/prod/Dockerfile`, ADR, `deployment.md` | Comprobación (P2 1.2.1): primer despliegue con `deploy/verificar.sh` y login por rol más cita ficticia · verificación manual: dirección pública con HTTPS, en el servidor |
| CA2: todo en https detrás del túnel | `bootstrap/app.php`, variables de entorno | Feature `ProxyHttpsTest` · verificación manual: `curl -I` de `/` y del login en producción (Location https, cookie `Secure`) |
| CA3 (abuso): nada interno accesible y SSH solo con clave | `docker-compose.prod.yml`, `deploy/preparar-servidor.sh` | Feature `ConfiguracionProduccionTest` (sin `ports`, sin phpMyAdmin) · comprobación: `ss -tlnp` y conexión desde otro equipo de la red a 3306, 8080 y 80 cerradas, SSH con contraseña rechazado |
| CA4 (abuso): error genérico sin detalles | variables de entorno, `deploy/verificar.sh` | Feature `ErroresProduccionTest` (caracterización) · comprobación: `verificar.sh` (`APP_DEBUG=false`) |
| CA5 (abuso): contraseñas del volcado inválidas y secretos fuera | `RotarCredencialesVolcado`, `.dockerignore`, `env_file`, `db` con usuario propio | Feature `RotarCredencialesTest`, `ConfiguracionProduccionTest` · comprobación: login con las del README rechazado en producción |
| CA6: copia antes de cada despliegue y restauración ensayada | `deploy/respaldar.sh`, `deploy/restaurar.sh`, `deploy/desplegar.sh` | Comprobación: ensayo de copia y restauración (recuento de filas por tabla igual) |
| CA7: vuelta atrás en 15 minutos o menos, ensayada | `deploy/volver.sh` | Comprobación: ensayo cronometrado |
| CA8 (abuso): límites de intentos | `AuthController`, `AppServiceProvider`, `routes/web.php`, `dentissa.prod.conf` | Feature `LimiteIntentosTest` · comprobación: sexto intento rechazado en producción, sin bloquear otra red |
| CA9: vuelve sola tras reiniciar y conserva logs | `restart: unless-stopped`, volúmenes `storage` y `nginx_logs` | Comprobación: reinicio cronometrado y logs anteriores presentes |
| CA10: versiones fijas, `local.ini` aplicado y paquete limpio | Dockerfiles, Compose, `.dockerignore` | Feature `ConfiguracionProduccionTest` · comprobación: `verificar.sh` (`php -i`) y listado de la imagen |
| TM1 | Compose, `ufw`, SSH | `ConfiguracionProduccionTest`; comprobación de CA3 |
| TM2 | `APP_DEBUG=false` | `ErroresProduccionTest`; `verificar.sh` |
| TM3 | limitadores | `LimiteIntentosTest` |
| TM4 | `real_ip` y `trustProxies` acotado | `ProxyHttpsTest`; comprobación de CA8 |
| TM5 | comando de contraseñas | `RotarCredencialesTest`; comprobación de CA5 |
| TM6 | `.dockerignore`, `env_file` | `ConfiguracionProduccionTest`; gitleaks; listado de la imagen |
| TM7 | imágenes fijadas | `ConfiguracionProduccionTest` |
| TM8 | copia y vuelta atrás | ensayos de CA6 y CA7 |
| TM9 | HTTPS y cookie `Secure` | `ProxyHttpsTest`; `curl -I` en producción |
| RNF: desplegar en 15 minutos o menos | `deploy/desplegar.sh` | Comprobación: primer despliegue cronometrado (sin la verificación) |
| RNF: vuelta atrás en 15 minutos o menos | `deploy/volver.sh` | Ensayo de CA7 |
| RNF: reinicio en 5 minutos o menos | Compose | Ensayo de CA9 |
| RNF: ningún despliegue sin aprobación | `deployment.md`, `/release` | Comprobación: nota de aprobación en T096 |

## Observabilidad
- **Logs:** no se añaden. Los de Laravel quedan en el volumen `storage` y los de nginx en
  `nginx_logs`, y sobreviven a reinicios y a recrear los contenedores (OB7.a). Nivel `info` y
  `APP_DEBUG=false` en producción (OB4.b, P8).
- **Auditoría:** no aplica (spec, Auditoría). Los rechazos por límite se registrarán como login
  fallido cuando exista el registro (spec 020).
- **Métricas y alertas:** ninguna nueva → spec 031 (OB6.b).
- **Verificación posterior al despliegue:**
  - `deploy/verificar.sh`;
  - `docker compose -f docker-compose.prod.yml logs --since 15m web app` sin errores 5xx;
  - login por rol y una cita ficticia agendada y cancelada.

## Rollout
- Este es el **primer despliegue a producción** del proyecto, en dos tiempos (D13):
  - **Ensayo en `/implement`, sin exposición** (con `cloudflared` parado, nadie de fuera llega):
    1. `preparar-servidor.sh`;
    2. clonar el repo con la deploy key;
    3. crear `/opt/dentissa/.env.production`;
    4. `desplegar.sh <commit>` de la rama;
    5. `dentissa:rotar-credenciales --confirmar`;
    6. `verificar.sh --interna`;
    7. ensayos de CA3, CA6, CA7, CA9 y CA10.
  - **Apertura en `/release 030`**, siguiendo `docs/deployment.md` (actualizado en esta spec) y con aprobación explícita del usuario:
    1. `desplegar.sh vX.Y.Z` (el tag de la versión);
    2. `verificar.sh --interna`;
    3. arrancar `cloudflared` (`docker compose -f docker-compose.prod.yml up -d cloudflared`);
    4. `verificar.sh` contra la dirección pública y comprobaciones de CA1, CA2 y CA8.
- Feature flag: no aplica. El límite de intentos está siempre activo, y la exposición se controla
  arrancando o parando `cloudflared`.
- Compatibilidad: en local, `trustProxies` y los limitadores se comportan igual; el Compose de local
  solo cambia el montaje de `local.ini` y las versiones de imagen.
- Vuelta atrás:
  - parar `cloudflared` deja la app sin exposición al instante;
  - `volver.sh <tag anterior> [copia]` restaura la versión y, si hubo migración, la copia;
  - en el primer despliegue, la vuelta atrás es parar el túnel.
- Qué vigilar (15 minutos): errores 5xx en los logs de `web` y `app`, el login por rol, y que el
  límite no bloquee a personas legítimas.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Plataforma (→ [ADR 0005](../../adr/0005-produccion-servidor-propio-cloudflare-tunnel.md), RD3.a):** servidor propio con Ubuntu Server 24.04, Docker Compose y Cloudflare Tunnel.
  - Opciones: (a) servidor propio con túnel; (b) PaaS gratuito (Render, Railway, Fly.io); (c) VPS de pago.
  - Se elige (a) (decisión del usuario, 2026-10-03): sin coste, sin abrir puertos del router y con HTTPS de Cloudflare.
- **D2 · Un solo entorno, producción** (decisión del usuario, 2026-10-03). Staging queda en el roadmap, junto con RD1.c.
- **D3 · IP del cliente:** nginx `real_ip` desde `CF-Connecting-IP`, solo desde `172.16.0.0/12` (red de Docker); Laravel `trustProxies` solo para protocolo, host y puerto.
  - Comprobado con una prueba de concepto el 2026-10-03: con `CF-Connecting-IP` y un `X-Forwarded-For` falso, `REMOTE_ADDR` es la IP real; sin la cabecera, es la del proxy.
  - Se descarta `trustProxies('*')` con `X-Forwarded-For`, que permitiría falsear la IP.
- **D4 · Imágenes de producción:** Dockerfile de producción multietapa (`app` y `web`) con el código dentro de la imagen y sin montaje del repo.
  - El `Dockerfile` de local no cambia de estructura; solo se fijan sus versiones.
  - Se descarta montar el clon como en local: la imagen tendría `.git` y `.env` a la vista, y no sería reproducible.
- **D5 · Versiones de imagen:** exactas (p. ej. `php:8.4.N-fpm`, `nginx:1.N.N-alpine`, `mariadb:10.11.N`, `composer:2.N.N`, `cloudflare/cloudflared:AAAA.M.N`), elegidas al implementar entre las publicadas hace 7 días o más (`.ai/project.yaml`), e iguales en local y en producción (RS7.d, CA10).
- **D6 · Cómo se trabaja en el servidor:** scripts versionados en `deploy/` que ejecuta el usuario por SSH; el agente no tiene acceso al servidor ni a sus secretos (decisión del usuario, 2026-10-03).
- **D7 · Código en el servidor:** clon de GitHub con una deploy key de solo lectura y `checkout` del tag de cada versión (decisión del usuario, 2026-10-03).
- **D8 · Datos iniciales:** el volcado (datos ficticios) y las migraciones; después, `dentissa:rotar-credenciales` cambia las contraseñas antes de abrir el túnel (RS6.c) (decisión del usuario, 2026-10-03).
- **D9 · Copias:** en `/var/backups/dentissa` (fuera de los contenedores), las 14 más recientes, y copia manual a tu PC con `scp` tras cada despliegue (decisión del usuario, 2026-10-03). El cifrado de las copias va a la spec 031 (RS15.b).
- **D10 · Límites (RS4):** login por `email|ip` contando solo fallos, en el controlador; alta (contador común de los dos pasos) y Google por IP con `RateLimiter::for` y `throttle`. Todos de 5 por minuto (spec CA8 retocado, decisión del usuario, 2026-10-03).
- **D11 · Logs persistentes (OB7.a):** volúmenes `storage` y `nginx_logs`; se descarta depender del log de Docker, que se pierde al recrear el contenedor.
- **D12 · `local.ini` (RD4.a):** los dos Compose montan `docker/nginx/php/local.ini`, el archivo real; se descarta moverlo para no tocar más archivos.
- **D13 · Ensayo en el servidor durante `/implement`** (decisión del usuario, 2026-10-03): los criterios que solo se comprueban en el servidor (CA3, CA6, CA7, CA9 y CA10) se ensayan antes de T092, con la rama desplegada por commit y `cloudflared` parado, de modo que la aplicación no queda expuesta antes de la aprobación de `/release`. En `/release` solo se despliega el tag, se abre el túnel y se comprueba lo público (CA1, CA2 y CA8 con la IP real). Se descarta dejarlo todo para `/release`: esos criterios no podrían marcarse en T092.

## Impacto en arquitectura
Al implementar (T090/T091):
- **`docs/architecture.md`:**
  - entornos (local y producción);
  - diagrama con `cloudflared` → `web` → `app` → `db`;
  - `deploy/` y `docker/prod/`;
  - límites de intentos.
- **`docs/deployment.md`:**
  - tabla de entornos (producción en el servidor propio; staging pendiente);
  - plataforma e infraestructura;
  - cómo desplegar, copias, vuelta atrás y verificación;
  - variables de entorno de producción;
  - RD1.a, RD1.b, RD3.a, RD3.b, RD3.c y RD4.a → "en curso (spec 030)".
- **`docs/security.md`:**
  - RS4.a, RS4.b, RS4.c, RS6.c, RS7.a, RS7.b, RS7.c y RS7.d → "en curso (spec 030)";
  - superficie de ataque (túnel y SSH);
  - herramientas (sin cambios).
- **`docs/observability.md`:** OB4.b y OB7.a → "en curso (spec 030)"; dónde están los logs en producción.
- **ADR 0005** `proposed` → `accepted` al aprobar este plan.

## Riesgos y mitigaciones
- **Quedarse sin acceso SSH al endurecer el servidor.** `preparar-servidor.sh` desactiva la contraseña solo si existe `~/.ssh/authorized_keys` con al menos una clave, y la guía pide abrir una segunda sesión de prueba antes de cerrar la primera.
- **Bloquear a personas legítimas detrás de la misma IP** (p. ej. una red escolar con NAT). El límite del login es por correo y dirección, no solo por dirección. El de alta y Google es por dirección: 5 por minuto basta para el uso real. Se vigila tras el despliegue.
- **Que la IP real no llegue** (si Cloudflare cambia la cabecera) → todos compartirían la IP de `cloudflared`. Mitigación: la comprobación de CA8 en producción, con dos redes distintas, y la prueba de concepto de D3.
- **El volcado trae datos y cuentas por defecto.** Las contraseñas se rotan antes de abrir el túnel. El volcado se sustituirá por datos generados en el objetivo 1.
- **El servidor de casa no es de alta disponibilidad** (cortes de luz o de internet): `restart: unless-stopped` y el reinicio ensayado (CA9). Es aceptable para un proyecto académico.
- **Las imágenes base fijadas acumulan avisos con el tiempo.** Una actualización periódica, como en la 029, queda en el roadmap.
- **El redirect de Google en https** debe registrarse en Google Cloud Console; si no, falla el login con Google. Está en `deploy/README.md` y en la verificación de CA1.
