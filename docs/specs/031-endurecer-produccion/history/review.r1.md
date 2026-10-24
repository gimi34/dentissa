---
spec: 031-endurecer-produccion
verdict: changes_requested
round: 1
date: 2026-10-19
base: bb57848
head: ba5cdda
human_signoff: no requerido
---

# Review · 031 Endurecer producción tras la apertura

## Resumen
Sin bloqueantes: la verificación automática está en verde y seguridad no encuentra vulnerabilidades
críticas ni altas. El usuario decide corregir los tres importantes del código (R1: «Entorno aplicado»
puede dar OK sin comprobar todos los servicios; R2: `aplicar-entorno.sh` sin test; R3: un test de la
vuelta de Google que no prueba lo que dice) y los menores R5–R11; R4 (CA11 en el despliegue de la
versión) queda como condición de `/release`, y R12–R19 van al roadmap.
Revisores con perfil de modelo: A y S con `opus`; C+D y SEO con `sonnet`. Sin revisor de accesibilidad
(la 031 no toca vistas ni JavaScript).
**Rango:** `bb57848..ba5cdda`. `bb57848` es `main` antes del PR #34, que fusionó en `main` la rama de la
031 hasta `d043e29` el 2026-10-18 al subirla para el ensayo T040. Sin tag ni despliegue sobre ese
merge: producción sigue en v0.9.0. Lo posterior a `d043e29` entra en `main` con otro PR antes del release.
Conteo: bloqueante 0 · importante 4 · menor 15

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` | ✅ 344 tests (6616 aserciones), sin fallos |
| Tests de la 031 (`CabecerasSeguridadTest`, `GoogleVueltaFallidaTest`, `ConfiguracionProduccionTest`), dos veces | ✅ 27 y 27 |
| `pint --test` (PHP tocados) | ✅ 4 archivos |
| `bash -n` (scripts tocados) | ✅ `aplicar-entorno.sh`, `desplegar.sh`, `preparar-acceso-remoto.sh`, `verificar.sh` |
| `aidd.py validate docs/specs/031-endurecer-produccion` | ✅ 0 errores |
| Respuestas en local (nginx y php-fpm recreados) | ✅ CSP, X-Frame-Options, nosniff y Referrer-Policy en `/registro`, `/login`, la 404, la 302 de `/admin` y `robots.txt`; `Server: nginx` sin versión y sin `X-Powered-By` |
| `npm run build` | ➖ no aplica |
| `--medir` | ➖ no aplica (la 031 no toca el layout, `menu.js` ni `movil.css`) |

## Criterios de aceptación
| CA | Test o ensayo | Estado | Nota |
|---|---|---|---|
| CA1 | `CabecerasSeguridadTest`, `verificar.sh` (T040) | ✅ | consola sin bloqueos de la CSP por la dirección pública: en T097 |
| CA2 | `CabecerasSeguridadTest`, iframe desde otro origen (T022) | ✅ | |
| CA3 | `CabecerasSeguridadTest`, `verificar.sh` público (T040) | ✅ | |
| CA4 | `CabecerasSeguridadTest`, `verificar.sh` (T040) | ✅ | |
| CA5 | `GoogleVueltaFallidaTest` | ⚠ | R3: la mitad de un test no prueba nada; faltan el `state` real y el error de red |
| CA6 | `verificar.sh` roto y reparado en el servidor (T040) | ✅ | |
| CA7 | `verificar.sh` y `aplicar-entorno.sh` en el servidor (T040) | ⚠ | R1: puede dar OK sin comprobar todos los servicios; R2: `aplicar-entorno.sh` sin test |
| CA8 | T040, T041 | ✅ | |
| CA9 | T041, `verificar.sh` (SSH solo con clave) | ✅ | R18: la comprobación solo pregunta a localhost |
| CA10 | T042 | ✅ | |
| CA11 | `ConfiguracionProduccionTest`, `trivy image` (T031), construcción sin caché con `REFRESCO_SISTEMA` (T054–T057) | ⚠ | R4: se completa en el despliegue de la versión |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `scope.md` marca `Dockerfile` «sin tarea», pero T029 lo cita (falso positivo).
- Partes sin implementar: ninguna; T001–T003, T031, T042, T050 y T051 solo tocan `docs/` o el servidor.
- Añadidas durante `/implement` con decisión del usuario: T054–T057 (construir sin caché y `REFRESCO_SISTEMA`).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 Test que falla antes y pasa después | ⚠ | R2: `aplicar-entorno.sh` (CA7) sin test; R3: un test de CA5 que pasaría con cualquier implementación |
| P3–P6, P8 | ✅ | sin cambios de autorización ni datos; secretos fuera del repo (gitleaks); SCA limpio; errores sin detalles internos (`GoogleAuthController`) |
| P7 Auditoría | ❌ aceptado | excepción del plan: `Failed` se emite y lo registrará la 021 (R12) |
| P9, P10 | ➖ | sin interfaz ni rutas indexables nuevas (SEO sin brechas altas ni medias) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`bb57848..HEAD`) | ✅ sin fugas |
| semgrep (`p/php --metrics=off`) | ✅ sin hallazgos |
| composer audit + npm audit | crít: 0 · alta: 0 · media: 0 |
| trivy fs | ✅ solo el falso positivo conocido de `vendor/mockery/mockery/docs/requirements.txt` |
| trivy image (T031, 2026-10-19) | residuo en EX2 hasta el 2026-11-30 (`app` sin corrección de Debian; `mariadb` y `cloudflared` con corrección que aún no cumple 7 días) |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A02 Configuración: CSP, X-Frame-Options, nosniff, Referrer-Policy, sin versiones | ✅ | `docker/nginx/seguridad/comunes.conf` con `always`; `server_tokens off`; `expose_php = Off`; residuo `'unsafe-inline'` → RS8.d |
| A04 HSTS solo en producción | ✅ | `produccion.conf`; comprobado por la dirección pública en T040 |
| A07 Acceso administrativo por Tailscale | ✅ | ADR 0006; ACL solo al 22; `--ssh=false`; dispositivo retirado en T042 (R15, R17, R18 al roadmap) |
| A07/A08 Vuelta de Google | ✅ | `state` de Socialite activo (sin `stateless()`), `throttle:google`, nada se vincula ni se crea al fallar (R3: un test débil) |
| A09 Registro | ✅ / ➖ | solo se reporta `GuzzleException`, sin datos personales; auditoría con excepción P7 |
| A10 Errores | ✅ | mensaje genérico; ningún 500 en las variantes probadas (R13: otras excepciones de Socialite) |
| A03 Cadena de suministro | ✅ | etiquetas exactas con 7 días o más, EX2, Tailscale por repositorio firmado (R14, R16 al roadmap) |
| Proceso: rutas protegidas | ✅ | `Dockerfile`, `docker/prod/Dockerfile` y los dos Compose, aprobados por el usuario (notas A11 de T029/T030 y de T056) |

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| Indexación de `/registro` (sin `noindex` ni `X-Robots-Tag` accidental) | ✅ | cabeceras y HTML de `/registro` en local |
| Recursos que la CSP deja pasar para pintar `/registro` (estilos, Google Fonts, logo) | ✅ | `style-src` y `font-src` admiten Google Fonts; el resto, del mismo origen |
| Códigos de estado (404 real, 302 de la ruta privada) | ✅ | respuestas en local |
| Cadena http → https en producción | no determinado | solo hay respuestas locales → comprobación en T097 (R19) |
- Sección «Descubribilidad» del plan: no existe (la spec es anterior a la regla); corresponde «No aplica» (R11).

## Observabilidad
- Eventos de auditoría: `Failed` en la vuelta de Google, sin listener hasta la 021 (excepción P7).
- Datos sensibles en logs del código tocado: ninguno (el `report()` de `GuzzleException` no lleva secretos; R6 quita los argumentos de las trazas).
- `request_id`: no aplica (sin correlación hasta OB2).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `deploy/verificar.sh:186-211` | «Entorno aplicado» puede dar OK sin comprobar todos los servicios: si `python3` lanza una excepción dentro del bucle (`ps -q` vacío en `.split()[0]` con un contenedor que se reinicia, o un `docker inspect` que falla), los servicios ya vistos salen OK, la traza se descarta en el `grep` y los que faltan no suman FALLO; no se mira el código de salida (CA7) | Test que falla hoy y una línea OK/FALLO por cada servicio de `config`, aunque falle la comparación |
| R2 | importante | `deploy/aplicar-entorno.sh` | El script de CA7 no tiene test: quitar el rechazo de `db`, el `--force-recreate --no-deps`, las cachés o la verificación final no rompe nada (P2) | Test de contenido anclado a las órdenes reales |
| R3 | importante | `tests/Feature/GoogleVueltaFallidaTest.php:149-161` | La segunda mitad de `test_el_error_del_proveedor_se_reporta_y_el_estado_invalido_no` no prueba nada: el segundo `Socialite::shouldReceive('driver')` no sustituye al primero (Mockery usa la primera expectativa sin límite), así que vuelve a lanzar `ClientException` y `assertNotReported(InvalidStateException::class)` pasa siempre. Además, ningún test pasa por el comprobador real del `state` (TM6) ni por un error de red (`ConnectException`) | Separar el test; añadir la vuelta sin `state` en la sesión y con un `state` distinto, sin simular Socialite, y un caso con `ConnectException` |
| R4 | importante | `docker-compose.prod.yml:70`; `docker/prod/Dockerfile` | CA11 en el despliegue de la versión: `cloudflared` 2026.10.1 (corrige 5 altas) cumple 7 días el 2026-10-22 y la reconstrucción de `mariadb:10.11.19` el 2026-10-24; en el servidor aún no se vio que la capa `apk upgrade` de `web` no sale «CACHED» con `REFRESCO_SISTEMA`, ni se escaneó ese `web` | Condición de `/release` (T097): refrescar lo que ya cumpla 7 días (rutas protegidas, con aprobación), ver la capa de `web` y pasar `trivy image` al `web` del servidor; ajustar EX2 a lo que quede |
| R5 | menor | `deploy/aplicar-entorno.sh:37` | `grep -qx "${servicio}"` toma el nombre como expresión regular (`.*` pasa) y uno que empiece por `-` como opción | `grep -qxF -- "${servicio}"` |
| R6 | menor | `docker/nginx/php/local.ini` | Sin `zend.exception_ignore_args`, las trazas de `report()` incluyen argumentos de texto truncados (p. ej. el prefijo del `code` de OAuth) | `zend.exception_ignore_args = On` junto a `expose_php = Off` |
| R7 | menor | `deploy/verificar.sh:~298, ~344` | `revisar_cabeceras` mete `X-Powered-By` y `Server` con versión en la lista «faltan» («sin X-Powered-By» se lee al revés); la variable `ruta` del bucle no es `local` | Mensajes «X-Powered-By presente» / «Server con versión»; `local ruta` |
| R8 | menor | `tests/Feature/ConfiguracionProduccionTest.php` (acceso remoto y construcción) | Los regex sobre `preparar-acceso-remoto.sh` y `desplegar.sh` aceptan cualquier línea no comentada con esas palabras (un `echo` o un `paso "build …"`) | Anclar a la orden real (`^\s*tailscale up`, `{compose[@]}" build`…) |
| R9 | menor | `docker/prod/Dockerfile:13-14` | El `echo` de `REFRESCO_SISTEMA` y el `&& apt-get update` van en una línea con relleno, distinto del resto del archivo | `echo … \` y `&& apt-get …` en líneas separadas |
| R10 | menor | `app/Http/Controllers/GoogleAuthController.php:90` | `volverAlLoginPorFallo()` sin tipo de retorno | `: RedirectResponse` |
| R11 | menor | `docs/specs/031-endurecer-produccion/plan.md` | Sin sección «Descubribilidad» (aviso del validador) | «No aplica: solo cabeceras del hosting y la vuelta de Google, sin rutas ni vistas nuevas» |
| R12 | menor | `tests/Feature/GoogleVueltaFallidaTest.php` | P7: el test comprueba que se despacha `Failed`, pero ningún listener lo registra (excepción P7 del plan) | → roadmap: aserción sobre el registro cuando exista (spec 021) |
| R13 | menor | `app/Http/Controllers/GoogleAuthController.php` | Solo se capturan `InvalidStateException` y `GuzzleException`; otra excepción de Socialite seguiría dando 500 | → roadmap |
| R14 | menor | `deploy/preparar-acceso-remoto.sh:46` | La clave del repositorio de Tailscale se baja por HTTPS sin comprobar su huella | → roadmap (o anotarlo en el ADR 0006) |
| R15 | menor | `deploy/README.md:290` | La ACL del tailnet, el control real de TM9, solo se comprobó una vez; nada avisa si vuelve la política por defecto | → roadmap: bloque `"tests"` en la política |
| R16 | menor | `docker-compose.prod.yml`, `docker/prod/Dockerfile` | Imágenes fijadas por etiqueta, no por digest: lo escaneado puede no ser lo que se construye | → roadmap (y el `trivy image` de R4 en T097) |
| R17 | menor | `deploy/verificar.sh:149` | La exclusión de la red privada es por rango de direcciones, no por el proceso `tailscaled` | → roadmap |
| R18 | menor | `deploy/verificar.sh:233` | «SSH sin contraseña» solo pregunta a localhost (un `Match Address` no se vería) | → roadmap |
| R19 | menor | producción | Cadena http → https no comprobada con la respuesta real | → comprobación en T097 (`curl -sIL http://dentissa.app/registro`: un salto y 200) |

