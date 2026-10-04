---
spec: 030-despliegue-servidor-propio
status: approved
constitution_version: 1.2.1   # versión con la que se evaluó el Constitution Check (upgrade a AI-DD 1.11.2)
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
del cliente de `CF-Connecting-IP` (`real_ip`, solo desde la red de Docker). Laravel solo se fía del proxy
para el protocolo, nunca para el host, el puerto ni la IP (prueba de concepto del 2026-10-03). Así el
límite de intentos (RS4) cuenta por cliente real y no se puede saltar con un `X-Forwarded-For`
falso.

El servidor se prepara y opera con scripts versionados en `deploy/`, que el usuario ejecuta por
SSH: preparar el servidor, desplegar, copia de seguridad, restaurar, volver a la versión anterior y
verificar. Los secretos viven en archivos de entorno, uno por servicio, que solo existen en el servidor.

En la aplicación cambian tres cosas:
- `trustProxies` acotado;
- tres limitadores de intentos (login, alta y vuelta de Google);
- un comando que cambia las contraseñas de las cuentas del volcado.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 030 `approved` el 2026-10-03 (CA8 retocado en este plan, decisión del usuario). | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Constitución 1.2.1. Los criterios de comportamiento tienen tests PHPUnit: CA2 (HTTPS detrás del proxy), CA5 (comando de contraseñas) y CA8 (límites y evento de bloqueo) fallan hoy. CA4 (error genérico) es de caracterización y se comprueba encendiendo la depuración. Las partes comprobables de CA3 y CA10 (sin puertos en producción, puertos de local solo en `127.0.0.1`, sin phpMyAdmin, entorno separado por servicio, subred fija, imágenes fijadas, `.dockerignore`) tienen tests estáticos que fallan hoy. Lo que solo existe en el servidor (CA1, CA6, CA7, CA9 y las partes de red de CA3) se verifica con comprobaciones declaradas en `deploy/verificar.sh` y en el ensayo, anotadas en su tarea. | test: `LimiteIntentosTest`, `ProxyHttpsTest`, `RotarCredencialesTest`, `ErroresProduccionTest`, `ConfiguracionProduccionTest` (fallo previo anotado) · manual: comprobaciones del servidor anotadas |
| P3 Autorización en servidor | ➖ | Login, alta y vuelta de Google son rutas públicas cuya autorización no cambia; solo se les añade un límite de intentos. No hay rutas nuevas con datos. | — |
| P4 Entrada validada y SQL con bindings | ✅ | Sin entradas nuevas. El comando usa Eloquent/Query Builder y la validación existente no cambia. | test: `RotarCredencialesTest` · lint: semgrep `p/php` |
| P5 Nada sensible en el repo | ✅ | Secretos (`APP_KEY`, contraseñas de la base, token del túnel, OAuth) solo en `app.env`, `db.env` y `tunnel.env` de `/opt/dentissa/entorno` en el servidor (carpeta 700, archivos 600), fuera del clon (`/opt/dentissa/app`), y cada servicio recibe solo el suyo; además, `.dockerignore` (`**/*.env`) y `.gitignore` (`*.env`) excluyen esos nombres (D15, /analyze A36). `.dockerignore` deja fuera `.env*`, `.git` y `database/*.sql`. Las contraseñas nuevas se muestran una vez por consola y nunca se escriben en el repo ni en logs. | test: `ConfiguracionProduccionTest::test_los_archivos_de_entorno_quedan_fuera_del_paquete_y_del_repo` · lint: gitleaks |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias PHP ni npm nuevas. Línea base del 2026-10-03: `composer audit` y `npm audit` con 0 avisos. Las imágenes se fijan por versión exacta, y antes del ensayo en el servidor las imágenes base fijadas y las construidas (`app` y `web`) pasan `trivy image` sin críticas ni altas. Si alguna no tiene corrección publicada, la tarea se bloquea hasta que el usuario decida una excepción con vencimiento en `docs/security.md` (D14). | lint: composer audit, npm audit, trivy fs y trivy image en la tarea previa al ensayo y al final |
| P7 Auditoría de datos sensibles | ❌ aceptado | La 030 cambia el inicio de sesión y no registra los logins fallidos, porque el registro de auditoría aún no existe (spec 020). Para que la 020 los registre sin volver a tocar el login, cada rechazo por límite en el login y en la vuelta de Google emite el evento estándar de bloqueo del framework (`Illuminate\Auth\Events\Lockout`) — aprobado por el usuario el 2026-10-03 (spec, Decisiones; /analyze A4). | test: `LimiteIntentosTest` (evento de bloqueo emitido) |
| P8 Correlación y logs limpios | ✅ | Producción con `APP_DEBUG=false` y `LOG_LEVEL=info` (comprobado en cada despliegue). Sin logs nuevos; el comando de contraseñas no escribe en el log. | test: `ErroresProduccionTest`, `RotarCredencialesTest::test_no_escribe_las_contrasenas_en_el_log` · manual: `deploy/verificar.sh` |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Sin cambios de interfaz: los mensajes de límite se muestran con el bloque de errores que ya tienen las vistas de login y registro. | — |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `bootstrap/app.php` | `$middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_PROTO)`: **solo** el protocolo; ni `X_FORWARDED_FOR`, ni `X_FORWARDED_HOST`, ni `X_FORWARDED_PORT`. La IP del cliente es `REMOTE_ADDR`, que nginx toma de `CF-Connecting-IP` (D3); el host sale de la cabecera `Host` y el puerto, del esquema (D3, TM10) | Medio: en local, sin proxy, no cambia nada |
| `app/Providers/AppServiceProvider.php` | `RateLimiter::for('registro', …)` y `RateLimiter::for('google', …)`: 5 por minuto por IP, con respuesta que vuelve con el error de Contratos (alta: `back()->withErrors()->withInput()` sin contraseñas; Google: a `login` con el error y emitiendo `Lockout`) | Bajo |
| `routes/web.php` | `throttle:registro` en `POST /registro` y `POST /registro/contacto`; `throttle:google` en `GET /auth/google/callback` | Bajo |
| `app/Http/Controllers/AuthController.php` | En `login`, `RateLimiter` con clave `minúsculas(email)|ip`. Con 5 fallos en la ventana, el siguiente intento se rechaza sin intentar autenticar (con el mismo mensaje aunque la cuenta no exista) y emite `Lockout`; cada fallo suma con 60 s de espera y el éxito limpia la cuenta | Medio: toca el login |
| `app/Console/Commands/RotarCredencialesVolcado.php` (nuevo) | `dentissa:rotar-credenciales --confirmar`: a cada usuario con contraseña le pone una aleatoria de 20 caracteres y muestra la tabla correo → contraseña una sola vez por la salida estándar. Las cuentas solo de Google no cambian. Sin `--confirmar` no hace nada. Pensado para ejecutarse una vez en el primer despliegue, antes de abrir el túnel | Medio: cambia contraseñas; requiere confirmación |
| `docker/prod/Dockerfile` (nuevo) | Multietapa sobre imágenes fijadas: `app` (php-fpm con extensiones, `composer install --no-dev --optimize-autoloader`, código copiado y permisos de `storage`) y `web` (nginx con `public/` copiado de `app` y `dentissa.prod.conf`) | Medio |
| `docker/nginx/dentissa.prod.conf` (nuevo) | Como `dentissa.conf`, más `set_real_ip_from 172.28.0.0/24` (la subred fija de la red de producción, D3) y `real_ip_header CF-Connecting-IP`; si `X-Forwarded-Proto` es `http` (visitante que entra por `http://`), redirección 301 a `https://$host$request_uri` antes de pasar a php-fpm (D18, TM9); `fastcgi_pass app:9000`; logs a `/var/log/nginx` en un volumen | Bajo |
| `docker-compose.prod.yml` (nuevo) | Los servicios `app`, `web`, `db` y `cloudflared` en una red interna con subred fija `172.28.0.0/24`, **sin `ports`** y con `restart: unless-stopped`. Cada servicio lee solo su archivo de entorno (Contratos): `app` → `${DENTISSA_ENV_DIR:-/opt/dentissa/entorno}/app.env`, `db` → `db.env`, `cloudflared` → `tunnel.env`, y `web` ninguno (D15). `db` tiene `healthcheck` (`healthcheck.sh --connect --innodb_initialized`) y `app` arranca con `depends_on: db: condition: service_healthy`. Volúmenes: `db_data`, `storage` (logs de Laravel y sesiones) y `nginx_logs`. `db` crea su usuario propio (`MARIADB_USER`) y carga el volcado solo la primera vez; la contraseña de root no llega a `app`. `local.ini` se monta desde `docker/nginx/php/local.ini`. Imágenes con versión exacta | Medio |
| `docker-compose.yml` | Montaje de `local.ini` corregido a `./docker/nginx/php/local.ini` (RD4.a), imágenes fijadas por versión exacta (RS7.d), las mismas que en producción, y todos los `ports` publicados solo en `127.0.0.1` (`127.0.0.1:8000:80`, `127.0.0.1:3306:3306`, `127.0.0.1:8080:80`) (RS7.b, /analyze A2) | Bajo: solo local; la app sigue en `http://localhost:8000` |
| `Dockerfile` | `FROM php` y `composer` fijados a las mismas versiones exactas que `docker/prod/Dockerfile` | Bajo |
| `.dockerignore` | Excluye `.env*`, `**/*.env` (archivos de entorno del servidor, D15), `.git`, `node_modules`, `vendor`, `database/*.sql`, `storage/*` (salvo la estructura), `tests`, `docs`, `.ai`, `aidlc`, `.claude` y `.github` | Bajo |
| `.gitignore` | Añade `*.env`, para que un archivo de entorno del servidor nunca se pueda añadir al repo (D15, /analyze A36) | Bajo |
| `deploy/preparar-servidor.sh` (nuevo) | Se ejecuta una vez, con `sudo` desde la cuenta del usuario creada al instalar Ubuntu, que es la **cuenta de despliegue** (D16); se niega a correr si `SUDO_USER` está vacío o es `root`. Actualiza el sistema, activa `unattended-upgrades`, instala Docker Engine desde el repositorio oficial y configura `ufw` (deniega todo lo entrante y permite SSH solo desde la red local que recibe como argumento). SSH queda solo con clave (`PasswordAuthentication no`, `PermitRootLogin no`) **después** de comprobar que `~SUDO_USER/.ssh/authorized_keys` tiene al menos una clave. Añade la cuenta de despliegue al grupo `docker` y crea `/opt/dentissa` (propietaria la cuenta, 750), `/opt/dentissa/entorno` (700, para los archivos de entorno, fuera del clon) y `/var/backups/dentissa` (propietaria la cuenta, 700); el clon irá a `/opt/dentissa/app` | Alto: puede dejar al usuario sin acceso si se ejecuta mal; el script lo comprueba antes |
| `deploy/respaldar.sh` (nuevo) | `mariadb-dump` de la base, ejecutado dentro de `db` con las credenciales de su entorno (nunca en la línea de comandos del servidor), a `/var/backups/dentissa/dentissa-AAAAMMDD-HHMMSS.sql.gz` (permisos 600); conserva las 14 más recientes. Sale con error si la copia queda vacía | Bajo |
| `deploy/restaurar.sh` (nuevo) | Restaura una copia en la base, previa confirmación escrita | Medio |
| `deploy/desplegar.sh` (nuevo) | `desplegar.sh <tag o commit>` (en producción, siempre el tag `vX.Y.Z`; un commit solo en el ensayo de D13), en este orden: (1) comprueba que el árbol no tiene cambios; (2) copia previa con `respaldar.sh`, salvo en el **primer despliegue** (no existe el volumen `db_data`), en el que lo anuncia y sigue; si la copia falla en otro caso, se detiene; (3) `git fetch --tags` y `checkout` de la referencia; (4) construye las imágenes; (5) `up -d db` y espera a que esté sano; (6) `migrate --force` con la imagen nueva (`docker compose run --rm app`), **antes** de cambiar el tráfico (`docs/deployment.md`); (7) `up -d app web` (nunca arranca ni para `cloudflared`); (8) `config:cache` y `route:cache`; (9) `verificar.sh --interna`. Si falla la verificación, lo indica y propone `volver.sh` | Medio |
| `deploy/volver.sh` (nuevo) | `volver.sh <ref anterior> [copia]`: `checkout` de la referencia anterior y construcción; si se indica una copia (hubo migración), la restaura con `restaurar.sh` **antes** de levantar la versión anterior; después `up -d app web`, cachés y `verificar.sh --interna` | Medio |
| `deploy/verificar.sh` (nuevo) | `verificar.sh [--interna]`. Con `--interna`, `GET /up` por la red de Docker (200); sin él, por la dirección pública (200), con la redirección de `/` en https y con `http://<subdominio>/login` respondiendo 301 a `https://` (A35). En los dos modos, dentro de `app`: `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=info`, `APP_KEY` no vacía y `CACHE_STORE` distinto de `array`, sin imprimir valores secretos; ningún puerto de Docker publicado (`docker compose ps`) ni nada escuchando fuera de `localhost` salvo SSH (`ss -tlnp`); `upload_max_filesize` y `date.timezone` iguales a `local.ini`. Sale con código distinto de 0 si algo falla. `desplegar.sh` y `volver.sh` lo llaman siempre con `--interna`; la verificación pública es un paso aparte del Rollout (/analyze A8) | Bajo |
| `deploy/README.md` (nuevo) | Guía paso a paso: preparar el servidor, crear el túnel en Cloudflare (Zero Trust → Tunnels, hostname público → `http://web:80`), la deploy key de solo lectura en GitHub, el clon en `/opt/dentissa/app`, los tres archivos de entorno en `/opt/dentissa/entorno` (permisos 600; nunca dentro del clon) y cómo generar `APP_KEY` (`docker compose run --rm app php artisan key:generate --show`), la aprobación antes de cada ensayo, primer despliegue, rotar credenciales, copia a tu PC con `scp`, volver atrás, abrir el túnel y registrar el redirect de Google | Bajo |
| `tests/Feature/LimiteIntentosTest.php` (nuevo) | CA8 | — |
| `tests/Feature/ProxyHttpsTest.php` (nuevo) | CA2 y TM4 | — |
| `tests/Feature/RotarCredencialesTest.php` (nuevo) | CA5 | — |
| `tests/Feature/ErroresProduccionTest.php` (nuevo) | CA4 | — |
| `tests/Feature/ConfiguracionProduccionTest.php` (nuevo) | Partes estáticas de CA3, CA5, CA10 y TM4 | — |
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

