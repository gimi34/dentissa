---
result: pass
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-11-20
spec_sha: 125ed25ccc50
plan_sha: 94b76e927846
tasks_sha: 4626dc454717
---

# Análisis · 011 Perfil y contraseña del paciente

## Resumen
`pass`: las correcciones de la ronda 1 resuelven A1–A5 y A10 sin efectos nuevos. Los tests de CA6 usan dos sesiones
reales y un identificador de sesión fijado, la spec declara sus riesgos y la excepción P7 cubre los cierres forzados.
Delta sin subagente (`aidd.py changes`: ~26 KB, sin cambio estructural, cinco hallazgos abiertos): se releyeron desde
cero las secciones cambiadas de spec, plan y tareas y el patrón de `SesionVigenteTest` que citan.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 0

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 7 / 7 (CA1–CA5 ya se cumplen, con test de caracterización o existente; CA6 y CA7 con test que falla antes) |
| Amenazas con control y test | 5 / 5 dentro; TM6 fuera → spec 018 (decisión del usuario) |
| Principios de la constitución evaluados | 10 / 10 (sin cambios respecto a la ronda 1 salvo P7, ampliado) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto | `plan.md` Estrategia («Dos sesiones reales», «Rechazos»); `tasks.md` nota de cabecera, T014, T016 y rotura del `put` en T021. La otra sesión entra de verdad y se vuelve a ella con `forgetGuards()`, así que el guard relee la cuenta con la versión nueva, como en `SesionVigenteTest::assertSesionCerrada`. Sin el `put`, la sesión actual guarda la versión del inicio de sesión y se cierra al releer la cuenta (T015). |
| A2 | ALTA | resuelto | `plan.md` Estrategia («La sesión actual»); `tasks.md` T015 y su nota. El cambio parte de un identificador conocido en la cookie y la respuesta debe traer otro: en rojo sin `regenerate()`. Si el arnés no lo permite, la nota obliga a anotarlo antes de seguir. |
| A3 | MEDIA | resuelto | `spec.md` «Cobertura de riesgos» (RS2.a fuera en parte → 015, RS2.b dentro CA7, RS9.a dentro CA6, RS9.b dentro CA5 con el residuo → 018) y «parcialmente» en el Problema; `plan.md` Impacto corrección por corrección, sin declarar mitigado RS2 completo. `aidd.py validate` sin errores. |
| A4 | MEDIA | resuelto | `spec.md` Seguridad y Decisiones; `plan.md` TM6 (fuera → 018), D6 como decisión del usuario; `tasks.md` tabla de amenazas con TM6 fuera. |
| A5 | MEDIA | resuelto | `spec.md` Auditoría y Decisiones (contradicción con fuente `usuario`); `plan.md` P7, Observabilidad y D7; `tasks.md` Constitution Check P7. |
| A6 | BAJA | aceptado | → nota de T021, T022 (sin cambios) |
| A7 | MEDIA | aceptado | → nota de T091 (sin cambios) |
| A8 | BAJA | aceptado | → nota de T012 (sin cambios) |
| A9 | BAJA | aceptado | → nota de T016 (sin cambios) |
| A10 | BAJA | resuelto | `spec.md`: CA3 y CA4 `[x]` con sus tests, evidencia `routes/web.php:212-215` |

## Hallazgos nuevos
Ninguno.

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-11-20 (ronda 1):
- **A6** → nota de T021, T022: sin ningún `DB::raw` nuevo (la versión nueva se calcula como en la 041); T022 lo comprueba con un grep del diff
- **A7** → nota de T091: lista todos los documentos que cambian de destino (README de specs, Fuera de alcance de la 014, `docs/roadmap.md`, `docs/security.md`) para que `/release` no deje ninguna referencia a la 016 ni a la 015 para lo que resuelve la 011
- **A8** → nota de T012: el test cubre también el registro; TM5 se acota a los caminos con test, y si el arnés de Google lo permite, también ese
- **A9** → nota de T016: añadir el rechazo sin `password_actual`, con su mensaje y la contraseña sin cambiar (caracteriza CA3; en rojo al quitar la regla)