## Preparación para release
- Rollback factible: sí; sin migraciones, `deploy/volver.sh v0.9.0` (ensayado en 22 s, T040).
- Migraciones: no hay. Feature flags: el plan no pide ninguno.
- Docs: `architecture.md` (T090); `deployment.md`, `security.md` y `observability.md` (T091).
- SCA sin críticas ni altas; EX2 vigente hasta el 2026-11-30.
- Cobertura de riesgos: RS8.a, RS8.b y RS18.a «en curso (spec 031)»; RS8.c y RS8.d fuera, declaradas en la 030 y en el objetivo 5.
- Condiciones de T097: R4 y R19, más la consola sin bloqueos de la CSP (CA1).

## Tareas añadidas
- T060 ← R1
- T061 ← R1
- T062 ← R2, R5
- T063 ← R5
- T064 ← R3
- T065 ← R6, R7, R8
- T066 ← R6, R7
- T067 ← R9, R10, R11
- T068 ← R1–R3, R5–R11 (verificación final)

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-19:
- **R4** → condición de `/release` (T097): refrescar `cloudflared` y `mariadb` si ya cumplen 7 días, ver la capa de `web` sin «CACHED» y `trivy image` del `web` del servidor; ajustar EX2.
- **R12** → roadmap: aserción sobre el registro de `Failed` cuando exista la auditoría (spec 021).
- **R13** → roadmap: decidir si la vuelta de Google captura cualquier excepción de Socialite.
- **R14** → roadmap: comprobar la huella de la clave del repositorio de Tailscale.
- **R15** → roadmap: bloque `"tests"` en la política del tailnet.
- **R16** → roadmap: fijar por digest las imágenes de producción.
- **R17** → roadmap: excluir de «nada escucha fuera de localhost» solo el proceso `tailscaled`.
- **R18** → roadmap: comprobar el SSH sin contraseña también para la red local y la privada (`sshd -T -C addr=…`).
- **R19** → condición de `/release` (T097): cadena http → https con la respuesta real.
