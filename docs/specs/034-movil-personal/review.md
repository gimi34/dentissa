---
spec: 034-movil-personal
verdict: approved
round: 2
date: 2026-10-16
base: d9518cf
head: 165a3f7
human_signoff: no requerido
---

# Review · 034 Móvil para el personal y listas en tarjetas

## Resumen
Ronda 2 (`--rerun`): los nueve hallazgos con tarea de la ronda 1 (R1–R7, R10 y R11) están corregidos
con T042–T046, con tests que fallaron antes y verificación manual del usuario, y el diff desde
d9518cf no introduce hallazgos nuevos. La verificación automática completa sale en verde. Veredicto
`approved`; los aceptados de la ronda 1 (R8, R9, R12–R22) siguen aceptados y `/release` los lleva al
roadmap.
Conteo: bloqueante 0 · importante 0 · menor 0 (nuevos en esta ronda)

Revisión del diff d9518cf..165a3f7 (`code.diff` 18 KB, 7 archivos de código, 0 sin tarea) hecha
por el agente releyendo los archivos desde cero, sin subagentes (`--rerun` con diff < 30 KB). El
plugin `design` de Anthropic no está instalado en esta sesión.

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` | ✅ 310 tests, 5099 aserciones, 0 fallos (línea base T002: 287; ronda 1: 306) |
| `php artisan test --filter='MovilPersonalTest\|MovilPacienteTest'` (dos veces) | ✅ 50 y 50, sin intermitencias |
| `vendor/bin/pint --test` (`MovilPersonalTest`, `MovilPacienteTest`) | ✅ |
| `node docs/design/capturar.mjs --medir` (línea base de v0.7.0 en la misma sesión, 46 capturas) | ✅ código 0, «medir: todo en orden» |
| `python .ai/bin/aidd.py validate docs/specs/034-movil-personal` | ✅ 0 errores |
| build | ➖ sin build (CSS y JS servidos tal cual) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `MovilPersonalTest` (marcado y CSS de tarjetas) · `--medir` | ✅ | |
| CA2 | `test_las_tablas_incluidas_se_marcan_como_tarjetas`, `test_el_icono_de_la_agenda_de_la_dentista_es_decorativo` · `--medir` | ✅ | R4 corregido |
| CA3 | `test_movil_css_convierte_las_tablas_en_tarjetas` (con `.tabla-tarjetas .solo-escritorio`) | ✅ | R7 corregido, comprobado rompiendo |
| CA4 | `test_las_celdas_de_servicios_se_centran_con_clase`, `test_movil_css_convierte_las_tablas_en_tarjetas` (`text-align: start`) | ✅ | R1 corregido |
| CA5 | `test_el_buscador_tiene_su_boton_en_el_movil`, `test_el_contador_cuenta_las_fechas_como_un_filtro`, `test_el_bloque_034_de_app_css_reproduce_los_estilos_con_tokens` (`.filtro-citas .field:focus`) · `--medir` | ✅ | R2, R3 y R6 corregidos |
| CA6 | `test_el_detalle_de_cita_sigue_el_orden_de_la_spec` (con `#estado` antes que `#acciones`) · `--medir` | ✅ | R11 corregido |
| CA7 | `MovilPacienteTest`, `MovilPersonalTest` · `--medir` (palabras de 30 letras) | ✅ | |
| CA8 | `--medir` (anchos, toque, solapes, sin JS) | ✅ | |
| CA9 | `test_movil_css_cambia_el_boton_menu_y_la_marca`, `MovilPacienteTest` | ✅ | |
| CA10 | `test_movil_css_cambia_el_boton_menu_y_la_marca`, `test_los_pares_nuevos_cumplen_aa` | ✅ | R5 corregido (comentario) |
| CA11 | `test_el_bloque_034_de_app_css_reproduce_los_estilos_con_tokens` (bloque cerrado en `/* Fin spec 034 */`) · `--medir` (escritorio contra línea base) | ✅ | R6 y R10 corregidos; «Buscar» solo en el móvil |

## Conformidad con el plan
- Cambios fuera del plan: ninguno; los 7 archivos de código del diff están en T042–T045.
- Partes del plan sin implementar: ninguna. T046 solo regenera capturas (generado por comando).
- La separación de «Buscar» (`.filtro-buscar .btn`, `margin-top: var(--space-4)`) se añadió en T045
  tras la verificación manual del usuario, con su aserción antes (nota de T045).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Tareas de `/review` sobre la spec aprobada |
