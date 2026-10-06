---
spec: 031-endurecer-produccion
status: approved
constitution_version: 1.2.1
created: 2026-10-06
---

# Plan · 031 Endurecer producción tras la apertura

## Enfoque técnico
- **Cabeceras de seguridad en nginx:** se definen en fragmentos que incluyen la configuración local
  y la de producción. Así cubren también los estáticos, la redirección a https y los errores que
  genera el propio nginx. La política de contenido está activa también en local, de modo que una
  pantalla rota se ve antes de producción. La cabecera de solo https (HSTS) va únicamente en
  producción.
- **Versiones ocultas:** `server_tokens off` en nginx y `expose_php = Off` en PHP.
- **Vuelta de Google:** cualquier fallo de Socialite (estado inválido, cancelación o error del
  proveedor) vuelve al login con el mensaje de la spec. Además emite el evento estándar
  `Illuminate\Auth\Events\Failed`, para que la auditoría (020/021) lo registre sin tocar otra vez
  el controlador.
- **`deploy/verificar.sh`:** detecta las credenciales de Google con el texto de ejemplo y los
  contenedores que usan una configuración distinta de la guardada. Un script nuevo aplica los
  cambios de entorno con `--force-recreate`.
- **Administración desde fuera:** Tailscale (ADR 0006), instalado con un script desde el
  repositorio oficial firmado. Se sigue usando OpenSSH con clave, sin el SSH de Tailscale, y no se
  abre ningún puerto.
- **Dependencias e imágenes:** se corrige el aviso alto de npm de la línea base y se refrescan y
  reescanean las imágenes antes de que venza EX2 (CA11).

Línea base de dependencias (2026-10-06): `composer audit --locked` sin avisos; `npm audit`, 1 alto
(`source-map-js` 1.0.0–1.2.1, GHSA-68fv-2mgg-jv7q, transitiva de las herramientas de desarrollo,
con corrección disponible) → se corrige en esta spec (D12; decisión del usuario, 2026-10-06).

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 031 aprobada el 2026-10-06 y aprobada de nuevo con CA11 al aprobar este plan (decisión del usuario). | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | CA5 cambia el comportamiento de la aplicación: `GoogleVueltaFallidaTest` falla hoy con un 500. CA1–CA4 dependen de la configuración de nginx y PHP: `CabecerasSeguridadTest` lee esa configuración y falla hoy; la respuesta real se comprueba con `verificar.sh` y con `curl` en local. CA6–CA11 no son comportamiento de la aplicación (scripts de despliegue, red del servidor, imágenes), así que se verifican con las comprobaciones declaradas en Trazabilidad (constitución 1.2.1), anotadas en su tarea; las de `verificar.sh`, comprobadas rompiendo. | test: `GoogleVueltaFallidaTest`, `CabecerasSeguridadTest`, `ConfiguracionProduccionTest` · manual: comprobaciones de Trazabilidad |
| P3 Autorización en servidor | ➖ | Sin rutas ni permisos nuevos. La vuelta de Google sigue siendo pública y su autorización no cambia. | — |
| P4 Entrada validada y SQL con bindings | ✅ | La vuelta de Google solo mira si llega el parámetro `error`; no lo guarda ni lo muestra. Sin SQL nuevo. | lint: semgrep `p/php` sobre los archivos tocados |
| P5 Nada sensible en el repo | ✅ | Sin secretos nuevos. El alta en Tailscale es interactiva, sin clave de autorización en el repo. `verificar.sh` y `aplicar-entorno.sh` nunca imprimen valores. | lint: gitleaks · manual: revisión de la salida de `verificar.sh` |
| P6 Sin vulnerabilidades críticas/altas | ✅ | El aviso alto de npm se corrige (D12). Composer, sin avisos. Las imágenes se refrescan y pasan `trivy image`; EX2 se renueva solo para lo que no tenga corrección publicada, o se retira (D13, CA11). | lint: `npm audit`, `composer audit --locked`, `trivy image` |
| P7 Auditoría de datos sensibles | ❌ aceptado | El fallo de la vuelta de Google es un login fallido y no se audita hasta la 020/021: excepción P7 de la 030, aprobada por el usuario el 2026-10-03 y recogida en la sección Auditoría de la 031, aprobada el 2026-10-06. Se emite `Failed` para que la 021 lo registre sin tocar el controlador — aprobado por el usuario el 2026-10-06. | test: `GoogleVueltaFallidaTest::test_el_fallo_cuenta_como_login_fallido` |
| P8 Correlación y logs limpios | ✅ | Sin `Log::` nuevos: la correlación por petición todavía no existe (020). La depuración apagada y el nivel `info` no cambian. | manual: revisión del diff y `verificar.sh` |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Sin cambios visuales: el mensaje de la vuelta de Google se muestra con el bloque de errores del login. | — |

