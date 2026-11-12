---
spec: 047-reportes-admin
verdict: changes_requested
round: 1
date: 2026-11-12
base: 8b683d8
head: 403bc47
human_signoff: pending
---

# Review · 047 Reportes de la clínica para la administración

## Resumen
`changes_requested`. El código de la 047 cumple sus diez criterios, sigue el plan y respeta la
constitución: no hay bloqueantes ni hallazgos importantes de código. El único hallazgo importante es
ajeno a la spec: un aviso nuevo de `phpseclib` (R1) hace fallar `composer audit` y bloquearía la CI del
PR y `/release`; el usuario decidió corregirlo dentro de la 047 (2026-11-12), así que la review pide una
ronda más. De los cuatro menores, dos se corrigen en la misma ronda (R4, R5) y dos se proponen como
aceptados (R2, R3).

Revisión hecha sin subagentes, releyendo desde cero el paquete de review (`code.diff` de 89,5 KB, 15
archivos) y probando casos límite contra la aplicación servida en local; el diseño no tiene
`design/criterios.json` (`exploration: none`), así que no aplica `evaluate.mjs`. Las skills
`design:accessibility-review` y `design:design-critique` no están en la sesión: la accesibilidad se
revisó con la lista de `/review` y las medidas del navegador.

Conteo: bloqueante 0 · importante 1 · menor 4

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa) | ✅ 980 en verde (14 527 aserciones); línea base de `/implement`: 911 más los 69 de la 047 |
| `php artisan test tests/Feature/Reportes` (dos pasadas) | ✅ 69/69 las dos veces (489 aserciones) |
| `php artisan test --group=rnf --filter=ReportesRendimientoTest` | ✅ 10 000 citas en 366 días, respuesta por debajo de 2 s |
| `vendor/bin/pint --test` (12 archivos PHP tocados) | ✅ |
| `node docs/design/capturar.mjs --medir` (T027, 2026-11-11, contra la línea base de v0.16.0) | ✅ «medir: todo en orden», con «Reportes» en las pantallas incluidas y su comprobación de la primera pantalla |
| `python .ai/bin/aidd.py validate docs/specs/047-reportes-admin` | ✅ 0 errores |
| Build | ➖ sin build: la pantalla no usa Vite (CSS y JS en `public/`) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `ReportesPeriodoTest::test_sin_fechas_se_muestra_el_mes_en_curso_de_la_clinica`, `ReportesVistasTest::test_la_pagina_muestra_los_tres_reportes_y_el_periodo`, `ReportesCitasTest::test_una_cita_cuenta_por_su_fecha_agendada_y_no_por_la_de_creacion` | ✅ | el mes se calcula en la hora de la clínica (caso de las 03:00 UTC del día 1) |
| CA2 | `ReportesPeriodoTest::test_el_periodo_elegido_recalcula_los_reportes`, `test_los_dos_dias_extremos_cuentan`, `test_los_atajos_enlazan_este_mes_mes_pasado_y_este_ano` | ✅ | un solo día y cambio de año comprobados también a mano |
| CA3 | `ReportesCitasTest::test_citas_por_estado_muestra_los_seis_estados_con_su_porcentaje`, `test_los_estados_sin_citas_aparecen_con_cero`, `test_las_tasas_de_cancelacion_e_inasistencia`; `ReportesVistasTest::test_los_porcentajes_van_con_coma_y_un_decimal` | ✅ | |
| CA4 | `ReportesCitasTest::test_citas_por_dentista_ordena_y_cuadra_con_el_total`, `test_los_dentistas_empatados_se_ordenan_por_nombre_y_apellido`, `test_un_dentista_inactivo_con_citas_aparece_y_uno_sin_citas_no`; `ReportesVistasTest::test_la_tabla_por_dentista_tiene_fila_de_totales` | ✅ | los tres reportes salen de la misma consulta: no pueden descuadrarse |
| CA5 | `ReportesCitasTest::test_servicios_mas_solicitados_cuenta_las_no_canceladas`, `test_las_citas_sin_servicio_y_el_servicio_inactivo`; `ReportesVistasTest::test_la_tabla_de_servicios_muestra_sin_servicio` | ✅ | con todas las citas canceladas, la frase decidida por el usuario (nota de T025) |
| CA6 | `ReportesVistasTest::test_un_periodo_sin_citas_muestra_el_estado_vacio_y_el_filtro` | ✅ | el mensaje, exactamente una vez |
| CA7 | `ReportesPeriodoTest::test_un_periodo_mal_formado_se_rechaza_sin_error_del_servidor` (12 casos), `test_hasta_anterior_a_desde`, `test_falta_una_de_las_dos_fechas`, `test_un_periodo_de_mas_de_366_dias_se_rechaza`; `ReportesVistasTest::test_el_error_va_junto_a_su_campo_y_no_hay_cifras` | ✅ | probado además con años 0001 y 9999, byte nulo y parámetros de más: 200 o 302, nunca 500 |
| CA8 | `ReportesVistasTest::test_la_cabecera_impresa_lleva_clinica_periodo_y_fecha`, `test_el_boton_imprimir_nace_oculto_y_sin_codigo_en_linea`; `ReportesCssTest` (ocultar al imprimir, negro sobre blanco, filas sin partir) | ✅ | el botón se rotula «Descargar» (decisión `refinado` del `design.md`); hoja en carta vista en captura |
| CA9 (abuso) | `ReportesAccesoTest` (roles con y sin periodo, Gate, sin sesión, auditoría, menú, no-caché, identificador) | ✅ | caracterizaciones comprobadas rompiendo cada pieza (nota de T010) |
| CA10 | `ReportesVistasTest::test_no_aparece_ningun_dato_de_pacientes_ni_de_una_cita_concreta` | ✅ | con control positivo; `generar()` no lee `pacientes` ni notas |

