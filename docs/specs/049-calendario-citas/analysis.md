---
result: fail
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-11-17
spec_sha: 0f9f96e0a224
plan_sha: 7e61e4d2b464
tasks_sha: 352638968d37
---

# Análisis · 049 Calendario de citas

## Resumen
`fail` por decisión del usuario. Las seis ALTAS de la ronda 1 están resueltas y no aparece ninguna nueva.
Quedan tres hallazgos MEDIOS y tres BAJOS. Uno de ellos (A28) cambia el CA7 por decisión del usuario, y el
usuario decide corregirlos todos antes de aprobar. La siguiente es la ronda 3, delta.
Análisis delta (175 líneas cambiadas desde la ronda 1; tareas ~39 %, plan ~25 %; sin criterios nuevos) con un
subagente independiente (perfil `standard`). Cada hallazgo se verificó abriendo el archivo citado.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 3 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 3 / 3 |
| Principios de la constitución evaluados | 10 / 10 (delta: P1, P2, P3, P4, P9 afectados por los cambios) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto con efecto nuevo (→ A27) | T021 ya no exige tests de página; T023 exige `EstadosCitaRolesTest` |
| A2 | ALTA | resuelto | T018 (`test_tras_agendar_se_vuelve_a_la_semana_y_al_dia_de_la_cita`); T021 depende de T018 |
| A3 | ALTA | resuelto | T028 adapta `capturar.mjs` con control positivo; T026 depende de T028 |
| A4 | ALTA | resuelto | Plan D10; T026 mide la build renderizada con los datos y la hora de los candidatos |
| A5 | ALTA | resuelto | Plan «Huecos» (`H:i` con cero); T011 y T014 (`09:00`) |
| A6 | ALTA | resuelto | Plan P9 y T017 con todos los pares nuevos |
| A7 | MEDIA | resuelto | T014 y T022 («ningún `style=""`») |
| A8 | MEDIA | resuelto | Spec, acta y plan con cinco refinados; «Fuera de alcance» ajustado |
| A9 | MEDIA | resuelto con efecto nuevo (→ A28) | CA7 y plan «Huecos» con la decisión del 2026-11-16 |
| A10 | MEDIA | resuelto con efecto nuevo (→ A31) | Plan «Posición de las citas no alineadas»; T010 con 10:15 y 45 min |
| A11 | MEDIA | resuelto | Plan «Filtros y navegación»; T012 y T013 |
| A12 | MEDIA | resuelto con efecto nuevo (→ A27) | Plan «Día del móvil»; T014 (`data-dia-movil`, título del móvil) |
| A13 | MEDIA | resuelto con efecto nuevo (→ A29) | Plan P4/TM3; T014 y T029 (`fecha`, `hora`, `dentista`) |
| A14 | MEDIA | resuelto | Plan P3 (excepción al Gate justificada); T015 |
| A15 | MEDIA | resuelto | `origin/main` fusionada (6564f55); el componente va en el sistema 1.19.0 (T001) |
| A16 | MEDIA | resuelto | Spec RNF; T026 (local) y T097 (producción) |
| A17 | MEDIA | resuelto | T022, T023 y T024 dependen de T001 |
| A18 | MEDIA | resuelto con efecto nuevo (→ A27) | T022 ya no exige la lista |
| A19 | MEDIA | resuelto | T024 (844×390) y T098 |
| A20 | MEDIA | resuelto | Clases `.cal-lista-*`; T001/T024 actualizan el «Filtro plegable» de `system.md` |
| A21 | BAJA | resuelto | Plan «Diseño»: variante con nombre accesible fijado |
| A22 | BAJA | resuelto | Nota de T025 |
| A23 | BAJA | resuelto | `@push('styles')` en T022 |
| A24 | BAJA | resuelto con efecto nuevo (→ A30) | Plan «desde el siguiente tramo»; T011 con «ahora» a las 11:20 |
| A25 | BAJA | resuelto | T024 mide a 900 y 1024 px |
| A26 | BAJA | resuelto | Fila en el Historial de la spec |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A27 | Inconsistencia | MEDIA | T022, T023, T014 | T022 exige las partes de CA8 de T014 (día y título del móvil), pero esas piezas las construye la lista de T023; e `index.blade.php` (T022) incluye parciales de T023 | Cada tarea se cierra en su orden con lo que construye: el «hecho cuando» de T022 no exige el día ni el título del móvil, y T023 sí → `/tasks --fix` |
| A28 | Ambigüedad | MEDIA | spec CA7; plan «Huecos»; T011, T014 | «Sin cita vigente a la vista» no dice qué hacen `buscar` y `estado`: con un filtro, se ofrecerían huecos sobre citas reales ocultas | Decisión del usuario (2026-11-17): los huecos se calculan con todas las citas vigentes del alcance; solo cuenta el filtro de dentista (o la vista de la dentista); `buscar` y `estado` no crean huecos. Test: con `estado=cancelada` y con `buscar`, ningún hueco en un tramo con una cita vigente oculta → `/specify --edit`, `/plan --fix`, `/tasks --fix` |
| A29 | Seguridad (P4) | MEDIA | plan P4/TM3; T014, T029; `CitasController::create` (`paciente_id`) | La corrección de A13 no cubre `paciente_id`, que `create` también lee y usa en consultas sin comprobar que sea texto | Todo parámetro no escalar de «Nueva cita», `paciente_id` incluido, da 200 y se ignora (test: `?paciente_id[]=x` en T014) → `/plan --fix`, `/tasks --fix` |
| A30 | Ambigüedad | BAJA | spec CA7; T011 | No se fija si a las 11:30:00 en punto el tramo de las 11:30 se ofrece | Límite fijado en el plan y probado en T011 con «ahora» a las 11:30:00 → `/plan --fix`, `/tasks --fix` |
| A31 | Cobertura | BAJA | plan «Posición de las citas no alineadas»; T011 | La regla de que una cita 10:15/45 min ocupa los tramos de 10:00 y 10:30 para los huecos no se prueba | Caso en T011: sin hueco en 10:00 ni 10:30 con esa cita → `/tasks --fix` |
| A32 | Redacción | BAJA | plan P1; `design.md` (resumen «Hoy, martes 17») | P1 da la spec por `approved` y está en `draft`; `design.md` describe el resumen de «hoy» que el refinado 5 cambia | P1 sin fecha de aprobación obsoleta; `design.md` aclara que esas líneas son del candidato → `/plan --fix` y acta |

## Decisiones pendientes del usuario
Resueltas el 2026-11-17: A28 (huecos con todas las citas vigentes; solo cuenta el filtro de dentista) y
corregir A27–A32 antes de aprobar, en lugar de aceptarlos como notas.

## Aceptados
Ninguno: el usuario decide corregirlos todos (`/specify --edit`, `/plan --fix`, `/tasks --fix`) y repetir
`/analyze` (ronda 3, delta).