**Redirección a https** (nginx de producción, D18): toda petición que llega con `X-Forwarded-Proto: http` recibe `301` a `https://<host><ruta y consulta>`, sin pasar por la aplicación. Las peticiones internas (sin la cabecera) no se redirigen.

**Comando:** `php artisan dentissa:rotar-credenciales --confirmar`
- Salida: una tabla correo → contraseña nueva, solo por consola.
- Código de salida 0. Sin `--confirmar`, código 1 y no cambia nada.

**Variables de entorno de producción** (solo los nombres). Tres archivos en `/opt/dentissa/entorno` (configurable con `DENTISSA_ENV_DIR`; carpeta 700 fuera del clon `/opt/dentissa/app`), permisos 600, cada uno leído solo por su servicio (D15):
- `app.env` (servicio `app`):
  - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<subdominio>`, `APP_KEY` (generada una vez con `key:generate --show`), `LOG_LEVEL=info`;
  - `SESSION_DRIVER=file`, `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN` vacío, `CACHE_STORE=database` (los limitadores necesitan un almacén compartido, nunca `array`);
  - `DB_CONNECTION=mariadb`, `DB_HOST=db`, `DB_PORT=3306`, `DB_DATABASE=dentissa`, `DB_USERNAME` y `DB_PASSWORD` (los mismos que `MARIADB_USER` y `MARIADB_PASSWORD`);
  - `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI` en https.
- `db.env` (servicio `db`): `MARIADB_DATABASE=dentissa`, `MARIADB_USER`, `MARIADB_PASSWORD` y `MARIADB_ROOT_PASSWORD`.
- `tunnel.env` (servicio `cloudflared`): `TUNNEL_TOKEN`.

## Estrategia de pruebas
- **`LimiteIntentosTest` (CA8, TM3):**
  - `test_tras_cinco_fallos_el_login_se_bloquea_un_minuto`: cinco contraseñas erróneas y el sexto intento, aun con la contraseña correcta, se rechaza con el mensaje; pasado el minuto (`travel`), entra.
  - `test_el_mensaje_no_revela_si_la_cuenta_existe`: el mismo texto con un correo existente y con uno inexistente.
  - `test_el_bloqueo_es_por_correo_y_direccion`: otro correo desde la misma IP no queda bloqueado.
  - `test_el_alta_admite_cinco_intentos_por_minuto_y_direccion`: el sexto POST se rechaza sin crear el usuario.
  - `test_la_vuelta_de_google_admite_cinco_por_minuto`: con Socialite simulado, la sexta vuelta va a `login` con el mensaje.
  - `test_el_rechazo_por_limite_emite_el_evento_de_bloqueo`: con `Event::fake`, el rechazo en el login y en la vuelta de Google emite `Lockout`, y el de alta no (P7, /analyze A4).
  - Todos fallan hoy (no hay límite).
- **`ProxyHttpsTest` (CA2, TM4, TM9):**
  - con `X-Forwarded-Proto: https`, `url('/')` y las redirecciones salen en https;
  - con `SESSION_SECURE_COOKIE=true`, la cookie de sesión lleva `Secure`;
  - con un `X-Forwarded-For` falso, `request()->ip()` sigue siendo `REMOTE_ADDR` (la IP que deja nginx);
  - con un `X-Forwarded-Host` o un `X-Forwarded-Port` ajenos, `url('/')` y las redirecciones conservan el host de la petición y el puerto del esquema (TM10).
  - Los casos de https fallan hoy, porque sin `trustProxies` el esquema es http. Los de IP, host y puerto pasan hoy y se comprueban rompiendo (añadiendo esas cabeceras a `trustProxies`).
- **`RotarCredencialesTest` (CA5, TM5):**
  - tras el comando con `--confirmar`, la contraseña anterior de una cuenta ya no funciona y la mostrada sí;
  - una cuenta solo de Google no cambia;
  - sin `--confirmar` no cambia nada y devuelve 1;
  - `test_no_escribe_las_contrasenas_en_el_log`.
  - Falla hoy, porque el comando no existe.
- **`ErroresProduccionTest` (CA4, TM2):** con `app.debug=false`, una excepción en una ruta de prueba devuelve 500 sin el mensaje de la excepción, sin rutas de archivos ni traza. Es de caracterización (Laravel ya lo hace): se comprueba poniendo `app.debug=true` un momento.
- **`ConfiguracionProduccionTest` (CA3, CA5, CA10, TM1, TM4, TM6, TM7):** lee `docker-compose.prod.yml`, `docker-compose.yml`, `Dockerfile`, `docker/prod/Dockerfile`, `docker/nginx/dentissa.prod.conf` y `.dockerignore`.
  - Ningún servicio de producción tiene `ports`, y todo `ports` de local empieza por `127.0.0.1:`.
  - Cada servicio de producción lee solo su archivo de entorno: `cloudflared` y `db` no leen `app.env`, y `app` no lee `db.env` ni `tunnel.env`.
  - La red de producción declara la subred `172.28.0.0/24`, y `set_real_ip_from` de `dentissa.prod.conf` es esa misma subred.
  - `dentissa.prod.conf` redirige a `https://` con 301 cuando `X-Forwarded-Proto` es `http` (TM9, A35).
  - Los archivos de entorno quedan fuera del paquete y del repo: `.dockerignore` contiene `**/*.env`, `.gitignore` contiene `*.env`, y la ruta por defecto de `DENTISSA_ENV_DIR` en `docker-compose.prod.yml` (`/opt/dentissa/entorno`) no está dentro de la ruta del clon (`/opt/dentissa/app`) (TM6, A36).
  - No hay `phpmyadmin` en producción.
  - Toda imagen (`image:` y `FROM`) tiene una etiqueta con versión exacta (sin `latest`, con números) y es la misma en local y en producción.
  - `.dockerignore` contiene las exclusiones de Cambios por módulo.
  - El montaje de `local.ini` apunta a un archivo que existe.
  - `db` usa `MARIADB_USER` y la app no usa root.
  - Falla hoy.
