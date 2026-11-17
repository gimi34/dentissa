---
spec: 049-calendario-citas
plan: plan.md
status: approved
impl_base: eb65b90
---

# Tareas · 049 Calendario de citas

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias.
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`).
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.

Rama con `origin/main` fusionado (v0.20.0, spec 046) en `/analyze` r1. Tests con `docker run --rm` sobre
`dentissa-041` (imagen `dentissa-app`, `vendor_data` de solo lectura, red `dentissa_dentissa_network`):
el contenedor `dentissa_app` monta el clon de otra sesión (plan, Riesgos). Suite completa al cerrar cada
fase; Pint sobre lo tocado.

## Constitution Check
Sobre el plan (constitución 1.3.0), lo que el desglose podría romper:
- ✅ P1: ningún código antes de aprobar este `tasks.md`.
- ✅ P2: cada tarea de implementación depende de sus tests (T010–T018), que fallan antes; T015 y la parte
  del alta de T014 son caracterización y se comprueban rompiendo lo que protegen; la redirección de `store`
  tiene su test (T018).
- ✅ P3: T021 conserva `aplicarAlcanceRol` en la consulta del periodo y la aplica en la de ocupación de los huecos; T015 lo fija por rol, con el 403 de cada grupo.
- ✅ P4: T021 valida cada parámetro (también no escalares); T016 lo prueba; T029 arregla `create` con T014.
- ✅ P5: gitleaks en T092; datos sintéticos.
- ✅ P9: T001 (sistema) antes del CSS y las vistas (T022, T023 y T024 dependen de él); T017 prueba todos los
  pares de contraste nuevos; T014 exige la página sin `style=""`.
- ➖ P6, P7, P8, P10: sin dependencias, auditoría, logs ni rutas indexables nuevas (plan).

## Preparación
- [x] T001 Añadir el componente «Calendario de citas» (barra del periodo, semana por horas con carriles, huecos y fuera de horario, tira de seis días, mes, lista del periodo con clases `.cal-lista-*`, resumen del periodo) con los cinco refinados del acta, en 1.19.0 y con su fila en Decisiones; y actualizar «Filtro plegable (spec 034)»: en «Citas» ya no hay «Desde/Hasta» ni atajos — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: los dos describen el componente y el filtro como quedan, y `python .ai/bin/aidd.py validate` no da errores

## Tests (antes de implementar)
- [x] T010 Escribir los unitarios del periodo y la cuadrícula de `CalendarioCitas` (firma vacía que lanza «sin implementar»): semana de lunes a sábado para un lunes, un miércoles y un domingo; mes con sus celdas de lunes a sábado; filas de 9:00 a 20:00 (sábado hasta 14:00, el resto fuera de horario); filas y domingo añadidos cuando hay citas fuera del horario; una cita de 10:15 y 45 min ocupa los tramos de 10:00 y 10:30 — `tests/Unit/CalendarioCitasTest.php`, `app/Agenda/CalendarioCitas.php` (firma) — hecho cuando: fallan por «sin implementar» — cubre: CA1, CA3
- [x] T011 Escribir los unitarios de carriles, huecos y cuentas: dos y tres citas a la vez (carriles y ancho del día), una cancelada junto a otra (la atenuada ocupa carril); huecos con «ahora» a las 11:20 (el primero, el de las 11:30) y a las 11:30:00 en punto (el primero, el de las 12:00) (A30), ninguno en tramos con cita vigente, ninguno en 10:00 ni 10:30 con una cita de 10:15 y 45 min (A31), ninguno sobre una cita vigente que `buscar` o `estado` dejan fuera de la vista (A28), sí junto a una cancelada o «no asistió», con una sola dentista solo sus tramos libres, ninguno fuera de horario; la hora del hueco de las 9:00 es `09:00`; cuentas por día — `tests/Unit/CalendarioCitasTest.php` — hecho cuando: fallan por «sin implementar» — cubre: CA6, CA7 — depende: T010
- [x] T012 Escribir `CalendarioCitasTest` (Feature, reloj fijo, `EscenarioEstadosCita`): la semana muestra cada cita en su día y tramo con hora, paciente, dentista y estado en texto y su clase de estado (CA1); «Anterior», «Hoy», «Siguiente», «Semana» y «Mes» llevan `vista` y `fecha` correctos y conservan `buscar`, `dentista_id` y `estado`; la página pedida por esos enlaces es la del periodo (CA2) — `tests/Feature/CalendarioCitasTest.php` — hecho cuando: fallan porque la página no tiene calendario — cubre: CA1, CA2
- [x] T013 Añadir a `CalendarioCitasTest`: mes con «N citas» por día y el día como enlace con `dia` (CA3); lista agrupada por día en orden; con `dia`, solo ese día y «Ver toda la semana»; `buscar`, `dentista_id` y `estado` cambian a la vez calendario y lista; el formulario lleva `vista`, `fecha` y `dia` ocultos y «Limpiar» conserva el periodo (CA4); periodo sin citas con «No hay citas en esta semana» y «Agendar una cita» (CA9); el resumen de cifras es del periodo o del día elegido y su título lo dice (D5) — `tests/Feature/CalendarioCitasTest.php` — hecho cuando: fallan por la ausencia de esos elementos — cubre: CA3, CA4, CA9 — depende: T012
- [x] T014 Añadir a `CalendarioCitasTest`: cada cita del calendario y de la lista es un único enlace a su detalle, con nombre «<hora>, <paciente>, <dentista>, <estado>» (CA5); dos citas que coinciden llevan carril y el día su ancho; canceladas y «no asistió» con su clase atenuada y el estado en texto (CA6); los huecos enlazan a `<rol>.citas.create` con `fecha`, `hora` (`09:00` con cero) y, con una sola dentista, `dentista`, y con `estado=cancelada` o con `buscar` no aparece ningún hueco en el tramo de una cita vigente oculta (CA7, A28); la tira tiene seis días con sus cuentas y el elegido con `aria-current`; el día del móvil (`data-dia-movil`) es el elegido, o hoy si está en el periodo, o el primero, y el título del móvil lo nombra (CA8); ningún `<script>` nuevo y ningún `style=""` en la página (A7); y, de caracterización, «Nueva cita» con `fecha`, `hora` o `dentista` mal formados responde 200 y `store` rechaza una cita fuera de las reglas (CA10, TM3); con `?fecha[]=x`, `?hora[]=x`, `?dentista[]=x` o `?paciente_id[]=x`, «Nueva cita» responde 200 (A13, A29) — `tests/Feature/CalendarioCitasTest.php` — hecho cuando: los de CA5–CA8 y los no escalares de «Nueva cita» fallan (por la ausencia del calendario y por el 500 de hoy); el resto del alta pasa y se comprueba rompiendo un momento la validación de horario de `store` — cubre: CA5, CA6, CA7, CA8, CA10 — depende: T013
  - nota: fallan los de CA5–CA8 (sin calendario; 41 `style=""` en la página de hoy) y los no escalares de «Nueva cita» (500 con `fecha[]=x`). `test_el_alta_ignora_parametros_no_validos` pasa desde el principio (caracterización): comprobado rompiendo un momento la franja de `ReservaCitas::comprobarHorario` (`if (false && …)`): falla con «Session is missing expected key [errors]»; restaurado.
- [x] T015 Escribir `CalendarioCitasAlcanceTest`: la dentista y la asistente, con `dentista_id` ajeno, otro periodo o la vista de mes, solo ven sus citas en el calendario, la lista, las cuentas por día y el resumen, y una cita ajena a su alcance no les quita ningún hueco (A28); la administración ve todas; y, de caracterización, el paciente recibe 403 en `/admin/citas`, `/dentista/citas` y `/asistente/citas`, la dentista y la asistente en `/admin/citas` y en el grupo de la otra (CA10, TM1) — `tests/Feature/CalendarioCitasAlcanceTest.php` — hecho cuando: los 403 pasan y se comprueban quitando un momento el `role:` del grupo; los del calendario fallan porque no existe — cubre: CA10
  - nota: los 403 pasan; comprobado quitando un momento `role:dentista` del grupo de la dentista (el paciente recibe 200 en `/dentista/citas`); restaurado. Los del calendario fallan porque no hay resumen del periodo ni calendario. La comprobación rompiendo `aplicarAlcanceRol` se hace en T023, cuando pasen.
- [x] T016 Escribir `CalendarioCitasParametrosTest`: cada parámetro no válido (`vista`, `fecha`, `dia` fuera del periodo, `dentista_id` que no es UUID, `estado` desconocido, `buscar` de más de 100 caracteres) o no escalar (`?vista[]=`, `?fecha[]=`) da 200 y la semana actual (o el filtro ignorado), sin error; `fecha_desde` abre la semana de esa fecha (CA2, TM2) — `tests/Feature/CalendarioCitasParametrosTest.php` — hecho cuando: fallan los que exigen el calendario — cubre: CA2
- [x] T017 Escribir `CalendarioCitasCssTest` (trait `ReglasCss`): `calendario.css` existe y `literales()` vacío con control positivo; todos los pares nuevos con su contraste: texto ≥ 4,5:1 de cada cita por estado y de las atenuadas sobre `--white`, opción actual de la vista y cabecera del día elegido (`--white` sobre `--p600`), «N citas» (`--p600` sobre `--white`), hora de la lista, «Cerrado» sobre `--white`, y el número de los días de otro mes (o su `aria-hidden` justificado); bordes y foco ≥ 3:1 (anillo, borde discontinuo, interruptor) — `tests/Feature/CalendarioCitasCssTest.php` — hecho cuando: falla porque la hoja no existe — cubre: CA1, CA6, CA8
- [x] T018 Escribir `test_tras_agendar_se_vuelve_a_la_semana_y_al_dia_de_la_cita`: tras un alta válida, la redirección lleva `vista=semana`, `fecha` y `dia` de la cita nueva — `tests/Feature/CalendarioCitasTest.php` — hecho cuando: falla porque hoy redirige con `fecha_desde` — depende: T014

## Implementación
- [x] T020 Implementar `CalendarioCitas` (periodo, filas, fuera de horario, posición de citas no alineadas, carriles, huecos según la decisión del usuario, cuentas, mes, títulos del periodo y del resumen) — `app/Agenda/CalendarioCitas.php` — hecho cuando: T010 y T011 pasan — cubre: CA1, CA3, CA6, CA7 — depende: T010, T011
  - nota: hecha antes que T012–T018 (solo depende de T010 y T011). La clase recibe además la dentista que muestra el calendario (`$dentista`, o null): filtra la ocupación de los huecos y la lleva al enlace; así «con una sola dentista, sus tramos libres» se prueba sin base de datos. En T011 se corrigió un caso: «tres a la vez» tenía la tercera cita a las 16:00, que no coincidía a la vez con las otras dos (máximo real 2); pasa a las 15:30. Semgrep y gitleaks limpios.
- [ ] T021 Rehacer `CitasController::index` (validación de parámetros escalares con alias `fecha_desde`, periodo, consulta del periodo con alcance y filtros, consulta de ocupación de los huecos con alcance y dentista pero sin `buscar` ni `estado` (A28), resumen del periodo antes del filtro de estado, `CalendarioCitas`; variables `calendario`, `citas`, `dentistas`, `resumen`, `tituloResumen`) y que `store` redirija a la semana y el día de la cita nueva — `app/Http/Controllers/CitasController.php` — hecho cuando: T018 pasa y los unitarios siguen en verde (los tests de la página se exigen en T022 y T023, porque la vista de hoy no sirve sin paginación) — cubre: CA2, CA4, CA10 — depende: T018, T020
- [ ] T029 Hacer que «Nueva cita» ignore `fecha`, `hora`, `dentista` y `paciente_id` no escalares o mal formados (los propone solo si son texto con su formato) — `app/Http/Controllers/CitasController.php` — hecho cuando: los casos no escalares de T014 pasan — cubre: CA10 — depende: T014, T021
- [ ] T022 Rehacer la vista de «Citas» con el resumen del periodo sin `style=""`, la barra del periodo y la semana por horas (y su tira), dentro de `.formulario-accesible`, con «Nueva cita» de la barra superior también dentro, el `@push('styles')` de `calendario.css` y `filtros.js` como hoy; `index` incluye el mes y la lista de T023 solo cuando existen sus parciales, para que la página se sostenga sin ellos (A27) — `resources/views/citas/index.blade.php`, `resources/views/citas/calendario/barra.blade.php`, `resources/views/citas/calendario/semana.blade.php` — hecho cuando: T012 y las partes del calendario de T014 (CA5 en el calendario, CA6, CA7, y de CA8 la tira de seis días con sus cuentas y `aria-current`) pasan; el día y el título del móvil se cierran en T023 (A27) — cubre: CA1, CA2, CA5, CA6, CA7, CA8 — depende: T001, T012, T014, T021
- [ ] T023 Añadir el mes y la lista del periodo (día elegido, «Ver toda la semana / todo el mes», vacío, día y título del móvil, refinados 1, 2 y 5) — `resources/views/citas/calendario/mes.blade.php`, `resources/views/citas/calendario/lista.blade.php` — hecho cuando: T013, T014 (la parte de la lista, el día del móvil `data-dia-movil` y su título, y `style=""`), T015, T016 y `EstadosCitaRolesTest` pasan; T015 se comprueba rompiendo un momento `aplicarAlcanceRol` — cubre: CA3, CA4, CA5, CA8, CA9, CA10 — depende: T001, T013, T022
- [ ] T024 Escribir `calendario.css` desde `design/candidatos/c6/calendario.css` con los refinados 3 y 4 y las clases `.cal-lista-*` — `public/css/calendario.css` — hecho cuando: T017 pasa · verificación manual: las cuatro pantallas (semana, día elegido, mes, vacía) a 320, 390, 844×390, 900, 1024 y 1440 px contra `c6` y los refinados del acta — cubre: CA1, CA6, CA8 — depende: T001, T017, T023
- [ ] T025 Adaptar `MovilPersonalTest` a la lista nueva: la lista de «Citas» sale de las tablas `citas-personal` (D2), del filtro de fechas, de los atajos y del contador de fechas; los casos de las demás listas no cambian y `test_la_lista_de_citas_carga_filtros_js_en_head` sigue igual (A22) — `tests/Feature/MovilPersonalTest.php` — hecho cuando: `MovilPersonalTest` en verde y cada caso retirado o adaptado listado en la nota — cubre: CA8 — depende: T024
- [ ] T028 Adaptar `capturar.mjs` a la «Citas» nueva: retirar o pasar a la pantalla nueva las comprobaciones de la 034 sobre la lista del personal (atajos en una línea, foco de «Hoy», capturas `tabla` y `badges-estado`) y sacar `citas-index-{admin,dentista,asistente}` de la comparación del escritorio; ejecutar `capturar.mjs --medir` — `docs/design/capturar.mjs` — hecho cuando: `--medir` termina en verde y se comprueba con un control positivo (una comprobación de «Citas» rota a propósito falla) — cubre: CA8 — depende: T025
- [ ] T026 Renderizar las cuatro páginas del acta con la app (datos y hora de los candidatos) y medirlas con `design/medir.mjs`; comprobar en la app con sesión que pulsar una cita y un hueco llevan a su destino; medir en local un mes con 300 citas — `.ai/inspect/049-render-build.php` (no versionado), `docs/specs/049-calendario-citas/design/medir.mjs` — hecho cuando: puertas y KPIs del acta en verde en las cuatro páginas, los dos destinos correctos y el mes en < 1 s (informe en la nota) — cubre: CA1, CA2, CA3, CA4, CA5, CA7, CA8, CA9 — depende: T028

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` (`App\Agenda\CalendarioCitas`, `calendario.css`) y el mapa de `AGENTS.md` (ruta protegida: con aprobación del usuario)
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas (el plan dice que no: dejar nota)
- [ ] T092 Marcar spec como `implemented`: suite completa, Pint, gitleaks sobre el diff de la spec, criterios `[x]` y `docs/specs/README.md`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging: la comprobación se hace en producción, dentro de T097 (semana, mes, día elegido, un hueco que abre «Nueva cita», en escritorio y móvil)
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh vX.Y.Z` (sin migraciones), `verificar.sh` y la comprobación de T095; `5xx` en las tres rutas de «Citas» la primera hora; el usuario abre el mes en producción y comprueba que carga sin esperas (RNF). Rollback: `bash deploy/volver.sh <tag anterior>`
- [ ] T098 Marcar spec como `released`; en la spec 034, una línea en su Historial: la lista de «Citas» del personal la reemplaza la 049 (CA2, CA5 y CA11 para esa lista; CA8 sigue)

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T012, T017, T026 | T020, T022, T024 |
| CA2 | T012, T016, T026 | T021, T022 |
| CA3 | T010, T013, T026 | T020, T023 |
| CA4 | T013, T026 | T021, T023 |
| CA5 | T014, T026 | T022, T023 |
| CA6 | T011, T014, T017 | T020, T022, T024 |
| CA7 | T011, T014, T026 | T020, T022 |
| CA8 | T014, T017, T026, T028 | T022, T023, T024, T025 |
| CA9 | T013, T026 | T023 |
| CA10 | T014, T015 | T021, T023, T029 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T021 (`aplicarAlcanceRol` en la consulta del periodo y en la de ocupación) | T015 |
| TM2 | T021 (validación de parámetros escalares) | T016 |
| TM3 | T029 (`create`), `store` de hoy | T014 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| `docs/design/system.md`, `system.html` | T001 |
| `app/Agenda/CalendarioCitas.php` | T010, T020 |
| `app/Http/Controllers/CitasController.php` | T021, T029 |
| `resources/views/citas/index.blade.php` | T022 |
| `resources/views/citas/calendario/barra.blade.php`, `semana.blade.php` | T022 |
| `resources/views/citas/calendario/mes.blade.php`, `lista.blade.php` | T023 |
| `public/css/calendario.css` | T024 |
| `tests/Unit/CalendarioCitasTest.php` | T010, T011 |
| `tests/Feature/CalendarioCitasTest.php` | T012, T013, T014, T018 |
| `tests/Feature/CalendarioCitasAlcanceTest.php` | T015 |
| `tests/Feature/CalendarioCitasParametrosTest.php` | T016 |
| `tests/Feature/CalendarioCitasCssTest.php` | T017 |
| `tests/Feature/MovilPersonalTest.php` | T025 |
| `docs/design/capturar.mjs` | T028 |
| `.ai/inspect/049-render-build.php`, `docs/specs/049-calendario-citas/design/medir.mjs` | T026 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| WCAG 2.1 AA, 44 px en el móvil | T017, T024, T026, T028 |
| Sin JavaScript | T014, T026 |
| Mes de ~300 citas en < 1 s | T026 (local), T097 (comprobación del usuario en producción) |