Requisitos no funcionales: accesibilidad (`ReportesCssTest`, `ReportesVistasTest::test_las_tablas_tienen_titulo_y_encabezados_de_fila_y_columna`, `--medir`), rendimiento (`ReportesRendimientoTest`), exactitud (`test_dos_consultas_del_mismo_periodo_dan_las_mismas_cifras`) y no indexación (sin sesión → `/login`): ✅.

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 15 archivos de código, 0 sin tarea). `AGENTS.md`, ruta
  protegida, se editó con la aprobación del usuario (nota de T091).
- Partes del plan sin implementar: ninguna. Las tareas sin diff de código (T001, T002 y T027) son de
  documentación y de capturas generadas.
- Desvíos anotados: las reglas de orden y de 366 días son una regla propia y no `after_or_equal` (nota
  de T021, por /analyze A6); el botón «Imprimir» del plan se rotula «Descargar» (nota de T026; R3).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | spec, diseño, plan y tareas aprobados y `/analyze` r2 `pass` antes del primer commit de código (`8b683d8`) |
| P2 Test que falla antes | ✅ | T010–T015 confirmados antes que el código (`f66402e`); fallos previos y piezas rotas en sus notas; el cambio a «Descargar», con su aserción fallando antes |
| P3 Autorización en el servidor | ✅ | `role:admin` del grupo y `Gate::authorize('ver-reportes')` en la primera línea de `ReportesController::index()` |
| P4 Entrada validada y SQL con bindings | ✅ | `$request->validate()` con `ReporteCitas::reglasDePeriodo()`; `whereBetween` y `whereIn` con bindings; `COUNT(*) AS citas` constante; la vista solo escapa |
| P5 Nada sensible en el repositorio | ✅ | gitleaks sin hallazgos; los tests siembran en la transacción |
| P6 Sin vulnerabilidades críticas o altas | ⚠ | la spec no cambia dependencias, pero `composer audit` falla por un aviso nuevo de `phpseclib` (R1) |
| P7 Auditoría | ✅ | el 403 queda como `acceso_denegado` por el registro central; test de caracterización |
| P8 Correlación y logs | ✅ | sin `Log::` nuevo; `X-Request-Id` en la respuesta |
| P9 Sistema de diseño | ✅ | `reportes.css` solo con tokens (test con control positivo); `system.md` 1.14.0; medido en el navegador |
| P10 Rutas indexables | ➖ | la ruta exige sesión |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`8b683d8..HEAD`, 10 commits) | ✅ sin secretos |
| semgrep 1.99.0 `p/php` (`--baseline-commit 8b683d8`) | ✅ 0 hallazgos, sin supresiones |
| `composer audit --locked --ignore-severity=low --ignore-severity=medium` | ❌ 1 aviso sin severidad asignada: `phpseclib/phpseclib` 3.0.57, PKSA-h7cz-q7zq-mdkt (R1) |
| `npm audit --audit-level=high` | ✅ 0 vulnerabilidades |
| trivy 0.57.1 `fs --severity HIGH,CRITICAL` | ✅ sin hallazgos (con `--timeout 30m` y sin `vendor/`, `node_modules/`, `storage/` ni `.git/`: la primera pasada agotó el tiempo recorriendo esas carpetas) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | TM1: `role:admin` y Gate; dentista, asistente y cliente → 403, con y sin periodo; sin sesión → `/login` |
| A03:2025 Cadena de suministro | ⚠ | sin dependencias nuevas (`deps.stat` vacío); aviso previo de `phpseclib` (R1) |
| A05:2025 Inyección | ✅ | TM2: fechas validadas y consulta con bindings; el valor rechazado vuelve escapado al campo (TM5) |
| A04:2025 Fallos criptográficos / datos sensibles | ✅ | TM4: solo totales; sin pacientes, notas ni horas; respuesta `no-store` (TM7) |
| A06:2025 Diseño inseguro (abuso de volumen) | ✅ | TM3: 366 días como mucho y test de rendimiento; peticiones repetidas, residuo aceptado en el plan |
| A09:2025 Registro y alertas | ✅ | TM6: denegación auditada |
| A10:2025 Manejo de errores | ✅ | periodo no válido → 302 con mensaje propio, nunca 500 (también año 0001, 9999 y byte nulo) |
| Proceso del agente | ✅ | sin instrucciones dirigidas a agentes en el diff; ruta protegida con aprobación |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: sí, `acceso_denegado` con `accion_solicitada: admin.reportes` (`ReportesAccesoTest::test_el_acceso_denegado_queda_en_el_registro_de_auditoria`); la consulta del admin no se audita (spec, Auditoría).
- Datos sensibles en logs del código tocado: ninguno; no hay llamadas nuevas a `Log::`.
- `request_id` presente en los logs nuevos: no hay logs nuevos; la respuesta lleva `X-Request-Id`.