- **Escaneo de imágenes (P6, /analyze A11):** antes del ensayo, `trivy image --severity HIGH,CRITICAL` sobre cada imagen base fijada y sobre las construidas (`app` y `web`).
- **Comprobaciones en el servidor (P2 1.2.1), anotadas en su tarea.** Se hacen en el ensayo de D13, durante `/implement`, con `cloudflared` parado y con aprobación explícita del usuario antes de cada ensayo (spec, Decisiones; /analyze A5). Las que necesitan la dirección pública (CA2 y CA8 con la IP real, y la verificación pública de la liberación) se hacen en `/release`:
  - `deploy/verificar.sh --interna` en el ensayo (CA1, CA3, CA4, CA10), y `verificar.sh` contra la dirección pública en `/release`.
  - Una petición interna a `web` con `CF-Connecting-IP` y un `X-Forwarded-For` falso deja en el access log la IP de `CF-Connecting-IP` (TM4).
  - Desde otro equipo de la red: `nmap` o intento de conexión a 3306, 8080 y 80 del servidor, todos cerrados, y SSH con contraseña rechazado (CA3).
  - Las contraseñas del README no autentican en el servidor tras rotarlas (CA5).
  - Copia y restauración ensayadas, con la base igual por recuento de filas y suma de comprobación del volcado (CA6).
  - Vuelta atrás ensayada, cronometrada en 15 minutos o menos (CA7).
  - `sudo reboot` del servidor, cronometrado en 5 minutos o menos, y los logs anteriores siguen en sus volúmenes (CA9).
  - `docker run --rm --entrypoint ls` de la imagen sin `.env`, `app.env`, `db.env`, `tunnel.env`, `.git`, `database/*.sql` ni `vendor` de desarrollo (CA10).
  - Desde fuera, 6 intentos de login fallidos con el sexto rechazado, comprobando que dos redes distintas no se bloquean entre sí (CA8 con la IP real).
