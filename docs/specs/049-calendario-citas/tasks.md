---
spec: 049-calendario-citas
plan: plan.md
status: draft
---

# Tareas · 049 Calendario de citas

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias.
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`).
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.

Tests con `docker run --rm` sobre `dentissa-041` (imagen `dentissa-app`, `vendor_data` de solo lectura, red
`dentissa_dentissa_network`): el contenedor `dentissa_app` monta hoy el clon de otra sesión (plan, Riesgos).
Suite completa al cerrar cada fase; Pint sobre lo tocado.

## Constitution Check
Sobre el plan (constitución 1.3.0), lo que el desglose podría romper:
- ✅ P1: ningún código antes de aprobar este `tasks.md`.
- ✅ P2: cada tarea de implementación depende de sus tests (T010–T017), que fallan antes; T015 y la parte
  del alta de T014 son caracterización y se comprueban rompiendo lo que protegen.
- ✅ P3: T021 conserva `aplicarAlcanceRol` en la consulta única; T015 lo fija por rol.
- ✅ P4: T021 valida cada parámetro; T016 lo prueba.
- ✅ P5: gitleaks en T092; datos sintéticos.
- ✅ P9: T001 (sistema) antes del CSS y las vistas; T017 exige solo tokens y contraste; ningún `style=""` nuevo.
- ➖ P6, P7, P8, P10: sin dependencias, auditoría, logs ni rutas indexables nuevas (plan).

## Preparación
- [ ] T001 Añadir el componente «Calendario de citas» (barra del periodo, semana por horas con carriles, huecos y fuera de horario, tira de seis días, mes, lista del periodo, resumen del periodo) con los cinco refinados del acta, en la versión siguiente a la vigente y con su fila en Decisiones — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: los dos describen el componente del acta («Cambios a incorporar al sistema» y refinados) y `python .ai/bin/aidd.py validate` no da errores

## Tests (antes de implementar)
- [ ] T010 Escribir los unitarios del periodo y la cuadrícula de `CalendarioCitas` (firma vacía que lanza «sin implementar»): semana de lunes a sábado para un lunes, un miércoles y un domingo; mes con sus celdas de lunes a sábado; filas de 9:00 a 20:00 (sábado hasta 14:00 con el resto fuera de horario); filas y domingo añadidos cuando hay citas fuera del horario — `tests/Unit/CalendarioCitasTest.php`, `app/Agenda/CalendarioCitas.php` (firma) — hecho cuando: fallan por «sin implementar» — cubre: CA1, CA3
- [ ] T011 Escribir los unitarios de carriles, huecos y cuentas: dos y tres citas a la vez (carriles y ancho del día), sin coincidencias (un carril); huecos solo libres, en horario y desde el tramo en curso (hora de la clínica), con su fecha y hora; cuentas por día — `tests/Unit/CalendarioCitasTest.php` — hecho cuando: fallan por «sin implementar» — cubre: CA6, CA7 — depende: T010
- [ ] T012 Escribir `CalendarioCitasTest` (Feature, reloj fijo, `EscenarioEstadosCita`): la semana muestra cada cita en su día y tramo con hora, paciente, dentista y estado en texto y su clase de estado (CA1); «Anterior», «Hoy», «Siguiente», «Semana» y «Mes» con `vista` y `fecha` correctos en su `href`, y la página pedida por esos enlaces es la del periodo (CA2) — `tests/Feature/CalendarioCitasTest.php` — hecho cuando: fallan porque la página no tiene calendario — cubre: CA1, CA2
- [ ] T013 Añadir a `CalendarioCitasTest`: mes con «N citas» por día y el día como enlace con `dia` (CA3); lista agrupada por día en orden; con `dia`, solo ese día y «Ver toda la semana»; `buscar`, `dentista_id` y `estado` cambian a la vez calendario y lista (CA4); periodo sin citas con «No hay citas en esta semana» y «Agendar una cita» (CA9); el resumen de cifras es del periodo y su título lo dice (D5) — `tests/Feature/CalendarioCitasTest.php` — hecho cuando: fallan por la ausencia de esos elementos — cubre: CA3, CA4, CA9 — depende: T012
- [ ] T014 Añadir a `CalendarioCitasTest`: cada cita del calendario y de la lista es un único enlace a su detalle, con nombre que empieza por la hora y nombra paciente y dentista (CA5); dos citas que coinciden llevan carril y el día su ancho; canceladas y «no asistió» con su clase atenuada y el estado en texto (CA6); los huecos enlazan a `<rol>.citas.create` con `fecha`, `hora` y, con un solo dentista, `dentista`, y no hay hueco pasado ni fuera de horario (CA7); la tira tiene seis días con sus cuentas y el elegido con `aria-current` (CA8); ningún `<script>` nuevo; y, de caracterización, «Nueva cita» con `fecha`, `hora` o `dentista` no válidos responde 200 y `store` rechaza una cita fuera de las reglas (CA10, TM3) — `tests/Feature/CalendarioCitasTest.php` — hecho cuando: los de CA5–CA8 fallan por la ausencia del calendario; el del alta pasa y se comprueba rompiendo un momento la validación de horario de `store` — cubre: CA5, CA6, CA7, CA8, CA10 — depende: T013
- [ ] T015 Escribir `CalendarioCitasAlcanceTest`: la dentista y la asistente, con `dentista_id` ajeno, otro periodo o la vista de mes, solo ven sus citas en el calendario, la lista, las cuentas por día y el resumen; la administración ve todas (CA10, TM1) — `tests/Feature/CalendarioCitasAlcanceTest.php` — hecho cuando: pasa con la lista de hoy donde aplica y falla donde exige el calendario; tras T021, se comprueba rompiendo un momento `aplicarAlcanceRol` — cubre: CA10
- [ ] T016 Escribir `CalendarioCitasParametrosTest`: cada parámetro no válido (`vista`, `fecha`, `dia` fuera del periodo, `dentista_id` que no es UUID, `estado` desconocido, `buscar` de más de 100 caracteres) da 200 y la semana actual (o el filtro ignorado), sin error; `fecha_desde` abre la semana de esa fecha (CA2, TM2) — `tests/Feature/CalendarioCitasParametrosTest.php` — hecho cuando: fallan los que exigen el calendario — cubre: CA2
- [ ] T017 Escribir `CalendarioCitasCssTest` (trait `ReglasCss`): `calendario.css` existe, `literales()` vacío con control positivo; pares de contraste ≥ 4.5:1 del texto de cada cita sobre su fondo por estado y ≥ 3:1 del anillo de foco y del borde discontinuo de las atenuadas sobre `--white` — `tests/Feature/CalendarioCitasCssTest.php` — hecho cuando: falla porque la hoja no existe — cubre: CA1, CA6, CA8

## Implementación
- [ ] T020 Implementar `CalendarioCitas` (periodo, filas, fuera de horario, carriles, huecos, cuentas, mes, títulos del periodo y del resumen) — `app/Agenda/CalendarioCitas.php` — hecho cuando: T010 y T011 pasan — cubre: CA1, CA3, CA6, CA7 — depende: T010, T011
- [ ] T021 Rehacer `CitasController::index` (validación de parámetros con alias `fecha_desde`, periodo, consulta única con alcance y filtros, resumen del periodo antes del filtro de estado, `CalendarioCitas`; variables `calendario`, `citas`, `dentistas`, `resumen`, `tituloResumen`) y que `store` redirija a la semana y el día de la cita nueva — `app/Http/Controllers/CitasController.php` — hecho cuando: T015 y T016 pasan y `EstadosCitaRolesTest` sigue en verde — cubre: CA2, CA4, CA10 — depende: T015, T016, T020
- [ ] T022 Rehacer la vista de «Citas» con el resumen del periodo, la barra del periodo y la semana por horas (y su tira), dentro de `.formulario-accesible`, con «Nueva cita» de la barra superior también dentro — `resources/views/citas/index.blade.php`, `resources/views/citas/calendario/barra.blade.php`, `resources/views/citas/calendario/semana.blade.php` — hecho cuando: T012 y la parte de CA5–CA8 de T014 pasan — cubre: CA1, CA2, CA5, CA6, CA7, CA8 — depende: T012, T014, T021
- [ ] T023 Añadir el mes y la lista del periodo (día elegido, «Ver toda la semana / todo el mes», vacío, refinados 1 y 2) — `resources/views/citas/calendario/mes.blade.php`, `resources/views/citas/calendario/lista.blade.php` — hecho cuando: T013 pasa — cubre: CA3, CA4, CA9 — depende: T013, T022
- [ ] T024 Escribir `calendario.css` desde `design/candidatos/c6/calendario.css` con los refinados 3 y 4 y cargarlo con `@push('styles')` — `public/css/calendario.css` — hecho cuando: T017 pasa · verificación manual: las cuatro pantallas (semana, día elegido, mes, vacía) a 320, 390, 1024 y 1440 px contra `c6` y los refinados del acta — cubre: CA1, CA6, CA8 — depende: T017, T023
- [ ] T025 Adaptar `MovilPersonalTest` a la lista nueva: la lista de «Citas» sale de las tablas `citas-personal` (D2), del filtro de fechas, de los atajos y del contador de fechas; los casos de las demás listas no cambian (plan D7) — `tests/Feature/MovilPersonalTest.php` — hecho cuando: `MovilPersonalTest` en verde y cada caso retirado o adaptado listado en la nota — cubre: CA8 — depende: T024
- [ ] T026 Medir la build con `design/medir.mjs` (cuentas de medición en la base local, servidor aparte) y comprobar en el navegador que pulsar una cita y pulsar un hueco llevan a su destino; medir el tiempo de un mes con 300 citas — `docs/specs/049-calendario-citas/design/medir.mjs` — hecho cuando: puertas y KPIs del acta en verde en las cuatro pantallas, los dos destinos correctos y el mes en < 1 s (informe en la nota) — cubre: CA1, CA2, CA3, CA4, CA5, CA7, CA8, CA9 — depende: T025

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` (`App\Agenda\CalendarioCitas`, `calendario.css`) y el mapa de `AGENTS.md` (ruta protegida: con aprobación del usuario)
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas (el plan dice que no: dejar nota)
- [ ] T092 Marcar spec como `implemented`: suite completa, Pint, gitleaks sobre el diff de la spec, criterios `[x]` y `docs/specs/README.md`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging: la comprobación se hace en producción, dentro de T097 (semana, mes, día elegido, un hueco que abre «Nueva cita», en escritorio y móvil)
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh vX.Y.Z` (sin migraciones), `verificar.sh` y la comprobación de T095; `5xx` en las tres rutas de «Citas» la primera hora. Rollback: `bash deploy/volver.sh <tag anterior>`
- [ ] T098 Marcar spec como `released`; en la spec 034, una línea en su Historial: la lista de «Citas» del personal la reemplaza la 049 (CA2, CA5 y CA11 para esa lista)

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T012, T017, T026 | T020, T022, T024 |
| CA2 | T012, T016, T026 | T021, T022 |
| CA3 | T010, T013, T026 | T020, T023 |
| CA4 | T013, T026 | T021, T023 |
| CA5 | T014, T026 | T022 |
| CA6 | T011, T014, T017 | T020, T022, T024 |
| CA7 | T011, T014, T026 | T020, T022 |
| CA8 | T014, T017, T026 | T022, T024, T025 |
| CA9 | T013, T026 | T023 |
| CA10 | T014, T015 | T021 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T021 (`aplicarAlcanceRol` en la consulta única) | T015 |
| TM2 | T021 (validación de parámetros) | T016 |
| TM3 | T021 (`store` de hoy, redirección) | T014 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| `docs/design/system.md`, `system.html` | T001 |
| `app/Agenda/CalendarioCitas.php` | T010, T020 |
| `app/Http/Controllers/CitasController.php` | T021 |
| `resources/views/citas/index.blade.php` | T022 |
| `resources/views/citas/calendario/barra.blade.php`, `semana.blade.php` | T022 |
| `resources/views/citas/calendario/mes.blade.php`, `lista.blade.php` | T023 |
| `public/css/calendario.css` | T024 |
| `tests/Unit/CalendarioCitasTest.php` | T010, T011 |
| `tests/Feature/CalendarioCitasTest.php` | T012, T013, T014 |
| `tests/Feature/CalendarioCitasAlcanceTest.php` | T015 |
| `tests/Feature/CalendarioCitasParametrosTest.php` | T016 |
| `tests/Feature/CalendarioCitasCssTest.php` | T017 |
| `tests/Feature/MovilPersonalTest.php` | T025 |
| `docs/specs/049-calendario-citas/design/medir.mjs` | T026 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| WCAG 2.1 AA, 44 px en el móvil | T017, T024, T026 |
| Sin JavaScript | T014, T026 |
| Mes de ~300 citas en < 1 s | T026 |