## Cambios por módulo
| Módulo / ruta | Cambio | Riesgo |
|---|---|---|
| `docker/nginx/seguridad/comunes.conf` (nuevo) | Cabeceras CSP, `X-Frame-Options`, `X-Content-Type-Options` y `Referrer-Policy`, con `always` (D1, D2) | medio: una directiva de la CSP puede romper una pantalla |
| `docker/nginx/seguridad/produccion.conf` (nuevo) | `include` de `comunes.conf` y `Strict-Transport-Security` (D3) | bajo |
| `docker/nginx/dentissa.conf` (local) | `include` de `comunes.conf` en el `server` y en el `location` de estáticos; `server_tokens off` (D4) | bajo |
| `docker/nginx/dentissa.prod.conf` | `include` de `produccion.conf` en el `server` y en el `location` de estáticos; `server_tokens off` | bajo |
| `docker/nginx/php/local.ini` | `expose_php = Off` (se monta en local y en producción) | bajo |
| `docker/prod/Dockerfile` | etapa `web`: `COPY docker/nginx/seguridad /etc/nginx/snippets/dentissa/`; imágenes refrescadas (D13) | medio: cambio de imágenes |
| `docker-compose.yml` | montar `./docker/nginx/seguridad:/etc/nginx/snippets/dentissa:ro` en `nginx`; imágenes refrescadas | bajo |
| `Dockerfile` (local), `docker-compose.prod.yml` | imágenes refrescadas, iguales a las de producción | bajo |
| `app/Http/Controllers/GoogleAuthController.php` | `callback`: fallo de Socialite → `Failed` y vuelta a `login` con el mensaje (D5) | bajo |
| `deploy/verificar.sh` | D6, D7, D11 y cabeceras por la red interna y por la pública | medio: falsos FALLO |
| `deploy/aplicar-entorno.sh` (nuevo) | D8 | bajo |
| `deploy/preparar-acceso-remoto.sh` (nuevo) | D9 | medio: se ejecuta como root |
| `deploy/README.md` | §4 y §5: avisos de los marcadores; §10 cambiar un archivo de entorno; §11 acceso desde fuera y alta y retirada de dispositivos | — |
| `package-lock.json` | `npm audit fix`, sin cambios de versión mayor (D12) | bajo |
| `tests/Feature/CabecerasSeguridadTest.php` (nuevo), `tests/Feature/GoogleVueltaFallidaTest.php` (nuevo), `tests/Feature/ConfiguracionProduccionTest.php` | ver Estrategia de pruebas | — |
| `docs/deployment.md`, `docs/security.md`, `docs/architecture.md`, `docs/roadmap.md`, `docs/adr/0006-administracion-remota-tailscale.md` (nuevo) | acceso remoto, aplicar el entorno, fila del SSH, RS8.a y RS8.b divididas (RS8.c y RS8.d nuevas), RS18.a, EX2 e infraestructura | — |

Sin solapes con las specs en curso: los planes aprobados de la 014 y la 020 no tocan
`GoogleAuthController`, nginx ni `deploy/`. La 020/021 escuchará `Failed` y `Lockout`.

## Contratos y datos
- **Vuelta de Google** (`GET /auth/google/callback`, `throttle:google` sin cambios):

  | Caso | Antes | Después |
  |---|---|---|
  | Llega `?error=…` (cancelado en Google) | 500 | 302 a `/login` con el error `email` = «No se pudo iniciar sesión con Google. Inténtalo de nuevo.»; Socialite no se llama |
  | `InvalidStateException` (sin inicio previo, caducado o manipulado) | 500 | lo mismo |
  | `GuzzleHttp\Exception\GuzzleException` (código inválido, error de Google) | 500 | lo mismo |
  | Éxito | sin cambios | sin cambios |

  En los fallos: sin sesión, sin `google_registro` en la sesión, sin crear ni vincular cuentas, y
  `event(new Failed('web', null, ['proveedor' => 'google']))`. El mensaje es una constante pública
  del controlador.
