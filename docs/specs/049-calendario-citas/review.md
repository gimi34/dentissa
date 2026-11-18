---
spec: 049-calendario-citas
verdict: changes_requested
round: 2
date: 2026-11-17
base: da2d873
head: d791377
human_signoff: no requerido
---

# Review · 049 Calendario de citas

## Resumen
`changes_requested`: los 16 hallazgos que la ronda 1 mandó corregir están resueltos (R4, R10, R11, R12 y R27
con efecto nuevo), sin bloqueantes. Las correcciones dejan dos importantes nuevos de accesibilidad (R36: el
anillo del foco por dentro no se ve sobre la opción actual y el día elegido, que son granates; R37: el nombre
de los días de la tira no contiene su texto visible) y cinco menores. El usuario decide corregir R36, R37, R38,
R41 y R42 (T038–T040); R39 y R40 van al roadmap. Ronda 2 con un revisor independiente (encargos A, C+D y S
juntos, perfil crítico) sobre el diff desde la ronda 1 (37 KB).
Conteo: bloqueante 0 · importante 2 · menor 5

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa) | ✅ 1318 en verde (ronda 1: 1303) |
| `vendor/bin/pint --test` (11 PHP tocados) | ✅ |
| semgrep `p/php` y gitleaks (archivos tocados) | ✅ sin hallazgos |
| `python .ai/bin/aidd.py validate docs/specs/049-calendario-citas` | ✅ 0 errores |
| `design/medir.mjs` sobre la build renderizada | ✅ 12/12 puertas y todos los KPIs en las cuatro pantallas |
| Verificación manual del foco (T037) | ✅ usuario, 2026-11-17 («Semana», «Mes» no seleccionado y una cabecera) |

## Criterios de aceptación
Sin cambios respecto a la ronda 1 salvo: CA2 ✅ (R4, R6), CA4 ✅ (R2), CA6 ✅ (R1), CA7 ✅ (R3), CA10 ✅ (R5,
R7); CA8 ⚠ por R37 (nombre de los días de la tira) y R36 (foco del día elegido).

## Seguimiento de la ronda 1
| ID | Estado | Evidencia |
|---|---|---|
| R1 | corregido | `semana.blade.php:18,25,36,45` hasta 8; `calendario.css`; `CalendarioCitasTest::test_cinco_citas_a_la_vez_se_ven_cada_una_en_su_carril` y `CalendarioCitasCssTest` (carriles 2–8) |
| R2 | corregido | `lista.blade.php:27`; `test_un_dia_elegido_sin_citas_lo_dice_y_ofrece_volver_al_periodo` |
| R3 | corregido | `CalendarioCitas.php:87-91, 415-420`; Unit `test_una_cita_oculta_antes_de_abrir_no_quita_el_hueco_de_las_9` |
| R4 | corregido con efecto nuevo (→ R38) | `CalendarioCitas.php:152-156`; Unit `test_semana_desde_un_mes_que_empieza_en_domingo_…` |
| R5 | corregido (dentista y paciente, solo caracterización → R40) | `test_el_alta_deja_los_valores_por_defecto_ante_parametros_manipulados` |
| R6 | corregido | `CalendarioCitasParametrosTest.php:50-60` |
| R7 | corregido | `test_la_ocupacion_de_los_huecos_respeta_el_alcance_con_buscador_o_dentista_ajena` |
| R8 | corregido (control positivo débil → R41) | `test_todo_color_de_texto_de_la_hoja_tiene_su_par_de_contraste` |
| R9 | corregido | `index.blade.php:112,119` con `@include` |
| R10 | corregido con efecto nuevo (→ R36) | `calendario.css:37-38` (`.cal-pagina`, gana a `app.css:592`) |
| R11 | corregido con efecto nuevo (→ R36) | La misma regla, en `.cal-dia-cab` |
| R12 | corregido con efecto nuevo (→ R37) | Borde `--border-width-strong` y «, hoy» en el `aria-label` |
| R14 | corregido | `barra.blade.php:12,14`: «Anterior semana / mes», flechas `aria-hidden` |
| R21 | corregido | `lista.blade.php:15` `tabindex="-1"`; `test_el_destino_de_saltar_a_la_lista_recibe_el_foco` |
| R26 | corregido | `CalendarioCitas.php:312-315`; Unit `test_el_titulo_del_resumen_lleva_el_anio_…` |
| R27 | corregido con efecto nuevo (→ R39) | `CitasController.php:188, 197-199`; `test_un_anio_fuera_de_rango_muestra_la_semana_actual` |

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 Test que falla antes | ✅ | T030–T033 en commits anteriores a T034–T037; caracterizaciones comprobadas rompiendo |
| P3 Autorización en servidor | ✅ | La segunda consulta ya tiene test (R7) |
| P4 Entrada validada | ✅ | `fechaDe` acotada; salidas nuevas con `{{ }}` |
| P9 Solo el sistema de diseño | ⚠ | Solo tokens; R36 (foco 1:1 sobre `--p600`), R37 (nombre ≠ texto visible) |

