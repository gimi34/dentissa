---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-16
spec_sha: 2b378963e6dc
plan_sha: c25886861f8d
tasks_sha: 1dbf66c2a51d
---

# Análisis · 049 Calendario de citas

## Resumen
`fail`: seis hallazgos altos. Una tarea no se puede cerrar en su orden (A1); un cambio de comportamiento
no tiene test (A2); el test del móvil en el navegador (`capturar.mjs`) se rompería sin tarea (A3); la
medición de la build no es posible tal como está (A4); el candidato genera horas sin cero inicial que el
alta rechaza (A5); y P9 se verifica a mano en varios pares de contraste (A6). Además, la 046 ya está en
`main` (v0.20.0) y la rama no la tiene (A15). Análisis completo con un subagente independiente; cada
hallazgo se verificó abriendo el archivo citado.
Conteo: CRÍTICA 0 · ALTA 6 · MEDIA 14 · BAJA 6

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 3 / 3 |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia | ALTA | tasks T021; `citas/index.blade.php` (`hasPages`, `firstItem`, `total`) | T021 exige que pasen tests que necesitan la vista de T022, y con `citas` sin paginar la vista actual da 500 | El «hecho cuando» de cada tarea se puede cumplir en su orden; `EstadosCitaRolesTest` en verde donde se exija → `/tasks --fix` |
| A2 | Constitución (P2) | ALTA | plan «Cambios por módulo»; T021 | La redirección de `store` a la semana y el día de la cita nueva no tiene test que falle antes | Test: tras un alta válida, el destino lleva `vista`, `fecha` y `dia` de la cita; en Trazabilidad → `/plan --fix`, `/tasks --fix` |
| A3 | Inconsistencia | ALTA | `docs/design/capturar.mjs` (filtro con atajos, `.filtro-atajos`, tabla de citas, `citas-index-*` en la comparación) | La 049 retira atajos y tabla: `capturar.mjs --medir` quedaría en rojo sin tarea | `capturar.mjs --medir` pasa con la «Citas» nueva (comprobaciones de la 034 retiradas o adaptadas, `citas-index-*` fuera de la comparación); tarea con control positivo → `/plan --fix`, `/tasks --fix` |
| A4 | Inconsistencia | ALTA | T026; `design/medir.mjs`; `criterios.json` | `medir.mjs` no puede medir la build: sin URL por pantalla, sin sesión y con textos de datos ficticios fijos | La build se mide con los mismos datos y reloj que los candidatos (páginas renderizadas por la app con datos y hora fijos) y queda una pasada en verde → `/plan --fix`, `/tasks --fix` |
| A5 | Inconsistencia | ALTA | `c6/*.html` (`hora=9:00`); alta (`date_format:H:i`, `input type=time`) | Las horas de los huecos sin cero inicial no las acepta el alta | Hora de los huecos siempre `H:i` con cero; T011/T014 con un caso de las 9:00 → `/plan --fix`, `/tasks --fix` |
| A6 | Constitución (P9) | ALTA | plan P9; T017; `c6/calendario.css` | Solo algunos pares de contraste nuevos se prueban; el resto, a mano (y el número de los días de otro mes da 3,51:1) | `CalendarioCitasCssTest` con la lista completa de pares texto/fondo y borde/fondo nuevos (o justificación de lo oculto) → `/plan --fix`, `/tasks --fix` |
| A7 | Constitución (P9) | MEDIA | `c6/semana.html` (resumen con `style=""`) | La vista se rehace y conserva `style=""` | `citas/index` y sus parciales sin `style=""`; test sobre el HTML → `/tasks --fix` |
| A8 | Inconsistencia | MEDIA | spec, acta, plan («cuatro correcciones»); spec «Fuera de alcance» | Los refinados son cinco; el 5 cambia el resumen de cifras, que la spec deja fuera de alcance | Spec, acta y plan con los cinco; la spec registra el cambio del resumen → `/specify --edit`, `/plan --fix` |
| A9 | Ambigüedad | MEDIA | spec CA6, CA7; plan «Huecos» | No se define qué es un hueco libre (canceladas, varios dentistas) | Decisión del usuario (2026-11-16): hueco donde no hay cita vigente a la vista; las canceladas y «no asistió» no lo tapan; con una dentista, los suyos → `/specify --edit`, `/plan --fix` |
| A10 | Ambigüedad | MEDIA | spec CA1; plan; T010/T011 | Citas no alineadas a 30 min (10:15, 45 min) sin regla de posición ni de huecos | Regla fijada y probada con 10:15 y 45 min → `/plan --fix`, `/tasks --fix` |
| A11 | Ambigüedad | MEDIA | spec CA4; `c6` (enlaces y formulario) | No se dice si la navegación conserva los filtros ni si el filtro conserva el periodo | La navegación conserva `buscar`, `dentista_id` y `estado`; «Filtrar» y «Limpiar» conservan el periodo; T012/T013 lo prueban → `/plan --fix`, `/tasks --fix` |
| A12 | Ambigüedad | MEDIA | spec CA8; plan; T014 | Día por defecto del móvil fuera de la semana de hoy y título del móvil sin test | Por defecto, hoy si está en el periodo y, si no, el primer día; marcas en el HTML probadas en T014 → `/plan --fix`, `/tasks --fix` |
| A13 | Seguridad (P4) | MEDIA | `create` (`old('fecha', …)` con array); T014, T016 | Parámetros no escalares dan 500 en `create` y no se prueban en `index` | Todo parámetro no escalar en `index` y `create` da 200; arreglo en `create` → `/plan --fix`, `/tasks --fix` |
| A14 | Constitución (P3) | MEDIA | plan P3; T015 | Falta el 403 por rol de `GET /{rol}/citas` y la justificación del alcance sin Gate | Caracterización de 403 (paciente y personal en grupos ajenos); excepción al Gate justificada → `/plan --fix`, `/tasks --fix` |
| A15 | Conflicto | MEDIA | plan Riesgos; `git diff HEAD...origin/main` | La 046 ya está en `main` (v0.20.0, sistema 1.18.0) y toca `citas/create`, `capturar.mjs`, `app.css` y `system.md` | Fusionar `origin/main` antes de T001 (componente en 1.19.0) y reconciliar `capturar.mjs` con A3 → `/plan --fix`, `/tasks --fix` |
| A16 | Cobertura RNF | MEDIA | spec RNF; T026, T097 | El RNF dice «en producción» y se mide en local | Decisión del usuario (2026-11-16): medida en local (< 1 s con 300 citas) y comprobación del usuario en producción en T097 → `/specify --edit`, `/tasks --fix` |
| A17 | Inconsistencia | MEDIA | tasks T022, T024 | No dependen de T001 | Dependen de T001 → `/tasks --fix` |
| A18 | Inconsistencia | MEDIA | T022 | Exige la parte de la lista de CA5, que es de T023 | Cada tarea se cierra con lo suyo → `/tasks --fix` |
| A19 | Inconsistencia | MEDIA | 034 CA8 (844×390) | La 049 no mide ni reemplaza 034 CA8 | Se mantiene: verificación a 844×390 → `/plan --fix`, `/tasks --fix` |
| A20 | Inconsistencia | MEDIA | `c6/calendario.css` (`.lista-citas`); `app.css` (027); `system.md` «Filtro plegable» | Colisión con la clase `.lista-citas` de la 027; el sistema seguiría describiendo «Desde/Hasta» | Clases propias del componente; `system.md` describe el filtro de «Citas» tal como queda → `/plan --fix`, `/tasks --fix` |
| A21 | Inconsistencia | BAJA | spec (componente de la 048) | Ambigüedad entre reutilizar «Cita que abre su detalle» o una variante | → plan (variante: el bloque o la fila entera es el enlace; nombre accesible fijado) |
| A22 | Inconsistencia | BAJA | `c6` (`filtros.js` con `defer`) | La 034 lo exige en `<head>` sin `defer` | → nota de T025: `test_la_lista_de_citas_carga_filtros_js_en_head` no cambia |
| A23 | Inconsistencia | BAJA | T024 | El `@push` está en un archivo de otra tarea | → `/tasks --fix` (el `@push` en T022) |
| A24 | Ambigüedad | BAJA | spec CA7 | ¿Se ofrece el tramo en curso? | → plan (no: desde el tramo siguiente) y T011 con «ahora» a mitad de tramo |
| A25 | Riesgos | BAJA | plan Riesgos | La franja 900–1023 se mide a 1024 | → plan y T024 (medir a 900) |
| A26 | Redacción | BAJA | spec Historial | Sin fila de la reaprobación | → `/specify --edit` |

## Decisiones pendientes del usuario
Resueltas el 2026-11-16: A9 (hueco donde no hay cita vigente a la vista; con una dentista, los suyos) y
A16 (medida en local y comprobación del usuario en producción).

## Aceptados
Ninguno todavía: todos van a `/specify --edit`, `/plan --fix` y `/tasks --fix`.