- **Regresión:** la suite completa. Los tests existentes de login y registro no deben superar el límite: cada test se ejecuta con el limitador limpio.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Information disclosure / Elevation: acceso directo a la base de datos, phpMyAdmin u otros servicios del servidor | A02:2025 | `docker-compose.prod.yml`, `ufw`, SSH | Sin puertos publicados; sin phpMyAdmin; `ufw` deniega lo entrante salvo SSH desde la red local; SSH solo con clave | `ConfiguracionProduccionTest`; comprobación de red de CA3 |
| TM2 | Information disclosure: páginas de depuración con detalles internos | A02:2025 | configuración de producción | `APP_DEBUG=false`, comprobado en cada despliegue | `ErroresProduccionTest`; `deploy/verificar.sh` |
| TM3 | Spoofing: fuerza bruta de contraseñas, altas masivas y abuso del acceso con Google | A07:2025 | `AuthController`, rutas de alta y Google | Limitadores de CA8 | `LimiteIntentosTest` |
| TM4 | Spoofing / Tampering: falsear la IP con `X-Forwarded-For` para saltarse el límite | A07:2025 | nginx, `bootstrap/app.php`, red de producción | IP de `CF-Connecting-IP` solo desde la subred fija de la red de producción (`real_ip`); Laravel no se fía de `X-Forwarded-For` | `ProxyHttpsTest` (XFF ignorado); `ConfiguracionProduccionTest` (subred fija y `set_real_ip_from` iguales); petición con cabeceras falsas en el ensayo; comprobación de CA8 en producción |
| TM5 | Elevation: entrar con las contraseñas publicadas del volcado | A07:2025 | cuentas del volcado | Comando de contraseñas en el primer despliegue, antes de abrir el túnel | `RotarCredencialesTest`; login con las del README rechazado en producción |
| TM6 | Information disclosure: secretos (claves, token del túnel, `.env`) en la imagen, el repo u otro servicio | A04:2025 | `.dockerignore`, `.gitignore`, `env_file` | Secretos solo en los archivos de entorno del servidor (600), uno por servicio, en `/opt/dentissa/entorno`, fuera del clon; la imagen excluye `.env*`, `**/*.env`, `.git` y el volcado, y el repo ignora `*.env` | `ConfiguracionProduccionTest` (entorno por servicio); gitleaks; listado de la imagen (CA10) |
| TM7 | Tampering: una imagen mutable (`latest`) cambia entre construcciones | A03:2025 | Dockerfiles y Compose | Imágenes con versión exacta, iguales en local y producción | `ConfiguracionProduccionTest` |
| TM8 | Tampering / DoS: pérdida de datos por un despliegue fallido | A08:2025 | `deploy/` | Copia antes de cada despliegue; restauración y vuelta atrás ensayadas | Ensayos de CA6 y CA7 |
| TM9 | Information disclosure: sesión robada o contraseña leída por una conexión sin cifrar | A04:2025 | `trustProxies`, sesión, nginx de producción | HTTPS de Cloudflare; toda entrada por `http://` redirigida con 301 a `https://` antes de servir una página (D18); URLs en https; cookie `Secure` | `ProxyHttpsTest`; `ConfiguracionProduccionTest` (regla de redirección); `verificar.sh` y `curl -I http://<subdominio>/login` en producción (CA2) |
| TM10 | Tampering / Spoofing: inyectar host o puerto con `X-Forwarded-Host` o `X-Forwarded-Port` para envenenar URLs y redirecciones | A01:2025 | `bootstrap/app.php` | `trustProxies` solo con `X_FORWARDED_PROTO` (D3) | `ProxyHttpsTest` (host y puerto ajenos ignorados) |
| TM11 | Information disclosure: lectura de las copias de seguridad, con datos personales y de salud, en el servidor o en el PC del usuario | A04:2025 | `deploy/respaldar.sh`, `/var/backups/dentissa` | Carpeta 700 y copias 600, propietaria la cuenta de despliegue; solo las 14 más recientes; transferencia con `scp` por SSH. Residuo: sin cifrar → spec 031 (RS15.b) | Comprobación de permisos en el ensayo de CA6 |