- **Cabeceras** (todas con `always`, en local y en producción salvo HSTS):
  - `Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net; font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'`
  - `X-Frame-Options: DENY`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - Solo en producción: `Strict-Transport-Security: max-age=15552000; includeSubDomains`
- **`deploy/aplicar-entorno.sh <app|cloudflared>`:**
  - recrea el servicio (`up -d --force-recreate --no-deps`);
  - en `app`, `config:cache` y `route:cache` como `www-data`;
  - después, `verificar.sh --interna`;
  - sale con 1 si el servicio no es válido. Con `db` explica que `db.env` solo se aplica al crear
    la base y remite a la guía.
- **`deploy/verificar.sh`:** salida `OK`/`FALLO` como hasta ahora, nunca con valores.
  - Configuración: pasa de 10 a 12 comprobaciones (CA6).
  - Secciones nuevas:
    - «Entorno aplicado» (CA7);
    - «Cabeceras» (interna y pública);
    - «Administración remota» (`tailscaled` en marcha, SSH sin contraseña).
- Sin migraciones, sin datos nuevos y sin cambios en `.ai/project.yaml`.

## Estrategia de pruebas
- **`CabecerasSeguridadTest`** (nuevo). Lee la configuración como texto, igual que
  `ConfiguracionProduccionTest`, y falla hoy:
  - `comunes.conf` declara las cuatro cabeceras con `always` y los valores del contrato:
    `frame-ancestors 'none'`, `object-src 'none'`, y ningún origen externo distinto de los de las
    fuentes;
  - HSTS solo está en `produccion.conf`, con `max-age` de al menos 15552000 e
    `includeSubDomains`; no aparece en `comunes.conf` ni en `dentissa.conf`;
  - en las dos configuraciones, el `server` y todo `location` con `add_header` propio incluyen su
    fragmento;
  - `server_tokens off` en las dos;
  - `expose_php = Off` en `local.ini`;
  - el Dockerfile de producción copia los fragmentos y `docker-compose.yml` los monta.
- **`GoogleVueltaFallidaTest`** (nuevo, Feature, con Socialite simulado como en
  `LimiteIntentosTest`). Todos fallan hoy con un 500:
  - `test_sin_inicio_previo_vuelve_al_login_con_mensaje`: con `InvalidStateException` → `login`
    con el mensaje, invitado, sin `google_registro` y sin usuario nuevo.
  - `test_cancelar_en_google_vuelve_al_login_sin_llamar_al_proveedor`: con `?error=access_denied`
    → igual, y `shouldNotReceive('user')`.
  - `test_un_error_del_proveedor_vuelve_al_login`: con `ClientException` → igual.
  - `test_el_fallo_no_vincula_una_cuenta_existente`: el `google_id` de la cuenta con el mismo
    correo no cambia.
  - `test_el_fallo_cuenta_como_login_fallido`: `Event::fake` → `Failed` una vez, con guard `web`
    y sin usuario.
  - `test_los_fallos_siguen_contando_para_el_limite`: cinco fallos y la sexta vuelta da el
    mensaje de límite (030 CA8).
- **`ConfiguracionProduccionTest`** (ampliado):
  - `preparar-acceso-remoto.sh` usa `signed-by`, no tiene `| sh`, lleva `--ssh=false` y añade la
    regla de `ufw` por `tailscale0`;
  - el test de versiones iguales en local y en producción cubre las imágenes refrescadas.
