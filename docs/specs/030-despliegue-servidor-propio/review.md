---
spec: 030-despliegue-servidor-propio
verdict: changes_requested
round: 1
date: 2026-10-04
base: 1a4e7cc
head: a3ef507
human_signoff: no requerido
---

# Review · 030 Producción en un servidor propio expuesto a internet

## Resumen
**changes_requested.** Hay un bloqueante. El límite de intentos del login se elude con variantes
acentuadas del mismo correo (R1, CA8/TM3): la base compara el correo sin acentos y el limitador
cuenta cada variante por separado. A eso se suman cinco importantes:
- R2, R3 y R4: robustez de los scripts de despliegue;
- R5: un test que falta;
- R6: rotación de IPv6 para eludir los límites.

El usuario decide corregir R1–R6 y los menores R8–R11 y R19, y acepta sin tarea el resto de
menores.

Revisores independientes (subagentes de contexto limpio): A (spec), C+D (constitución y calidad) y
S (seguridad). La spec no toca la interfaz, así que no hay revisor de accesibilidad.

Conteo: bloqueante 1 · importante 5 · menor 15

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 180 en verde (1384 aserciones); línea base de T001: 152, más 28 nuevos |
| Los 28 tests nuevos, segunda ejecución | ✅ 28 en verde (sin intermitencias) |
| `vendor/bin/pint --test` sobre los 10 PHP tocados | ✅ PASS (T050; sin cambios de código desde entonces) |
| `bash -n deploy/*.sh` | ✅ los seis |
| `python .ai/bin/aidd.py validate docs/specs/030-despliegue-servidor-propio` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | ensayo T040 y T046 en el servidor; `deploy/verificar.sh` | ⚠ | Despliegue desde cero comprobado. En el primer despliegue, la verificación de `desplegar.sh` falla siempre antes de rotar las credenciales y sugiere volver atrás (R4) |
| CA2 | `ProxyHttpsTest`, `ConfiguracionProduccionTest` (redirección) | ✅ | La parte pública se comprueba en la liberación (T097). El caso de la cookie es de caracterización (R7, A15) |
| CA3 | `ConfiguracionProduccionTest`; T041 en el servidor | ✅ | |
| CA4 | `ErroresProduccionTest`; `verificar.sh` | ✅ | |
| CA5 | `RotarCredencialesTest`, `ConfiguracionProduccionTest`; T042 | ✅ | `verificar.sh` no comprueba en cada despliegue que el usuario de la base no sea root (R11) |
| CA6 | ensayo T043 | ⚠ | Si la comprobación de datos de `desplegar.sh` falla, se despliega sin copia (R2) |
| CA7 | ensayo T044 | ✅ | |
| CA8 | `LimiteIntentosTest` | ❌ | Se elude con variantes acentuadas del correo (R1). Falta el test de "el éxito limpia el contador" (R5). Rotación IPv6 (R6) |
| CA9 | ensayo T045 | ⚠ | Los logs se conservan, pero `artisan` como root puede dejar `laravel.log` sin permiso de escritura para php-fpm (R3) |
| CA10 | `ConfiguracionProduccionTest`; T033, T042 | ✅ | `post_max_size` no se verifica (R11). Upgrade de paquetes solo en producción (R15) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno sin justificar. `scope.md` marca `Dockerfile` como "sin tarea", pero lo cita T024 (falso positivo del analizador). Las tareas añadidas durante `/implement` (T015, T034–T038) llevan su nota y la decisión del usuario.
- Partes del plan sin implementar: ninguna. T002, T003, T032 y T036 no aparecen en `code.diff` porque solo tocan documentación.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Spec, plan y tareas aprobados; `/analyze` r3 pass (re-análisis de las tareas añadidas omitido por decisión del usuario) |
| P2 | ⚠ | Tests escritos antes con su fallo anotado y controles comprobados rompiendo, salvo `RateLimiter::clear()` (R5) |
| P3 | ➖ | Sin cambios de autorización |
| P4 | ✅ | El comando usa Eloquent; sin entradas nuevas |
| P5 | ✅ | gitleaks limpio; secretos fuera del clon y de la imagen (`ConfiguracionProduccionTest`, T042) |
| P6 | ✅ | composer audit y npm audit limpios; trivy image dentro de la excepción EX2 |
| P7 | ❌ aceptado | Excepción hasta la 020; `Lockout` emitido y probado |
| P8 | ✅ | Sin logs nuevos; el comando no escribe contraseñas en el log (test) |
| P9 | ➖ | Sin cambios de interfaz |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`1a4e7cc..HEAD`) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` (app, bootstrap, routes) | ✅ 0 hallazgos |
| composer audit `--locked` / npm audit | ✅ crít: 0 · alta: 0 · media: 0 |
| trivy 0.57.1 fs | ✅ solo el falso positivo conocido de `vendor/mockery/mockery/docs/requirements.txt` (027/R20) |
| trivy 0.57.1 image (`app`, `web`) | ⚠ `web` 0. `app`: 114 altas y 3 críticas sin corrección publicada, cubiertas por EX2 (vence 2026-10-31) |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ➖ | Sin rutas nuevas; puertos y red comprobados (T041) |
| A02 Configuración | ✅ | `APP_DEBUG=false`, sin puertos publicados, local en `127.0.0.1`. R17 (red de `db` con salida) aceptado |
| A03 Cadena de suministro | ✅ | Imágenes fijadas y escaneadas; EX2 vigente |
| A04 Criptografía y secretos | ✅ | HTTPS, cookie `Secure`, secretos 600 fuera del clon; copias sin cifrar → spec 031 |
| A05 Inyección | ⚠ | Sin SQL nuevo; en `verificar.sh`, `APP_URL` va dentro de `bash -c` (R10) |
| A07 Autenticación | ❌ | Límite de login eludible con variantes del correo (R1) y con rotación IPv6 (R6) |
| A08 Integridad | ⚠ | `desplegar.sh` sigue sin copia si falla la comprobación de datos (R2) |
| A09 Logging | ⚠ | `laravel.log` creado como root (R3) |
| A10 Errores | ✅ | Error genérico (`ErroresProduccionTest`) |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (excepción P7 hasta la 020). `Lockout` se emite en los rechazos de login y Google (`test_el_rechazo_por_limite_emite_el_evento_de_bloqueo`).
- Datos sensibles en logs del código tocado: ninguno. El comando de credenciales no escribe en el log (test).
- `request_id` presente en los logs nuevos: no hay logs nuevos.
- Logs persistentes en producción: sí (volúmenes `storage` y `nginx_logs`; T045), con el riesgo de propiedad de R3.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | bloqueante | `app/Http/Controllers/AuthController.php:42` | La clave del limitador es `minúsculas(email)\|ip`, pero `usuarios.email` usa `utf8mb4_unicode_ci`, que no distingue acentos, y la regla `email` acepta partes locales con acentos. Comprobado: `ádmin.prueba@…` autentica la cuenta `admin.prueba@…`. Cada variante abre un contador nuevo con 5 intentos sobre la misma cuenta (CA8, TM3). | Contar por la identidad de la cuenta cuando exista, sin cambiar el mensaje; test con variantes de mayúsculas y acentos de la misma cuenta. |
| R2 | importante | `deploy/desplegar.sh:42-45,64` | `base_con_datos` descarta stderr y no mira el código de salida de `docker compose run`. Si ese `run` falla con el volumen lleno, el script anuncia "Primer despliegue" y sigue sin copia (CA6). Lo señalaron los revisores A, C+D y S. | Si el volumen existe y la comprobación falla, abortar con `error`. |
| R3 | importante | `deploy/desplegar.sh:84,90`, `deploy/volver.sh`, `deploy/verificar.sh`, `deploy/README.md` | `artisan` y `php -r` corren como root dentro de `app`. Si root crea `storage/logs/laravel.log` en el volumen (la marca de T045 lo hizo con `tinker`), php-fpm (`www-data`) ya no puede escribir en el log. | Ejecutar como `www-data` en los scripts y en la guía; reparar la propiedad en el servidor y comprobarla. |
| R4 | importante | `deploy/desplegar.sh:97`, `deploy/README.md` §6 | En el primer despliegue, la verificación falla siempre porque las contraseñas del README aún no están rotadas, y el script propone `volver.sh`. La guía no lo avisa (CA1). | En el primer despliegue, no proponer la vuelta atrás: avisar de que hay que rotar las credenciales y verificar de nuevo, y explicarlo en la guía. |
| R5 | importante | `app/Http/Controllers/AuthController.php:57`, `tests/Feature/LimiteIntentosTest.php` | "El éxito limpia el contador" (Contratos) no tiene test: sin `RateLimiter::clear()`, `LimiteIntentosTest` sigue 6/6 (comprobado). | Test: 4 fallos, un login correcto y 5 fallos más sin bloqueo; el 6.º sí bloquea. |
| R6 | importante | `AuthController.php:42`, `AppServiceProvider.php:40,48` | Los límites cuentan por la IP exacta. Con IPv6 (Cloudflare la entrega por defecto), un cliente con un /64 puede rotar de dirección sin coste y eludir los límites del alta, de Google y del login. | Contar las IPv6 por su prefijo /64; test con dos direcciones del mismo /64 y una de otro. |
| R7 | menor | `tests/Feature/ProxyHttpsTest.php:70-77` | El caso de la cookie `Secure` pasa con cualquier implementación: es de caracterización de `session.secure` (ya declarado así, A15). En producción lo protege `verificar.sh` (`SESSION_SECURE_COOKIE=true`). | Aceptado; ver Aceptados sin tarea. |
| R8 | menor | `deploy/restaurar.sh:41-45` | Si la carga falla después del `DROP DATABASE`, el script sale sin mensaje y la base queda vacía. | Mensaje de error que indique que la base quedó vacía y cómo repetir la restauración. |
| R9 | menor | `deploy/verificar.sh:52-64` | El bloque de configuración ignora el código de salida de `php` (`\|\| true`): si `php` muere a mitad, las comprobaciones que no llegan a ejecutarse no cuentan como fallo. El temporal `/tmp/verificar-config.$$` tiene nombre predecible y no se limpia si se interrumpe. | Exigir las líneas esperadas o el código de salida; `mktemp` con `trap`. |
| R10 | menor | `deploy/verificar.sh:128-132` | `APP_URL` se interpola dentro de `bash -c "…'${url}'…"`: una comilla en el valor rompe el entrecomillado (inyección de comandos por construcción; la fuente es el propio responsable). | Llamar a `curl` directamente, sin `bash -c`. |
| R11 | menor | `deploy/verificar.sh:56-60,108` | No comprueba `post_max_size` (también limita las subidas, CA10) ni que el usuario de la base no sea root (CA5) en cada despliegue. | Añadir las dos comprobaciones. |
| R12 | menor | `deploy/verificar.sh:70` | Las candidatas del README se sacan por patrón: incluye la palabra "asistente" y, cuando RS6.a quite las contraseñas, habrá 0 candidatas y la comprobación fallará. | Aceptado; ver Aceptados sin tarea. |
| R13 | menor | `app/Console/Commands/RotarCredencialesVolcado.php:37` | El comando cambia la contraseña de todas las cuentas que la tienen, no solo las del volcado; volver a ejecutarlo con usuarios reales los dejaría sin acceso. | Aceptado; ver Aceptados sin tarea. |
| R14 | menor | `tests/Feature/LimiteIntentosTest.php:184,213` | El desbloqueo pasada la espera solo se prueba en el login, no en el alta ni en Google. | Aceptado; ver Aceptados sin tarea. |
| R15 | menor | `docker/prod/Dockerfile:13,66` | `apt-get upgrade` y `apk upgrade` solo en producción: los paquetes del sistema difieren de local (D14 y T035), frente a la letra de CA10. | Aceptado; ver Aceptados sin tarea. |
| R16 | menor | `app/Http/Controllers/AuthController.php:44-70` | Ventana entre `tooManyAttempts` y `hit`: peticiones simultáneas pueden superar los 5 intentos (acotado por los procesos de php-fpm). | Aceptado; ver Aceptados sin tarea. |
| R17 | menor | `docker-compose.prod.yml:89-92` | `db` comparte la red `produccion`, con salida a internet, sin necesitarla (A38). | Aceptado; ver Aceptados sin tarea. |
| R18 | menor | `tests/Feature/ConfiguracionProduccionTest.php:118,145,161-163` | La regex de archivos de entorno no detecta un `.env` a secas; la aserción de la ruta del clon es redundante y solo se mira la primera aparición de `DENTISSA_ENV_DIR`; la regla de redirección no se ancla al nivel de `server`. | Aceptado; ver Aceptados sin tarea. |
| R19 | menor | `docs/specs/030-despliegue-servidor-propio/plan.md:41` | El Constitution Check de P5 cita `test_el_paquete_excluye_secretos_y_sobrantes`, que no existe (el real es `test_los_archivos_de_entorno_quedan_fuera_del_paquete_y_del_repo`). | Corregir el nombre en el plan. |
| R20 | menor | `app/Providers/AppServiceProvider.php:42,53` | El provider depende de un controlador para el mensaje común. | Aceptado; ver Aceptados sin tarea. |
| R21 | menor | `app/Console/Commands/RotarCredencialesVolcado.php:38` | La rama `password_hash = ''` no tiene test. | Aceptado; ver Aceptados sin tarea. |

## Preparación para release
- Rollback factible: sí. Vuelta atrás ensayada en 27 s con restauración (T044), y parar `cloudflared` deja la app sin exposición.
- Migraciones compatibles con la versión anterior: no hay migraciones nuevas.
- Feature flags: no aplica.
- Docs actualizadas: `architecture.md` (T090), `deployment.md`, `security.md` y `observability.md` (T091), `roadmap.md` (T051).
- SCA: sin críticas ni altas en dependencias; las de las imágenes, en EX2.
- Cobertura de riesgos: cada corrección "dentro" figura "en curso (spec 030)"; las de staging se dividieron y quedan pendientes. Nada figura como mitigado antes de la release.
- Antes de `/release`: actualizar la rama con `main` (diseño 1.2.0, PR #17).

## Tareas añadidas
- T060 ← R1 (test)
- T061 ← R5 (test)
- T062 ← R6 (test)
- T063 ← R1
- T064 ← R6
- T065 ← R2
- T066 ← R3
- T067 ← R4
- T068 ← R3, R9, R10, R11
- T069 ← R8
- T070 ← R19
- T071 ← R3 (servidor)

## Aceptados sin tarea
- **R7** → roadmap: el caso de la cookie `Secure` de `ProxyHttpsTest` es de caracterización de `session.secure`; en producción lo protege `verificar.sh` (`SESSION_SECURE_COOKIE=true`).
- **R12** → roadmap: las contraseñas candidatas de `verificar.sh` salen del README por patrón; revisarlo al cerrar RS6.a (sin contraseñas en el README, 0 candidatas).
- **R13** → roadmap: `dentissa:rotar-credenciales` cambia todas las cuentas con contraseña; limitarlo a las del volcado o pedir confirmación por número antes de usarlo con usuarios reales.
- **R14** → roadmap: el desbloqueo pasada la espera solo se prueba en el login (falta en el alta y en Google).
- **R15** → roadmap: `apt-get upgrade`/`apk upgrade` solo en producción (paquetes del sistema distintos de local).
- **R16** → roadmap: ventana entre `tooManyAttempts` y `hit` en el login con peticiones simultáneas.
- **R17** → roadmap: separar `db` en una red `internal` solo con `app`.
- **R18** → roadmap: endurecer tres regex de `ConfiguracionProduccionTest` (`.env` a secas, todas las apariciones de `DENTISSA_ENV_DIR`, redirección a nivel de `server`).
- **R20** → roadmap: mover el mensaje común de límite fuera del controlador.
- **R21** → roadmap: test del caso `password_hash = ''` del comando de credenciales.