Casos de abuso de la spec → amenazas: CA3 → TM1; CA4 → TM2; CA5 → TM5 y TM6; CA8 → TM3 y TM4.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: despliegue desde cero, sin exponer, con verificación desde dentro del servidor | `deploy/*`, `docker-compose.prod.yml`, `docker/prod/Dockerfile`, ADR, `deployment.md` | Comprobación (P2 1.2.1): primer despliegue en el ensayo con `deploy/verificar.sh --interna` · verificación manual: login por rol y cita ficticia por túnel SSH al contenedor `web`. La verificación por la dirección pública con HTTPS es parte de la liberación (Rollout, `/release`) |
| CA2: todo en https detrás del túnel | `bootstrap/app.php`, `dentissa.prod.conf` (redirección http → https), variables de entorno | Feature `ProxyHttpsTest`, `ConfiguracionProduccionTest` (regla de redirección) · verificación manual: `curl -I` de `/` y del login por `https://` (Location https, cookie `Secure`) y `curl -I http://<subdominio>/login` → 301 a https, en producción |
| CA3 (abuso): nada interno accesible, SSH solo con clave y local solo desde el propio equipo | `docker-compose.prod.yml`, `docker-compose.yml`, `deploy/preparar-servidor.sh` | Feature `ConfiguracionProduccionTest` (sin `ports` en producción, `127.0.0.1` en local, sin phpMyAdmin) · comprobación: `ss -tlnp` y conexión desde otro equipo de la red a 3306, 8080 y 80 cerradas, SSH con contraseña rechazado |
| CA4 (abuso): error genérico sin detalles | variables de entorno, `deploy/verificar.sh` | Feature `ErroresProduccionTest` (caracterización) · comprobación: `verificar.sh` (`APP_DEBUG=false`) |
| CA5 (abuso): contraseñas del volcado inválidas y secretos fuera | `RotarCredencialesVolcado`, `.dockerignore`, `env_file`, `db` con usuario propio | Feature `RotarCredencialesTest`, `ConfiguracionProduccionTest` · comprobación: login con las del README rechazado en producción |
| CA6: copia antes de cada despliegue y restauración ensayada | `deploy/respaldar.sh`, `deploy/restaurar.sh`, `deploy/desplegar.sh` | Comprobación: ensayo de copia y restauración (recuento de filas y `CHECKSUM TABLE` por tabla iguales; copia con permisos 600) |
| CA7: vuelta atrás en 15 minutos o menos, ensayada | `deploy/volver.sh` | Comprobación: ensayo cronometrado |
| CA8 (abuso): límites de intentos | `AuthController`, `AppServiceProvider`, `routes/web.php`, `dentissa.prod.conf`, `docker-compose.prod.yml` (subred) | Feature `LimiteIntentosTest`, `ConfiguracionProduccionTest` (subred) · comprobación: IP de `CF-Connecting-IP` en el access log del ensayo; sexto intento rechazado en producción, sin bloquear otra red (`/release`) |
| CA9: vuelve sola tras reiniciar y conserva logs | `restart: unless-stopped`, volúmenes `storage` y `nginx_logs` | Comprobación: reinicio cronometrado y logs anteriores presentes |
| CA10: versiones fijas, `local.ini` aplicado y paquete limpio | Dockerfiles, Compose, `.dockerignore`, `.gitignore` | Feature `ConfiguracionProduccionTest` · comprobación: `verificar.sh` (`php -i`) y listado de la imagen |
| TM1 | Compose, `ufw`, SSH | `ConfiguracionProduccionTest`; comprobación de CA3 |
| TM2 | `APP_DEBUG=false` | `ErroresProduccionTest`; `verificar.sh` |
| TM3 | limitadores | `LimiteIntentosTest` |
| TM4 | `real_ip` con subred fija y `trustProxies` acotado | `ProxyHttpsTest`; `ConfiguracionProduccionTest`; petición con cabeceras falsas en el ensayo; comprobación de CA8 en `/release` |
| TM5 | comando de contraseñas | `RotarCredencialesTest`; comprobación de CA5 |
| TM6 | `.dockerignore`, entorno por servicio | `ConfiguracionProduccionTest`; gitleaks; listado de la imagen |
| TM7 | imágenes fijadas | `ConfiguracionProduccionTest` |
| TM8 | copia y vuelta atrás | ensayos de CA6 y CA7 |
| TM9 | HTTPS, redirección http → https y cookie `Secure` | `ProxyHttpsTest`; `ConfiguracionProduccionTest`; `verificar.sh` y `curl -I` en producción |
| TM10 | `trustProxies` solo con el protocolo | `ProxyHttpsTest` |
| TM11 | permisos y retención de las copias | ensayo de CA6 |
| RNF: desplegar en 15 minutos o menos | `deploy/desplegar.sh` | Comprobación: primer despliegue cronometrado (sin la verificación) |
| RNF: vuelta atrás en 15 minutos o menos | `deploy/volver.sh` | Ensayo de CA7 |
| RNF: reinicio en 5 minutos o menos | Compose | Ensayo de CA9 |
| RNF: ningún despliegue sin aprobación, incluidos los ensayos | `deployment.md`, `/release` | Comprobación: nota de aprobación del usuario antes de cada ensayo en el servidor y en T096 |