- **Comprobaciones declaradas** (P2, constitución 1.2.1), anotadas en su tarea:
  - **Prueba de concepto de D7 al empezar `/implement`**, con el Compose de producción en local:
    el entorno resuelto por `docker compose config` coincide con el `docker inspect` del
    contenedor.
  - **En local:**
    - `curl -sI http://localhost:8000/login` y un `.css` llevan las cuatro cabeceras, sin HSTS, sin
      `X-Powered-By` y con `Server: nginx` sin versión;
    - se recorren las pantallas de cada rol con la consola del navegador sin bloqueos de la CSP;
    - una página de prueba con `<iframe>` no muestra la app.
  - **En el servidor, con aprobación previa:** `verificar.sh` comprobado rompiendo:
    1. poner el marcador en `GOOGLE_CLIENT_ID` sin recrear → FALLO de CA7;
    2. `aplicar-entorno.sh app` → FALLO de CA6;
    3. restaurar el valor y `aplicar-entorno.sh app` → todo OK.
  - **Tailscale, en el servidor y con aprobación previa:**
    - `preparar-acceso-remoto.sh`;
    - `ssh` por la red privada desde el móvil con datos;
    - `sudo reboot` cronometrado (5 minutos o menos);
    - `nmap` desde la red privada: solo el 22;
    - comprobador de puertos externo sobre la IP pública de la casa (22, 80, 443, 3306 y 8080
      cerrados);
    - SSH con contraseña rechazado;
    - un dispositivo de prueba se autoriza, entra, se retira y deja de llegar mientras los demás
      siguen entrando (CA10);
    - `tailscale debug prefs` con `RunSSH: false`.
  - **RS18.a:** fecha de cada etiqueta en Docker Hub (≥ 7×24 h) y `trivy image` sobre las imágenes
    base y las construidas.
  - **npm:** `npm audit` en 0 y `npm run build` correcto.
- **Regresión:** la suite completa (183 tests en la línea base), Pint sobre los PHP tocados,
  `bash -n` sobre los scripts, gitleaks y semgrep `p/php`.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering/Spoofing: otra web incrusta la app para que la persona pulse sin saberlo (clickjacking) | A02:2025 | nginx | `frame-ancestors 'none'` y `X-Frame-Options: DENY` | `CabecerasSeguridadTest`; `verificar.sh`; página de prueba con `<iframe>` |
| TM2 | Tampering: scripts o estilos de terceros inyectados | A05:2025 | nginx, vistas | CSP con orígenes limitados (el código incrustado se acepta como residuo, RS8.d) | `CabecerasSeguridadTest` |
| TM3 | Information disclosure: el navegador deduce el tipo de un archivo o envía la dirección completa a terceros (fuentes) | A02:2025 | nginx | `nosniff` y `Referrer-Policy: strict-origin-when-cross-origin` | `CabecerasSeguridadTest` |
| TM4 | Spoofing: rebaja a http en la primera visita o por un enlace `http` | A04:2025 | nginx de producción | HSTS de 180 días con subdominios, más la precarga de `.app` | `CabecerasSeguridadTest`; `verificar.sh` (pública) |
| TM5 | Information disclosure: identificar las versiones para usar fallos conocidos | A02:2025 | nginx, PHP | `server_tokens off` y `expose_php = Off` | `CabecerasSeguridadTest`; `verificar.sh` |
| TM6 | Spoofing/DoS: vuelta de Google falsificada, repetida o sin inicio (login CSRF, 500 en masa) | A07:2025 | `GoogleAuthController` | Se mantiene la comprobación del `state` de Socialite; el fallo vuelve al login sin sesión ni cuentas, y el límite sigue contando | `GoogleVueltaFallidaTest` |
| TM7 | Repudiation: los fallos de Google no dejan rastro para la auditoría | A09:2025 | `GoogleAuthController` | Evento `Failed` (la 021 lo registrará; excepción P7) | `GoogleVueltaFallidaTest::test_el_fallo_cuenta_como_login_fallido` |
| TM8 | Tampering: un servicio sigue con una configuración caducada o de ejemplo sin que se note | A02:2025 | `verificar.sh`, `aplicar-entorno.sh` | Comprobaciones de CA6 y CA7; recreación con un solo script | `verificar.sh` comprobado rompiendo en el servidor |
| TM9 | Elevation: SSH accesible desde internet, o adivinación de contraseñas | A07:2025 | servidor | Sin puertos (Tailscale solo abre conexiones salientes), OpenSSH solo con clave, `ufw` | `verificar.sh` (puertos y SSH sin contraseña); comprobador de puertos externo |
| TM10 | Spoofing: un dispositivo perdido conserva el acceso | A07:2025 | red privada, `authorized_keys` | Retirar el dispositivo del tailnet y su clave (guía §11) | ensayo de CA10 |
| TM11 | Supply chain: paquete de Tailscale manipulado | A03:2025 | `preparar-acceso-remoto.sh` | Repositorio oficial con `signed-by` y actualizaciones automáticas | `ConfiguracionProduccionTest` |
| TM12 | Elevation: el SSH de Tailscale salta la política de OpenSSH | A07:2025 | `preparar-acceso-remoto.sh` | `--ssh=false` | `ConfiguracionProduccionTest`; `tailscale debug prefs` en el ensayo |
| TM13 | Supply chain: imágenes base con vulnerabilidades conocidas (RS18) | A03:2025 | Dockerfiles, Compose | Refresco a versiones con 7 días o más y `trivy image` | escaneo anotado en la tarea (CA11) |

