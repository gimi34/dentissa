---
spec: 049-calendario-citas
verdict: approved
round: 3
date: 2026-11-17
base: d791377
head: 8163d14
human_signoff: no requerido
---

# Review · 049 Calendario de citas

## Resumen
`approved`: los cinco hallazgos que la ronda 2 mandó corregir (R36, R37, R38, R41, R42) están resueltos y no hay
hallazgos nuevos. En la verificación manual de T040 el usuario encontró que el día elegido se ponía en blanco al
pasar el puntero; se corrigió dentro de la tarea con su test. Ronda 3 sin subagente: diff de 12 KB desde la
ronda 2, releído desde cero. La firma humana no la exige la definición de terminado (solo producción).
Conteo: bloqueante 0 · importante 0 · menor 0

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa) | ✅ 1322 en verde (ronda 2: 1318) |
| `vendor/bin/pint --test` (archivos tocados) | ✅ |
| semgrep `p/php` y gitleaks (archivos tocados) | ✅ sin hallazgos |
| `python .ai/bin/aidd.py validate docs/specs/049-calendario-citas` | ✅ 0 errores |
| `design/medir.mjs` sobre la build renderizada | ✅ 12/12 puertas y todos los KPIs en las cuatro pantallas |
| Verificación manual (T040) | ✅ usuario, 2026-11-17: foco en la opción actual y en el día elegido, y el día elegido con el puntero encima |

## Criterios de aceptación
CA1–CA10 ✅ (los avisos de CA8 de la ronda 2, R36 y R37, quedan resueltos).

## Seguimiento de la ronda 2
| ID | Estado | Evidencia |
|---|---|---|
| R36 | corregido | `calendario.css:41-43`: anillo `--focus-ring-inverso` separado del borde (0,4,0 > 0,3,0); `CalendarioCitasCssTest::test_el_foco_sobre_la_opcion_actual_y_el_dia_elegido_se_ve`; verificación manual |
| R37 | corregido | `semana.blade.php:62`: «Mar 17, 1 cita, hoy»; `CalendarioCitasTest::test_en_la_tira_el_dia_de_hoy_lo_dice_tambien_sin_color` comprueba que el nombre empieza por el texto visible en todos los días |
| R38 | corregido | `CalendarioCitas.php:154`: el salto solo desde el día 1 en domingo; Unit `test_semana_desde_el_mes_un_domingo_abre_la_semana_de_ese_domingo` (falla con el código anterior) |
| R41 | corregido | `selectoresSinPar()` se aplica a la hoja y al CSS de control (`['.cal-nueva']`); comprobado rompiendo la expresión |
| R42 | corregido | `CalendarioCitasTest::test_el_calendario_va_dentro_de_cal_pagina`; comprobado quitando la clase |

Además, hallado en la verificación manual: `.cal-dia-cab:hover` (fondo `--p50`) ganaba a `.cal-dia-cab--elegido`
y dejaba el texto blanco sobre casi blanco. Ahora `.cal-dia-cab--elegido:hover` va a `--p800` (`calendario.css:81`);
`test_el_dia_elegido_conserva_el_contraste_al_pasar_el_puntero` falla antes del arreglo.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 Test que falla antes | ✅ | T038 antes que T039 y T040; el test del hover, antes de su arreglo |
| P3 Autorización en servidor | ✅ | Sin cambios |
| P4 Entrada validada | ✅ | Sin cambios |
| P9 Solo el sistema de diseño | ✅ | Solo tokens (`--focus-ring-inverso`, `--p800`, `calc(-2 * var(--focus-ring-width))`); contraste probado |

## Seguridad
Sin cambios en el alcance, la validación ni las dependencias; semgrep y gitleaks limpios. R13 (`npm audit`, ajeno a
la 049) sigue pendiente de un `/chore` antes del `/release`.

## Observabilidad
Sin cambios: sin logs ni eventos de auditoría nuevos.

## Hallazgos
Ninguno nuevo.

## Preparación para release
- Rollback factible: sí, `bash deploy/volver.sh <tag anterior>`; sin migraciones ni datos nuevos.
- Migraciones compatibles: no hay migraciones. Feature flags: no (D9).
- Docs: `architecture.md` y `AGENTS.md` (T090); `deployment.md` sin cambios (T091). `system.md` 1.19.0 (T001).
- SCA: `composer audit` limpio; `npm audit` con 6 altas de `picomatch` (R13) → `/chore` de npm antes del `/release`.

## Tareas añadidas
Ninguna en esta ronda (ronda 1: T030–T037; ronda 2: T038–T040).

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-17 (rondas 1 y 2):
- **R39** → roadmap: la semana del 27-12-2100 ofrece huecos de 2101 (fuera del rango de fechas admitido)
- **R40** → roadmap: la parte de dentista y paciente del test del alta manipulada es solo caracterización
- **R13** → roadmap: `npm audit` con 6 altas de `picomatch` vía `vite` (dependencias de build ajenas a la 049); se corrige con un `/chore` de npm antes del `/release`
- **R15** → roadmap: muchas paradas de tabulador en los huecos; «Saltar a la lista» las evita
- **R16** → roadmap: nombre de la región de la lista en el móvil
- **R17** → roadmap: días de la lista como landmarks
- **R18** → roadmap: grupo con nombre en la cuadrícula del mes
- **R19** → roadmap: cita agendada sobre el día elegido, mismo fondo
- **R20** → roadmap: zoom de texto al 200 % en el calendario
- **R22** → roadmap: test de `style=""` en más vistas
- **R23** → roadmap: comparaciones exactas de «N citas»
- **R24** → roadmap: estados y etiquetas centralizados
- **R25** → roadmap: hora y etiqueta desde `CalendarioCitas`
- **R28** → roadmap: `buscar` en los enlaces (excepción en `security.md` o no propagarlo)
- **R29** → roadmap: `vista=mes` con fecha no válida
- **R30** → roadmap: «Hoy» un domingo
- **R31** → roadmap: citas en domingo en el mes y la tira
- **R32** → roadmap: primera fila del mes que empieza en domingo
- **R33** → roadmap: consulta de ocupación de la dentista
- **R34** → roadmap: tests de títulos y navegación del mes
- **R35** → roadmap: test Feature de «Cerrado desde las 14:00»