## Accesibilidad y diseño
- Un `h1` y un `h2` por reporte; tablas con `caption`, `th scope="col"` y `th scope="row"`; campos con
  `label for`, `aria-invalid` y `aria-describedby`; aviso `role="alert"` y vacío `role="status"`.
- Medido en el navegador (Playwright, 320×568, 390×844 y 1440): sin desplazamiento horizontal, ningún
  control de menos de 44 px, la frase del periodo y «Citas en el periodo» en la primera pantalla;
  «Descargar» oculto sin JavaScript.
- Contraste: los colores de texto de `reportes.css` ≥ 4,5:1 sobre `--white` y `--bg` (test); el aviso
  de error, `--red` sobre `--red-bg`, 6,15:1 calculado a mano, sin test (R5).
- Fidelidad al `design.md`: componentes del sistema, sin valores fuera de los tokens; el cambio de
  rótulo está registrado como `refinado`.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `composer.lock` (`phpseclib/phpseclib` 3.0.57) | Aviso PKSA-h7cz-q7zq-mdkt (GHSA-w3p9-g4p2-h3rr, 2026-11-02): el número de iteraciones de PBKDF sin límite al leer claves cifradas permite agotar la CPU. No lo introduce la 047 (dependencia transitiva, sin cambios en el lockfile), pero `composer audit` sale con 1 y bloquea la CI del PR y `/release` | Subir a ≥ 3.0.58 y repetir `composer audit` (decisión del usuario, 2026-11-12: dentro de la 047) |
| R2 | menor | `app/Http/Controllers/ReportesController.php:37` | La vuelta de un periodo rechazado se detecta por los errores de la sesión: si la siguiente petición de esa sesión no es la redirección (otra pestaña, un cliente que no la sigue), una consulta válida muestra una vez el filtro con el error en lugar de sus cifras. Es el mismo comportamiento del filtro de auditoría (spec 020) | Aceptar; al recargar se ven las cifras |
| R3 | menor | `resources/views/reportes/index.blade.php:86`, `tests/Feature/Reportes/ReportesVistasTest.php:304`, `plan.md:70` | Nombres que ya no coinciden con lo que se ve: el botón «Descargar» conserva el `id` `reporte-imprimir` y su test se llama `test_el_boton_imprimir…`; `plan.md` y `tasks.md` siguen diciendo «Imprimir», y el plan escribe el mensaje con comillas inglesas donde el código usa «Hasta» y «Desde» | Aceptar: el `design.md` registra el cambio y renombrar no cambia el comportamiento |
| R4 | menor | `tests/Feature/Reportes/ReportesAccesoTest.php:90` | El evento de auditoría se busca con `->sole()` entre todos los `acceso_denegado` de la tabla: si `dentissa_test` conserva alguno, el test falla sin que el código esté mal | Filtrar por el actor del test |
| R5 | menor | `tests/Feature/Reportes/ReportesCssTest.php:96` | El test de contraste compara los colores de texto con `--white` y `--bg`, pero el aviso de error se pinta sobre `--red-bg`; ese par cumple (6,15:1) sin que ningún test lo vigile | Añadir el par `--red` sobre `--red-bg` |

## Preparación para release
- Rollback factible: sí. Sin migraciones ni datos; `bash deploy/volver.sh <tag anterior>` devuelve el
  placeholder (que se conserva en el repositorio).
- Migraciones compatibles con la versión anterior: no hay.
- Feature flags: el plan no pide ninguno.
- Docs actualizadas: `architecture.md` (módulo y ruta), `security.md` (Gate y superficie de ataque),
  índice de specs, roadmap y `AGENTS.md`; `deployment.md` y `observability.md`, sin cambios.
- SCA: pendiente de R1.
- Cobertura de riesgos: la spec no cita riesgos documentados.

## Tareas añadidas
- T029 ← R1
- T030 ← R4, R5

## Aceptados sin tarea
Propuestos el 2026-11-12, a confirmar por el usuario con la aprobación de la ronda 2:
- **R2** → roadmap: la vuelta de un periodo rechazado en «Reportes» se detecta por la sesión; una consulta válida que llegue antes que la redirección muestra una vez el error.
- **R3** → roadmap: el botón «Descargar» de «Reportes» conserva el `id` y el nombre de test de «Imprimir», y el plan de la 047 no recoge el rótulo nuevo.