Casos de abuso de la spec: CA2 → TM1; CA4 → TM5; CA5 → TM6, TM7; CA9 → TM9, TM11, TM12; el
dispositivo perdido (Seguridad y privacidad) → TM10.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: cabeceras de protección sin romper pantallas | `comunes.conf`, `dentissa.conf`, `dentissa.prod.conf` (D1, D2) | `CabecerasSeguridadTest`; `verificar.sh` (cabeceras). Verificación manual: recorrer las pantallas de cada rol en local (en `/implement`) y por la dirección pública (en `/release`) con la consola del navegador sin bloqueos de la CSP |
| CA2 (abuso): clickjacking | `comunes.conf` (D2) | `CabecerasSeguridadTest` (`frame-ancestors` y `X-Frame-Options`). Verificación manual: una página local de prueba con `<iframe>` no muestra la app |
| CA3: solo https durante 180 días, no en local | `produccion.conf` (D3) | `CabecerasSeguridadTest`; `verificar.sh` (pública) |
| CA4 (abuso): sin versiones en las cabeceras | `server_tokens off`, `expose_php = Off` (D4) | `CabecerasSeguridadTest`; `verificar.sh` (sin `X-Powered-By` y `Server` sin versión en `/login`, en un `.css` y en un 404) |
| CA5 (abuso): fallo de la vuelta de Google → login | `GoogleAuthController` (D5) | `GoogleVueltaFallidaTest` (seis casos) |
| CA6: credenciales de Google de ejemplo | `verificar.sh` (D6), `deploy/README.md` §4 y §5 | `verificar.sh` comprobado rompiendo en el servidor |
| CA7: configuración aplicada | `verificar.sh` (D7), `aplicar-entorno.sh` (D8), `deploy/README.md` §10 | prueba de concepto de D7; `verificar.sh` comprobado rompiendo; `aplicar-entorno.sh` en el ensayo |
| CA8: administración desde fuera, vuelve sola tras reiniciar | `preparar-acceso-remoto.sh` (D9), `verificar.sh` (D11), `deploy/README.md` §11 | ensayo: `ssh` por la red privada desde el móvil con datos y `sudo reboot` cronometrado; `verificar.sh` (`tailscaled` en marcha) |
| CA9 (abuso): sin puertos ni contraseña | D9, D11 | `verificar.sh` (puertos y SSH sin contraseña); comprobador de puertos externo; `nmap` desde la red privada; `ConfiguracionProduccionTest` (script) |
| CA10: retirar un dispositivo | `deploy/README.md` §11 | ensayo con un dispositivo de prueba |
| CA11: imágenes refrescadas y escaneadas | Dockerfiles y Compose (D13), `docs/security.md` (EX2) | fechas de Docker Hub y `trivy image` anotados en la tarea; `ConfiguracionProduccionTest::test_las_imagenes_de_produccion_tienen_version_exacta_e_iguales_a_local` |
| RNF: ninguna pantalla pierde funciones ni contenido | D2 | verificación manual de CA1 |
| RNF: la vía desde fuera vuelve sola tras un reinicio en 5 minutos o menos | D9 | `sudo reboot` cronometrado en el ensayo |
| RNF: ningún despliegue sin aprobación explícita | Rollout | T096 |
| TM1: clickjacking | `comunes.conf` (`frame-ancestors`, `X-Frame-Options`) | `CabecerasSeguridadTest`; `verificar.sh` |
| TM2: scripts o estilos de terceros | `comunes.conf` (CSP) | `CabecerasSeguridadTest` |
| TM3: tipo deducido y dirección completa a terceros | `comunes.conf` (`nosniff`, `Referrer-Policy`) | `CabecerasSeguridadTest` |
| TM4: rebaja a http | `produccion.conf` (HSTS) | `CabecerasSeguridadTest`; `verificar.sh` (pública) |
| TM5: versiones visibles | `server_tokens off`, `expose_php = Off` | `CabecerasSeguridadTest`; `verificar.sh` |
| TM6: vuelta de Google falsificada o repetida | `GoogleAuthController` | `GoogleVueltaFallidaTest` |
| TM7: fallos de Google sin rastro | `GoogleAuthController` (`Failed`) | `GoogleVueltaFallidaTest::test_el_fallo_cuenta_como_login_fallido` |
| TM8: configuración caducada o de ejemplo | `verificar.sh`, `aplicar-entorno.sh` | `verificar.sh` comprobado rompiendo en el servidor |
| TM9: SSH desde internet o con contraseña | `preparar-acceso-remoto.sh`, `verificar.sh` | `verificar.sh` (puertos, SSH sin contraseña); comprobador de puertos externo |
| TM10: dispositivo perdido | `deploy/README.md` §11 | ensayo de CA10 |
| TM11: paquete de Tailscale manipulado | `preparar-acceso-remoto.sh` (`signed-by`) | `ConfiguracionProduccionTest` |
| TM12: SSH de Tailscale | `preparar-acceso-remoto.sh` (`--ssh=false`) | `ConfiguracionProduccionTest`; `tailscale debug prefs` en el ensayo |
| TM13: imágenes vulnerables | Dockerfiles y Compose (D13) | `trivy image` anotado (CA11) |

