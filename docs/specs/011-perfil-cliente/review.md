---
spec: 011-perfil-cliente
verdict: approved
round: 2
date: 2026-11-21
base: 6f07615
head: a022d3f
human_signoff: no requerido
---

# Review · 011 Perfil y contraseña del paciente

## Resumen
`approved`: R1 y R2 de la ronda 1 están corregidos y probados (T023–T026), sin hallazgos nuevos. R3–R9 siguen aceptados
como deuda y `/release` los lleva al roadmap.
`--rerun` sin subagentes: el diff desde la ronda 1 son 5,3 KB en 2 archivos (`PerfilController`, `PerfilClienteTest`);
se releyó desde cero.
Conteo: bloqueante 0 · importante 0 · menor 0 (nuevos)

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, contenedor efímero) | ✅ 1513 en verde, 19 633 aserciones (ronda 1: 1511; línea base: 1502) |
| `vendor/bin/pint --test` sobre los 2 PHP tocados | ✅ |
| `aidd.py validate docs/specs/011-perfil-cliente` | ✅ 0 errores |

## Criterios de aceptación
Sin cambios respecto a la ronda 1 (`history/review.r1.md`): CA1–CA7 ✅. CA6 suma
`PerfilClienteTest::test_un_cambio_simultaneo_cierra_tambien_la_sesion_actual` (R1) y
`…_la_sesion_antigua_desaparece_del_almacen` (R2).

## Conformidad con el plan
- Cambios fuera del plan: ninguno; los dos archivos tienen tarea (T023–T026, añadidas por `/review`).
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
Sin cambios respecto a la ronda 1. P2: T023 y T025 fallaban antes de T024 y T026. P4: `lockForUpdate()` y `update` del
query builder con bindings, sin `DB::raw` (semgrep 0). P7: sigue la excepción aceptada; el diff no añade eventos ni logs.

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`dir` sobre los 2 archivos) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` | ✅ 0 hallazgos |
| `composer audit` + `auditar-npm.mjs` | sin cambios de dependencias desde la ronda 1 (crít: 0 · alta: 0) |
| trivy | sin cambios de dependencias ni de imágenes desde la ronda 1 (0 altas o críticas) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A07:2025 Sesiones (TM2) — concurrencia (R1) | ✅ | `PerfilController.php:131-140`: transacción con `lockForUpdate()`; la sesión guarda la versión que escribe su cambio; el hash se calcula fuera, así el bloqueo dura poco |
| A07:2025 Fijación de sesión (TM3) — sesión antigua (R2) | ✅ | `PerfilController.php:144`: `regenerate(true)` borra la sesión antigua del almacén |
| Resto de temas | ✅ | sin cambios respecto a la ronda 1 |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: sin cambios (excepción P7; el `acceso_denegado` de CA7 sigue probado).
- Datos sensibles en logs del código tocado: ninguno (sin `Log::` nuevos).
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## Hallazgos
### Seguimiento de la ronda 1
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| R1 | menor | resuelto | T023 (en rojo antes de T024; en rojo al volver a leer la versión tras escribir), T024 |
| R2 | menor | resuelto | T025 (en rojo antes de T026; en rojo sin el `true`), T026 |
| R3–R9 | menor | aceptados | sin cambios; ver «Aceptados sin tarea» |

### Nuevos
Ninguno.

## Preparación para release
- Rollback factible: sí, `bash deploy/volver.sh <tag anterior>` sin copia (sin migraciones).
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Docs actualizadas: `docs/architecture.md` (T090); `docs/deployment.md` y `docs/observability.md` sin cambios (T091).
  `docs/security.md`, el roadmap, el índice de specs y la 014 se concilian en `/release` (nota A7 de T091).

## Tareas añadidas
- T023, T024 ← R1 (ronda 1)
- T025, T026 ← R2 (ronda 1)

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-21 (ronda 1):
- **R3** → roadmap: el test de TM3 no comprueba directamente que el identificador antiguo deje de valer (lo cubren el de las otras sesiones y, desde R2, el del almacén)
- **R4** → roadmap: un doble envío del cambio de contraseña puede dejar fuera al propio paciente
- **R5** → roadmap: el cambio de contraseña desde el perfil no avisa por correo ni deja evento (excepción P7 → 016, 018 o 021)
- **R6** → roadmap: `assertForbidden()` sin el rol en el mensaje en el test de CA7
- **R7** → roadmap: separar los tres caminos del test de «Recordarme»
- **R8** → roadmap: el límite de 5 intentos escrito a mano en el test de rechazos
- **R9** → roadmap: constantes del test en mitad de la clase