## Observabilidad
- **Logs:** no se añaden. Los de Laravel quedan en el volumen `storage` y los de nginx en
  `nginx_logs`, y sobreviven a reinicios y a recrear los contenedores (OB7.a). Nivel `info` y
  `APP_DEBUG=false` en producción (OB4.b, P8).
- **Auditoría:** excepción P7 aceptada hasta la spec 020 (spec, Auditoría). Los rechazos por límite
  en el login y en la vuelta de Google emiten `Lockout` (test en `LimiteIntentosTest`), para que la
  020 los registre como login fallido sin tocar el login.
- **Métricas y alertas:** ninguna nueva → spec 031 (OB6.b).
- **Verificación posterior al despliegue:**
  - `deploy/verificar.sh --interna` y, con el túnel abierto, `deploy/verificar.sh`;
  - `docker compose -f docker-compose.prod.yml logs --since 15m web app` sin errores 5xx;
  - login por rol y una cita ficticia agendada y cancelada.

## Rollout
- Este es el **primer despliegue a producción** del proyecto, en dos tiempos (D13):
  - **Ensayo en `/implement`, sin exposición** (con `cloudflared` parado, nadie de fuera llega), con aprobación explícita del usuario antes de cada ensayo, anotada en su tarea (spec, Decisiones; /analyze A5):
    1. `preparar-servidor.sh`;
    2. clonar el repo con la deploy key;
    3. crear `app.env`, `db.env` y `tunnel.env` en `/opt/dentissa/entorno` (fuera del clon `/opt/dentissa/app`);
    4. `desplegar.sh <commit>` de la rama (primer despliegue, sin copia previa);
    5. `dentissa:rotar-credenciales --confirmar`;
    6. `verificar.sh --interna`;
    7. ensayos de CA3, CA6, CA7, CA9 y CA10.
  - **Apertura en `/release 030`**, siguiendo `docs/deployment.md` (actualizado en esta spec) y con aprobación explícita del usuario:
    1. `desplegar.sh vX.Y.Z` (el tag de la versión), que verifica por la red interna;
    2. arrancar `cloudflared` (`docker compose -f docker-compose.prod.yml up -d cloudflared`);
    3. `verificar.sh` contra la dirección pública; login por rol y cita ficticia por la dirección pública con HTTPS (la parte pública de la liberación, CA1); comprobaciones públicas de CA2 y CA8.