## Observabilidad
- **Logs nuevos:** ninguno. La correlación por petición todavía no existe (spec 020, P8), y los
  fallos de Google quedan identificados por el evento `Failed`.
- **Eventos de auditoría:** ninguno se registra en esta spec (excepción P7). `Failed` se emite en
  `GoogleAuthController::callback` y lo comprueba
  `GoogleVueltaFallidaTest::test_el_fallo_cuenta_como_login_fallido`.
- **Métricas y alertas:** ninguna nueva; las alertas van a la spec 032.
- **Cómo se verifica tras el deploy:**
  - `verificar.sh` con la dirección pública: cabeceras, entorno aplicado y administración remota;
  - en `dentissa-access.log`, ningún 5xx en `/auth/google/callback` tras la liberación.

## Rollout
- **Feature flag:** no aplica. Es configuración de nginx y PHP más una corrección del
  controlador, y la CSP se prueba en local antes de liberar.
- **Orden de despliegue:**
  1. **Durante `/implement`, con aprobación explícita antes de cada ensayo:** instalar Tailscale
     con `preparar-acceso-remoto.sh` y hacer los ensayos de CA8–CA10. Además, desplegar un commit
     de la rama sin cambiar el túnel para comprobar `verificar.sh`, `aplicar-entorno.sh` y las
     imágenes refrescadas.
  2. **En `/release` (v0.6.0, minor), con aprobación:**
     - `desplegar.sh v0.6.0`, que verifica por la red interna;
     - `verificar.sh` con la dirección pública;
     - recorrer las pantallas por la dirección pública;
     - vuelta de Google correcta y cancelada;
     - vigilar 15 minutos.
- **Compatibilidad:** sin migraciones ni cambios de datos. HSTS queda guardado 180 días en los
  navegadores, sin efecto práctico porque el dominio solo funciona con https.
- **Rollback:**
  - `volver.sh v0.5.0`, sin copia porque no hay migraciones, en 15 minutos o menos;
  - Tailscale se desactiva con `sudo tailscale down` (o se desinstala), y el acceso desde la red
    local sigue igual.