## Seguridad
Sin cambios en dependencias ni en el alcance; semgrep y gitleaks limpios; nada nuevo explotable.

## Observabilidad
Sin cambios: sin logs ni eventos de auditoría nuevos.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R36 | importante | `public/css/calendario.css:37-38` (con `:28` y `:74`); `CalendarioCitasCssTest.php:81` | El anillo por dentro (`--focus-ring` = `--p600`) cae sobre el fondo `--p600` de la opción actual de «Semana · Mes» y de la cabecera del día elegido: 1:1, foco invisible (2.4.7); `FONDOS_FOCO` no incluye `--p600` | Anillo `--focus-ring-inverso` (blanco) en esos dos estados; par en el test, comprobado rompiendo; verificación manual con el foco en la opción actual y en el día elegido |
| R37 | importante | `semana.blade.php:62`; `CalendarioCitasTest.php:448-449` | El `aria-label` de la tira («Martes 17 de noviembre, 1 cita, hoy») no contiene el texto visible «Mar 17» (2.5.3, F96) | Nombre que empiece por lo visible: «Mar 17, 1 cita, hoy»; ajustar el test |
| R38 | menor | `CalendarioCitas.php:154-156` | El salto al lunes se aplica a cualquier base en domingo: desde «Mes» un domingo con hoy dentro, «Semana» abre la semana siguiente; el 31-01-2027 sale del mes | Saltar solo cuando la base es el día 1; unitario con hoy en domingo |
| R39 | menor | `CitasController.php:197-199` | La semana del 27-12-2100 ofrece huecos de 2101 cuyo enlace pierde la fecha | Límite aceptado (casi inalcanzable) |
| R40 | menor | `CalendarioCitasTest.php:477-486` | «Ningún dentista ni paciente elegido» pasaría sin `Str::isUuid`: un valor que no es UUID nunca coincide con un `<option>` | Decirlo en el test o usar un UUID válido ajeno al desplegable |
| R41 | menor | `CalendarioCitasCssTest.php:136-138` | El control positivo del test exhaustivo no pasa por el detector | Detector en un helper aplicado a la hoja y al CSS de control (debe dar `['.cal-nueva']`) |
| R42 | menor | `CalendarioCitasCssTest.php:147-157`; `index.blade.php:41` | Ningún test comprueba que `.cal-pagina` envuelva el calendario: sin la clase vuelve el foco por fuera | Aserto Feature: `.cal-vista` dentro de `.cal-pagina` |

## Preparación para release
Sin cambios respecto a la ronda 1: rollback factible, sin migraciones ni feature flags; R13 (`npm audit`) se
corrige con un `/chore` antes del `/release`.

## Tareas añadidas
- T038 ← R36, R37, R38, R41, R42 (tests)
- T039 ← R38 (`CalendarioCitas`)
- T040 ← R36, R37 (hoja y tira)

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-17:
- **R39** → roadmap: la semana del 27-12-2100 ofrece huecos de 2101 (fuera del rango de fechas admitido)
- **R40** → roadmap: la parte de dentista y paciente del test del alta manipulada es solo caracterización

De la ronda 1 (`history/review.r1.md`), siguen aceptados:
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