- Feature flag: no aplica. El límite de intentos está siempre activo, y la exposición se controla
  arrancando o parando `cloudflared`.
- Compatibilidad: en local, `trustProxies` y los limitadores se comportan igual; el Compose de local
  solo cambia el montaje de `local.ini`, las versiones de imagen y que los puertos se publican en
  `127.0.0.1`. La migración corre antes de levantar la versión nueva, así que toda migración debe
  convivir con el código anterior (expand, `docs/deployment.md`).
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
- **D3 · IP del cliente y cabeceras del proxy:** nginx `real_ip` desde `CF-Connecting-IP`, solo desde la subred fija `172.28.0.0/24` de la red de producción (/analyze A14); Laravel `trustProxies` solo para el protocolo (/analyze A13). Como nginx reescribe `REMOTE_ADDR` con la IP del cliente, Laravel no puede distinguir el proxy del cliente, así que confiar en `X-Forwarded-Host` o `X-Forwarded-Port` permitiría inyectarlos (TM10). El host sale de la cabecera `Host` que fija Cloudflare según el hostname del túnel, y el puerto, del esquema https.
  - Comprobado con una prueba de concepto el 2026-10-03: con `CF-Connecting-IP` y un `X-Forwarded-For` falso, `REMOTE_ADDR` es la IP real; sin la cabecera, es la del proxy.
  - Se descarta `trustProxies('*')` con `X-Forwarded-For`, que permitiría falsear la IP, y con `X-Forwarded-Host`/`Port` (TM10).
  - Se descarta dejar `set_real_ip_from 172.16.0.0/12` sin fijar la subred: si Docker asignara otra, todos compartirían la IP de `cloudflared` y los límites de alta y Google serían globales.
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
- **D13 · Ensayo en el servidor durante `/implement`** (decisión del usuario, 2026-10-03; con aprobación explícita antes de cada ensayo, contradicción aceptada en la spec, /analyze A5): los criterios que solo se comprueban en el servidor (CA3, CA6, CA7, CA9 y CA10) se ensayan antes de T092, con la rama desplegada por commit y `cloudflared` parado, de modo que la aplicación no queda expuesta antes de la aprobación de `/release`. En `/release` solo se despliega el tag, se abre el túnel y se comprueba lo público (CA1, CA2 y CA8 con la IP real). Se descarta dejarlo todo para `/release`: esos criterios no podrían marcarse en T092.