- **Métricas a vigilar tras el deploy:**
  - 5xx en `dentissa-access.log` y errores en `laravel.log`;
  - la consola del navegador en las pantallas principales de cada rol;
  - que el login con Google siga funcionando.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Dónde van las cabeceras.** En nginx, con `add_header … always`:
  - `docker/nginx/seguridad/comunes.conf` define CSP, `X-Frame-Options`, `X-Content-Type-Options`
    y `Referrer-Policy`;
  - `produccion.conf` incluye `comunes.conf` y añade HSTS;
  - se incluyen en el `server` y en cada `location` con `add_header` propio, porque nginx no hereda
    las cabeceras en ese caso (el de estáticos tiene `Cache-Control`).

  Descartado: un middleware de Laravel, que no cubre los estáticos ni las respuestas propias de
  nginx; y el panel de Cloudflare, que no queda en el repo ni se puede probar.
- **D2 · Valores de las cabeceras.** Los del contrato. La CSP admite el código incrustado actual
  (`'unsafe-inline'`) y las fuentes de Google y Bunny. Corrección RS8.a dividida: RS8.a, dentro
  (CA1 y CA2); RS8.d, la CSP estricta, fuera → roadmap, por pantallas (decisión del usuario,
  2026-10-05). Descartado: `Content-Security-Policy-Report-Only`, que no protege.
- **D3 · HSTS.** `max-age=15552000; includeSubDomains` (180 días, decisión del usuario,
  2026-10-06), sin `preload`, porque `.app` ya está en la lista de precarga. Solo en producción.
  Corrección RS8.b dividida: RS8.b (producción), dentro (CA3); RS8.c (staging), fuera → objetivo 5.
- **D4 · Versiones.** `server_tokens off` en las dos configuraciones de nginx y `expose_php = Off`
  en `docker/nginx/php/local.ini`. Descartado: solo `fastcgi_hide_header`, que oculta la cabecera
  sin desactivar el origen.
- **D5 · Vuelta de Google.**
  - Si llega `?error=`, no se llama a Socialite.
  - Solo alrededor de `Socialite::driver('google')->user()` se capturan
    `Laravel\Socialite\Two\InvalidStateException` y `GuzzleHttp\Exception\GuzzleException`.
  - En los tres casos se emite `Failed` y se vuelve a `login` con el mensaje.
  - `throttle:google` sigue contando todas las vueltas.
  - Sin `Log::` nuevo (P8).

  Descartado: capturar `\Throwable`, que ocultaría los errores de base de datos posteriores.
- **D6 · Credenciales de Google de ejemplo (CA6).** `verificar.sh` exige que
  `services.google.client_id` cumpla `^[0-9]+-[0-9a-z]+\.apps\.googleusercontent\.com$` y que
  `client_secret` no esté vacío ni contenga `<`, `>` o espacios. El fallo dice cuál es, sin el
  valor.
- **D7 · Configuración aplicada (CA7).**
  - `verificar.sh` toma el entorno resuelto de cada servicio con `docker compose config --format
    json` y lo compara con `Config.Env` de `docker inspect`, solo para las claves que declara el
    Compose, con el `python3` del sistema.
  - Imprime `OK` o `FALLO <servicio> usa una configuración distinta de la guardada (N variables)`,
    sin valores.
  - Si `cloudflared` está parado, no lo compara. En `db`, el fallo avisa de que `db.env` solo se
    aplica al crear la base.

  Descartado: leer los `.env` a mano, que falla con las comillas y la interpolación de Compose. Se
  confirma con una prueba de concepto al empezar `/implement`.
- **D8 · Aplicar un cambio de entorno.** `deploy/aplicar-entorno.sh <app|cloudflared>` (contrato).
  Descartado: solo documentarlo, porque en la liberación de la 030 se olvidó el paso dos veces.
- **D9 · Administración desde fuera** → [ADR 0006](../../adr/0006-administracion-remota-tailscale.md).
  Script `deploy/preparar-acceso-remoto.sh` (sudo, idempotente; decisión del usuario, 2026-10-06):
  - instala Tailscale desde `pkgs.tailscale.com/stable/ubuntu` (`noble`) con `signed-by` y sin
    `curl | sh`;
  - `systemctl enable --now tailscaled`;
  - `tailscale up --ssh=false`, con un alta interactiva;
  - `ufw allow in on tailscale0 to any port 22 proto tcp`;
  - indica desactivar la caducidad de la clave del servidor en la consola.

  Descartado: Cloudflare Access para SSH, abrir un puerto en el router y WireGuard propio (ver el
  ADR).