| P2 Test que falla antes y pasa después | ✅ | Notas de T042, T043 y T045 (fallaron antes; R7 y R11 comprobados rompiendo) |
| P3 Autorización en el servidor | ➖ | Sin cambios en rutas, controladores ni consultas |
| P4 Entrada validada y SQL con bindings | ➖ | El contador lee los mismos parámetros; sin consultas nuevas |
| P5 Nada sensible en el repo | ✅ | gitleaks sin fugas |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas; audits limpios |
| P7 Auditoría de accesos | ➖ | No aplica |
| P8 Correlación y logs sin datos sensibles | ➖ | No aplica |
| P9 Solo el sistema de diseño · WCAG 2.1 AA | ✅ | Reglas nuevas solo con tokens (`--border-control`, `--space-4`), comprobadas en `test_el_bloque_034…` y `assertDeclara`; borde ≥ 3:1 con el foco del ratón medido con Playwright (nota de T045) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`5a802e6..HEAD`) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` (`--error`) | ✅ código 0 |
| composer audit --locked · npm audit --audit-level=high | ✅ crít: 0 · alta: 0 |
| trivy fs 0.57.1 HIGH,CRITICAL | ✅ solo el falso positivo conocido de `vendor/mockery/mockery/docs/requirements.txt` (fuera de git y de la imagen) |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ➖ | El diff no toca rutas, controladores ni el alcance de los datos |
| A05 Inyección / XSS | ✅ | «Buscar» es un `<button>` estático; el contador sigue siendo un entero calculado en la vista; sin `{!! !!}` |
| A02 Configuración (CSP) | ✅ | Sin scripts ni manejadores en línea nuevos |
| A03 Cadena de suministro | ✅ | `deps.stat` vacío |
| Proceso | ✅ | Sin rutas protegidas ni instrucciones dirigidas a agentes |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (el plan no define eventos).
- Datos sensibles en logs del código tocado: ninguno (sin logs nuevos).
- `request_id` presente en los logs nuevos: no aplica.

## Hallazgos
Seguimiento de la ronda 1:

| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| R1 | importante | corregido (T043, T044, T045) | `servicios/index.blade.php` sin `text-align` en las `<td>`; `.celda-centrada` en `app.css` y `.tabla-tarjetas td { text-align: start }` en `movil.css` |
| R2 | importante | corregido (T042, T044, T045) | `<button type="submit" class="btn btn-primary solo-movil">Buscar</button>` en `.filtro-buscar`, separado con `--space-4` |
| R3 | menor | corregido (T042, T044) | `$aplicados` cuenta `fecha_desde \|\| fecha_hasta` como un filtro; test con lunes y miércoles |
| R4 | menor | corregido (T042, T044) | `aria-hidden="true"` en el `svg` de `dashboard/dentista.blade.php:101` |
| R5 | menor | corregido (T045) | Comentario de `layouts/app.blade.php:20` |
| R6 | importante | corregido (T043, T045) | `.filtro-citas .field:focus { border-color: var(--border-control); box-shadow: none; }` |
| R7 | importante | corregido (T043) | `assertDeclara('.tabla-tarjetas .solo-escritorio', 'display: none')`, comprobado rompiendo |
| R10 | menor | corregido (T043, T045) | `/* Fin spec 034 */` y `bloque034()` cortado en él |
| R11 | menor | corregido (T042) | `//*[@id='estado']/following::*[@id='acciones']`, comprobado con la consulta invertida |

Sin hallazgos nuevos en esta ronda.

## Preparación para release
- Rollback factible: sí; solo vistas, CSS, JS y tests, sin migraciones: volver a v0.7.0 restaura todo.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: el plan no pide.
- Docs actualizadas: `architecture.md` (T090); `deployment.md` y `observability.md` sin cambios (T091); roadmap (T041).
- SCA sin críticas ni altas.
- Cobertura de riesgos: la spec no cita riesgos `RS`, `OB` ni `RD`.

## Tareas añadidas
- Ninguna en esta ronda (las de la ronda 1, T042–T046, están hechas).

## Aceptados sin tarea
- **R8** → roadmap: `.content` con `break-word` (D8) deja sin `anywhere` las pantallas del personal fuera de la 034; la spec del resto del móvil del personal lo resuelve en sus filas flexibles (decisión del usuario, 2026-10-16).
- **R9** → roadmap: el `focusout` de `menu.js` olvida `ultimoFoco` al cambiar de app o pestaña; ignorarlo con `document.hasFocus()` falso (decisión del usuario, 2026-10-16).
- **R12** → roadmap: el test de orden de reglas globales de `MovilPacienteTest` usa `strpos` del primer selector.
- **R13** → roadmap: «Cancelar» no se estira en las tarjetas de «Mis citas».
- **R14** → roadmap: nombres accesibles de las acciones de tarjeta sin contexto del elemento.
- **R15** → roadmap: logo con máscara invisible con colores forzados.
- **R16** → roadmap: encabezados, `label` sin `for` y `svg` sin `aria-hidden` en los bloques movidos del detalle de cita.
- **R17** → roadmap: `style=""` de `.detalle-datos-rejilla` a la clase con un token.
- **R18** → roadmap: ayudantes y aserciones duplicados entre `MovilPersonalTest` y `MovilPacienteTest`.
- **R19** → roadmap: sangría de `citas/show.blade.php`.
- **R20** → roadmap: `id` de los campos del filtro iguales a clases de sus contenedores.
- **R21** → roadmap: `onclick` con `addslashes` en servicios, a `data-*` en la spec de la CSP.
- **R22** → roadmap: semántica de tabla en VoiceOver; se comprueba en T097 y, si se pierde, roles explícitos en una spec siguiente.