- **D14 · Escaneo de imágenes (P6, /analyze A11):** `trivy image --severity HIGH,CRITICAL` sobre las imágenes base fijadas y las construidas, antes del ensayo y al final de la implementación. Se descarta `--ignore-unfixed`: una alta sin corrección también llega a producción, así que se decide como excepción (motivo, aprobador y vencimiento en `docs/security.md`), no se oculta. Se descarta cambiar a imágenes `alpine` para reducir avisos: cambiaría la base de local y producción a la vez (CA10) y la instalación de extensiones; si los avisos sin corrección resultan muchos, se reconsidera en una spec aparte.
- **D15 · Entorno por servicio (/analyze A9, A10):** tres archivos (`app.env`, `db.env` y `tunnel.env`) en `${DENTISSA_ENV_DIR:-/opt/dentissa/entorno}`, una carpeta 700 fuera del clon (`/opt/dentissa/app`), cada uno en el `env_file` de su servicio; además `.dockerignore` (`**/*.env`) y `.gitignore` (`*.env`) excluyen esos nombres por si alguien los crea dentro del clon (/analyze A36); `web` no recibe ninguno. Así `cloudflared` (imagen de terceros) no recibe `APP_KEY` ni contraseñas, y `app` no recibe la contraseña de root ni el token. La variable `DENTISSA_ENV_DIR` permite validar el Compose en el equipo de desarrollo (`docker compose config` con una carpeta temporal de archivos vacíos). La contraseña del usuario de la base se repite en `app.env` (`DB_PASSWORD`) y `db.env` (`MARIADB_PASSWORD`). Se descarta un único archivo (cada servicio vería los secretos de todos) y la interpolación `${…}` en el Compose (exigiría pasar `--env-file` a mano en cada comando).
- **D16 · Cuenta de despliegue (/analyze A22):** la cuenta con sudo creada al instalar Ubuntu; `preparar-servidor.sh` la toma de `SUDO_USER`, comprueba su `authorized_keys`, la añade al grupo `docker` y la hace propietaria de `/opt/dentissa` y `/var/backups/dentissa`. Se descarta una cuenta aparte: un solo administrador en un servidor doméstico, y otra cuenta exigiría gestionar otra clave.
- **D17 · Orden del despliegue (/analyze A7, A20):** el primer despliegue (sin volumen `db_data`) sigue sin copia previa y lo anuncia; la migración espera a que `db` esté sana y corre con la imagen nueva antes de cambiar el tráfico; `volver.sh` restaura la copia antes de levantar la versión anterior. Se descarta migrar tras `up -d`: contradice `docs/deployment.md`, y una migración fallida dejaría la versión nueva sirviendo sobre un esquema a medias.

- **D18 · Entrada por `http://` (/analyze A35):** el nginx de producción redirige con 301 a `https://` toda petición que llega con `X-Forwarded-Proto: http`, antes de pasar a la aplicación; las internas (sin la cabecera) no se redirigen. Comprobado con una prueba de concepto el 2026-10-03: con `X-Forwarded-Proto: http`, 301 a `https://<host>/login?x=1`; con `https` o sin la cabecera, 200. Se descarta depender solo de la opción "Always Use HTTPS" de Cloudflare: no se puede probar desde el repo y depende de la configuración de la cuenta (la guía la recomienda además). HSTS sigue en la spec 031 (RS8.b).

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
  - correcciones divididas (/analyze A1): RD1.a y RD3.c pasan a referirse a producción, y se crean RD1.d y RD3.d (staging), pendientes → objetivo 5;
  - RD1.a, RD1.b, RD3.a, RD3.b, RD3.c y RD4.a → "en curso (spec 030)";
  - el ensayo en el servidor sin exposición, con aprobación (/analyze A5).
- **`docs/security.md`:**
  - correcciones divididas: RS7.a pasa a producción y se crea RS7.e (staging) → objetivo 5; RS13.a pasa a ser el límite del alta y se crea RS13.b (mensaje genérico) → objetivo 2;
  - RS4.a, RS4.b, RS4.c, RS6.c, RS7.a, RS7.b, RS7.c, RS7.d y RS13.a → "en curso (spec 030)";
  - `hub.docker.com` entre los dominios del agente (/analyze A28);
  - superficie de ataque (túnel y SSH);
  - herramientas (sin cambios).
- **`docs/observability.md`:** OB4.b y OB7.a pasan a producción y se crean OB4.c y OB7.b (staging) → objetivo 5; OB4.b y OB7.a → "en curso (spec 030)"; dónde están los logs en producción.
- **ADR 0005** `proposed` → `accepted` al aprobar este plan.

## Riesgos y mitigaciones
- **Quedarse sin acceso SSH al endurecer el servidor.** `preparar-servidor.sh` desactiva la contraseña solo si la cuenta que invoca `sudo` tiene `~/.ssh/authorized_keys` con al menos una clave, y la guía pide abrir una segunda sesión de prueba antes de cerrar la primera.
- **Bloquear a personas legítimas detrás de la misma IP** (p. ej. una red escolar con NAT). El límite del login es por correo y dirección, no solo por dirección. El de alta y Google es por dirección: 5 por minuto basta para el uso real. Se vigila tras el despliegue.
- **Que la IP real no llegue** (si Cloudflare cambia la cabecera) → todos compartirían la IP de `cloudflared`. Mitigación: la comprobación de CA8 en producción, con dos redes distintas, y la prueba de concepto de D3.
- **El volcado trae datos y cuentas por defecto.** Las contraseñas se rotan antes de abrir el túnel. El volcado se sustituirá por datos generados en el objetivo 1.
- **El servidor de casa no es de alta disponibilidad** (cortes de luz o de internet): `restart: unless-stopped` y el reinicio ensayado (CA9). Es aceptable para un proyecto académico.
- **Las imágenes base fijadas acumulan avisos con el tiempo.** `trivy image` antes del ensayo (D14); una actualización periódica, como en la 029, queda en el roadmap.
- **Vistas del volcado con `DEFINER=root@localhost`.** La imagen de MariaDB conserva `root@localhost`, así que siguen funcionando; la copia y la restauración se hacen dentro de `db` con sus credenciales para conservarlas.
- **El redirect de Google en https** debe registrarse en Google Cloud Console; si no, falla el login con Google. Está en `deploy/README.md` y en la verificación de CA1.
