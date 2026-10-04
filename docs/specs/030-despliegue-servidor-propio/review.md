---
spec: 030-despliegue-servidor-propio
verdict: approved
round: 2
date: 2026-10-04
base: a3ef507
head: 6baa003
human_signoff: no requerido
---

# Review · 030 Producción en un servidor propio expuesto a internet

## Resumen
**approved.** Los once hallazgos que el usuario decidió corregir en la ronda 1 están corregidos
(T060–T071): el bloqueante R1, los importantes R2–R6 y los menores R8–R11 y R19. No aparecen
hallazgos nuevos. Los diez menores aceptados sin tarea en la ronda 1 siguen igual.

El diff de esta ronda (`a3ef507..6baa003`, 18 KB) se revisó directamente, releyendo los archivos
desde cero, como prevé `--rerun` para un diff pequeño.

Conteo de la ronda: bloqueante 0 · importante 0 · menor 0.

Ronda anterior: [history/review.r1.md](history/review.r1.md).

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 183 en verde (1394 aserciones); 180 en la ronda 1, más los 3 tests nuevos de T060–T062 |
| `LimiteIntentosTest`, segunda ejecución | ✅ 9/9 |
| `vendor/bin/pint --test` (AuthController, AppServiceProvider, LimiteIntentosTest) | ✅ PASS |
| `bash -n deploy/*.sh` | ✅ los seis |
| `python .ai/bin/aidd.py validate docs/specs/030-despliegue-servidor-propio` | ✅ 0 errores (un aviso: `tasks.md` cita RS18, que nació durante `/implement` y no está en la Cobertura de riesgos de la spec) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | ensayos T040 y T046; `verificar.sh` | ✅ | El primer despliegue ya no sugiere volver atrás: avisa de rotar las credenciales y verificar de nuevo (R4, T067) |
| CA2 | `ProxyHttpsTest`, `ConfiguracionProduccionTest` | ✅ | Sin cambios en esta ronda |
| CA3 | `ConfiguracionProduccionTest`; T041 | ✅ | `verificar.sh` exige ahora todas sus comprobaciones (T068) |
| CA4 | `ErroresProduccionTest`; `verificar.sh` | ✅ | |
| CA5 | `RotarCredencialesTest`, `ConfiguracionProduccionTest`; T042; `verificar.sh` | ✅ | `verificar.sh` comprueba en cada despliegue que la base no se usa como root (R11) |
| CA6 | ensayo T043 | ✅ | `desplegar.sh` se detiene si no puede comprobar los datos (R2, T065) y `restaurar.sh` avisa si la carga falla (R8, T069) |
| CA7 | ensayo T044 | ✅ | |
| CA8 | `LimiteIntentosTest` (9 casos) | ✅ | Variantes de la misma cuenta comparten contador (R1); el éxito limpia el contador (R5); IPv6 por /64 (R6) |
| CA9 | ensayo T045; T071 | ✅ | `artisan` como `www-data`; en el servidor, `laravel.log` vuelve a ser de `www-data` y se puede escribir (R3) |
| CA10 | `ConfiguracionProduccionTest`; T033, T042; `verificar.sh` | ✅ | `post_max_size` comprobado (R11) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `scope.md` muestra 0 archivos sin tarea. T066 añadió `docs/deployment.md` como archivo extra justificado: las órdenes de producción allí documentadas pasan a llevar `-u www-data`.
- Partes del plan sin implementar: ninguna. T070 solo toca `plan.md`, por eso no aparece en `code.diff`.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 | ✅ | T060 y T062 se escribieron antes y fallaban; T061 se comprobó rompiendo (sin `RateLimiter::clear()` falla); T063 y T064, rompiendo (clave por correo → falla T060; IP exacta → falla T062) |
| P1, P3–P9 | ✅ / ➖ / ❌ aceptado (P7) | Sin cambios respecto a la ronda 1 |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`a3ef507..HEAD`) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` (app, bootstrap, routes) | ✅ 0 hallazgos |
| composer audit `--locked` | ✅ sin avisos |
| trivy image | ➖ sin cambios en Dockerfiles ni imágenes desde la ronda 1 (EX2 vigente) |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A07 Autenticación | ✅ | La clave del login es la cuenta (`login:cuenta:<id>\|<origen>`), buscada con la misma comparación que la autenticación, y las IPv6 cuentan por su /64 en los tres límites (`AuthController::origenLimite`); tests T060–T062 |
| A05 Inyección | ✅ | `verificar.sh` llama a `curl` directamente; `APP_URL` ya no pasa por `bash -c` |
| A08 Integridad | ✅ | Sin despliegue sin copia cuando no se pueden comprobar los datos |
| A09 Logging | ✅ | Ningún `artisan` ni `php` de los scripts como root; propiedad reparada en el servidor (T071) |
| Resto | ✅ / ➖ | Sin cambios respecto a la ronda 1 |

## Observabilidad
- Eventos de auditoría: no aplica (excepción P7). `Lockout` sigue emitiéndose y probándose.
- Datos sensibles en logs del código tocado: ninguno; los cambios no añaden logs.
- Logs de producción: los escribe `www-data` (T066, T068, T071).

## Hallazgos
Seguimiento de la ronda 1:

| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| R1 | bloqueante | corregido | T060 (variantes de ancho completo de la misma cuenta; las tildes ya las pliega `RateLimiter::cleanRateLimiterKey`) y T063 (`AuthController::claveLimiteLogin`) |
| R2 | importante | corregido | T065: `base_con_datos` llama a `error` si el volumen existe y la comprobación falla; probado en 4 casos |
| R3 | importante | corregido | T066 y T068 (`-u www-data` en scripts, guía y `deployment.md`); T071 en el servidor: `laravel.log` era `root:root 644`, ahora es `www-data:www-data` y admite escritura |
| R4 | importante | corregido | T067: aviso de primer despliegue en lugar de `volver.sh`; guía §6 |
| R5 | importante | corregido | T061, comprobado rompiendo |
| R6 | importante | corregido | T062 y T064; probado `origenLimite` con IPv4, IPv6 y `::ffff:a.b.c.d` |
| R8 | menor | corregido | T069 |
| R9, R10, R11 | menor | corregido | T068; ejecutado contra una pila de prueba (todo OK salvo `ss` en Windows) y con `db` parada (detecta "9 de 10") |
| R19 | menor | corregido | T070 |
| R7, R12–R18, R20, R21 | menor | aceptados sin tarea (ronda 1) | sin cambios |

Hallazgos nuevos: ninguno.

## Preparación para release
- Rollback factible: sí (vuelta atrás ensayada en 27 s, T044; parar `cloudflared` deja la app sin exposición).
- Migraciones: no hay migraciones nuevas.
- Feature flags: no aplica.
- Docs actualizadas: `architecture.md` (T090), `deployment.md`, `security.md` y `observability.md` (T091, y `deployment.md` en T066), `roadmap.md` (T051).
- SCA: sin críticas ni altas en dependencias; las de las imágenes, en EX2 (vence 2026-10-31).
- Cobertura de riesgos: correcciones "dentro" en curso, nada mitigado antes de la release. RS18 (imágenes base) está en `security.md` y en el roadmap; la release decide cómo citarlo en la spec (aviso del validador).
- Antes de `/release`: actualizar la rama con `main` (diseño 1.2.0, PR #17) y subirla.

## Tareas añadidas
(Ronda 1: T060 ← R1, T061 ← R5, T062 ← R6, T063 ← R1, T064 ← R6, T065 ← R2, T066 ← R3, T067 ← R4, T068 ← R3, R9, R10, R11, T069 ← R8, T070 ← R19, T071 ← R3; todas hechas.)

## Aceptados sin tarea
Aceptados por el usuario en la ronda 1 (2026-10-04); ver [history/review.r1.md](history/review.r1.md):
- **R7** → roadmap: el caso de la cookie `Secure` de `ProxyHttpsTest` es de caracterización de `session.secure`; en producción lo protege `verificar.sh` (`SESSION_SECURE_COOKIE=true`).
- **R12** → roadmap: las contraseñas candidatas de `verificar.sh` salen del README por patrón; revisarlo al cerrar RS6.a (sin contraseñas en el README, 0 candidatas).
- **R13** → roadmap: `dentissa:rotar-credenciales` cambia todas las cuentas con contraseña; limitarlo a las del volcado o pedir confirmación por número antes de usarlo con usuarios reales.
- **R14** → roadmap: el desbloqueo pasada la espera solo se prueba en el login (falta en el alta y en Google).
- **R15** → roadmap: `apt-get upgrade`/`apk upgrade` solo en producción (paquetes del sistema distintos de local).
- **R16** → roadmap: ventana entre `tooManyAttempts` y `hit` en el login con peticiones simultáneas.
- **R17** → roadmap: separar `db` en una red `internal` solo con `app`.
- **R18** → roadmap: endurecer tres regex de `ConfiguracionProduccionTest` (`.env` a secas, todas las apariciones de `DENTISSA_ENV_DIR`, redirección a nivel de `server`).
- **R20** → roadmap: mover el mensaje común de límite (y ahora `origenLimite`) fuera del controlador.
- **R21** → roadmap: test del caso `password_hash = ''` del comando de credenciales.