- **D10 · Verificación de la dependencia nueva** (`shared/agent-security.md` §2): cliente de
  Tailscale.
  - Existe y tiene buena reputación: código abierto (BSD-3, github.com/tailscale/tailscale),
    publicado desde 2019 y muy usado.
  - Se distribuye por un repositorio oficial firmado.
  - El `postinst` solo registra la unidad de systemd.
  - El plan Personal es gratuito, así que cumple la restricción de no tener presupuesto.
- **D11 · Administración en `verificar.sh`.**
  - La regla «nada escucha fuera de localhost salvo SSH» admite las direcciones de Tailscale
    (`100.64.0.0/10` y `fd7a:115c:a1e0::/48`), donde escucha la API entre nodos de `tailscaled`.
  - Comprobaciones nuevas: `tailscale status --json` con `BackendState` en `Running`, y que SSH no
    ofrezca `password` ni `keyboard-interactive`
    (`ssh -o PubkeyAuthentication=no -o BatchMode=yes localhost`, sin root).
- **D12 · Aviso alto de npm** (P6; decisión del usuario, 2026-10-06). Se aplica `npm audit fix`,
  que solo cambia `package-lock.json` y no sube ninguna versión mayor, y luego `npm run build`.
- **D13 · Refresco de imágenes** (RS18.a, CA11; decisión del usuario, 2026-10-06). Mismas reglas que
  en 030 T002: la versión más nueva con 7×24 h o más en Docker Hub, dentro de la misma rama. Son
  `php:8.4.x-fpm`, `composer:2.x`, `nginx:1.30.x-alpine`, `mariadb:10.11.x`, `phpmyadmin:5.2.x`
  (solo en local) y `cloudflare/cloudflared`. Se pasa `trivy image` a las imágenes base y a las
  construidas, y EX2 se renueva solo para lo que no tenga corrección publicada, o se retira.

Estado de las correcciones al liberar:
- RS8.a y RS8.b → mitigadas;
- RS8.c y RS8.d → pendientes (objetivo 5 y roadmap);
- RS8 sigue abierto;
- RS18.a → mitigada;
- RS18 mitigado solo si EX2 se retira.

## Impacto en arquitectura
- `docs/architecture.md`, infraestructura de producción: cabeceras de seguridad en nginx y acceso
  de administración por la red privada (ADR 0006). Lo actualiza T090.
- `docs/deployment.md`: acceso remoto, `aplicar-entorno.sh` y la regla de recrear tras cambiar el
  entorno.
- `docs/security.md`:
  - la fila del SSH en la superficie de ataque;
  - RS8.c y RS8.d nuevas;
  - RS18.a;
  - EX2.

## Riesgos y mitigaciones
- **La CSP rompe una pantalla que nadie probó.**
  - El fragmento es el mismo en local y en producción.
  - Se recorren las pantallas por rol en local y por la dirección pública.
  - La vuelta atrás tarda menos de 15 minutos.
- **`form-action 'self'` bloquea un formulario que redirige a otro origen.** Hoy ninguno lo hace
  (la entrada con Google es un enlace). Lo cubre el recorrido de CA1.
- **Falsos FALLO de CA7 por cómo resuelve Compose los valores.** Se hace una prueba de concepto al
  empezar `/implement`. Si no cuadra, se compara clave a clave solo en `app` y `cloudflared`.
- **Las reglas de `tailscaled` aceptan lo que llega por `tailscale0`.** El control real es que el
  tailnet solo tiene dispositivos del responsable. Está en el ADR 0006 y se comprueba con `nmap`
  desde la red privada (solo el 22).
- **La clave del servidor en Tailscale caduca y el acceso desde fuera se pierde.** Se desactiva la
  caducidad (guía §11). El acceso desde la red local sigue.
- **Una imagen refrescada cambia de comportamiento.** El ensayo en el servidor con
  `verificar.sh --interna` y la suite completa se hacen antes de liberar.
- **El refresco de imágenes no llega antes del 2026-10-31.** Se prioriza en `/tasks`. Si se
  retrasa, EX2 se renueva solo para lo que no tenga corrección publicada.
